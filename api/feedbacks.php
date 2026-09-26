<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db     = getDB();

// ── SELF-HEALING: ensure campus and exam_difficulty columns exist ─────────────
try {
    $existingCols = $db->query("SHOW COLUMNS FROM feedbacks")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('campus', $existingCols)) {
        $db->exec("ALTER TABLE feedbacks ADD COLUMN campus VARCHAR(100) NULL DEFAULT NULL AFTER student_name");
    }
    if (!in_array('exam_difficulty', $existingCols)) {
        $db->exec("ALTER TABLE feedbacks ADD COLUMN exam_difficulty VARCHAR(50) NULL DEFAULT NULL AFTER category");
    }
} catch (Exception $e) { /* silently ignore — column likely already exists */ }

// ── CREATE (public submit — no auth required) ─────────────────
if ($action === 'create') {
    $body           = getBody();
    $studentNumber  = clean($body['student_number']  ?? '');
    $studentName    = clean($body['student_name']    ?? $body['name'] ?? '');
    $campus         = clean($body['campus']          ?? '');
    $college        = clean($body['college']         ?? '');
    $program        = clean($body['program']         ?? '');
    $category       = clean($body['category']        ?? '');
    $examDifficulty = clean($body['exam_difficulty'] ?? '');
    $subject        = clean($body['subject']         ?? '');
    $message        = clean($body['message']         ?? '');

    if (!$message) jsonResponse(false, 'Message is required.');

    try {
        $db->prepare(
            'INSERT INTO feedbacks
                 (student_number, student_name, campus, college, program, category, exam_difficulty, subject, message, is_read)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)'
        )->execute([
            $studentNumber  ?: null,
            $studentName    ?: null,
            $campus         ?: null,
            $college        ?: null,
            $program        ?: null,
            $category       ?: null,
            $examDifficulty ?: null,
            $subject        ?: null,
            $message,
        ]);
    } catch (Exception $e) {
        jsonResponse(false, 'Failed to save feedback: ' . $e->getMessage());
    }

    jsonResponse(true, 'Feedback submitted successfully.');
}

// All actions below require authentication
requireAuth();
$role = $_SESSION['user']['role'];

// ── DEBUG ──────────────────────────────────────────────────────
if ($action === 'debug') {
    if ($role !== 'Program Head') jsonResponse(false, 'Program Head only');
    $sessionCollege = $_SESSION['user']['college'] ?? '';
    $sessionCampus  = $_SESSION['user']['campus']  ?? '';
    $allFeedbacks   = $db->query('SELECT id, college, program, subject FROM feedbacks ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    $allColleges    = $db->query('SELECT c.id, c.code, c.name, c.campus, cp.program FROM colleges c LEFT JOIN college_programs cp ON cp.college_id = c.id ORDER BY c.code')->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(true, 'debug', [
        'session_college' => $sessionCollege,
        'session_campus'  => $sessionCampus,
        'feedbacks'       => $allFeedbacks,
        'colleges_in_db'  => $allColleges,
    ]);
}


function getHeadMatchValues($db, $sessionCollege, $sessionProgram = '') {
    $seeds = [];
    if ($sessionCollege !== '') $seeds[] = strtoupper(trim($sessionCollege));
    if ($sessionProgram  !== '') $seeds[] = strtoupper(trim($sessionProgram));
    if (empty($seeds)) return [];

    $values = $seeds;
    $collegeIds = [];

    foreach ($seeds as $seed) {
        $stmt = $db->prepare(
            'SELECT c.id, c.code FROM colleges c
             WHERE UPPER(TRIM(c.code)) = ? OR UPPER(TRIM(c.name)) = ?'
        );
        $stmt->execute([$seed, $seed]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $collegeIds[] = (int)$row['id'];
            $values[] = strtoupper(trim($row['code']));
        }

        $stmt2 = $db->prepare(
            "SELECT DISTINCT c.id, c.code FROM colleges c
             JOIN college_programs cp ON cp.college_id = c.id
             WHERE UPPER(TRIM(SUBSTRING_INDEX(cp.program, '=', 1))) = ?"
        );
        $stmt2->execute([$seed]);
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $collegeIds[] = (int)$row['id'];
            $values[] = strtoupper(trim($row['code']));
        }

        $stmt3 = $db->prepare(
            "SELECT c.id, c.code FROM colleges c
             WHERE UPPER(TRIM(c.code)) LIKE ?"
        );
        $stmt3->execute(['%' . $seed . '%']);
        foreach ($stmt3->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $collegeIds[] = (int)$row['id'];
            $values[] = strtoupper(trim($row['code']));
        }

        $stmt4 = $db->prepare(
            "SELECT c.id, c.code FROM colleges c
             WHERE UPPER(c.name) LIKE ? OR UPPER(c.code) LIKE ?"
        );
        $stmt4->execute(['%' . $seed . '%', '%' . $seed . '%']);
        foreach ($stmt4->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $collegeIds[] = (int)$row['id'];
            $values[] = strtoupper(trim($row['code']));
        }
    }

    $collegeIds = array_unique($collegeIds);
    if (!empty($collegeIds)) {
        $ph   = implode(',', array_fill(0, count($collegeIds), '?'));
        $pStmt = $db->prepare("SELECT program FROM college_programs WHERE college_id IN ($ph)");
        $pStmt->execute($collegeIds);
        foreach ($pStmt->fetchAll(PDO::FETCH_COLUMN) as $prog) {
            $pos     = strpos($prog, '=');
            $acronym = strtoupper(trim($pos !== false ? substr($prog, 0, $pos) : $prog));
            if ($acronym !== '') $values[] = $acronym;
        }
    }

    return array_values(array_unique($values));
}

