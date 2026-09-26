<?php
require_once 'config/Db.php';

$username  = 'superadmin';
$password  = 'password';
$full_name = 'Super Administrator';
$role      = 'Admin';
$hash      = password_hash($password, PASSWORD_BCRYPT);

$db = getDB();

// Check if superadmin already exists
$check = $db->prepare('SELECT id FROM users WHERE username = ?');
$check->execute([$username]);
$existing = $check->fetch();

if ($existing) {
    // Update existing account
    $db->prepare('UPDATE users SET password = ?, role = ?, campus = NULL, full_name = ? WHERE username = ?')
       ->execute([$hash, $role, $full_name, $username]);
    echo "✅ Superadmin password has been reset successfully.<br>";
    echo "Username: <strong>{$username}</strong><br>";
    echo "Password: <strong>{$password}</strong><br>";
} else {
    // Create new account
    $db->prepare(
        'INSERT INTO users (full_name, username, password, role, campus, college, program) VALUES (?, ?, ?, ?, NULL, NULL, NULL)'
    )->execute([$full_name, $username, $hash, $role]);
    echo "✅ Superadmin account created successfully.<br>";
    echo "Username: <strong>{$username}</strong><br>";
    echo "Password: <strong>{$password}</strong><br>";
}

echo "<br><strong style='color:red;'>⚠️ Delete this file immediately after logging in!</strong>";
?>
