<?php
// ============================================================
// api/import_schedule.php
// Bulk CSV / XLSX / XLS schedule importer
//
// LOCK FLOW (30-second SS precision):
//   1. SHA-256 hash of raw file bytes
//   2. acquire  → block if locked or already_imported
//   3. Parse & validate rows
//   4. INSERT each valid row (per-row pre-flight checks)
//   5. release(permanent=true)  if ≥1 row inserted → file blocked forever
//   6. release(permanent=false) if 0 rows inserted → let user fix & retry
//
// Role permissions:
//   Super Admin  → any campus
//   Campus Admin → own campus only
//   Program Head → own campus + own college only
//
// Required CSV columns:
//   course_code, course_name, college, exam_type, exam_date, time_slot, campus
// Optional:
//   program, year_level, section, semester, duration, room_name, proctor_name
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────────────────────────
requireAuth();
$u           = $_SESSION['user'];
$userId      = (int)($u['id']        ?? 0);
$userName    = trim($u['full_name']  ?? $u['username'] ?? 'Unknown');
$userRole    = $u['role']            ?? '';
$userCampus  = trim($u['campus']     ?? '');
$userCollege = strtoupper(trim($u['college'] ?? ''));

if ($userRole === 'Admin' && $userCampus === '') {
    $roleLabel = 'Super Admin';
} elseif ($userRole === 'Admin') {
    $roleLabel = 'Campus Admin';
} elseif ($userRole === 'Program Head') {
    $roleLabel = 'Program Head';
} else {
    jsonResponse(false, 'Forbidden.', [], 403);
}

$IMPORT_TYPE = 'schedule';
$db = getDB();

// ── File upload check ─────────────────────────────────────────────────────────
if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    jsonResponse(false, 'No file uploaded.');
}

$tmpPath  = $_FILES['file']['tmp_name'];
$origName = $_FILES['file']['name'] ?? 'import.csv';
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

if (!in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
    jsonResponse(false, 'Only CSV, XLSX, and XLS files are supported.');
}

// ── SHA-256 of raw file bytes ─────────────────────────────────────────────────
$fileBytes = file_get_contents($tmpPath);
if ($fileBytes === false) jsonResponse(false, 'Could not read uploaded file.');
$fileHash = hash('sha256', $fileBytes);

// ── Acquire import lock ───────────────────────────────────────────────────────
$lockResult = acquireImportLock($fileHash, $IMPORT_TYPE);
if (!($lockResult['success'] ?? false)) {
    $status  = $lockResult['status']  ?? 'locked';
    $message = $lockResult['message'] ?? 'This file is currently being imported or has already been imported.';
    $extra   = ['status' => $status];
    if ($status === 'locked')           $extra['seconds_left'] = $lockResult['seconds_left'] ?? 30;
    if ($status === 'already_imported') $extra['can_reset']    = $lockResult['can_reset']    ?? false;
    jsonResponse(false, $message, $extra);
}

// ── Parse file ────────────────────────────────────────────────────────────────
$rows = ($ext === 'csv') ? parseCsvToRows($fileBytes) : parseExcelToRows($tmpPath);

if (empty($rows)) {
    releaseImportLock($fileHash, $IMPORT_TYPE, false);
    jsonResponse(false, 'The file is empty or could not be parsed. No schedules were imported.');
}

// ── Validate required headers ─────────────────────────────────────────────────
$header   = array_map('strtolower', array_map('trim', array_keys($rows[0])));
$required = ['course_code', 'course_name', 'college', 'exam_type', 'exam_date', 'time_slot', 'campus'];
$missing  = array_diff($required, $header);
if ($missing) {
    releaseImportLock($fileHash, $IMPORT_TYPE, false);
    jsonResponse(false, 'Missing required columns: ' . implode(', ', $missing));
}

// ── Process rows ──────────────────────────────────────────────────────────────
$inserted  = 0;
$skipped   = 0;
$errors    = [];
$createdBy = $userId ?: safeCreatedBy();

