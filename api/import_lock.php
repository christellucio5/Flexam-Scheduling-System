<?php
// ============================================================
// api/import_lock.php
// Global import lock API
// Supports import_type: schedule | courses | colleges | proctors
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────────────────────────
if (empty($_SESSION['user']) || empty($_SESSION['user']['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthenticated.']);
    exit;
}

define('LOCK_TTL', 30);

$u          = $_SESSION['user'];
$userId     = (int)($u['id']       ?? 0);
$userName   = trim($u['full_name'] ?? $u['username'] ?? 'Unknown');
$userRole   = $u['role']           ?? '';
$userCampus = trim($u['campus']    ?? '');

if ($userRole === 'Admin' && $userCampus === '') {
    $roleLabel = 'Super Admin';
} elseif ($userRole === 'Admin') {
    $roleLabel = 'Campus Admin';
} else {
    $roleLabel = 'Program Head';
}

$isSuperAdmin = ($roleLabel === 'Super Admin');

$db     = getDB();
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = trim($body['action'] ?? $_GET['action'] ?? '');
$hash   = trim($body['file_hash'] ?? '');

// ── import_type ───────────────────────────────────────────────────────────────
$rawType    = trim($body['import_type'] ?? $_GET['import_type'] ?? 'schedule');
$importType = in_array($rawType, ['schedule', 'courses', 'colleges', 'proctors', 'rooms'], true)
            ? $rawType : 'schedule';

// ── Validate hash ─────────────────────────────────────────────────────────────
if ($action !== 'list_locks' && !preg_match('/^[a-f0-9]{64}$/', $hash)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing file_hash.']);
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function purgeStaleLocks(PDO $db, string $importType): void {
    $db->prepare(
        'DELETE FROM import_locks
          WHERE is_permanent = 0
            AND import_type  = ?
            AND locked_at < DATE_SUB(NOW(), INTERVAL ? SECOND)'
    )->execute([$importType, LOCK_TTL]);
}

function fetchLock(PDO $db, string $hash, string $importType): ?array {
    $s = $db->prepare(
        'SELECT * FROM import_locks
          WHERE file_hash   = ?
            AND import_type = ?
          LIMIT 1'
    );
    $s->execute([$hash, $importType]);
    return $s->fetch() ?: null;
}

function blockMsg(array $lock, int $secsLeft): string {
    $who  = htmlspecialchars($lock['locked_by_name'], ENT_QUOTES);
    $role = htmlspecialchars($lock['role'],           ENT_QUOTES);
    $camp = ($lock['campus'] !== '')
          ? ' (' . htmlspecialchars($lock['campus'], ENT_QUOTES) . ')'
          : '';
    $type = ucfirst($lock['import_type'] ?? 'schedule');

    if ((int)$lock['is_permanent']) {
        return "This {$type} file has already been imported by {$who} ({$role}{$camp}). "
             . "Importing the same file again is not allowed.";
    }
    $unit = $secsLeft === 1 ? 'second' : 'seconds';
    return "{$who} ({$role}{$camp}) is currently importing this {$type} file. "
         . "Please wait {$secsLeft} {$unit} and try again.";
}

/**
 * Returns true if ALL records that came from this file hash have been deleted
 * from the relevant table.  When true, the permanent lock is stale and safe
 * to clear so the file can be re-imported.
 */
function dataWasDeleted(PDO $db, string $hash, string $importType): bool {
    try {
        $table = match ($importType) {
            'courses'   => 'courses',
            'colleges'  => 'colleges',
            'proctors'  => 'proctors',
            'rooms'     => 'rooms',
            default     => 'schedules',  // 'schedule'
        };
        // Check whether the table even has a file_hash column
        $cols = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'file_hash'")->fetchAll();
        if (empty($cols)) {
            // No file_hash column — fall back: check if the whole table is empty
            $count = (int)$db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            return $count === 0;
        }
        $s = $db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE file_hash = ?");
        $s->execute([$hash]);
        return ((int)$s->fetchColumn()) === 0;
    } catch (PDOException $e) {
        // If we can't check, err on the side of caution — don't auto-clear
        return false;
    }
}

/**
 * Deletes the permanent lock row for this hash + import_type.
 */
function clearPermanentLock(PDO $db, string $hash, string $importType): void {
    $db->prepare(
        'DELETE FROM import_locks
          WHERE file_hash    = ?
            AND import_type  = ?
            AND is_permanent = 1'
    )->execute([$hash, $importType]);
}

purgeStaleLocks($db, $importType);


// =============================================================================
// ACTION: check
// =============================================================================
if ($action === 'check') {
    $lock = fetchLock($db, $hash, $importType);

    if (!$lock) {
        echo json_encode(['success' => true, 'status' => 'free']);
        exit;
    }

    if ((int)$lock['is_permanent']) {
        // Auto-release: if ALL records imported from this file were deleted from
        // the DB, the permanent lock is stale — clear it and allow re-import.
        if (dataWasDeleted($db, $hash, $importType)) {
            clearPermanentLock($db, $hash, $importType);
            echo json_encode(['success' => true, 'status' => 'free']);
            exit;
        }

        echo json_encode([
            'success'   => true,
            'status'    => 'already_imported',
            'message'   => blockMsg($lock, 0),
            'can_reset' => $isSuperAdmin,
        ]);
        exit;
    }

    $age      = (int)(microtime(true) - strtotime($lock['locked_at']));
    $secsLeft = max(0, LOCK_TTL - $age);
    echo json_encode([
        'success'      => true,
        'status'       => 'locked',
        'seconds_left' => $secsLeft,
        'message'      => blockMsg($lock, $secsLeft),
    ]);
    exit;
}


// =============================================================================
// ACTION: acquire — atomic first-writer-wins
// =============================================================================
if ($action === 'acquire') {

    // ── Step 1: Check for a permanent lock (file already fully imported) ──────
    $permCheck = fetchLock($db, $hash, $importType);
    if ($permCheck && (int)$permCheck['is_permanent']) {
        if (dataWasDeleted($db, $hash, $importType)) {
            clearPermanentLock($db, $hash, $importType);
            $permCheck = null; // stale — fall through
        } else {
            echo json_encode([
                'success'   => false,
                'status'    => 'already_imported',
                'message'   => blockMsg($permCheck, 0),
                'can_reset' => $isSuperAdmin,
            ]);
            exit;
        }
    }

    // ── Step 2: Forcibly delete ANY temp lock held by THIS user for this file. ─
    // Without this, two users can each call acquire simultaneously, each gets
    // their own temp lock, and both pass the same-user check — letting both import.
    $db->prepare(
        'DELETE FROM import_locks
          WHERE file_hash    = ?
            AND import_type  = ?
            AND locked_by    = ?
            AND is_permanent = 0'
    )->execute([$hash, $importType, $userId]);

    // ── Step 3: Purge stale temp locks from OTHER users ───────────────────────
    purgeStaleLocks($db, $importType);

    // ── Step 4: Check if another user holds a non-permanent lock right now ────
    $existing = fetchLock($db, $hash, $importType);
    if ($existing && !(int)$existing['is_permanent']) {
        $age      = (int)(microtime(true) - strtotime($existing['locked_at']));
        $secsLeft = max(0, LOCK_TTL - $age);
        echo json_encode([
            'success'      => false,
            'status'       => 'locked',
            'seconds_left' => $secsLeft,
            'message'      => blockMsg($existing, $secsLeft),
        ]);
        exit;
    }

    // ── Step 5: Try to insert — catch duplicate key if two users race here ────
    try {
        $db->prepare(
            'INSERT INTO import_locks
                 (file_hash, locked_by, locked_by_name, role, campus, import_type, locked_at, is_permanent)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)'
        )->execute([$hash, $userId, $userName, $roleLabel, $userCampus, $importType]);
        // Inserted successfully — we won the race
        echo json_encode(['success' => true, 'status' => 'acquired']);
    } catch (PDOException $e) {
        // Duplicate key — another user inserted between our check and our insert
        $lock     = fetchLock($db, $hash, $importType);
        $age      = $lock ? max(0, (int)(microtime(true) - strtotime($lock['locked_at']))) : 0;
        $secsLeft = max(0, LOCK_TTL - $age);
        echo json_encode([
            'success'      => false,
            'status'       => $lock && (int)$lock['is_permanent'] ? 'already_imported' : 'locked',
            'seconds_left' => $secsLeft,
            'message'      => $lock
                ? blockMsg($lock, $secsLeft)
                : 'This file is currently being imported by another user.',
        ]);
    }
    exit;
}


