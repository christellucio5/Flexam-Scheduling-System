<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// DB connection — adjust path as needed
$dbPath = __DIR__ . '/../db/flexam.db';
if (!file_exists($dbPath)) {
    // Try alternate paths
    foreach (['../database/flexam.db', '../flexam.db', '../../flexam.db', '../db/database.db', '../database.db'] as $p) {
        if (file_exists(__DIR__ . '/' . $p)) { $dbPath = __DIR__ . '/' . $p; break; }
    }
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    // Try MySQL fallback
    try {
        $host = 'localhost'; $db = 'flexam'; $user = 'root'; $pass = '';
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e2) {
        echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $e2->getMessage()]);
        exit;
    }
}

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    user_role TEXT NOT NULL,
    user_name TEXT,
    college TEXT,
    action TEXT NOT NULL,
    module TEXT NOT NULL,
    record_id INTEGER,
    description TEXT,
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$action = $_GET['action'] ?? '';

// ── LIST ──────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    $currentUser = $_SESSION['user'];
    $role        = $currentUser['role'] ?? '';

    $where  = [];
    $params = [];

    // Program Head sees only their own logs
    if ($role === 'Program Head') {
        $where[]  = 'user_id = :uid';
        $params[':uid'] = $currentUser['id'];
    }

    // Optional filters from query string
    if (!empty($_GET['module'])) {
        $where[]           = 'module = :module';
        $params[':module'] = $_GET['module'];
    }
    if (!empty($_GET['action_type'])) {
        $where[]               = 'action = :action_type';
        $params[':action_type'] = $_GET['action_type'];
    }
    if (!empty($_GET['user_role_filter']) && $role === 'Admin') {
        $where[]                    = 'user_role = :urfilt';
        $params[':urfilt'] = $_GET['user_role_filter'];
    }
    if (!empty($_GET['college']) && $role === 'Admin') {
        $where[]              = 'college = :college';
        $params[':college']   = $_GET['college'];
    }
    if (!empty($_GET['date_from'])) {
        $where[]               = 'date(created_at) >= :date_from';
        $params[':date_from']  = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[]             = 'date(created_at) <= :date_to';
        $params[':date_to']  = $_GET['date_to'];
    }
    if (!empty($_GET['search'])) {
        $where[]              = "(description LIKE :search OR user_name LIKE :search2 OR module LIKE :search3)";
        $params[':search']    = '%' . $_GET['search'] . '%';
        $params[':search2']   = '%' . $_GET['search'] . '%';
        $params[':search3']   = '%' . $_GET['search'] . '%';
    }

    $sql  = 'SELECT * FROM audit_logs';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY created_at DESC LIMIT 500';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $logs]);
    exit;
}

// ── LOG (create entry) ────────────────────────────────────────────────────────
if ($action === 'log') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $currentUser = $_SESSION['user'];

    $stmt = $pdo->prepare("INSERT INTO audit_logs
        (user_id, user_role, user_name, college, action, module, record_id, description, ip_address)
        VALUES (:uid, :role, :name, :college, :action, :module, :rid, :desc, :ip)");

    $stmt->execute([
        ':uid'    => $currentUser['id']       ?? 0,
        ':role'   => $currentUser['role']      ?? 'Unknown',
        ':name'   => $currentUser['full_name'] ?? 'Unknown',
        ':college'=> $currentUser['college']   ?? '',
        ':action' => $input['action']          ?? '',
        ':module' => $input['module']          ?? '',
        ':rid'    => $input['record_id']       ?? null,
        ':desc'   => $input['description']     ?? '',
        ':ip'     => $_SERVER['REMOTE_ADDR']   ?? '',
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// ── CLEAR own logs (Program Head only) ───────────────────────────────────────
if ($action === 'clear_own') {
    $currentUser = $_SESSION['user'];
    if ($currentUser['role'] !== 'Program Head') {
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM audit_logs WHERE user_id = :uid");
    $stmt->execute([':uid' => $currentUser['id']]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);