// ── LIST ──────────────────────────────────────────────────────
if ($action === 'list') {
    if ($role === 'Admin') {
        $adminCampus = trim($_SESSION['user']['campus'] ?? '');
        if ($adminCampus) {
            $stmt = $db->prepare(
                "SELECT * FROM feedbacks
                 WHERE LOWER(TRIM(campus)) = LOWER(?)
                    OR campus IS NULL OR campus = ''
                 ORDER BY created_at DESC"
            );
            $stmt->execute([$adminCampus]);
        } else {
            $stmt = $db->query('SELECT * FROM feedbacks ORDER BY created_at DESC');
        }
    } elseif ($role === 'Program Head') {
        $matchValues = getHeadMatchValues($db, $_SESSION['user']['college'] ?? '', $_SESSION['user']['program'] ?? '');
        if (empty($matchValues)) jsonResponse(true, 'OK', []);
        $ph   = implode(',', array_fill(0, count($matchValues), '?'));
        $stmt = $db->prepare(
            "SELECT * FROM feedbacks
             WHERE UPPER(TRIM(college)) IN ($ph)
                OR UPPER(TRIM(program))  IN ($ph)
             ORDER BY created_at DESC"
        );
        $stmt->execute(array_merge($matchValues, $matchValues));
    } else {
        jsonResponse(false, 'Forbidden', [], 403);
    }
    jsonResponse(true, 'OK', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// ── MARK READ ─────────────────────────────────────────────────
if ($action === 'mark_read') {
    if (!in_array($role, ['Admin', 'Program Head'])) jsonResponse(false, 'Forbidden', [], 403);
    $body = getBody();
    $id   = (int)($body['id'] ?? 0);
    if ($role === 'Admin') {
        if (!$id) { $db->query('UPDATE feedbacks SET is_read = 1'); }
        else { $db->prepare('UPDATE feedbacks SET is_read = 1 WHERE id = ?')->execute([$id]); }
    } else {
        $matchValues = getHeadMatchValues($db, $_SESSION['user']['college'] ?? '', $_SESSION['user']['program'] ?? '');
        if (empty($matchValues)) jsonResponse(true, 'Nothing to mark.');
        $ph = implode(',', array_fill(0, count($matchValues), '?'));
        if (!$id) {
            $db->prepare("UPDATE feedbacks SET is_read = 1 WHERE UPPER(TRIM(college)) IN ($ph) OR UPPER(TRIM(program)) IN ($ph)")
               ->execute(array_merge($matchValues, $matchValues));
        } else {
            $db->prepare("UPDATE feedbacks SET is_read = 1 WHERE id = ? AND (UPPER(TRIM(college)) IN ($ph) OR UPPER(TRIM(program)) IN ($ph))")
               ->execute(array_merge([$id], $matchValues, $matchValues));
        }
    }
    jsonResponse(true, 'Marked as read.');
}

// ── DELETE ────────────────────────────────────────────────────
if ($action === 'delete') {
    if (!in_array($role, ['Admin', 'Program Head'])) jsonResponse(false, 'Forbidden', [], 403);
    $body = getBody();
    $id   = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(false, 'Feedback ID is required.');
    if ($role === 'Admin') {
        $db->prepare('DELETE FROM feedbacks WHERE id = ?')->execute([$id]);
    } else {
        $matchValues = getHeadMatchValues($db, $_SESSION['user']['college'] ?? '', $_SESSION['user']['program'] ?? '');
        if (empty($matchValues)) jsonResponse(false, 'Not authorized.');
        $ph = implode(',', array_fill(0, count($matchValues), '?'));
        $db->prepare("DELETE FROM feedbacks WHERE id = ? AND (UPPER(TRIM(college)) IN ($ph) OR UPPER(TRIM(program)) IN ($ph))")
           ->execute(array_merge([$id], $matchValues, $matchValues));
    }
    jsonResponse(true, 'Feedback deleted.');
}

jsonResponse(false, 'Unknown action.', [], 400);