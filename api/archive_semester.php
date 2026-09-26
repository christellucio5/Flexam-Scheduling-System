<?php

// ── 1. Suppress ALL PHP error display — errors must never bleed into JSON ─────
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// ── 2. Buffer output so any stray bytes don't corrupt JSON ────────────────────
ob_start();

// ── 3. Shutdown handler catches fatal errors ──────────────────────────────────
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        ob_end_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $err['message'] . ' in ' . basename($err['file']) . ':' . $err['line'],
        ]);
    }
});

session_start();

// ── Helper: send clean JSON and exit ─────────────────────────────────────────
function sendJson(array $payload, int $status = 200): void {
    ob_end_clean();
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($status);
    }
    echo json_encode($payload);
    exit;
}

// ── Helper: fast COUNT query ──────────────────────────────────────────────────
function countQuery(PDO $pdo, string $table, string $extraWhere, array $extraParams): int {
    $sql  = "SELECT COUNT(*) FROM `{$table}` WHERE 1=1 {$extraWhere}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($extraParams);
    return (int) $stmt->fetchColumn();
}

// ── Helper: resolve effective campus scope ────────────────────────────────────
function effectiveCampus(string $sessionCampus, ?string $paramCampus): ?string {
    if ($sessionCampus !== '') {
        return $sessionCampus; // campus admin locked to their campus
    }
    return ($paramCampus !== null && $paramCampus !== '') ? $paramCampus : null;
}

// ── Auth guard ────────────────────────────────────────────────────────────────
if (empty($_SESSION['user'])) {
    sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
}

$sessionUser   = $_SESSION['user'];
$sessionRole   = $sessionUser['role']      ?? '';
$sessionCampus = $sessionUser['campus']    ?? '';
$actorName     = $sessionUser['full_name'] ?? 'Unknown';

if (!in_array($sessionRole, ['Admin', 'Super Admin', 'admin', 'super_admin'], true)) {
    sendJson(['success' => false, 'message' => 'Forbidden'], 403);
}

// ── DB connection ─────────────────────────────────────────────────────────────
// FIX 1: Correct filename (Db.php, capital D) + call getDB() factory.
//        The file never sets a global $pdo — only getDB() returns the connection.
require_once __DIR__ . '/../config/Db.php';
$pdo = getDB();

// ── Read request params ───────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$body = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw !== false && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $body = $decoded;
        }
    }
}

$paramCampus       = trim($_GET['campus']        ?? $body['campus']        ?? '');
$paramAcademicYear = trim($_GET['academic_year'] ?? $body['academic_year'] ?? '');
$paramSemester     = trim($_GET['semester']      ?? $body['semester']      ?? '');

