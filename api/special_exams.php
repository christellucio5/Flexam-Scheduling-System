<?php
/**
 * special_exams.php  — API for Special Exam Registrations
 * Place this file in: /api/special_exams.php  (same folder as schedules.php)
 *
 * Actions: list | create | update | delete
 *
 * Table DDL (run once):
 * CREATE TABLE IF NOT EXISTS special_exam_registrations (
 *   id            INT AUTO_INCREMENT PRIMARY KEY,
 *   school_year   VARCHAR(20)  NOT NULL,
 *   semester      VARCHAR(30)  NOT NULL,
 *   exam_type     VARCHAR(50)  NOT NULL,
 *   student_no    VARCHAR(50)  NOT NULL,
 *   last_name     VARCHAR(100) NOT NULL,
 *   first_name    VARCHAR(100) NOT NULL,
 *   college       VARCHAR(100) NOT NULL,
 *   program       VARCHAR(100) DEFAULT '',
 *   receipt_no    VARCHAR(100) NOT NULL,
 *   reason        TEXT         NOT NULL,
 *   num_exams     TINYINT      DEFAULT 1,
 *   exam_courses  TEXT         DEFAULT '[]',  -- JSON array of {course_id, course_label, exam_type}
 *   campus        VARCHAR(100) DEFAULT '',
 *   created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
 *   updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── DB connection ────────────────────────────────────────────────────────────
$configPath = __DIR__ . '/../config/Db.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/../config/database.php';
}
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/../includes/db.php';
}
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/../db.php';
}

if (file_exists($configPath)) {
    require_once $configPath;
    try {
        $pdo = getDB();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $e->getMessage()]);
        exit;
    }
} else {
    // Fallback hardcoded credentials
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=flexam_db;charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $e->getMessage()]);
        exit;
    }
}

// ── Ensure table exists ──────────────────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS special_exam_registrations (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        school_year   VARCHAR(20)  NOT NULL,
        semester      VARCHAR(30)  NOT NULL,
        exam_type     VARCHAR(50)  NOT NULL,
        student_no    VARCHAR(50)  NOT NULL,
        last_name     VARCHAR(100) NOT NULL,
        first_name    VARCHAR(100) NOT NULL,
        college       VARCHAR(100) NOT NULL,
        program       VARCHAR(100) DEFAULT '',
        receipt_no    VARCHAR(100) NOT NULL,
        reason        TEXT         NOT NULL,
        num_exams     TINYINT      DEFAULT 1,
        exam_courses  TEXT         DEFAULT '[]',
        campus        VARCHAR(100) DEFAULT '',
        created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // Table may already exist or DDL may differ — continue
}

// ── Session / campus restriction (matches your existing pattern) ─────────────
session_start();
// User data is stored under $_SESSION['user'], not at the top level.
// Reading the wrong key caused $currentCampus to always be empty,
// which made the list action return ALL records regardless of campus,
// and prevented campus from being stamped on new records via the session.
$currentCampus = $_SESSION['user']['campus'] ?? $_SESSION['campus'] ?? '';

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

function sanitize($val) {
    return htmlspecialchars(strip_tags(trim((string)($val ?? ''))), ENT_QUOTES, 'UTF-8');
}

switch ($action) {

    // ── LIST ──────────────────────────────────────────────────────────────────
    case 'list':
        try {
            $sql = "SELECT * FROM special_exam_registrations";
            $params = [];
            if ($currentCampus) {
                // Case-insensitive campus match so records created by admin/campus_admin
                // (which may differ in casing) are always visible to Program Heads
                // whose session campus may use different capitalisation.
                $sql .= " WHERE LOWER(campus) = LOWER(:campus)";
                $params[':campus'] = $currentCampus;
            }
            $sql .= " ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            // Cast numeric fields
            foreach ($rows as &$r) {
                $r['id']        = (int)$r['id'];
                $r['num_exams'] = (int)$r['num_exams'];
            }
            unset($r);
            echo json_encode(['success' => true, 'data' => $rows]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ── CREATE ────────────────────────────────────────────────────────────────
    case 'create':
        $required = ['school_year','semester','exam_type','student_no','last_name','first_name','college','receipt_no','reason','num_exams'];
        foreach ($required as $f) {
            if (empty($input[$f])) {
                echo json_encode(['success' => false, 'message' => "Field '$f' is required."]);
                exit;
            }
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO special_exam_registrations
                (school_year, semester, exam_type, student_no, last_name, first_name,
                 college, program, receipt_no, reason, num_exams, exam_courses, campus)
                VALUES
                (:sy, :sem, :et, :sno, :ln, :fn, :col, :prog, :rec, :rsn, :ne, :ec, :camp)");
            $stmt->execute([
                ':sy'   => sanitize($input['school_year']),
                ':sem'  => sanitize($input['semester']),
                ':et'   => sanitize($input['exam_type']),
                ':sno'  => sanitize($input['student_no']),
                ':ln'   => sanitize($input['last_name']),
                ':fn'   => sanitize($input['first_name']),
                ':col'  => sanitize($input['college']),
                ':prog' => sanitize($input['program'] ?? ''),
                ':rec'  => sanitize($input['receipt_no']),
                ':rsn'  => sanitize($input['reason']),
                ':ne'   => (int)($input['num_exams'] ?? 1),
                ':ec'   => $input['exam_courses'] ?? '[]',
                ':camp' => $currentCampus ?: sanitize($input['campus'] ?? ''),
            ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Registration created.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ── UPDATE ────────────────────────────────────────────────────────────────
    case 'update':
        if (empty($input['id'])) { echo json_encode(['success'=>false,'message'=>'ID required.']); exit; }
        try {
            $stmt = $pdo->prepare("UPDATE special_exam_registrations SET
                school_year   = :sy,
                semester      = :sem,
                exam_type     = :et,
                student_no    = :sno,
                last_name     = :ln,
                first_name    = :fn,
                college       = :col,
                program       = :prog,
                receipt_no    = :rec,
                reason        = :rsn,
                num_exams     = :ne,
                exam_courses  = :ec
                WHERE id      = :id" .
                ($currentCampus ? " AND LOWER(campus) = LOWER(:campus)" : ""));
            $params = [
                ':sy'   => sanitize($input['school_year']  ?? ''),
                ':sem'  => sanitize($input['semester']     ?? ''),
                ':et'   => sanitize($input['exam_type']    ?? ''),
                ':sno'  => sanitize($input['student_no']   ?? ''),
                ':ln'   => sanitize($input['last_name']    ?? ''),
                ':fn'   => sanitize($input['first_name']   ?? ''),
                ':col'  => sanitize($input['college']      ?? ''),
                ':prog' => sanitize($input['program']      ?? ''),
                ':rec'  => sanitize($input['receipt_no']   ?? ''),
                ':rsn'  => sanitize($input['reason']       ?? ''),
                ':ne'   => (int)($input['num_exams']       ?? 1),
                ':ec'   => $input['exam_courses']          ?? '[]',
                ':id'   => (int)$input['id'],
            ];
            if ($currentCampus) $params[':campus'] = $currentCampus;
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Registration updated.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ── DELETE ────────────────────────────────────────────────────────────────
    case 'delete':
        if (empty($input['id'])) { echo json_encode(['success'=>false,'message'=>'ID required.']); exit; }
        try {
            $sql = "DELETE FROM special_exam_registrations WHERE id = :id" .
                   ($currentCampus ? " AND LOWER(campus) = LOWER(:campus)" : "");
            $stmt = $pdo->prepare($sql);
            $params = [':id' => (int)$input['id']];
            if ($currentCampus) $params[':campus'] = $currentCampus;
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Registration deleted.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}