<?php

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

if ($userRole === 'Admin' && $userCampus === '') {
    $roleLabel = 'Super Admin';
} elseif ($userRole === 'Admin') {
    $roleLabel = 'Campus Admin';
} else {
    // Program Heads cannot import rooms — rooms are campus-level resources
    jsonResponse(false, 'Forbidden. Only Admins can import rooms.', [], 403);
}

$IMPORT_TYPE = 'rooms';
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
    jsonResponse(false, 'The file is empty or could not be parsed. No rooms were imported.');
}

// ── Validate required headers ─────────────────────────────────────────────────
$header  = array_map('strtolower', array_map('trim', array_keys($rows[0])));
$missing = array_diff(['name', 'building', 'campus'], $header);
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

        $name     = $r['name']     ?? '';
        $building = $r['building'] ?? '';
        $campus   = ($r['campus'] ?? '') !== '' ? $r['campus'] : $userCampus;
        $capacity = $r['capacity'] ?? '';
        $floor    = $r['floor']    ?? '';

        // ── Normalize capacity ────────────────────────────────────────────────────
        $capacityInt = null;
        if ($capacity !== '') {
            $capClean = ltrim($capacity, '0') ?: '0';
            if (!ctype_digit($capClean) || (int)$capacity < 0) {
                $errors[] = "Row {$rowNum}: capacity must be a non-negative integer (got '{$capacity}'). Skipped.";
                $skipped++;
                continue;
            }
            $capacityInt = (int)$capacity;
        }

        // ── Role-based campus guard ───────────────────────────────────────────────
        if ($roleLabel === 'Campus Admin' && $campus !== $userCampus) {
            $errors[] = "Row {$rowNum}: You can only import rooms for campus '{$userCampus}'. Row campus '{$campus}' skipped.";
            $skipped++;
            continue;
        }

        // ── Required field validation ─────────────────────────────────────────────
        if (!$name || !$building || !$campus) {
            $errors[] = "Row {$rowNum}: name, building, and campus are required. Skipped.";
            $skipped++;
            continue;
        }

        // ── Duplicate check: UNIQUE KEY unique_room_per_campus (name, building, campus) ──
        $dupCheck = $db->prepare(
            'SELECT id FROM rooms
              WHERE LOWER(name)     = LOWER(?)
                AND LOWER(building) = LOWER(?)
                AND LOWER(campus)   = LOWER(?)
              LIMIT 1'
        );
        $dupCheck->execute([$name, $building, $campus]);
        if ($dupCheck->fetch()) {
            $errors[] = "Row {$rowNum}: Room '{$name}' in building '{$building}' already exists in '{$campus}'. Skipped.";
            $skipped++;
            continue;
        }

        // ── INSERT ────────────────────────────────────────────────────────────────
        // locked defaults to 0 (rooms are unlocked when first imported).
        // floor is optional — stored as NULL if blank.
        try {
            $db->prepare(
                'INSERT INTO rooms
                   (name, building, capacity, floor, locked, campus, created_by, file_hash)
                 VALUES (?, ?, ?, ?, 0, ?, ?, ?)'
            )->execute([
                $name,
                $building,
                $capacityInt,       // null if not provided
                $floor ?: null,     // null if not provided
                $campus,
                $createdBy,
                $fileHash,          // ← DB trigger uses this to auto-clear lock on last-row delete
            ]);
            $inserted++;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = "Row {$rowNum}: Room '{$name}' in '{$building}' already exists (concurrent conflict). Skipped.";
            } else {
                $errors[] = "Row {$rowNum}: Database error — " . $e->getMessage();
            }
            $skipped++;
        }
    }
} catch (Throwable $e) {
    // Unexpected fatal error — always release the lock so it doesn't get stuck
    releaseImportLock($fileHash, $IMPORT_TYPE, false);
    jsonResponse(false, 'An unexpected error occurred during import: ' . $e->getMessage());
}