try {
    foreach ($rows as $lineNum => $raw) {
        $rowNum = $lineNum + 2; // +1 zero-index, +1 for header row

        // Normalise all keys to lowercase
        $r = [];
        foreach ($raw as $k => $v) $r[strtolower(trim($k))] = trim((string)$v);

        $courseCode  = strtoupper($r['course_code']  ?? '');
        $courseName  = $r['course_name']  ?? '';
        $college     = strtoupper($r['college']      ?? '');
        $program     = $r['program']      ?? '';
        $examType    = $r['exam_type']    ?? '';
        $yearLevel   = $r['year_level']   ?? '';
        $section     = $r['section']      ?? '';
        $semester    = $r['semester']     ?? '';
        $examDate    = normaliseDate($r['exam_date'] ?? '');
        $timeSlot    = $r['time_slot']    ?? '';
        $duration    = $r['duration']     ?? '';
        $roomName    = $r['room_name']    ?? '';
        $proctorName = $r['proctor_name'] ?? '';
        $campus      = $r['campus']       ?? $userCampus;

        // ── Role-based campus / college guard ─────────────────────────────────
        if ($roleLabel === 'Campus Admin' && $campus !== $userCampus) {
            $errors[] = "Row {$rowNum}: You can only import schedules for campus '{$userCampus}'. Row campus '{$campus}' skipped.";
            $skipped++;
            continue;
        }
        if ($roleLabel === 'Program Head') {
            if ($campus !== $userCampus) {
                $errors[] = "Row {$rowNum}: You can only import for your campus '{$userCampus}'.";
                $skipped++;
                continue;
            }
            if ($userCollege && $college !== $userCollege) {
                $errors[] = "Row {$rowNum}: You can only import schedules for your college '{$userCollege}'. Row college '{$college}' skipped.";
                $skipped++;
                continue;
            }
        }

        // ── Required field validation ─────────────────────────────────────────
        if (!$courseCode || !$courseName || !$examType || !$examDate || !$timeSlot || !$campus) {
            $errors[] = "Row {$rowNum}: Missing required fields (course_code, course_name, exam_type, exam_date, time_slot, campus). Skipped.";
            $skipped++;
            continue;
        }

        if (!isValidDate($examDate)) {
            $errors[] = "Row {$rowNum}: Invalid exam_date '{$r['exam_date']}'. Expected format: YYYY-MM-DD or MM/DD/YYYY.";
            $skipped++;
            continue;
        }

        // ── Resolve course_id ─────────────────────────────────────────────────
        $courseId = null;
        if ($courseCode) {
            $cs = $db->prepare('SELECT id FROM courses WHERE course_code = ? AND campus = ? LIMIT 1');
            $cs->execute([$courseCode, $campus]);
            $courseRow = $cs->fetch();
            if ($courseRow) $courseId = (int)$courseRow['id'];
        }

        // ── Resolve room_id ───────────────────────────────────────────────────
        $roomId = null;
        if ($roomName) {
            $rs = $db->prepare('SELECT id FROM rooms WHERE LOWER(TRIM(name)) = LOWER(?) AND campus = ? LIMIT 1');
            $rs->execute([$roomName, $campus]);
            $roomRow = $rs->fetch();
            if ($roomRow) $roomId = (int)$roomRow['id'];
        }

        // ── Resolve proctor_id ────────────────────────────────────────────────
        $proctorId = null;
        if ($proctorName) {
            $ps = $db->prepare('SELECT id FROM proctors WHERE LOWER(TRIM(name)) = LOWER(?) AND campus = ? LIMIT 1');
            $ps->execute([$proctorName, $campus]);
            $proctorRow = $ps->fetch();
            if ($proctorRow) $proctorId = (int)$proctorRow['id'];
        }

        // ── Room double-booking pre-flight ────────────────────────────────────
        if ($roomId && $examDate && $timeSlot) {
            $conflict = $db->prepare(
                'SELECT id FROM schedules
                  WHERE room_id    = ?
                    AND exam_date  = ?
                    AND time_slot  = ?
                  LIMIT 1'
            );
            $conflict->execute([$roomId, $examDate, $timeSlot]);
            if ($conflict->fetch()) {
                $errors[] = "Row {$rowNum}: Room '{$roomName}' is already booked on {$examDate} at {$timeSlot}. Skipped.";
                $skipped++;
                continue;
            }
        }

        // ── Section duplicate pre-flight ──────────────────────────────────────
        if ($section && $examDate && $campus) {
            $secConflict = $db->prepare(
                'SELECT id FROM schedules
                  WHERE section   = ?
                    AND exam_type = ?
                    AND exam_date = ?
                    AND campus    = ?
                  LIMIT 1'
            );
            $secConflict->execute([$section, $examType, $examDate, $campus]);
            if ($secConflict->fetch()) {
                $errors[] = "Row {$rowNum}: Section '{$section}' already has a {$examType} exam on {$examDate} at {$campus}. Skipped.";
                $skipped++;
                continue;
            }
        }

        // ── INSERT ────────────────────────────────────────────────────────────
        try {
            $db->beginTransaction();
            $db->prepare(
                'INSERT INTO schedules
                   (course_id, course_code, course_name, college, program,
                    exam_type, semester, year_level, section,
                    exam_date, time_slot, duration,
                    room_id, room_name, proctor_id, proctor_name,
                    campus, status, created_by, file_hash)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $courseId,   $courseCode,   $courseName, $college,     $program,
                $examType,   $semester ?: null, $yearLevel,  $section,
                $examDate,   $timeSlot,     $duration,
                $roomId,     $roomName,     $proctorId,  $proctorName,
                $campus ?: null, 'Pending', $createdBy,  $fileHash,
            ]);
            $db->commit();
            $inserted++;
        } catch (PDOException $e) {
            $db->rollBack();
            if ($e->getCode() === '23000') {
                $msg = strtolower($e->getMessage());
                if (str_contains($msg, 'unique_room_booking')) {
                    $errors[] = "Row {$rowNum}: Room '{$roomName}' is already booked on {$examDate} at {$timeSlot} (concurrent conflict). Skipped.";
                } elseif (str_contains($msg, 'unique_section_exam')) {
                    $errors[] = "Row {$rowNum}: Section '{$section}' already has a {$examType} exam on {$examDate} at {$campus} (concurrent conflict). Skipped.";
                } else {
                    $errors[] = "Row {$rowNum}: Duplicate schedule detected (concurrent conflict). Skipped.";
                }
            } else {
                $errors[] = "Row {$rowNum}: Database error — " . $e->getMessage();
            }
            $skipped++;
        }
    }
} catch (Throwable $e) {
    // Unexpected fatal error — always release lock so it doesn't get stuck
    releaseImportLock($fileHash, $IMPORT_TYPE, false);
    jsonResponse(false, 'An unexpected error occurred during import: ' . $e->getMessage());
}

