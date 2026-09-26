<?php
// ============================================================
// api/courses.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db     = getDB();

// list & filter are public (guests need them for Special Exam registration).
// Write actions are guarded by requireAuth() further below.
$isAuthenticated = !empty($_SESSION['user']);
$role = $isAuthenticated ? ($_SESSION['user']['role'] ?? '') : 'guest';

// ── LIST ──────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    $adminCampus = $isAuthenticated ? trim($_SESSION['user']['campus'] ?? '') : '';
    if ($adminCampus) {
        // Campus Admin / Program Head sees only their campus courses
        $stmt = $db->prepare(
            "SELECT * FROM courses
             WHERE LOWER(TRIM(campus)) = LOWER(?)
                OR campus IS NULL OR campus = ''
             ORDER BY course_code ASC"
        );
        $stmt->execute([$adminCampus]);
    } else {
        // Super Admin sees all
        $stmt = $db->query('SELECT * FROM courses ORDER BY course_code ASC');
    }
    jsonResponse(true, 'OK', $stmt->fetchAll());
}

// ── FILTER (for Special Exam modal — returns courses matching SY+Semester+College+Program+Campus) ──
if ($action === 'filter') {
    $school_year = trim($_GET['school_year'] ?? '');
    $semester    = trim($_GET['semester']    ?? '');
    $college     = trim($_GET['college']     ?? '');
    $program     = trim($_GET['program']     ?? '');
    $campus      = trim($_GET['campus']      ?? '');

    // At minimum, semester must be provided to filter meaningfully
    if (!$semester) {
        jsonResponse(false, 'Semester is required for filtering.');
    }

    $conditions = [];
    $params     = [];

    // school_year: filter only if the column exists AND a value was passed
    if ($school_year) {
        $conditions[] = "(school_year IS NULL OR school_year = '' OR LOWER(TRIM(school_year)) = LOWER(?))";
        $params[]     = $school_year;
    }

    // semester: strict match — courses with no semester stored are included
    // (they are "generic" courses that belong to any semester)
    $conditions[] = "(semester IS NULL OR semester = '' OR LOWER(TRIM(semester)) = LOWER(?))";
    $params[]     = $semester;

    if ($college) {
        $conditions[] = "LOWER(TRIM(college)) = LOWER(?)";
        $params[]     = $college;
    }

    if ($program) {
        $conditions[] = "(program IS NULL OR program = '' OR LOWER(TRIM(program)) = LOWER(?))";
        $params[]     = $program;
    }

    // Campus: respect campus admin scope first, then the passed value
    $adminCampus = $isAuthenticated ? trim($_SESSION['user']['campus'] ?? '') : '';
    $effectiveCampus = $adminCampus ?: $campus;
    if ($effectiveCampus) {
        $conditions[] = "(campus IS NULL OR campus = '' OR LOWER(TRIM(campus)) = LOWER(?))";
        $params[]     = $effectiveCampus;
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sql   = "SELECT * FROM courses {$where} ORDER BY course_code ASC";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(true, 'OK', $stmt->fetchAll());
    } catch (PDOException $e) {
        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

// ── Auth guard for write actions ──────────────────────────────────────────────
requireAuth();
if ($role !== 'Admin' && $role !== 'Program Head') jsonResponse(false, 'Forbidden', [], 403);
$body = getBody();

// ── CREATE ────────────────────────────────────────────────────────────────────
if ($action === 'create') {
    $courseCode  = strtoupper(clean($body['courseCode'] ?? $body['course_code'] ?? ''));
    $courseName  = clean($body['courseName'] ?? $body['course_name'] ?? '');
    $college     = clean($body['college']    ?? '');
    // Normalize program: uppercase + collapse all whitespace so "BS PSYCH" === "BSPSYCH"
    $program     = strtoupper(preg_replace('/\s+/', '', clean($body['program'] ?? '')));
    $yearLevel   = clean($body['yearLevel']  ?? $body['year_level']  ?? '');
    $semester    = clean($body['semester']   ?? '');
    $schoolYear  = clean($body['schoolYear'] ?? $body['school_year'] ?? '');
    $campus      = clean($body['campus']     ?? '');
    $createdBy   = safeCreatedBy();

    if (!$courseCode || !$courseName) {
        jsonResponse(false, 'Course code and name are required.');
    }
    if (preg_match('/\s/', $courseCode)) {
        jsonResponse(false, "Course code '{$courseCode}' must not contain spaces. Use a dash or no separator (e.g. RZAL211 or RZAL-211).");
    }
    // Default college to UNKNOWN if not provided (e.g. auto-created from schedule import)
    if (!$college) $college = 'UNKNOWN';

    // Duplicate check: same code + campus + program
    $dupCheck = $db->prepare(
        'SELECT id FROM courses WHERE course_code = ? AND campus = ? AND COALESCE(program,\'\') = ?'
    );
    $dupCheck->execute([$courseCode, $campus ?: '', $program ?: '']);
    if ($dupCheck->fetch()) {
        jsonResponse(false, "Course code '{$courseCode}' already exists in the {$campus} campus under the {$program} program.");
    }

    try {
        // file_hash is NULL for manually-created courses (only set during bulk import)
        $stmt = $db->prepare(
            'INSERT INTO courses (course_code, course_name, college, program, year_level, semester, school_year, campus, created_by, file_hash)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)'
        );
        $stmt->execute([
            $courseCode, $courseName, $college,
            $program    ?: null, $yearLevel  ?: null,
            $semester   ?: null, $schoolYear ?: null,
            $campus     ?: null, $createdBy,
        ]);
        $id = (int)$db->lastInsertId();
        $s  = $db->prepare('SELECT * FROM courses WHERE id = ?');
        $s->execute([$id]);
        jsonResponse(true, 'Course created successfully.', $s->fetch());
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            jsonResponse(false, "Course code '{$courseCode}' already exists in this campus and program.");
        }
        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($action === 'update') {
    $id         = (int)($body['id'] ?? 0);
    $courseCode = strtoupper(clean($body['courseCode'] ?? $body['course_code'] ?? ''));
    $courseName = clean($body['courseName'] ?? $body['course_name'] ?? '');
    $college    = clean($body['college']    ?? '');
    // Normalize program: uppercase + collapse all whitespace so "BS PSYCH" === "BSPSYCH"
    $program    = strtoupper(preg_replace('/\s+/', '', clean($body['program'] ?? '')));
    $yearLevel  = clean($body['yearLevel']  ?? $body['year_level']  ?? '');
    $semester   = clean($body['semester']   ?? '');
    $schoolYear = clean($body['schoolYear'] ?? $body['school_year'] ?? '');
    $campus     = clean($body['campus']     ?? '');

    if (!$id || !$courseCode || !$courseName || !$college) {
        jsonResponse(false, 'ID, course code, name, and college are required.');
    }
    if (preg_match('/\s/', $courseCode)) {
        jsonResponse(false, "Course code '{$courseCode}' must not contain spaces. Use a dash or no separator (e.g. RZAL211 or RZAL-211).");
    }

    // Duplicate check excluding self
    $dupCheck = $db->prepare(
        'SELECT id FROM courses WHERE course_code = ? AND campus = ? AND COALESCE(program,\'\') = ? AND id != ?'
    );
    $dupCheck->execute([$courseCode, $campus ?: '', $program ?: '', $id]);
    if ($dupCheck->fetch()) {
        jsonResponse(false, "Course code '{$courseCode}' already exists in the {$campus} campus under the {$program} program.");
    }

    try {
        $db->prepare(
            'UPDATE courses
             SET course_code=?, course_name=?, college=?, program=?,
                 year_level=?, semester=?, school_year=?, campus=?, updated_at=NOW()
             WHERE id=?'
        )->execute([
            $courseCode, $courseName, $college,
            $program    ?: null, $yearLevel  ?: null,
            $semester   ?: null, $schoolYear ?: null,
            $campus     ?: null, $id,
        ]);
        $s = $db->prepare('SELECT * FROM courses WHERE id = ?');
        $s->execute([$id]);
        jsonResponse(true, 'Course updated successfully.', $s->fetch());
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            jsonResponse(false, "Course code '{$courseCode}' already exists in this campus and program.");
        }
        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(false, 'Course ID is required.');

    // Get file_hash BEFORE deleting so we can check if lock should be cleared
    $hashRow = $db->prepare('SELECT file_hash FROM courses WHERE id = ?');
    $hashRow->execute([$id]);
    $deletedHash = ($hashRow->fetch()['file_hash'] ?? null);

    // Delete the course
    $db->prepare('DELETE FROM courses WHERE id = ?')->execute([$id]);

    // Auto-clear the import lock if NO courses remain from that same file.
    // This means: all data from this file is gone → file can be re-imported cleanly.
    if ($deletedHash) {
        $remaining = $db->prepare('SELECT COUNT(*) FROM courses WHERE file_hash = ?');
        $remaining->execute([$deletedHash]);
        if ((int)$remaining->fetchColumn() === 0) {
            $db->prepare(
                'DELETE FROM import_locks
                  WHERE file_hash    = ?
                    AND import_type  = ?
                    AND is_permanent = 1'
            )->execute([$deletedHash, 'courses']);
        }
    }

    jsonResponse(true, 'Course deleted successfully.');
}

jsonResponse(false, 'Unknown action.', [], 400);