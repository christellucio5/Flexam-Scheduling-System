<?php
// ============================================================
// api/import_colleges.php
// Bulk CSV / XLSX / XLS colleges + programs importer
//
// LOCK FLOW (30-second SS precision):
//   1. SHA-256 hash of raw file bytes
//   2. acquire  → block immediately if already_imported (permanent lock exists)
//              → block for 30 s if another user holds a temp lock
//   3. Parse & validate rows
//   4. UPSERT each college, INSERT each program (skips duplicates)
//   5. release(permanent=true)  when $touchedDB > 0 (reached the DB stage)
//   6. release(permanent=false) ONLY on hard errors (empty / bad headers /
//      all rows blocked by campus guard before any DB query)
//
// Role permissions:
//   Super Admin  → any campus
//   Campus Admin → own campus only
//   Program Head → NOT allowed (colleges are Admin-only)
//
// Required CSV columns:  name, code, campus
// Optional:              description, programs (semicolon-separated)
//
// ⚠ Uses NOW() — InfinityFree MySQL does not support fractional-second
//   precision (NOW(6)). The locked_at column is plain datetime.
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────────────────────────
requireAuth();
$u          = $_SESSION['user'];
$userId     = (int)($u['id']        ?? 0);
$userName   = trim($u['full_name']  ?? $u['username'] ?? 'Unknown');
$userRole   = $u['role']            ?? '';
$userCampus = trim($u['campus']     ?? '');

if ($userRole === 'Program Head') {
    jsonResponse(false, 'Program Heads are not permitted to import colleges.', [], 403);
}
if ($userRole !== 'Admin') {
    jsonResponse(false, 'Forbidden.', [], 403);
}

$roleLabel   = ($userCampus === '') ? 'Super Admin' : 'Campus Admin';
$IMPORT_TYPE = 'colleges';
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
    releaseImportLock($fileHash, $IMPORT_TYPE, false); // hard error — allow retry
    jsonResponse(false, 'The file is empty or could not be parsed. No colleges were imported.');
}

// ── Validate required headers ─────────────────────────────────────────────────
$header   = array_map('strtolower', array_map('trim', array_keys($rows[0])));
$required = ['name', 'code', 'campus'];
$missing  = array_diff($required, $header);
if ($missing) {
    releaseImportLock($fileHash, $IMPORT_TYPE, false); // hard error — allow retry
    jsonResponse(false, 'Missing required columns: ' . implode(', ', $missing));
}

// ── Resolve created_by ────────────────────────────────────────────────────────
$createdBy = null;
if ($userId > 0) {
    $chk = $db->prepare('SELECT id FROM users WHERE id = ?');
    $chk->execute([$userId]);
    if ($chk->fetch()) $createdBy = $userId;
}
if ($createdBy === null) {
    $fallback  = $db->query("SELECT id FROM users WHERE role='Admin' ORDER BY id LIMIT 1")->fetch();
    $createdBy = $fallback ? (int)$fallback['id'] : null;
}

// ── Process rows ──────────────────────────────────────────────────────────────
$insertedColleges = 0;
$insertedPrograms = 0;
$skipped          = 0;
$touchedDB        = 0;   // rows that actually reached the DB (inserted or duplicate)
$errors           = [];

