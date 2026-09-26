<?php
/**
 * FLEXAM – Announcements API
 * File: api/announcements.php
 *
 * Role visibility matrix:
 *   superadmin   – sees ALL announcements; can post to any campus or all campuses
 *   campus_admin – sees global + own-campus announcements; can post only to own campus
 *   head         – viewer only; sees global + own-campus announcements
 *   guest        – viewer only; sees global announcements only (no campus)
 *
 * Endpoints (action via GET or POST body):
 *   list    – GET  ?action=list[&campus=X]          Returns active announcements visible to caller
 *   create  – POST action=create                    Superadmin or campus admin creates announcement
 *   update  – POST action=update                    Superadmin or campus admin updates their own
 *   delete  – POST action=delete                    Superadmin or campus admin deletes their own
 *   poll    – GET  ?action=poll&since=<ms_timestamp> Returns announcements updated after timestamp
 */

session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');

// ── Helpers ───────────────────────────────────────────────────────────────────
function json_ok($data = [])       { echo json_encode(['success' => true]  + $data); exit; }
function json_err($msg, $code=400) { http_response_code($code); echo json_encode(['success'=>false,'message'=>$msg]); exit; }

// ── DB Connection ─────────────────────────────────────────────────────────────
function get_db(): PDO {
    // Use the project's shared DB helper (config/Db.php → getDB())
    $config = __DIR__ . '/../config/Db.php';
    if (file_exists($config)) require_once $config;
    return getDB();
}