$campusScope = effectiveCampus($sessionCampus, $paramCampus ?: null);

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: list — return archive_history rows
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    try {
        $where  = [];
        $params = [];

        if ($campusScope !== null) {
            $where[]           = 'campus = :campus';
            $params[':campus'] = $campusScope;
        }
        if ($paramAcademicYear !== '') {
            $where[]       = 'academic_year = :ay';
            $params[':ay'] = $paramAcademicYear;
        }
        if ($paramSemester !== '') {
            $where[]        = 'semester = :sem';
            $params[':sem'] = $paramSemester;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("
            SELECT id, academic_year, semester, campus,
                   schedule_count, course_count, feedback_count,
                   archived_by, archived_at
            FROM   archive_history
            {$whereClause}
            ORDER  BY archived_at DESC
        ");
        $stmt->execute($params);

        sendJson(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: archive — copy live data to archive tables, then delete live records.
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'archive' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($paramAcademicYear === '' || $paramSemester === '') {
        sendJson(['success' => false, 'message' => 'academic_year and semester are required.'], 422);
    }

    try {
        $pdo->beginTransaction();

        // Campus filter for tables that HAVE a campus column (schedules, courses).
        // feedbacks has NO campus column and is handled separately.
        $campusWhere  = '';
        $campusParams = [];
        if ($campusScope !== null) {
            $campusWhere             = 'AND campus = :campus';
            $campusParams[':campus'] = $campusScope;
        }

        // ── Duplicate guard ───────────────────────────────────────────────────
        // Prevent re-archiving the same academic_year + semester + campus combo.
        $dupCheckSql    = "SELECT COUNT(*) FROM archive_history WHERE academic_year = :ay AND semester = :sem AND campus = :campus";
        $dupCheckStmt   = $pdo->prepare($dupCheckSql);
        $dupCheckStmt->execute([
            ':ay'     => $paramAcademicYear,
            ':sem'    => $paramSemester,
            ':campus' => $campusScope ?? 'All Campuses',
        ]);
        if ((int) $dupCheckStmt->fetchColumn() > 0) {
            $pdo->rollBack();
            sendJson([
                'success' => false,
                'message' => "This semester ({$paramAcademicYear} – {$paramSemester}) has already been archived. Reset cannot be performed again on data that was already reset.",
            ], 409);
        }

        // FIX 2: table is 'schedules', not 'exam_schedules'.
        // FIX 4: feedbacks has no campus column — pass empty filter for its COUNT.
        $countSchedules = countQuery($pdo, 'schedules', $campusWhere, $campusParams);
        $countCourses   = countQuery($pdo, 'courses',   $campusWhere, $campusParams);
        $countFeedbacks = countQuery($pdo, 'feedbacks', '', []);

        // Count special exam registrations (has campus column)
        $countSpecialExams = 0;
        try {
            $countSpecialExams = countQuery($pdo, 'special_exam_registrations', $campusWhere, $campusParams);
        } catch (Exception $e) { $countSpecialExams = 0; }

        // ── Copy schedules → archived_schedules ───────────────────────────
        // FIX 2 & 3: source table is 'schedules'; denormalized columns used directly.
        // INSERT IGNORE skips any row whose (orig_id, academic_year) already exists.
        $pdo->prepare("
            INSERT IGNORE INTO archived_schedules
                (orig_id, course_code, course_name, college, program, year_level, section,
                 exam_type, orig_semester, academic_year, exam_date, time_slot, duration,
                 room_name, proctor_name, orig_campus, status, archived_at)
            SELECT
                es.id,
                es.course_code,
                es.course_name,
                es.college,
                es.program,
                es.year_level,
                es.section,
                es.exam_type,
                es.semester,
                :ay,
                es.exam_date,
                es.time_slot,
                es.duration,
                es.room_name,
                es.proctor_name,
                es.campus,
                es.status,
                NOW()
            FROM schedules es
            WHERE 1=1 {$campusWhere}
        ")->execute(array_merge([':ay' => $paramAcademicYear], $campusParams));

        // ── Copy courses → archived_courses ───────────────────────────────
        $pdo->prepare("
            INSERT IGNORE INTO archived_courses
                (orig_id, course_code, course_name, college, program, year_level,
                 orig_semester, orig_campus, academic_year, archived_at)
            SELECT id, course_code, course_name, college, program, year_level,
                   semester, campus, :ay, NOW()
            FROM courses
            WHERE 1=1 {$campusWhere}
        ")->execute(array_merge([':ay' => $paramAcademicYear], $campusParams));

        // ── Copy feedbacks → archived_feedbacks ───────────────────────────
        // FIX 4 & 5: feedbacks has no campus/rating/orig_campus columns.
        // Only columns that exist in both tables are mapped.
        $pdo->prepare("
            INSERT IGNORE INTO archived_feedbacks
                (orig_id, student_number, student_name, college, program, subject,
                 category, message, academic_year, created_at, archived_at)
            SELECT id, student_number, student_name, college, program, subject,
                   category, message, :ay, created_at, NOW()
            FROM feedbacks
        ")->execute([':ay' => $paramAcademicYear]);

        // ── Copy special_exams → archived_special_exams ───────────────────
        // Inside main transaction: if this fails the whole archive rolls back
        $pdo->prepare("
            INSERT IGNORE INTO archived_special_exams
                (orig_id, student_no, last_name, first_name, college, program,
                 campus, school_year, semester, exam_type, receipt_no,
                 num_exams, reason, exam_courses, orig_campus,
                 academic_year, orig_semester, archived_at)
            SELECT id, student_no, last_name, first_name, college, program,
                   campus, school_year, semester, exam_type, receipt_no,
                   num_exams, reason, exam_courses, campus,
                   :ay, semester, NOW()
            FROM special_exam_registrations
            WHERE 1=1 {$campusWhere}
        ")->execute(array_merge([':ay' => $paramAcademicYear], $campusParams));

        // ── Delete live records ───────────────────────────────────────────
        // FIX 2: 'schedules' not 'exam_schedules'
        // FIX 4: feedbacks has no campus column — plain delete, no WHERE campus
        $pdo->prepare("DELETE FROM schedules WHERE 1=1 {$campusWhere}")->execute($campusParams);
        $pdo->prepare("DELETE FROM courses   WHERE 1=1 {$campusWhere}")->execute($campusParams);
        $pdo->prepare("DELETE FROM feedbacks")->execute();
        $pdo->prepare("DELETE FROM special_exam_registrations WHERE 1=1 {$campusWhere}")->execute($campusParams);

        // ── Record to archive_history ─────────────────────────────────────
        $pdo->prepare("
            INSERT INTO archive_history
                (academic_year, semester, campus, schedule_count, course_count,
                 feedback_count, archived_by, archived_at)
            VALUES (:ay, :sem, :campus, :sc, :cc, :fc, :by, NOW())
        ")->execute([
            ':ay'     => $paramAcademicYear,
            ':sem'    => $paramSemester,
            ':campus' => $campusScope ?? 'All Campuses',
            ':sc'     => $countSchedules,
            ':cc'     => $countCourses,
            ':fc'     => $countFeedbacks,
            ':by'     => $actorName,
        ]);

        $pdo->commit();

        sendJson([
            'success' => true,
            'data'    => [
                'scheduleCount'    => $countSchedules,
                'courseCount'      => $countCourses,
                'feedbackCount'    => $countFeedbacks,
                'specialExamCount' => $countSpecialExams,
            ],
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: download — return archived records as JSON (JS/SheetJS builds the Excel)
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'download') {
    try {
        // Build WHERE filters for campus-scoped archive tables
        $where  = ['1=1'];
        $params = [];

        if ($campusScope !== null) {
            $where[]           = 'orig_campus = :campus';
            $params[':campus'] = $campusScope;
        }
        if ($paramAcademicYear !== '') {
            $where[]       = 'academic_year = :ay';
            $params[':ay'] = $paramAcademicYear;
        }
        if ($paramSemester !== '') {
            $where[]        = 'orig_semester = :sem';
            $params[':sem'] = $paramSemester;
        }

        $whereClause = implode(' AND ', $where);

        // Feedbacks only filter by academic_year (no campus column)
        $fbWhere  = ['1=1'];
        $fbParams = [];
        if ($paramAcademicYear !== '') {
            $fbWhere[]       = 'academic_year = :ay';
            $fbParams[':ay'] = $paramAcademicYear;
        }

        // Special exams filter (has campus column)
        $spWhere  = ['1=1'];
        $spParams = [];
        if ($campusScope !== null) {
            $spWhere[]           = 'orig_campus = :campus';
            $spParams[':campus'] = $campusScope;
        }
        if ($paramAcademicYear !== '') {
            $spWhere[]       = 'academic_year = :ay';
            $spParams[':ay'] = $paramAcademicYear;
        }
        if ($paramSemester !== '') {
            $spWhere[]        = 'orig_semester = :sem';
            $spParams[':sem'] = $paramSemester;
        }

        // Fetch archived schedules
        $stmt = $pdo->prepare("SELECT * FROM archived_schedules WHERE {$whereClause}");
        $stmt->execute($params);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch archived courses
        $stmt = $pdo->prepare("SELECT * FROM archived_courses WHERE {$whereClause}");
        $stmt->execute($params);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch archived feedbacks
        $stmt = $pdo->prepare("SELECT * FROM archived_feedbacks WHERE " . implode(' AND ', $fbWhere));
        $stmt->execute($fbParams);
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch archived special exam registrations (if table exists)
        $specialExams = [];
        try {
            $stmt = $pdo->prepare("SELECT * FROM archived_special_exams WHERE " . implode(' AND ', $spWhere));
            $stmt->execute($spParams);
            $specialExams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Table may not exist yet — return empty array gracefully
            $specialExams = [];
        }

        // Return all data as JSON — the JS frontend (SheetJS) builds the Excel file
        sendJson([
            'success' => true,
            'data'    => [
                'schedules'    => $schedules,
                'courses'      => $courses,
                'feedbacks'    => $feedbacks,
                'specialExams' => $specialExams,
            ],
        ]);

    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: retrieve — restore archived records back into live tables
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'retrieve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $archiveId = (int) ($body['archive_id'] ?? 0);
    if ($archiveId <= 0) {
        sendJson(['success' => false, 'message' => 'archive_id is required.'], 422);
    }

    try {
        // Load the archive_history row to verify scope and get metadata
        $histStmt = $pdo->prepare("SELECT * FROM archive_history WHERE id = :id");
        $histStmt->execute([':id' => $archiveId]);
        $hist = $histStmt->fetch(PDO::FETCH_ASSOC);

        if (!$hist) {
            sendJson(['success' => false, 'message' => 'Archive record not found.'], 404);
        }

        // Campus admins can only retrieve their own campus
        if ($campusScope !== null && $hist['campus'] !== $campusScope) {
            sendJson(['success' => false, 'message' => 'Forbidden: cannot retrieve another campus\'s archive.'], 403);
        }

        $ay  = $hist['academic_year'];
        $sem = $hist['semester'];
        $cap = $hist['campus']; // the campus stored in this archive row

        // Build campus filter for archived tables (orig_campus)
        $campusWhere  = '';
        $campusParams = [];
        if ($campusScope !== null) {
            $campusWhere             = 'AND orig_campus = :campus';
            $campusParams[':campus'] = $campusScope;
        }

        $pdo->beginTransaction();

        // ── Restore archived_courses → courses ────────────────────────────
        // (Fixed: Restoring courses FIRST prevents foreign key issues with schedules)
        $pdo->prepare("
            INSERT INTO courses
                (course_code, course_name, college, program, year_level, semester, campus)
            SELECT
                course_code, course_name, college, program, year_level, orig_semester, orig_campus
            FROM archived_courses
            WHERE academic_year = :ay AND orig_semester = :sem {$campusWhere}
        ")->execute(array_merge([':ay' => $ay, ':sem' => $sem], $campusParams));

        // ── Restore archived_schedules → schedules ────────────────────────
        // (Fixed: Removed 'academic_year' column to match your database schema)
        $pdo->prepare("
            INSERT INTO schedules
                (course_code, course_name, college, program, year_level, section,
                 exam_type, semester, exam_date, time_slot, duration,
                 room_name, proctor_name, campus, status)
            SELECT
                course_code, course_name, college, program, year_level, section,
                exam_type, orig_semester, exam_date, time_slot, duration,
                room_name, proctor_name, orig_campus, status
            FROM archived_schedules
            WHERE academic_year = :ay AND orig_semester = :sem {$campusWhere}
        ")->execute(array_merge([':ay' => $ay, ':sem' => $sem], $campusParams));


        // ── Restore archived_feedbacks → feedbacks ────────────────────────
        // feedbacks has no campus column; restore by academic_year only
        $pdo->prepare("
            INSERT INTO feedbacks
                (student_number, student_name, college, program, subject,
                 category, message, created_at)
            SELECT student_number, student_name, college, program, subject,
                   category, message, created_at
            FROM archived_feedbacks
            WHERE academic_year = :ay
        ")->execute([':ay' => $ay]);

        // ── Restore archived_special_exams → special_exams ────────────────
        try {
            $pdo->prepare("
                INSERT INTO special_exam_registrations
                    (student_no, last_name, first_name, college, program,
                     campus, school_year, semester, exam_type, receipt_no,
                     num_exams, reason, exam_courses)
                SELECT student_no, last_name, first_name, college, program,
                       orig_campus, school_year, orig_semester, exam_type, receipt_no,
                       num_exams, reason, exam_courses
                FROM archived_special_exams
                WHERE academic_year = :ay AND orig_semester = :sem {$campusWhere}
            ")->execute(array_merge([':ay' => $ay, ':sem' => $sem], $campusParams));
        } catch (Exception $e) { /* archived_special_exams table may not exist */ }

        // ── Delete from archive tables ────────────────────────────────────
        $pdo->prepare("
            DELETE FROM archived_schedules
            WHERE academic_year = :ay AND orig_semester = :sem {$campusWhere}
        ")->execute(array_merge([':ay' => $ay, ':sem' => $sem], $campusParams));

        $pdo->prepare("
            DELETE FROM archived_courses
            WHERE academic_year = :ay AND orig_semester = :sem {$campusWhere}
        ")->execute(array_merge([':ay' => $ay, ':sem' => $sem], $campusParams));

        $pdo->prepare("
            DELETE FROM archived_feedbacks WHERE academic_year = :ay
        ")->execute([':ay' => $ay]);

        // ── Delete archived special exams ─────────────────────────────────
        try {
            $pdo->prepare("
                DELETE FROM archived_special_exams
                WHERE academic_year = :ay AND orig_semester = :sem {$campusWhere}
            ")->execute(array_merge([':ay' => $ay, ':sem' => $sem], $campusParams));
        } catch (Exception $e) { /* table may not exist */ }

        // ── Remove from archive_history ───────────────────────────────────
        $pdo->prepare("DELETE FROM archive_history WHERE id = :id")->execute([':id' => $archiveId]);

        $pdo->commit();

        sendJson([
            'success' => true,
            'message' => "Semester {$ay} {$sem} has been restored to the live system.",
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ── Unknown / missing action ──────────────────────────────────────────────────
sendJson(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)], 400);