try {
    foreach ($rows as $lineNum => $raw) {
        $rowNum = $lineNum + 2;

        $r = [];
        foreach ($raw as $k => $v) $r[strtolower(trim($k))] = trim((string)$v);

        $name        = $r['name']        ?? '';
        $code        = strtoupper($r['code'] ?? '');
        $description = $r['description'] ?? '';
        $campus      = $r['campus']      ?? $userCampus;
        $programsRaw = $r['programs']    ?? '';

        // ── Campus guard ──────────────────────────────────────────────────────
        if ($roleLabel === 'Campus Admin' && $campus !== $userCampus) {
            $errors[] = "Row {$rowNum}: You can only import colleges for campus '{$userCampus}'. Row campus '{$campus}' skipped.";
            $skipped++;
            continue;
        }

        // ── Required field validation ─────────────────────────────────────────
        if (!$name || !$code || !$campus) {
            $errors[] = "Row {$rowNum}: name, code, and campus are required. Skipped.";
            $skipped++;
            continue;
        }

        // ── Parse programs list (semicolon-separated) ─────────────────────────
        $programs = [];
        if ($programsRaw !== '') {
            $programs = array_values(array_filter(array_map('trim', explode(';', $programsRaw))));
        }

        // ── Find or create the college ────────────────────────────────────────
        $existingCollege = $db->prepare(
            'SELECT id FROM colleges WHERE name = ? AND campus = ? LIMIT 1'
        );
        $existingCollege->execute([$name, $campus]);
        $existingRow = $existingCollege->fetch();

        if ($existingRow) {
            $collegeId    = (int)$existingRow['id'];
            $collegeIsNew = false;
            $touchedDB++;
            // Do NOT overwrite file_hash on existing colleges — that would break
            // the permanent-lock detection for the file that originally imported them.
        } else {
            try {
                $db->prepare(
                    'INSERT INTO colleges (name, code, description, campus, created_by, file_hash)
                     VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([$name, $code, $description ?: null, $campus, $createdBy, $fileHash]);
                $collegeId    = (int)$db->lastInsertId();
                $collegeIsNew = true;
                $insertedColleges++;
                $touchedDB++;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    // Race condition — fetch the winner row
                    $re = $db->prepare(
                        'SELECT id FROM colleges WHERE name = ? AND campus = ? LIMIT 1'
                    );
                    $re->execute([$name, $campus]);
                    $reRow = $re->fetch();
                    if ($reRow) {
                        $collegeId    = (int)$reRow['id'];
                        $collegeIsNew = false;
                        $touchedDB++; // college exists in DB (race-inserted by another user)
                        // Do NOT overwrite file_hash on the existing row
                    } else {
                        $errors[] = "Row {$rowNum}: Could not create or find college '{$name}' at '{$campus}'.";
                        $skipped++;
                        continue;
                    }
                } else {
                    $errors[] = "Row {$rowNum}: Database error — " . $e->getMessage();
                    $skipped++;
                    continue;
                }
            }
        }

        // ── Insert programs for this college ──────────────────────────────────
        $newProgramsThisRow = 0;
        foreach ($programs as $prog) {
            if ($prog === '') continue;
            $acronym = extractAcronymLocal($prog);

            // Match: exact program string  OR  stored as "ACRONYM=..."  OR  stored as plain "ACRONYM"
            $dupProg = $db->prepare(
                'SELECT id FROM college_programs
                  WHERE college_id = ?
                    AND (program = ?
                         OR program LIKE ?
                         OR program = ?)
                  LIMIT 1'
            );
            $dupProg->execute([$collegeId, $prog, $acronym . '=%', $acronym]);
            if ($dupProg->fetch()) {
                $errors[] = "College '{$name}' · {$campus}: Program '{$acronym}' already exists. Skipped.";
                $skipped++;
                $touchedDB++;  // program exists in DB — data from this file is there
                continue;
            }

            try {
                $db->prepare(
                    'INSERT INTO college_programs (college_id, program) VALUES (?, ?)'
                )->execute([$collegeId, $prog]);
                $insertedPrograms++;
                $newProgramsThisRow++;
            } catch (PDOException $e) {
                $errors[] = "College '{$name}': Program '{$prog}' — " . $e->getMessage();
                $skipped++;
            }
        }

        // If college already existed and no new programs were added from this row,
        // count the entire row as a duplicate skip.
        if (!$collegeIsNew && $newProgramsThisRow === 0 && !empty($programs)) {
            // errors were already added per-program above; just ensure skipped is counted
            // (each duplicate program already incremented $skipped — nothing extra needed)
        } elseif (!$collegeIsNew && empty($programs)) {
            // Existing college, no programs in the row at all — skip the whole row
            $errors[] = "College '{$name}' · {$campus}: Already exists (no new programs to add). Skipped.";
            $skipped++;
        }
    } // end foreach rows
} catch (Throwable $e) {
    releaseImportLock($fileHash, $IMPORT_TYPE, false);
    jsonResponse(false, 'An unexpected error occurred during import: ' . $e->getMessage());
}

// ── Race check: did another user permanently lock this file while we were processing? ──
//
// This catches the concurrent-import scenario where two users uploaded the same
// file at nearly the same time, both passed acquireImportLock() (temp lock), and
// one of them finished first and set is_permanent=1 before we got here.
// We check regardless of $touchedDB so both the "data inserted" and "all-duplicate"
// paths are protected.
//
$raceCheck = $db->prepare(
    'SELECT * FROM import_locks
      WHERE file_hash    = ?
        AND import_type  = ?
        AND is_permanent = 1
        AND locked_by   != ?
      LIMIT 1'
);
$raceCheck->execute([$fileHash, $IMPORT_TYPE, $userId]);
$raceLock = $raceCheck->fetch() ?: null;

if ($raceLock) {
    // We lost the race — release our temp lock and surface the block toast
    releaseImportLock($fileHash, $IMPORT_TYPE, false);
    $who  = htmlspecialchars($raceLock['locked_by_name'], ENT_QUOTES);
    $role = htmlspecialchars($raceLock['role'],           ENT_QUOTES);
    $camp = ($raceLock['campus'] !== '')
          ? ' (' . htmlspecialchars($raceLock['campus'], ENT_QUOTES) . ')' : '';
    jsonResponse(false,
        "This Colleges file has already been imported by {$who} ({$role}{$camp}). Re-importing the same file is not allowed.",
        ['status' => 'already_imported', 'can_reset' => ($roleLabel === 'Super Admin')]
    );
}

// ── Release lock & respond ────────────────────────────────────────────────────
//
// Go permanent when $touchedDB > 0 — meaning at least one row passed the campus
// guard and field validation and reached the DB (whether inserted fresh or found
// as an existing duplicate). This prevents re-importing the same file even when
// all colleges already existed (e.g. previously hand-created with file_hash=NULL).
//
// Go permanent=false (temp lock released / retry allowed) ONLY when $touchedDB=0,
// which happens exclusively when ALL rows were blocked before any DB query —
// e.g. every row failed the campus guard or the name/code/campus validation.
//
$totalInserted = $insertedColleges + $insertedPrograms;

