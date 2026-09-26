<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');

requireAuth();
if ($_SESSION['user']['role'] !== 'Admin') jsonResponse(false, 'Unauthorized', [], 403);

$action      = $_GET['action'] ?? '';
$db          = getDB();
$adminCampus = trim($_SESSION['user']['campus'] ?? ''); // empty = Super Admin

if ($action === 'list') {
    if ($adminCampus) {
        // Campus admin sees only users on their campus (or users with no campus set)
        $stmt = $db->prepare(
            "SELECT id, full_name, username, role, email, campus, college, program, created_at
             FROM users
             WHERE LOWER(TRIM(campus)) = LOWER(?)
                OR campus IS NULL OR campus = ''
             ORDER BY created_at DESC"
        );
        $stmt->execute([$adminCampus]);
    } else {
        // Super Admin sees everyone
        $stmt = $db->query(
            'SELECT id, full_name, username, role, email, campus, college, program, created_at
             FROM users ORDER BY created_at DESC'
        );
    }
    jsonResponse(true, 'OK', $stmt->fetchAll());
}

$body = getBody();

if ($action === 'create') {
    $full_name = clean($body['full_name'] ?? '');
    $username  = clean($body['username']  ?? '');
    $password  = $body['password']        ?? '';
    $role      = clean($body['role']      ?? '');
    $email     = clean($body['email']     ?? '');
    $campus    = clean($body['campus']    ?? '');
    $college   = clean($body['college']   ?? '');
    $program   = clean($body['program']   ?? '');

    // Campus admin can only create users for their own campus
    if ($adminCampus && $campus && strtolower($campus) !== strtolower($adminCampus)) {
        jsonResponse(false, "You can only create users for the {$adminCampus} campus.");
    }
    // Campus admin's new users inherit their campus if none specified
    if ($adminCampus && !$campus) $campus = $adminCampus;

    if (!$full_name || !$username || !$password || !$role)
        jsonResponse(false, 'Full name, username, password and role are required.');
    if (!in_array($role, ['Admin', 'Program Head']))
        jsonResponse(false, 'Invalid role.');
    if (strlen($password) < 6)
        jsonResponse(false, 'Password must be at least 6 characters.');

    $check = $db->prepare('SELECT id FROM users WHERE username = ?');
    $check->execute([$username]);
    if ($check->fetch()) jsonResponse(false, 'Username already exists.');

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $db->prepare(
        'INSERT INTO users (full_name, username, password, role, email, campus, college, program)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([$full_name, $username, $hash, $role,
                $email ?: null, $campus ?: null, $college ?: null, $program ?: null]);

    jsonResponse(true, "User \"{$username}\" created successfully.", ['id' => $db->lastInsertId()]);
}

if ($action === 'update') {
    $id        = (int)($body['id']        ?? 0);
    $full_name = clean($body['full_name'] ?? '');
    $username  = clean($body['username']  ?? '');
    $password  = $body['password']        ?? '';
    $role      = clean($body['role']      ?? '');
    $email     = clean($body['email']     ?? '');
    $campus    = clean($body['campus']    ?? '');
    $college   = clean($body['college']   ?? '');
    $program   = clean($body['program']   ?? '');

    if (!$id || !$full_name || !$username || !$role)
        jsonResponse(false, 'ID, full name, username and role are required.');
    if (!in_array($role, ['Admin', 'Program Head']))
        jsonResponse(false, 'Invalid role.');

    // Campus admin can only edit users on their own campus
    if ($adminCampus) {
        $targetUser = $db->prepare('SELECT campus FROM users WHERE id = ?');
        $targetUser->execute([$id]);
        $targetCampus = trim($targetUser->fetchColumn() ?? '');
        if ($targetCampus && strtolower($targetCampus) !== strtolower($adminCampus)) {
            jsonResponse(false, 'You can only edit users from your own campus.');
        }
        // Prevent reassigning user to a different campus
        if ($campus && strtolower($campus) !== strtolower($adminCampus)) {
            jsonResponse(false, "You cannot reassign users to a different campus.");
        }
        if (!$campus) $campus = $adminCampus;
    }

    $check = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
    $check->execute([$username, $id]);
    if ($check->fetch()) jsonResponse(false, 'Username already taken by another user.');

    if ($password !== '') {
        if (strlen($password) < 6) jsonResponse(false, 'New password must be at least 6 characters.');
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare(
            'UPDATE users SET full_name=?, username=?, password=?, role=?, email=?, campus=?, college=?, program=? WHERE id=?'
        )->execute([$full_name, $username, $hash, $role,
                    $email ?: null, $campus ?: null, $college ?: null, $program ?: null, $id]);
    } else {
        $db->prepare(
            'UPDATE users SET full_name=?, username=?, role=?, email=?, campus=?, college=?, program=? WHERE id=?'
        )->execute([$full_name, $username, $role,
                    $email ?: null, $campus ?: null, $college ?: null, $program ?: null, $id]);
    }
    jsonResponse(true, 'User updated successfully.');
}

if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(false, 'Invalid user ID.');
    if ($id === (int)$_SESSION['user']['id']) jsonResponse(false, 'You cannot delete your own account.');

    // Campus admin can only delete users on their own campus
    if ($adminCampus) {
        $targetUser = $db->prepare('SELECT campus FROM users WHERE id = ?');
        $targetUser->execute([$id]);
        $targetCampus = trim($targetUser->fetchColumn() ?? '');
        if ($targetCampus && strtolower($targetCampus) !== strtolower($adminCampus)) {
            jsonResponse(false, 'You can only delete users from your own campus.');
        }
    }
    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) jsonResponse(false, 'User not found.');
    jsonResponse(true, 'User deleted successfully.');
}

jsonResponse(false, 'Unknown action.', [], 400);