// ── Release lock ──────────────────────────────────────────────────────────────
if ($inserted > 0) {
    releaseImportLock($fileHash, $IMPORT_TYPE, true);
    $message = "{$inserted} schedule(s) imported successfully.";
    if ($skipped > 0) $message .= " {$skipped} row(s) were skipped.";
} else {
    releaseImportLock($fileHash, $IMPORT_TYPE, false);
    $message = "No schedules were imported. All {$skipped} row(s) were skipped or invalid.";
}

jsonResponse($inserted > 0, $message, [
    'inserted'  => $inserted,
    'skipped'   => $skipped,
    'errors'    => $errors,
    'file_hash' => $fileHash,
]);


// =============================================================================
// ── Lock helpers (consistent with import_rooms.php gold standard)
// ── Uses NOW() — compatible with InfinityFree MySQL (no fractional seconds).
// =============================================================================

/**
 * Acquire a 30-second exclusive import lock for this file hash + type.
 * Five-step race-safe algorithm (first-writer-wins via UNIQUE KEY):
 *   1. Check for a permanent lock (already fully imported).
 *   2. Delete any stale temp lock THIS user holds (prevents double-click bypass).
 *   3. Purge all stale temp locks from OTHER users (>30 s old).
 *   4. Check if another user holds a live temp lock right now.
 *   5. Atomic INSERT IGNORE — only one concurrent caller wins.
 */