// =============================================================================
// ACTION: release
// permanent=true  → is_permanent=1 (file fully committed, blocked forever)
// permanent=false → delete temp lock (import failed, allow retry)
// =============================================================================
if ($action === 'release') {
    $permanent = !empty($body['permanent']);

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
        } catch (PDOException $e) {
            // Already permanent — fine
        }
        echo json_encode(['success' => true, 'status' => 'permanent']);
    } else {
        // Delete the temp (non-permanent) lock owned by this user
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

        echo json_encode(['success' => true, 'status' => 'released']);
    }
    exit;
}


// =============================================================================
// ACTION: reset  (Super Admin only)
// Clears a permanent lock — use after bulk-deleting the imported data
// =============================================================================
if ($action === 'reset') {
    if (!$isSuperAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only a Super Admin can reset a permanent import lock.']);
        exit;
    }

    $db->prepare(
        'DELETE FROM import_locks
          WHERE file_hash    = ?
            AND import_type  = ?
            AND is_permanent = 1'
    )->execute([$hash, $importType]);

    echo json_encode([
        'success' => true,
        'status'  => 'reset',
        'message' => 'Import lock cleared. The file can be re-imported.',
    ]);
    exit;
}


// =============================================================================
// ACTION: list_locks  (Super Admin only)
// Returns all permanent locks, optionally filtered by import_type
// =============================================================================
if ($action === 'list_locks') {
    if (!$isSuperAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only a Super Admin can view import locks.']);
        exit;
    }

    $filterType = trim($_GET['import_type'] ?? $body['import_type'] ?? '');
    $validTypes = ['schedule', 'courses', 'colleges', 'proctors', 'rooms'];

    if ($filterType && in_array($filterType, $validTypes, true)) {
        $s = $db->prepare(
            'SELECT file_hash, locked_by_name, role, campus, import_type, locked_at, is_permanent
               FROM import_locks
              WHERE is_permanent = 1
                AND import_type  = ?
              ORDER BY locked_at DESC
              LIMIT 200'
        );
        $s->execute([$filterType]);
        $rows = $s->fetchAll();
    } else {
        $rows = $db->query(
            'SELECT file_hash, locked_by_name, role, campus, import_type, locked_at, is_permanent
               FROM import_locks
              WHERE is_permanent = 1
              ORDER BY import_type ASC, locked_at DESC
              LIMIT 200'
        )->fetchAll();
    }

    echo json_encode(['success' => true, 'locks' => $rows]);
    exit;
}


// =============================================================================
// Fallback
// =============================================================================
echo json_encode([
    'success' => false,
    'message' => 'Unknown action: ' . htmlspecialchars($action, ENT_QUOTES),
]);