// ── Auto-create table if missing ──────────────────────────────────────────────
function ensure_table(): void {
    get_db()->exec("
        CREATE TABLE IF NOT EXISTS `flexam_announcements` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `type`        ENUM('info','warning','success','urgent') NOT NULL DEFAULT 'info',
            `scope`       ENUM('all','campus') NOT NULL DEFAULT 'all',
            `campus`      VARCHAR(120)  NULL    COMMENT 'NULL / empty = all campuses',
            `target`      VARCHAR(60)   NOT NULL DEFAULT 'all' COMMENT 'all|special_exam|exam_schedule',
            `title`       VARCHAR(255)  NOT NULL,
            `body`        TEXT          NOT NULL,
            `pinned`      TINYINT(1)   NOT NULL DEFAULT 0,
            `active`      TINYINT(1)   NOT NULL DEFAULT 1,
            `created_by`  VARCHAR(120)  NOT NULL COMMENT 'full_name of poster',
            `poster_role` VARCHAR(60)   NOT NULL DEFAULT 'Admin' COMMENT 'Admin|CampusAdmin',
            `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_active`   (`active`),
            INDEX `idx_campus`   (`campus`),
            INDEX `idx_updated`  (`updated_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

try { ensure_table(); } catch (PDOException $e) { json_err('DB setup error: ' . $e->getMessage(), 500); }

// ── Auth helpers ──────────────────────────────────────────────────────────────
$user = $_SESSION['user'] ?? null;

/**
 * Superadmin = role 'Admin' or 'superadmin' with NO campus set.
 * Handles any capitalisation the session may store the role with.
 */
function is_superadmin(): bool {
    global $user;
    if (!$user) return false;
    $role      = strtolower(trim($user['role'] ?? ''));
    $hasCampus = !empty($user['campus']) && trim($user['campus']) !== '';
    return in_array($role, ['admin', 'superadmin']) && !$hasCampus;
}

/**
 * Campus admin = Admin-typed role WITH a campus set.
 * Accepts 'Admin', 'campus_admin', 'CampusAdmin' etc.
 */
function is_campus_admin(): bool {
    global $user;
    if (!$user) return false;
    $role      = strtolower(trim($user['role'] ?? ''));
    $hasCampus = !empty($user['campus']) && trim($user['campus']) !== '';
    return in_array($role, ['admin', 'campus_admin', 'campusadmin']) && $hasCampus;
}

/**
 * Viewer roles: Head, guest, student – read-only.
 */
function is_viewer(): bool {
    global $user;
    if (!$user) return true; // unauthenticated = guest viewer
    $role = strtolower(trim($user['role'] ?? ''));
    return in_array($role, ['head', 'guest', 'student', 'viewer']);
}

function is_any_admin(): bool { return is_superadmin() || is_campus_admin(); }

function current_campus(): string {
    global $user;
    return trim($user['campus'] ?? '');
}

function current_name(): string {
    global $user;
    return $user['full_name'] ?? ($user['username'] ?? 'Unknown');
}

// ── Routing ───────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

// Prefer JSON body, fall back to form-post
$body = [];
if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?: $_POST;
    // Also allow action in body
    if ($action === '' && isset($body['action'])) $action = $body['action'];
}

switch ($action) {

    // ── LIST ──────────────────────────────────────────────────────────────────
    case 'list': {
        $db     = get_db();
        $campus = trim($_GET['campus'] ?? '');   // optional campus filter

        $sql    = "SELECT * FROM flexam_announcements WHERE active = 1";
        $params = [];

        /*
         * Campus scoping rules for LIST:
         *
         * superadmin (no campus):
         *   – No filter → sees everything (all campuses + global).
         *   – campus filter supplied → sees global + that specific campus.
         *
         * campus_admin / head (has campus):
         *   – Always sees global announcements AND own-campus announcements.
         *
         * guest (no session campus):
         *   – Sees global announcements only.
         */
        // When the client explicitly requests campus='all', return ALL active
        // announcements regardless of session role. This is used by the guest
        // page so students can see campus-specific announcements from every campus.
        // This must be checked FIRST, before session-based routing, to prevent
        // a campus admin's active session from narrowing guest results.
        if ($campus === 'all') {
            // No extra WHERE clause — return everything active
        } elseif (is_superadmin()) {
            if ($campus !== '') {
                // Superadmin filtered view: global + requested campus
                $sql     .= " AND (campus IS NULL OR campus = '' OR campus = 'all' OR campus = ?)";
                $params[] = $campus;
            }
            // else: superadmin sees everything – no WHERE clause needed
        } else {
            // Determine viewer's own campus.
            // Priority: session campus → campus param sent by the JS client.
            // This ensures Head / campus_admin users always see the right scope
            // even when the session role string doesn't match 'Admin' exactly.
            $viewerCampus = current_campus();
            if ($viewerCampus === '' && $campus !== '') {
                // Fall back to the campus the JS client passed (trusted: it comes
                // from the server-rendered PHP variable in the page template).
                $viewerCampus = $campus;
            }
            if ($viewerCampus !== '') {
                // Campus admin / head: global + their campus
                $sql     .= " AND (campus IS NULL OR campus = '' OR campus = 'all' OR campus = ?)";
                $params[] = $viewerCampus;
            } else {
                // No campus known and no 'all' requested: global only
                $sql .= " AND (campus IS NULL OR campus = '' OR campus = 'all')";
            }
        }

        $sql .= " ORDER BY pinned DESC, created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['id']     = (int)$r['id'];
            $r['pinned'] = (bool)$r['pinned'];
            $r['active'] = (bool)$r['active'];
        }
        unset($r);

        json_ok(['data' => $rows, 'server_time' => round(microtime(true) * 1000)]);
    }

    // ── POLL (delta fetch since last known server_time) ───────────────────────
    case 'poll': {
        $db     = get_db();
        $since  = $_GET['since'] ?? null;   // JS ms timestamp
        $campus = trim($_GET['campus'] ?? '');

        // Poll returns ALL changes (active=0 included so clients can remove deleted items)
        $sql    = "SELECT * FROM flexam_announcements WHERE 1=1";
        $params = [];

        if ($since) {
            // Use UTC so the comparison always matches the DB's CURRENT_TIMESTAMP timezone.
            $sinceDate = gmdate('Y-m-d H:i:s', (int)($since / 1000));
            $sql     .= " AND updated_at > ?";
            $params[] = $sinceDate;
        } else {
            // No since → return only active (same as list)
            $sql .= " AND active = 1";
        }

        // Same campus scoping as LIST — campus='all' takes priority over session role
        if ($campus === 'all') {
            // Guest requesting all campuses — no filter
        } elseif (is_superadmin()) {
            if ($campus !== '') {
                $sql     .= " AND (campus IS NULL OR campus = '' OR campus = 'all' OR campus = ?)";
                $params[] = $campus;
            }
        } else {
            $viewerCampus = current_campus();
            if ($viewerCampus === '' && $campus !== '') {
                $viewerCampus = $campus; // fall back to JS-passed campus param
            }
            if ($viewerCampus !== '') {
                $sql     .= " AND (campus IS NULL OR campus = '' OR campus = 'all' OR campus = ?)";
                $params[] = $viewerCampus;
            } else {
                $sql .= " AND (campus IS NULL OR campus = '' OR campus = 'all')";
            }
        }

        $sql .= " ORDER BY pinned DESC, updated_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['id']     = (int)$r['id'];
            $r['pinned'] = (bool)$r['pinned'];
            $r['active'] = (bool)$r['active'];
        }
        unset($r);

        json_ok(['data' => $rows, 'server_time' => round(microtime(true) * 1000)]);
    }

    // ── CREATE ────────────────────────────────────────────────────────────────
    case 'create': {
        if (!is_any_admin()) json_err('Unauthorized', 403);

        $title  = trim($body['title']  ?? '');
        $bodyTx = trim($body['body']   ?? '');
        $type   = in_array($body['type']   ?? '', ['info','warning','success','urgent'])        ? $body['type']   : 'info';
        $target = in_array($body['target'] ?? '', ['all','special_exam','exam_schedule'])       ? $body['target'] : 'all';
        $pinned = !empty($body['pinned']) ? 1 : 0;

        if ($title === '') json_err('Title is required.');

        if (is_superadmin()) {
            // Superadmin can target 'all' campuses or a specific campus
            $campusVal = ($body['campus'] ?? 'all') === 'all' ? null : (trim($body['campus']) ?: null);
            $scope     = $campusVal ? 'campus' : 'all';
        } else {
            // Campus admin always posts to their own campus only
            $campusVal = current_campus();
            $scope     = 'campus';
        }

        $db = get_db();
        $db->prepare("
            INSERT INTO flexam_announcements
                (type, scope, campus, target, title, body, pinned, active, created_by, poster_role)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
        ")->execute([
            $type, $scope, $campusVal, $target, $title, $bodyTx, $pinned,
            current_name(),
            is_superadmin() ? 'Admin' : 'CampusAdmin',
        ]);

        json_ok(['id' => (int)$db->lastInsertId()]);
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────
    case 'update': {
        if (!is_any_admin()) json_err('Unauthorized', 403);

        $id     = (int)($body['id']    ?? 0);
        $title  = trim($body['title']  ?? '');
        $bodyTx = trim($body['body']   ?? '');
        $type   = in_array($body['type']   ?? '', ['info','warning','success','urgent'])   ? $body['type']   : 'info';
        $target = in_array($body['target'] ?? '', ['all','special_exam','exam_schedule'])  ? $body['target'] : 'all';
        $pinned = !empty($body['pinned']) ? 1 : 0;
        $active = isset($body['active']) ? ((bool)$body['active'] ? 1 : 0) : 1;

        if (!$id)          json_err('Missing id.');
        if ($title === '') json_err('Title is required.');

        $db   = get_db();
        $stmt = $db->prepare("SELECT * FROM flexam_announcements WHERE id = ?");
        $stmt->execute([$id]);
        $row  = $stmt->fetch();
        if (!$row) json_err('Announcement not found.', 404);

        // Campus admin: may only edit their own campus announcements
        if (is_campus_admin()) {
            if ($row['poster_role'] !== 'CampusAdmin' || trim((string)$row['campus']) !== trim(current_campus())) {
                json_err('You can only edit your own campus announcements.', 403);
            }
        }

        if (is_superadmin()) {
            $campusVal = ($body['campus'] ?? 'all') === 'all' ? null : (trim($body['campus']) ?: null);
            $scope     = $campusVal ? 'campus' : 'all';
        } else {
            $campusVal = current_campus();
            $scope     = 'campus';
        }

        $db->prepare("
            UPDATE flexam_announcements
               SET type=?, scope=?, campus=?, target=?, title=?, body=?, pinned=?, active=?
             WHERE id=?
        ")->execute([$type, $scope, $campusVal, $target, $title, $bodyTx, $pinned, $active, $id]);

        json_ok();
    }

    // ── DELETE ────────────────────────────────────────────────────────────────
    case 'delete': {
        if (!is_any_admin()) json_err('Unauthorized', 403);

        $id = (int)($body['id'] ?? 0);
        if (!$id) json_err('Missing id.');

        $db   = get_db();
        $stmt = $db->prepare("SELECT * FROM flexam_announcements WHERE id = ?");
        $stmt->execute([$id]);
        $row  = $stmt->fetch();
        if (!$row) json_err('Not found.', 404);

        // Campus admin: may only delete their own campus announcements
        if (is_campus_admin()) {
            if ($row['poster_role'] !== 'CampusAdmin' || trim((string)$row['campus']) !== trim(current_campus())) {
                json_err('You can only delete your own campus announcements.', 403);
            }
        }

        $db->prepare("DELETE FROM flexam_announcements WHERE id = ?")->execute([$id]);
        json_ok();
    }

    default:
        json_err('Unknown action.', 400);
}