function acquireImportLock(string $hash, string $importType): array {
    global $db, $userId, $userName, $roleLabel, $userCampus;

    // Step 1: Check for a permanent lock (file already fully imported)
    $s = $db->prepare(
        'SELECT * FROM import_locks
          WHERE file_hash   = ?
            AND import_type = ?
          LIMIT 1'
    );
    $s->execute([$hash, $importType]);
    $permCheck = $s->fetch() ?: null;

    if ($permCheck && (int)$permCheck['is_permanent']) {
        if (dataWasDeleted($db, $hash, $importType)) {
            clearPermanentLock($db, $hash, $importType);
            $permCheck = null; // stale — fall through
        } else {
            return [
                'success'   => false,
                'status'    => 'already_imported',
                'message'   => buildLockMsg($permCheck, 0),
                'can_reset' => ($roleLabel === 'Super Admin'),
            ];
        }
    }

    // Step 2: Delete any temp lock THIS user already holds for this file.
    // Without this, a double-click from the same user can bypass the UNIQUE check.
    $db->prepare(
        'DELETE FROM import_locks
          WHERE file_hash    = ?
            AND import_type  = ?
            AND locked_by    = ?
            AND is_permanent = 0'
    )->execute([$hash, $importType, $userId]);

    // Step 3: Purge stale temp locks from other users (older than 30 seconds)
    $db->prepare(
        'DELETE FROM import_locks
          WHERE is_permanent = 0
            AND import_type  = ?
            AND locked_at < DATE_SUB(NOW(), INTERVAL 30 SECOND)'
    )->execute([$importType]);

    // Step 4: Check if another user holds a live temp lock right now
    $s2 = $db->prepare(
        'SELECT * FROM import_locks WHERE file_hash = ? AND import_type = ? LIMIT 1'
    );
    $s2->execute([$hash, $importType]);
    $existing = $s2->fetch() ?: null;

    if ($existing && !(int)$existing['is_permanent']) {
        $age  = (int)(microtime(true) - strtotime($existing['locked_at']));
        $secs = max(0, 30 - $age);
        return [
            'success'      => false,
            'status'       => 'locked',
            'seconds_left' => $secs,
            'message'      => buildLockMsg($existing, $secs),
        ];
    }

    // Step 5: Atomic INSERT IGNORE — UNIQUE KEY (file_hash, import_type) ensures
    // only one concurrent caller wins the race.
    $ins = $db->prepare(
        'INSERT IGNORE INTO import_locks
             (file_hash, locked_by, locked_by_name, role, campus, import_type, locked_at, is_permanent)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)'
    );
    $ins->execute([$hash, $userId, $userName, $roleLabel, $userCampus, $importType]);

    if ($ins->rowCount() === 1) return ['success' => true, 'status' => 'acquired'];

    // Lost the race — fetch and report the winner's lock
    $s3 = $db->prepare(
        'SELECT * FROM import_locks WHERE file_hash = ? AND import_type = ? LIMIT 1'
    );
    $s3->execute([$hash, $importType]);
    $lock = $s3->fetch() ?: null;
    $age  = $lock ? (int)(microtime(true) - strtotime($lock['locked_at'])) : 0;
    $secs = max(0, 30 - $age);
    return [
        'success'      => false,
        'status'       => $lock && (int)$lock['is_permanent'] ? 'already_imported' : 'locked',
        'seconds_left' => $secs,
        'message'      => $lock
            ? buildLockMsg($lock, $secs)
            : 'This file is currently being imported by another user.',
    ];
}

/**
 * Release the lock after import completes.
 * permanent=true  → upgrade to is_permanent=1 (file fully committed, blocked forever).
 * permanent=false → delete the temp lock (import failed or 0 rows, allow retry).
 */
function releaseImportLock(string $hash, string $importType, bool $permanent): void {
    global $db, $userId, $userName, $roleLabel, $userCampus;

    if ($permanent) {
        try {
            $db->prepare(
                'INSERT INTO import_locks
                     (file_hash, locked_by, locked_by_name, role, campus, import_type, locked_at, is_permanent)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)
                 ON DUPLICATE KEY UPDATE
                     locked_by      = VALUES(locked_by),
                     locked_by_name = VALUES(locked_by_name),
                     role           = VALUES(role),
                     campus         = VALUES(campus),
                     locked_at      = VALUES(locked_at),
                     is_permanent   = 1'
            )->execute([$hash, $userId, $userName, $roleLabel, $userCampus, $importType]);
        } catch (PDOException $e) { /* Already permanent — OK */ }
    } else {
        // Delete the temp (non-permanent) lock owned by this user
        $db->prepare(
            'DELETE FROM import_locks
              WHERE file_hash    = ?
                AND import_type  = ?
                AND locked_by    = ?
                AND is_permanent = 0'
        )->execute([$hash, $importType, $userId]);

        // Also auto-clear a stale permanent lock if all data from this file was deleted
        if (dataWasDeleted($db, $hash, $importType)) {
            clearPermanentLock($db, $hash, $importType);
        }
    }
}

function buildLockMsg(array $lock, int $secsLeft): string {
    $who  = htmlspecialchars($lock['locked_by_name'], ENT_QUOTES);
    $role = htmlspecialchars($lock['role'],           ENT_QUOTES);
    $camp = ($lock['campus'] !== '')
          ? ' (' . htmlspecialchars($lock['campus'], ENT_QUOTES) . ')'
          : '';
    $type = ucfirst($lock['import_type'] ?? 'schedule');
    if ((int)$lock['is_permanent']) {
        return "This {$type} file has already been imported by {$who} ({$role}{$camp}). "
             . "Re-importing the same file is not allowed.";
    }
    $unit = $secsLeft === 1 ? 'second' : 'seconds';
    return "{$who} ({$role}{$camp}) is currently importing this {$type} file. "
         . "Please wait {$secsLeft} {$unit} before trying again.";
}

/**
 * Returns true if ALL rows imported from this file hash have been deleted
 * from the target table — meaning the permanent lock is stale and safe to clear.
 */