if ($touchedDB > 0) {
    // ── Permanent lock: file is fully processed, block any re-import ──────────
    releaseImportLock($fileHash, $IMPORT_TYPE, true);

    if ($totalInserted > 0) {
        $message = "{$insertedColleges} college(s) and {$insertedPrograms} program(s) imported successfully.";
        if ($skipped > 0) $message .= " {$skipped} row(s) were skipped.";
        jsonResponse(true, $message, [
            'inserted_colleges' => $insertedColleges,
            'inserted_programs' => $insertedPrograms,
            'skipped'           => $skipped,
            'errors'            => $errors,
            'file_hash'         => $fileHash,
        ]);
    } else {
        // Nothing new inserted but rows did reach the DB — all duplicates.
        // Permanent lock is now set. Return already_imported so the frontend
        // shows the red "file already imported" toast (same as schedule import).
        $message = "No new colleges were imported — all rows already exist or were invalid. {$skipped} row(s) skipped.";
        jsonResponse(false, $message, [
            'status'            => 'already_imported',
            'can_reset'         => ($roleLabel === 'Super Admin'),
            'inserted_colleges' => 0,
            'inserted_programs' => 0,
            'skipped'           => $skipped,
            'errors'            => $errors,
            'file_hash'         => $fileHash,
        ]);
    }
} else {
    // ── No DB interaction — release temp lock so user can fix & retry ─────────
    releaseImportLock($fileHash, $IMPORT_TYPE, false);
    $message = "No colleges were imported — all rows were invalid or blocked. {$skipped} row(s) skipped.";
    jsonResponse(false, $message, [
        'inserted_colleges' => 0,
        'inserted_programs' => 0,
        'skipped'           => $skipped,
        'errors'            => $errors,
        'file_hash'         => $fileHash,
    ]);
}


// =============================================================================
// ── Helpers
// =============================================================================

/**
 * Local acronym extractor — named uniquely to avoid fatal redeclare errors
 * if colleges.php is included in the same request chain.
 */
function extractAcronymLocal(string $prog): string {
    $pos = strpos($prog, '=');
    return $pos !== false ? trim(substr($prog, 0, $pos)) : trim($prog);
}

function acquireImportLock(string $hash, string $importType): array {
    global $db, $userId, $userName, $roleLabel, $userCampus;

    // Step 1: Check for a permanent lock — file already fully processed.
    // Blocks ALL users (Super Admin AND Campus Admin) from re-importing.
    // NOTE: We do NOT call dataWasDeleted() here for colleges because colleges
    // can be upserted from files where the existing rows carry file_hash=NULL
    // (hand-created colleges). In that case dataWasDeleted() would wrongly return
    // true (no rows with this hash) and auto-clear a valid permanent lock.
    // Lock clearance for colleges happens only via explicit delete in colleges.php.
    $s = $db->prepare(
        'SELECT * FROM import_locks
          WHERE file_hash    = ?
            AND import_type  = ?
            AND is_permanent = 1
          LIMIT 1'
    );
    $s->execute([$hash, $importType]);
    $permCheck = $s->fetch() ?: null;

    if ($permCheck) {
        return [
            'success'   => false,
            'status'    => 'already_imported',
            'message'   => buildLockMsg($permCheck, 0),
            'can_reset' => ($roleLabel === 'Super Admin'),
        ];
    }

    // Step 2: Delete any temp lock THIS user already holds for this file.
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
        // Hard-error path only: delete temp lock so user can fix & retry
        $db->prepare(
            'DELETE FROM import_locks
              WHERE file_hash    = ?
                AND import_type  = ?
                AND locked_by    = ?
                AND is_permanent = 0'
        )->execute([$hash, $importType, $userId]);
    }
}

function buildLockMsg(array $lock, int $secsLeft): string {
    $who  = htmlspecialchars($lock['locked_by_name'], ENT_QUOTES);
    $role = htmlspecialchars($lock['role'],           ENT_QUOTES);
    $camp = ($lock['campus'] !== '')
          ? ' (' . htmlspecialchars($lock['campus'], ENT_QUOTES) . ')'
          : '';
    $type = ucfirst($lock['import_type'] ?? 'colleges');
    if ((int)$lock['is_permanent']) {
        return "This {$type} file has already been imported by {$who} ({$role}{$camp}). "
             . "Re-importing the same file is not allowed.";
    }
    $unit = $secsLeft === 1 ? 'second' : 'seconds';
    return "{$who} ({$role}{$camp}) is currently importing this {$type} file. "
         . "Please wait {$secsLeft} {$unit} before trying again.";
}

/**
 * Returns true when no rows with this file_hash remain in the target table.
 * Only used for explicit reset checks (not called during acquireImportLock for colleges).
 */
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
            return (int)$db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() === 0;
        }

        $s = $db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE file_hash = ?");
        $s->execute([$hash]);
        return ((int)$s->fetchColumn()) === 0;

    } catch (PDOException $e) {
        return false;
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
    return parseCsvToRows(file_get_contents($tmpPath));
}