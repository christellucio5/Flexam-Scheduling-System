<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db     = getDB();
requireAuth();
$role   = $_SESSION['user']['role'];

// Explicit column list — guarantees building is always included in the response
$sql = "SELECT id, building, name, capacity, floor, campus, locked, block_reason, blocked_from, blocked_to FROM rooms";

// --- GET ALL ROOMS ---
if ($action === 'list') {
    $adminCampus = trim($_SESSION['user']['campus'] ?? '');
    if ($adminCampus) {
        // Campus admin sees only their campus rooms
        $stmt = $db->prepare(
            "$sql
             WHERE LOWER(TRIM(campus)) = LOWER(?)
                OR campus IS NULL OR campus = ''
             ORDER BY name"
        );
        $stmt->execute([$adminCampus]);
    } else {
        // Super Admin sees all rooms
        $stmt = $db->prepare("$sql ORDER BY name");
        $stmt->execute();
    }
    jsonResponse(true, 'OK', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// --- ADMIN ONLY ACTIONS ---
if ($role !== 'Admin') jsonResponse(false, 'Forbidden', [], 403);
$body = getBody();

// --- CREATE ROOM ---
if ($action === 'create') {
    $name      = clean($body['name']     ?? '');
    $building  = clean($body['building'] ?? '');
    $capacity  = (int)($body['capacity'] ?? 0);
    $floor     = clean($body['floor']    ?? '');
    $locked    = isset($body['locked']) ? (int)$body['locked'] : 0;
    $campus    = clean($body['campus']   ?? '');
    $createdBy = safeCreatedBy();

    if (!$name || !$building || !$capacity) {
        jsonResponse(false, 'Room name, building and capacity are required.');
    }

    // --- ATOMIC CHECK + INSERT via transaction ---
    $db->beginTransaction();
    try {
        // FOR UPDATE locks matched rows so no concurrent request can pass this check simultaneously
        $dupCheck = $db->prepare(
            'SELECT id FROM rooms
             WHERE LOWER(name) = LOWER(?) AND LOWER(building) = LOWER(?) AND campus = ?
             LIMIT 1 FOR UPDATE'
        );
        $dupCheck->execute([$name, $building, $campus]);
        if ($dupCheck->fetch()) {
            $db->rollBack();
            jsonResponse(false, "Room \"{$name}\" in \"{$building}\" already exists in the {$campus} campus.");
        }

        $db->prepare('INSERT INTO rooms (name, building, capacity, floor, locked, campus, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)')
           ->execute([$name, $building, $capacity, $floor ?: null, $locked, $campus ?: null, $createdBy]);

        $id = (int)$db->lastInsertId();
        $db->commit();

        $s = $db->prepare('SELECT * FROM rooms WHERE id = ?');
        $s->execute([$id]);
        jsonResponse(true, 'Room created successfully.', $s->fetch(PDO::FETCH_ASSOC));

    } catch (PDOException $e) {
        $db->rollBack();
        // Catches any race that slipped past the FOR UPDATE check (e.g. UNIQUE constraint violation)
        if ($e->getCode() === '23000') {
            jsonResponse(false, "Room \"{$name}\" in \"{$building}\" already exists in the {$campus} campus.");
        }
        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

// --- UPDATE ROOM ---
if ($action === 'update') {
    $id       = (int)($body['id']       ?? 0);
    $building = clean($body['building'] ?? '');
    $name     = clean($body['name']     ?? '');
    $capacity = (int)($body['capacity'] ?? 0);
    $floor    = clean($body['floor']    ?? '');
    $locked   = isset($body['locked']) ? (int)$body['locked'] : 0;
    $campus   = clean($body['campus']   ?? '');

    if (!$id || !$name || !$building || !$capacity) {
        jsonResponse(false, 'ID, name, building and capacity are required.');
    }

    try {
        $db->prepare('UPDATE rooms SET building=?, name=?, capacity=?, floor=?, locked=?, campus=?, updated_at=NOW() WHERE id=?')
           ->execute([$building, $name, $capacity, $floor ?: null, $locked, $campus ?: null, $id]);

        $s = $db->prepare('SELECT * FROM rooms WHERE id = ?');
        $s->execute([$id]);
        jsonResponse(true, 'Room updated successfully.', $s->fetch(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

// --- BLOCK ROOM ---
if ($action === 'block') {
    $id           = (int)($body['id']           ?? 0);
    $block_reason = clean($body['block_reason'] ?? '');
    $blocked_from = clean($body['blocked_from'] ?? '');
    $blocked_to   = clean($body['blocked_to']   ?? '');

    if (!$id) jsonResponse(false, 'Room ID is required.');

    // Check if block_reason column exists; if not, fall back to just locked=1
    try {
        $db->prepare(
            'UPDATE rooms SET locked = 1, block_reason = ?, blocked_from = ?, blocked_to = ?, updated_at = NOW() WHERE id = ?'
        )->execute([
            $block_reason ?: null,
            $blocked_from ?: null,
            $blocked_to   ?: null,
            $id
        ]);

        $s = $db->prepare('SELECT * FROM rooms WHERE id = ?');
        $s->execute([$id]);
        jsonResponse(true, 'Room blocked successfully.', $s->fetch(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        // Fallback: columns may not exist yet — just lock the room
        try {
            $db->prepare('UPDATE rooms SET locked = 1, updated_at = NOW() WHERE id = ?')->execute([$id]);
            jsonResponse(true, 'Room blocked (reason not saved — run migration).', []);
        } catch (PDOException $e2) {
            jsonResponse(false, 'Database error: ' . $e2->getMessage());
        }
    }
}

// --- UNBLOCK ROOM ---
if ($action === 'unblock') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(false, 'Room ID is required.');

    try {
        $db->prepare(
            'UPDATE rooms SET locked = 0, block_reason = NULL, blocked_from = NULL, blocked_to = NULL, updated_at = NOW() WHERE id = ?'
        )->execute([$id]);
    } catch (PDOException $e) {
        // Fallback if columns missing
        $db->prepare('UPDATE rooms SET locked = 0, updated_at = NOW() WHERE id = ?')->execute([$id]);
    }

    $s = $db->prepare('SELECT * FROM rooms WHERE id = ?');
    $s->execute([$id]);
    jsonResponse(true, 'Room unblocked successfully.', $s->fetch(PDO::FETCH_ASSOC));
}

// --- TOGGLE LOCK (legacy) ---
if ($action === 'toggle_lock') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(false, 'Room ID is required.');
    $db->prepare('UPDATE rooms SET locked = NOT locked, updated_at=NOW() WHERE id=?')->execute([$id]);
    jsonResponse(true, 'Room lock status changed.');
}

// --- DELETE ROOM ---
if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(false, 'Room ID is required.');

    // Get file_hash BEFORE deleting so we can check if lock should be cleared
    $hashRow = $db->prepare('SELECT file_hash FROM rooms WHERE id = ?');
    $hashRow->execute([$id]);
    $deletedHash = ($hashRow->fetch(PDO::FETCH_ASSOC)['file_hash'] ?? null);

    // Delete the room
    $db->prepare('DELETE FROM rooms WHERE id = ?')->execute([$id]);

    // Auto-clear the import lock if NO rooms remain from that same file.
    // This means: all data from this file is gone → file can be re-imported.
    if ($deletedHash) {
        $remaining = $db->prepare('SELECT COUNT(*) FROM rooms WHERE file_hash = ?');
        $remaining->execute([$deletedHash]);
        if ((int)$remaining->fetchColumn() === 0) {
            $db->prepare(
                'DELETE FROM import_locks
                  WHERE file_hash    = ?
                    AND import_type  = ?
                    AND is_permanent = 1'
            )->execute([$deletedHash, 'rooms']);
        }
    }

    jsonResponse(true, 'Room deleted successfully.');
}

jsonResponse(false, 'Unknown action.', [], 400);