function dataWasDeleted(PDO $db, string $hash, string $importType): bool {
    try {
        $table = match ($importType) {
            'courses'  => 'courses',
            'colleges' => 'colleges',
            'proctors' => 'proctors',
            'rooms'    => 'rooms',
            default    => 'schedules',  // 'schedule'
        };
        $cols = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'file_hash'")->fetchAll();
        if (empty($cols)) {
            // No file_hash column — fall back: treat table empty as "data gone"
            return (int)$db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() === 0;
        }
        $s = $db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE file_hash = ?");
        $s->execute([$hash]);
        return ((int)$s->fetchColumn()) === 0;
    } catch (PDOException $e) {
        return false; // Err on the side of caution — don't auto-clear
    }
}

function clearPermanentLock(PDO $db, string $hash, string $importType): void {
    $db->prepare(
        'DELETE FROM import_locks
          WHERE file_hash    = ?
            AND import_type  = ?
            AND is_permanent = 1'
    )->execute([$hash, $importType]);
}

// ── CSV parser ────────────────────────────────────────────────────────────────
function parseCsvToRows(string $bytes): array {
    $lines  = array_filter(explode("\n", str_replace(["\r\n", "\r"], "\n", $bytes)));
    $rows   = [];
    $header = null;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $cols = str_getcsv($line);
        if ($header === null) { $header = $cols; continue; }
        while (count($cols) < count($header)) $cols[] = '';
        $rows[] = array_combine($header, array_slice($cols, 0, count($header)));
    }
    return $rows;
}

// ── Excel parser (PhpSpreadsheet if available, fallback to CSV) ───────────────
function parseExcelToRows(string $tmpPath): array {
    if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
        try {
            $spreadsheet   = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpPath);
            $sheet         = $spreadsheet->getActiveSheet();
            $highestRow    = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            // Build header from first row
            $header = [];
            foreach ($sheet->getRowIterator(1, 1) as $row) {
                foreach ($row->getCellIterator('A', $highestColumn) as $cell) {
                    $header[] = trim((string)$cell->getValue());
                }
            }
            if (empty(array_filter($header))) return [];

            $rows = [];
            for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
                $rowData = [];
                $colIdx  = 0;
                foreach ($sheet->getRowIterator($rowIdx, $rowIdx) as $row) {
                    foreach ($row->getCellIterator('A', $highestColumn) as $cell) {
                        $key = $header[$colIdx] ?? $colIdx;
                        // For date cells, convert Excel serial to Y-m-d string
                        if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                            $ts            = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($cell->getValue());
                            $rowData[$key] = date('Y-m-d', (int)$ts);
                        } else {
                            $rowData[$key] = trim((string)$cell->getCalculatedValue());
                        }
                        $colIdx++;
                    }
                }
                // Skip completely empty rows
                if (count(array_filter($rowData, fn($v) => $v !== '')) === 0) continue;
                while (count($rowData) < count($header)) $rowData[] = '';
                $rows[] = $rowData;
            }
            return $rows;
        } catch (\Exception $e) { return []; }
    }
    return parseCsvToRows(file_get_contents($tmpPath));
}

// ── Date normaliser ───────────────────────────────────────────────────────────
function normaliseDate(string $raw): string {
    $raw = trim($raw);
    if (!$raw) return '';

    // Already YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) return $raw;

    // MM/DD/YYYY
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $raw, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
    }

    // DD/MM/YYYY (European) — only when day > 12 making MM/DD impossible
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $raw, $m) && (int)$m[1] > 12) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }

    // Excel serial number (integer-only string, typical range 1900–2200 = 1–109574)
    if (preg_match('/^\d+$/', $raw) && (int)$raw > 1 && (int)$raw < 200000) {
        $excelEpoch = mktime(0, 0, 0, 12, 30, 1899);
        $ts         = $excelEpoch + ((int)$raw * 86400);
        $year       = (int)date('Y', $ts);
        if ($year >= 2000 && $year <= 2100) return date('Y-m-d', $ts);
        return '';
    }

    // Generic fallback via strtotime — only accept results with a reasonable year
    $ts = strtotime($raw);
    if ($ts !== false && $ts > 0) {
        $year = (int)date('Y', $ts);
        if ($year >= 2000 && $year <= 2100) return date('Y-m-d', $ts);
    }

    return '';
}

function isValidDate(string $d): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
    if ($d === '0000-00-00') return false;
    [$y, $m, $day] = explode('-', $d);
    if ((int)$y < 2000 || (int)$y > 2100) return false;
    return checkdate((int)$m, (int)$day, (int)$y);
}