<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db     = getDB();
requireAuth();
$role = $_SESSION['user']['role'];

if ($action === 'list') {
    jsonResponse(true, 'OK', $db->query('SELECT * FROM proctors ORDER BY name')->fetchAll());
}

if (!in_array($role, ['Admin', 'Program Head'])) jsonResponse(false, 'Forbidden', [], 403);
$body = getBody();

if ($action === 'create') {
    $name           = clean($body['name']           ?? '');
    $collegeProgram = clean($body['collegeProgram'] ?? $body['college_program'] ?? '');
    $email          = clean($body['email']          ?? '');
    $phone          = clean($body['phone']          ?? '');
    $campus         = clean($body['campus']         ?? '');
    $createdBy      = $_SESSION['user']['id'] ?? null;

    if (!$name || !$email)
        jsonResponse(false, 'Proctor name and email are required.');

    // Duplicate check: same name + campus
    if ($campus) {
        $dup = $db->prepare('SELECT id FROM proctors WHERE name = ? AND campus = ? LIMIT 1');
        $dup->execute([$name, $campus]);
        if ($dup->fetch())
            jsonResponse(false, "Proctor '{$name}' already exists in '{$campus}'.");
    }

    // Duplicate check: same email globally
    $emailDup = $db->prepare('SELECT id FROM proctors WHERE email = ? LIMIT 1');
    $emailDup->execute([$email]);
    if ($emailDup->fetch())
        jsonResponse(false, "Email '{$email}' is already registered to another proctor.");

    try {
        $db->prepare('INSERT INTO proctors (name, college_program, email, phone, campus, created_by) VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$name, $collegeProgram ?: null, $email, $phone ?: null, $campus ?: null, $createdBy]);

        $id = (int)$db->lastInsertId();
        $s  = $db->prepare('SELECT * FROM proctors WHERE id = ?');
        $s->execute([$id]);
        jsonResponse(true, 'Proctor created successfully.', $s->fetch());
    } catch (PDOException $e) {
        // If created_by column doesn't exist, try without it
        if (strpos($e->getMessage(), 'created_by') !== false) {
            try {
                $db->prepare('INSERT INTO proctors (name, college_program, email, phone, campus) VALUES (?, ?, ?, ?, ?)')
                   ->execute([$name, $collegeProgram ?: null, $email, $phone ?: null, $campus ?: null]);
                $id = (int)$db->lastInsertId();
                $s  = $db->prepare('SELECT * FROM proctors WHERE id = ?');
                $s->execute([$id]);
                jsonResponse(true, 'Proctor created successfully.', $s->fetch());
            } catch (PDOException $e2) {
                jsonResponse(false, 'Database error: ' . $e2->getMessage());
            }
        }
        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

if ($action === 'update') {
    $id             = (int)($body['id']             ?? 0);
    $name           = clean($body['name']           ?? '');
    $collegeProgram = clean($body['collegeProgram'] ?? $body['college_program'] ?? '');
    $email          = clean($body['email']          ?? '');
    $phone          = clean($body['phone']          ?? '');
    $campus         = clean($body['campus']         ?? '');

    if (!$id || !$name || !$email)
        jsonResponse(false, 'ID, name and email are required.');

    try {
        $db->prepare('UPDATE proctors SET name=?, college_program=?, email=?, phone=?, campus=?, updated_at=NOW() WHERE id=?')
           ->execute([$name, $collegeProgram ?: null, $email, $phone ?: null, $campus ?: null, $id]);

        $s = $db->prepare('SELECT * FROM proctors WHERE id = ?');
        $s->execute([$id]);
        jsonResponse(true, 'Proctor updated successfully.', $s->fetch());
    } catch (PDOException $e) {
        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(false, 'Proctor ID is required.');

    // Get file_hash BEFORE deleting so we can check if lock should be cleared
    $hashRow = $db->prepare('SELECT file_hash FROM proctors WHERE id = ?');
    $hashRow->execute([$id]);
    $deletedHash = ($hashRow->fetch()['file_hash'] ?? null);

    // Delete the proctor
    $db->prepare('DELETE FROM proctors WHERE id = ?')->execute([$id]);

    // Auto-clear the import lock if NO proctors remain from that same file.
    // This means: all data from this file is gone → file can be re-imported.
    if ($deletedHash) {
        $remaining = $db->prepare('SELECT COUNT(*) FROM proctors WHERE file_hash = ?');
        $remaining->execute([$deletedHash]);
        if ((int)$remaining->fetchColumn() === 0) {
            $db->prepare(
                'DELETE FROM import_locks
                  WHERE file_hash    = ?
                    AND import_type  = ?
                    AND is_permanent = 1'
            )->execute([$deletedHash, 'proctors']);
        }
    }

    jsonResponse(true, 'Proctor deleted successfully.');
}

jsonResponse(false, 'Unknown action.', [], 400);