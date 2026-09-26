<?php
require_once __DIR__ . '/../config/Db.php';

function getBody(): array {
    $raw     = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $_POST;
}

function clean(string $value): string {
    return trim(strip_tags($value));
}

function jsonResponse(bool $success, string $message = '', $data = [], int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function requireAuth(): void {
    if (empty($_SESSION['user'])) {
        jsonResponse(false, 'Unauthorized. Please log in.', [], 401);
    }
}

function safeCreatedBy(): ?int {
    $db    = getDB();
    $rawId = (int)($_SESSION['user']['id'] ?? 0);
    if ($rawId > 0) {
        $s = $db->prepare('SELECT id FROM users WHERE id = ?');
        $s->execute([$rawId]);
        if ($s->fetch()) return $rawId;
    }
    $row = $db->query("SELECT id FROM users WHERE role='Admin' ORDER BY id ASC LIMIT 1")->fetch();
    return $row ? (int)$row['id'] : null;
}