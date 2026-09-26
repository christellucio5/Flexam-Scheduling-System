<?php

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

$IMPORT_TYPE = 'courses';
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
    jsonResponse(false, 'The file is empty or could not be parsed. No courses were imported.');
}

// ── Validate required headers ─────────────────────────────────────────────────
$header   = array_map('strtolower', array_map('trim', array_keys($rows[0])));
$required = ['course_code', 'course_name', 'campus'];
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
        $rowNum = $lineNum + 2;

        $r = [];
        foreach ($raw as $k => $v) $r[strtolower(trim($k))] = trim((string)$v);

        $courseCode = strtoupper($r['course_code'] ?? '');
        $courseName = $r['course_name'] ?? '';
        $college    = strtoupper($r['college']    ?? '');
        $program    = $r['program']    ?? '';
        $yearLevel  = $r['year_level'] ?? '';
        $semester   = $r['semester']   ?? '';
        $campus     = $r['campus']     ?? $userCampus;

        // ── Role-based campus / college guard ─────────────────────────────────
        if ($roleLabel === 'Campus Admin' && $campus !== $userCampus) {
            $errors[] = "Row {$rowNum}: You can only import courses for campus '{$userCampus}'. Row campus '{$campus}' skipped.";
            $skipped++;
            continue;
        }
        if ($roleLabel === 'Program Head') {
            if ($campus !== $userCampus) {
                $errors[] = "Row {$rowNum}: You can only import courses for your campus '{$userCampus}'.";
                $skipped++;
                continue;
            }
            // If row specifies a college that isn't yours, block it
            if ($userCollege && $college && $college !== $userCollege) {
                $errors[] = "Row {$rowNum}: You can only import courses for your college '{$userCollege}'. Row college '{$college}' skipped.";
                $skipped++;
                continue;
            }
            // Auto-fill college from session if row leaves it blank
            if (!$college && $userCollege) $college = $userCollege;
        }

        // ── Required field validation ─────────────────────────────────────────
        if (!$courseCode || !$courseName || !$campus) {
            $errors[] = "Row {$rowNum}: course_code, course_name, and campus are required. Skipped.";
            $skipped++;
            continue;
        }

        // ── Space-in-course-code check ────────────────────────────────────────
        if (preg_match('/\s/', $courseCode)) {
            $errors[] = "Row {$rowNum}: Course code '{$courseCode}' must not contain spaces (e.g. use RZAL211 or RZAL-211). Skipped.";
            $skipped++;
            continue;
        }

        // ── Duplicate check: same course_code + campus + program ─────────────
        $dupCheck = $db->prepare(
            'SELECT id FROM courses
              WHERE course_code = ? AND campus = ? AND COALESCE(program,\'\') = ?
              LIMIT 1'
        );
        $dupCheck->execute([$courseCode, $campus, $program ?: '']);
        if ($dupCheck->fetch()) {
            $errors[] = "Row {$rowNum}: Course '{$courseCode}' already exists in '{$campus}'"
                      . ($program ? " under program '{$program}'" : '') . ". Skipped.";
            $skipped++;
            continue;
        }

        // ── INSERT ────────────────────────────────────────────────────────────
        try {
            $db->prepare(
                'INSERT INTO courses
                   (course_code, course_name, college, program, year_level, semester, campus, created_by, file_hash)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $courseCode,
                $courseName,
                $college   ?: 'UNKNOWN',
                $program   ?: null,
                $yearLevel ?: null,
                $semester  ?: null,
                $campus    ?: null,
                $createdBy,
                $fileHash,          // stored so delete can auto-clear the lock
            ]);
            $inserted++;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = "Row {$rowNum}: Course '{$courseCode}' already exists (concurrent conflict). Skipped.";
            } else {
                $errors[] = "Row {$rowNum}: Database error — " . $e->getMessage();
            }
            $skipped++;
        }
    }
} catch (Throwable $e) {
    releaseImportLock($fileHash, $IMPORT_TYPE, false);
    jsonResponse(false, 'An unexpected error occurred during import: ' . $e->getMessage());
}

// ── Release lock ──────────────────────────────────────────────────────────────
if ($inserted > 0) {
    releaseImportLock($fileHash, $IMPORT_TYPE, true);  // permanent — blocks re-import
    $message = "{$inserted} course(s) imported successfully.";
    if ($skipped > 0) $message .= " {$skipped} row(s) were skipped.";
} else {
    releaseImportLock($fileHash, $IMPORT_TYPE, false); // temp released — allow retry
    $message = "No courses were imported. All {$skipped} row(s) were skipped or invalid.";
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
            $permCheck = null; // stale — fall through and allow re-import
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
    // Prevents the same user from holding two concurrent temp locks.
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

    // Step 5: Atomic INSERT IGNORE — only one concurrent caller wins
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

function releaseImportLock(string $hash, string $importType, bool $permanent): void {
    global $db, $userId, $userName, $roleLabel, $userCampus;

    if ($permanent) {
        // Upsert to permanent — blocks this file hash from ever being re-imported
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
        // Delete the temp lock owned by this user so others can retry
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
    $type = ucfirst($lock['import_type'] ?? 'courses');
    if ((int)$lock['is_permanent']) {
        return "This {$type} file has already been imported by {$who} ({$role}{$camp}). "
             . "Re-importing the same file is not allowed.";
    }
    $unit = $secsLeft === 1 ? 'second' : 'seconds';
    return "{$who} ({$role}{$camp}) is currently importing this {$type} file. "
         . "Please wait {$secsLeft} {$unit} before trying again.";
}

function dataWasDeleted(PDO $db, string $hash, string $importType): bool {
    try {
        $table = match ($importType) {
            'courses'  => 'courses',
            'colleges' => 'colleges',
            'proctors' => 'proctors',
            'rooms'    => 'rooms',
            default    => 'schedules',
        };
        $cols = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'file_hash'")->fetchAll();
        if (empty($cols)) {
            // No file_hash column — treat table-empty as fully deleted
            return (int)$db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() === 0;
        }
        $s = $db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE file_hash = ?");
        $s->execute([$hash]);
        return ((int)$s->fetchColumn()) === 0;
    } catch (PDOException $e) {
        return false; // err on the side of caution — don't auto-clear
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

function parseExcelToRows(string $tmpPath): array {
    if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
        try {
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpPath)->getActiveSheet();
            $data  = $sheet->toArray(null, true, true, false);
            if (empty($data)) return [];
            $header = array_map('trim', $data[0]);
            $rows   = [];
            for ($i = 1; $i < count($data); $i++) {
                $row = $data[$i];
                while (count($row) < count($header)) $row[] = '';
                $rows[] = array_combine($header, array_slice($row, 0, count($header)));
            }
            return $rows;
        } catch (\Exception $e) { return []; }
    }
    // Fallback: try treating it as CSV
    return parseCsvToRows(file_get_contents($tmpPath));
}