// ── Release lock ──────────────────────────────────────────────────────────────
if ($inserted > 0) {
    releaseImportLock($fileHash, $IMPORT_TYPE, true);
    $message = "{$inserted} room(s) imported successfully.";
    if ($skipped > 0) $message .= " {$skipped} row(s) were skipped.";
} else {
    releaseImportLock($fileHash, $IMPORT_TYPE, false);
    $message = "No rooms were imported. All {$skipped} row(s) were skipped or invalid.";
}

jsonResponse($inserted > 0, $message, [
    'inserted'  => $inserted,
    'skipped'   => $skipped,
    'errors'    => $errors,
    'file_hash' => $fileHash,
]);


// =============================================================================
// ── Lock helpers
// ⚠ All NOW() → NOW() for InfinityFree MySQL compatibility.
// =============================================================================

function acquireImportLock(string $hash, string $importType): array {
    global $db, $userId, $userName, $roleLabel, $userCampus;

    // Step 1: Check for a permanent lock (file already fully imported)
    $s = $db->prepare('SELECT * FROM import_locks WHERE file_hash = ? AND import_type = ? LIMIT 1');
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

    // Step 2: Delete any temp lock THIS user already holds for this file
    // (prevents same-user double-click from bypassing the unique key)
    $db->prepare(
        'DELETE FROM import_locks
          WHERE file_hash    = ?
            AND import_type  = ?
            AND locked_by    = ?
            AND is_permanent = 0'
    )->execute([$hash, $importType, $userId]);

    // Step 3: Purge stale temp locks from other users
    $db->prepare(
        'DELETE FROM import_locks
          WHERE is_permanent = 0
            AND import_type  = ?
            AND locked_at < DATE_SUB(NOW(), INTERVAL 30 SECOND)'
    )->execute([$importType]);

    // Step 4: Check if another user holds a temp lock right now
    $s2 = $db->prepare('SELECT * FROM import_locks WHERE file_hash = ? AND import_type = ? LIMIT 1');
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

    // Step 5: Try to insert — UNIQUE KEY (file_hash, import_type) ensures only one winner
    try {
        $db->prepare(
            'INSERT INTO import_locks
                 (file_hash, locked_by, locked_by_name, role, campus, import_type, locked_at, is_permanent)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)'
        )->execute([$hash, $userId, $userName, $roleLabel, $userCampus, $importType]);
        // Inserted successfully — we won the race
        return ['success' => true, 'status' => 'acquired'];
    } catch (PDOException $e) {
        // Duplicate key — another user inserted between our check and insert
        $s3 = $db->prepare('SELECT * FROM import_locks WHERE file_hash = ? AND import_type = ? LIMIT 1');
        $s3->execute([$hash, $importType]);
        $lock = $s3->fetch() ?: null;
        $age  = $lock ? max(0, (int)(microtime(true) - strtotime($lock['locked_at']))) : 0;
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
        $db->prepare(
            'DELETE FROM import_locks
              WHERE file_hash    = ?
                AND import_type  = ?
                AND locked_by    = ?
                AND is_permanent = 0'
        )->execute([$hash, $importType, $userId]);

        // Also clear a stale permanent lock if the data was already wiped from DB
        if (dataWasDeleted($db, $hash, $importType)) {
            clearPermanentLock($db, $hash, $importType);
        }
    }
}

function buildLockMsg(array $lock, int $secsLeft): string {
    $who  = htmlspecialchars($lock['locked_by_name'], ENT_QUOTES);
    $role = htmlspecialchars($lock['role'],           ENT_QUOTES);
    $camp = $lock['campus'] !== ''
          ? ' (' . htmlspecialchars($lock['campus'], ENT_QUOTES) . ')'
          : '';
    $type = ucfirst($lock['import_type'] ?? 'rooms');
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
            'rooms'    => 'rooms',
            'courses'  => 'courses',
            'colleges' => 'colleges',
            'proctors' => 'proctors',
            default    => 'schedules',
        };
        $cols = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'file_hash'")->fetchAll();
        if (empty($cols)) {
            $count = (int)$db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            return $count === 0;
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