<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db     = getDB();

if (!function_exists('intOrNull')) {
    function intOrNull($val): ?int {
        return ($val !== null && $val !== '' && $val !== false) ? (int)$val : null;
    }
}

// ── Multi-proctor helpers ────────────────────────────────────────────────────

/**
 * Parse the incoming proctorIds field. Accepts:
 *   - an array of ints  (from JSON body: [1,2,3])
 *   - a comma-separated string "1,2,3"
 *   - a single int (legacy)
 * Returns a clean array of positive ints.
 */
function parseProctorIds($raw): array {
    if (is_array($raw)) {
        return array_values(array_filter(array_map('intval', $raw)));
    }
    if (is_string($raw) || is_int($raw)) {
        return array_values(array_filter(array_map('intval', explode(',', (string)$raw))));
    }
    return [];
}

/**
 * Resolve proctor names from the DB for a list of IDs.
 * Returns an associative array [id => name].
 */
function resolveProctorNames(array $ids, $db): array {
    if (!$ids) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $ps = $db->prepare("SELECT id, name FROM proctors WHERE id IN ($placeholders)");
    $ps->execute($ids);
    $map = [];
    foreach ($ps->fetchAll(PDO::FETCH_ASSOC) as $row) $map[(int)$row['id']] = $row['name'];
    return $map;
}

/**
 * Ensure the schedule_proctors junction table exists.
 * MUST be called OUTSIDE any open transaction — DDL causes implicit commit in MariaDB/MySQL.
 */
function ensureJunctionTable($db): void {
    try {
        $db->query('SELECT schedule_id FROM schedule_proctors LIMIT 0');
    } catch (PDOException $e) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `schedule_proctors` (
                `schedule_id` int(10) UNSIGNED NOT NULL,
                `proctor_id`  int(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`schedule_id`, `proctor_id`),
                CONSTRAINT `sp_schedule_fk` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
                CONSTRAINT `sp_proctor_fk`  FOREIGN KEY (`proctor_id`)  REFERENCES `proctors`  (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

/**
 * Sync the schedule_proctors junction table for a given schedule.
 * Replaces all existing rows with the new set of proctor IDs.
 * Call ensureJunctionTable() BEFORE any transaction, then call this INSIDE it.
 */
function syncScheduleProctors(int $scheduleId, array $proctorIds, $db): void {
    $db->prepare('DELETE FROM schedule_proctors WHERE schedule_id = ?')->execute([$scheduleId]);
    if ($proctorIds) {
        $ins = $db->prepare('INSERT IGNORE INTO schedule_proctors (schedule_id, proctor_id) VALUES (?, ?)');
        foreach ($proctorIds as $pid) {
            $ins->execute([$scheduleId, $pid]);
        }
    }
}

/**
 * Enrich a schedule row with its full proctor list from the junction table.
 * Adds: proctor_ids (array), proctor_names (array), proctor_name (comma string, kept for BC).
 */
function enrichWithProctors(array $row, $db): array {
    $scheduleId = (int)$row['id'];

    // Try junction table first; fall back to legacy single-proctor columns.
    try {
        $ps = $db->prepare(
            'SELECT p.id, p.name FROM schedule_proctors sp
             JOIN proctors p ON p.id = sp.proctor_id
             WHERE sp.schedule_id = ?
             ORDER BY p.name'
        );
        $ps->execute([$scheduleId]);
        $rows = $ps->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $rows = []; // junction table doesn't exist yet
    }

    if ($rows) {
        $row['proctor_ids']   = array_column($rows, 'id');
        $row['proctor_names'] = array_column($rows, 'name');
        $row['proctor_name']  = implode(', ', $row['proctor_names']); // BC field
        // Keep legacy proctor_id as first proctor for compatibility
        $row['proctor_id']    = $row['proctor_ids'][0] ?? null;
    } else {
        // Legacy fallback
        $row['proctor_ids']   = $row['proctor_id'] ? [(int)$row['proctor_id']] : [];
        $row['proctor_names'] = $row['proctor_name'] ? [$row['proctor_name']] : [];
    }

    return $row;
}

/**
 * Check if any proctor in $proctorIds is already assigned to another schedule
 * at the same date+time. Returns the first conflict found or null.
 * $excludeId: schedule id to exclude (for updates).
 */
function checkProctorConflicts(array $proctorIds, string $examDate, string $timeSlot, int $excludeId, $db): ?array {
    if (!$proctorIds) return null;

    // Check junction table
    try {
        $placeholders = implode(',', array_fill(0, count($proctorIds), '?'));
        $params = array_merge($proctorIds, [$examDate, $timeSlot, $excludeId]);
        $chk = $db->prepare(
            "SELECT sp.proctor_id, p.name AS proctor_name, s.room_name
             FROM schedule_proctors sp
             JOIN schedules s ON s.id = sp.schedule_id
             JOIN proctors p  ON p.id = sp.proctor_id
             WHERE sp.proctor_id IN ($placeholders)
               AND s.exam_date  = ?
               AND s.time_slot  = ?
               AND s.status    != 'Rejected'
               AND s.id        != ?
             LIMIT 1"
        );
        $chk->execute($params);
        if ($row = $chk->fetch(PDO::FETCH_ASSOC)) return $row;
    } catch (PDOException $e) {
        // Junction table may not exist yet; fall through to legacy check
    }

    // Legacy single-proctor check (covers old records not yet in junction table)
    $placeholders = implode(',', array_fill(0, count($proctorIds), '?'));
    $params = array_merge($proctorIds, [$examDate, $timeSlot, $excludeId]);
    $chk = $db->prepare(
        "SELECT proctor_id, proctor_name, room_name
         FROM schedules
         WHERE proctor_id IN ($placeholders)
           AND exam_date  = ?
           AND time_slot  = ?
           AND status    != 'Rejected'
           AND id        != ?
         LIMIT 1"
    );
    $chk->execute($params);
    return $chk->fetch(PDO::FETCH_ASSOC) ?: null;
}
// ────────────────────────────────────────────────────────────────────────────

if ($action === 'list') {
    $isLoggedIn = isset($_SESSION['user']) && !empty($_SESSION['user']['role']);

    // Helper: fill in missing semester from the linked course record
    function enrichSemesters(array $rows, $db): array {
        $courseCache = [];
        $roomCache   = [];
        foreach ($rows as &$row) {
            // Enrich semester
            if (empty($row['semester'])) {
                $cid = $row['course_id'] ?? null;
                if ($cid) {
                    if (!isset($courseCache[$cid])) {
                        $cs = $db->prepare('SELECT semester FROM courses WHERE id = ? LIMIT 1');
                        $cs->execute([$cid]);
                        $courseCache[$cid] = ($cs->fetch(PDO::FETCH_ASSOC)['semester'] ?? '');
                    }
                    if ($courseCache[$cid]) $row['semester'] = $courseCache[$cid];
                }
            }
            // Enrich room_name with building if not already included
            $rid = $row['room_id'] ?? null;
            if ($rid) {
                if (!isset($roomCache[$rid])) {
                    $rs = $db->prepare('SELECT name, building FROM rooms WHERE id = ? LIMIT 1');
                    $rs->execute([$rid]);
                    $roomCache[$rid] = $rs->fetch(PDO::FETCH_ASSOC) ?: [];
                }
                $r = $roomCache[$rid];
                if (!empty($r['building'])) {
                    $currentName = $row['room_name'] ?? '';
                    if (!empty($currentName) && stripos($currentName, $r['building']) === false) {
                        // building first, then room name — matches UI display convention
                        $row['room_name'] = $r['building'] . ', ' . $currentName;
                    } elseif (empty($currentName) && !empty($r['name'])) {
                        $row['room_name'] = $r['building'] . ', ' . $r['name'];
                    }
                }
            }
            // Enrich with multi-proctor data
            $row = enrichWithProctors($row, $db);
        }
        unset($row);
        return $rows;
    }

    if (!$isLoggedIn) {
        $stmt = $db->query("SELECT * FROM schedules WHERE status = 'Approved' ORDER BY exam_date ASC, time_slot ASC");
        jsonResponse(true, 'OK', enrichSemesters($stmt->fetchAll(PDO::FETCH_ASSOC), $db));
        exit;
    }

    $role = $_SESSION['user']['role'];

    if ($role === 'Admin') {
        $adminCampus = trim($_SESSION['user']['campus'] ?? '');
        if ($adminCampus) {
            $stmt = $db->prepare(
                "SELECT * FROM schedules
                 WHERE LOWER(TRIM(campus)) = LOWER(?)
                    OR campus IS NULL OR campus = ''
                 ORDER BY exam_date ASC, time_slot ASC"
            );
            $stmt->execute([$adminCampus]);
        } else {
            $stmt = $db->query('SELECT * FROM schedules ORDER BY exam_date ASC, time_slot ASC');
        }
        jsonResponse(true, 'OK', enrichSemesters($stmt->fetchAll(PDO::FETCH_ASSOC), $db));
        exit;
    }

    $myCampus  = trim($_SESSION['user']['campus']  ?? '');
    $myCollege = strtoupper(trim($_SESSION['user']['college'] ?? ''));
    $myUserId  = (int)($_SESSION['user']['id'] ?? 0);

    if ($myCampus) {
        $stmt = $db->prepare(
            "SELECT * FROM schedules
             WHERE LOWER(TRIM(campus)) = LOWER(?)
                OR campus IS NULL
                OR campus = ''
                OR created_by = ?
                OR (? != '' AND UPPER(TRIM(college)) = ?)
             ORDER BY exam_date ASC, time_slot ASC"
        );
        $stmt->execute([$myCampus, $myUserId, $myCollege, $myCollege]);
    } elseif ($myUserId) {
        $stmt = $db->prepare(
            "SELECT * FROM schedules
             WHERE created_by = ?
                OR campus IS NULL
                OR campus = ''
             ORDER BY exam_date ASC, time_slot ASC"
        );
        $stmt->execute([$myUserId]);
    } else {
        $stmt = $db->query("SELECT * FROM schedules ORDER BY exam_date ASC, time_slot ASC");
    }

    jsonResponse(true, 'OK', enrichSemesters($stmt->fetchAll(PDO::FETCH_ASSOC), $db));
    exit;
}

requireAuth();
$role = $_SESSION['user']['role'];
$body = getBody();

if ($action === 'create') {
    $courseId    = intOrNull($body['courseId']   ?? $body['course_id']    ?? null);
    $courseCode  = clean($body['courseCode']     ?? $body['course_code']  ?? '');
    $courseName  = clean($body['courseName']     ?? $body['course_name']  ?? '');
    $college     = strtoupper(clean($body['college'] ?? ''));
    $program     = clean($body['program']        ?? '');
    $examType    = clean($body['examType']       ?? $body['exam_type']    ?? '');
    $yearLevel   = clean($body['yearLevel']      ?? $body['year_level']   ?? '');
    $semester    = clean($body['semester']       ?? '');
    $section     = clean($body['section']        ?? $body['section_name'] ?? $body['class_section'] ?? '');
    $examDate    = clean($body['examDate']       ?? $body['exam_date']    ?? '');
    $timeSlot    = clean($body['timeSlot']       ?? $body['time_slot']    ?? '');
    $duration    = clean($body['duration']       ?? '');
    $roomId      = intOrNull($body['roomId']     ?? $body['room_id']      ?? null);
    $roomName    = clean($body['roomName']       ?? $body['room_name']    ?? '');
    $campus      = clean($body['campus']         ?? '');
    $isOnline    = isset($body['is_online']) ? (int)(bool)$body['is_online'] : 0;
    $fileHash    = trim($body['file_hash']       ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $fileHash)) $fileHash = null;
    $createdBy   = safeCreatedBy();

    // Online exams don't need a room — but do NOT override room_name automatically;
    // let the user keep whatever room they entered (or leave it blank).
    if ($isOnline) { $roomId = null; }

    // ── Multi-proctor: accept proctorIds[] array OR legacy proctorId ─────────
    $rawProctorIds = $body['proctorIds'] ?? $body['proctor_ids'] ?? null;
    if ($rawProctorIds !== null) {
        $proctorIds = parseProctorIds($rawProctorIds);
    } else {
        // Legacy single-proctor field
        $legacyId   = intOrNull($body['proctorId'] ?? $body['proctor_id'] ?? null);
        $proctorIds = $legacyId ? [$legacyId] : [];
    }

    // Resolve names
    $nameMap     = resolveProctorNames($proctorIds, $db);
    $proctorId   = $proctorIds[0] ?? null;   // legacy FK column: first proctor
    $proctorName = implode(', ', array_values($nameMap)); // denormalized display string

    // ─────────────────────────────────────────────────────────────────────────

    $isImport = (!$roomId && !$isOnline && clean($body['status'] ?? '') === 'Pending' && isset($body['file_hash']) && trim($body['file_hash'] ?? '') !== '');

    if ($role === 'Admin' && !$isImport) {
        $rawStatus = clean($body['status'] ?? 'Approved');
        $status    = in_array($rawStatus, ['Pending', 'Approved', 'Rejected']) ? $rawStatus : 'Approved';
    } else {
        $status = 'Pending';
    }

    if ($courseId && (!$courseCode || !$courseName)) {
        $cs = $db->prepare('SELECT course_code, course_name, college, program FROM courses WHERE id = ?');
        $cs->execute([$courseId]);
        if ($cRow = $cs->fetch(PDO::FETCH_ASSOC)) {
            $courseCode = $courseCode ?: $cRow['course_code'];
            $courseName = $courseName ?: $cRow['course_name'];
            $program    = $program    ?: trim($cRow['program'] ?? ''); // ✅ FIX: auto-fill program from courses table
            if (!$college) {
                $dbCollege = strtoupper(trim($cRow['college'] ?? ''));
                $dbProgram = strtoupper(trim($cRow['program'] ?? ''));
                $college   = $dbCollege ?: $dbProgram;
            }
        }
    }
    if ($roomId && !$roomName) {
        $rs = $db->prepare('SELECT name FROM rooms WHERE id = ?');
        $rs->execute([$roomId]);
        if ($rRow = $rs->fetch(PDO::FETCH_ASSOC)) $roomName = $rRow['name'];
    }

    // ── Time slot boundary validation (7:00 AM – 9:00 PM) ───────────────────
    if ($timeSlot) {
        // Parse start time from slot string e.g. "07:00 AM - 08:00 AM"
        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)/i', $timeSlot, $sm)) {
            $sh = (int)$sm[1]; $smn = (int)$sm[2]; $sap = strtoupper($sm[3]);
            if ($sap === 'PM' && $sh !== 12) $sh += 12;
            if ($sap === 'AM' && $sh === 12) $sh = 0;
            $startMins = $sh * 60 + $smn; // minutes from midnight
            if ($startMins < 420) { // before 7:00 AM
                jsonResponse(false, 'Time slot must start at 7:00 AM or later.');
            }
        }
        // Parse end time from slot string
        if (preg_match('/[-–]\s*(\d{1,2}):(\d{2})\s*(AM|PM)\s*$/i', $timeSlot, $em)) {
            $eh = (int)$em[1]; $emn = (int)$em[2]; $eap = strtoupper($em[3]);
            if ($eap === 'PM' && $eh !== 12) $eh += 12;
            if ($eap === 'AM' && $eh === 12) $eh = 0;
            $endMins = $eh * 60 + $emn;
            if ($endMins > 1260) { // after 9:00 PM
                jsonResponse(false, 'Time slot must end by 9:00 PM.');
            }
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

    // ── Conflict checks ──────────────────────────────────────────────────────
    $excludeId = 0;

    // Check 1: Room conflict (skip for online exams)
    if ($roomId && !$isOnline) {
        $chk = $db->prepare("SELECT id FROM schedules
            WHERE room_id = ? AND exam_date = ? AND time_slot = ?
            AND status != 'Rejected' AND id != ? LIMIT 1");
        $chk->execute([$roomId, $examDate, $timeSlot, $excludeId]);
        if ($chk->fetch(PDO::FETCH_ASSOC)) jsonResponse(false, "Room \"{$roomName}\" is already booked on {$examDate} at {$timeSlot}.");
    }

    // Check 2: Section time overlap — scoped to college + program.
    // Section names like "1-Y2-1" are reused across different colleges/programs,
    // so a conflict only applies when the same college AND program share the same slot.
    if ($section && $examDate && $timeSlot && $college && $program) {
        $chk = $db->prepare("SELECT id, room_name, course_code FROM schedules
            WHERE LOWER(TRIM(section)) = LOWER(?) AND exam_date = ? AND time_slot = ?
            AND UPPER(TRIM(college)) = UPPER(?) AND UPPER(TRIM(program)) = UPPER(?)
            AND status != 'Rejected' AND id != ? LIMIT 1");
        $chk->execute([trim($section), $examDate, $timeSlot, trim($college), trim($program), $excludeId]);
        if ($row2 = $chk->fetch(PDO::FETCH_ASSOC)) {
            jsonResponse(false, "Section \"{$section}\" ({$college} - {$program}) is already scheduled at {$timeSlot} on {$examDate} (Course: {$row2['course_code']}, Room: {$row2['room_name']}). Students cannot be in two places at once.");
        }
    }

    // Check 3: Proctor conflict — each proctor can only be in ONE room at a time
    if ($proctorIds) {
        $conflict = checkProctorConflicts($proctorIds, $examDate, $timeSlot, $excludeId, $db);
        if ($conflict) {
            jsonResponse(false, "Proctor \"{$conflict['proctor_name']}\" is already assigned to room \"{$conflict['room_name']}\" at {$timeSlot} on {$examDate}.");
        }
    }

    // Check 4: Section + course + date uniqueness — scoped to college + program
    if ($section && $courseId && $examDate && $examType && $college && $program) {
        $chk = $db->prepare("SELECT id FROM schedules
            WHERE LOWER(TRIM(section)) = LOWER(?) AND course_id = ?
            AND exam_date = ? AND LOWER(exam_type) = LOWER(?)
            AND UPPER(TRIM(college)) = UPPER(?) AND UPPER(TRIM(program)) = UPPER(?)
            AND status != 'Rejected' AND id != ? LIMIT 1");
        $chk->execute([trim($section), $courseId, $examDate, $examType, trim($college), trim($program), $excludeId]);
        if ($chk->fetch(PDO::FETCH_ASSOC)) {
            jsonResponse(false, "Section \"{$section}\" ({$college} - {$program}) already has a {$examType} exam for this course on {$examDate}.");
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

    // Ensure optional columns exist (safe migration)
    try { $db->query("SELECT section FROM schedules LIMIT 1"); }
    catch (PDOException $e) { $db->exec("ALTER TABLE schedules ADD COLUMN section VARCHAR(100) DEFAULT NULL AFTER year_level"); }
    try { $db->query("SELECT semester FROM schedules LIMIT 1"); }
    catch (PDOException $e) { $db->exec("ALTER TABLE schedules ADD COLUMN semester VARCHAR(50) DEFAULT NULL AFTER exam_type"); }

    // Ensure proctor_name column can hold multiple names (TEXT instead of VARCHAR 150)
    try {
        $db->exec("ALTER TABLE schedules MODIFY COLUMN proctor_name TEXT DEFAULT NULL");
    } catch (PDOException $e) { /* already TEXT or no permission — silently continue */ }

    // Ensure junction table exists BEFORE opening transaction (DDL = implicit commit in MariaDB)
    ensureJunctionTable($db);

    try {
        $db->beginTransaction();

        $db->prepare(
            "INSERT INTO schedules
                (course_id, course_code, course_name, college, program,
                 exam_type, semester, year_level, section,
                 exam_date, time_slot, duration,
                 room_id, room_name, is_online, proctor_id, proctor_name,
                 campus, status, created_by, file_hash)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $courseId, $courseCode, $courseName, $college, $program,
            $examType, $semester ?: null, $yearLevel, $section,
            $examDate, $timeSlot, $duration,
            $roomId, $roomName, $isOnline, $proctorId, $proctorName ?: null,
            $campus ?: null, $status, $createdBy, $fileHash ?: null
        ]);


        $id = (int) $db->lastInsertId();

        // Save to junction table
        syncScheduleProctors($id, $proctorIds, $db);

        $db->commit();

        $row = $db->prepare('SELECT * FROM schedules WHERE id = ?');
        $row->execute([$id]);
        $result = enrichWithProctors($row->fetch(PDO::FETCH_ASSOC), $db);
        jsonResponse(true, 'Schedule created successfully.', $result);

    } catch (PDOException $e) {
        $db->rollBack();

        if ($e->getCode() === '23000') {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'unique_room_booking')) {
                jsonResponse(false, 'This room is already booked for that date and time slot. (Detected on concurrent upload)');
            }
            if (str_contains($msg, 'unique_section_course_exam')) {
                jsonResponse(false, "Section \"{$section}\" already has a {$examType} exam for this course on {$examDate}. (Detected on concurrent upload)");
            }
            jsonResponse(false, 'A duplicate schedule was detected. This record was already saved by another upload running at the same time.');
        }

        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

if ($action === 'update') {
    $id          = intOrNull($body['id']         ?? null) ?? 0;
    $courseId    = intOrNull($body['courseId']   ?? $body['course_id']    ?? null);
    $college     = strtoupper(clean($body['college'] ?? ''));
    $program     = clean($body['program']        ?? '');
    $examType    = clean($body['examType']       ?? $body['exam_type']    ?? '');
    $semester    = clean($body['semester']       ?? '');
    $yearLevel   = clean($body['yearLevel']      ?? $body['year_level']   ?? '');
    $section     = clean($body['section']        ?? $body['section_name'] ?? $body['class_section'] ?? '');
    $examDate    = clean($body['examDate']       ?? $body['exam_date']    ?? '');
    $timeSlot    = clean($body['timeSlot']       ?? $body['time_slot']    ?? '');
    $duration    = clean($body['duration']       ?? '');
    $roomId      = intOrNull($body['roomId']     ?? $body['room_id']      ?? null);
    $campus      = clean($body['campus']         ?? '');
    $isOnline    = isset($body['is_online']) ? (int)(bool)$body['is_online'] : 0;

    // Online exams don't need a room — but do NOT override room_name automatically;
    // let the user keep whatever room they entered (or leave it blank).
    if ($isOnline) { $roomId = null; }

    if (!$id) jsonResponse(false, 'Invalid schedule ID.');
    if (!$examType || !$examDate || !$timeSlot) {
        jsonResponse(false, 'Exam type, date and time slot are required.');
    }

    if ($role === 'Admin') {
        $rawStatus = clean($body['status'] ?? '');
        $status    = in_array($rawStatus, ['Pending', 'Approved', 'Rejected']) ? $rawStatus : 'Pending';
    } else {
        $status = 'Pending';
    }

    // ── Multi-proctor ────────────────────────────────────────────────────────
    $rawProctorIds = $body['proctorIds'] ?? $body['proctor_ids'] ?? null;
    if ($rawProctorIds !== null) {
        $proctorIds = parseProctorIds($rawProctorIds);
    } else {
        $legacyId   = intOrNull($body['proctorId'] ?? $body['proctor_id'] ?? null);
        $proctorIds = $legacyId ? [$legacyId] : [];
    }
    $nameMap     = resolveProctorNames($proctorIds, $db);
    $proctorId   = $proctorIds[0] ?? null;
    $proctorName = implode(', ', array_values($nameMap));
    // ─────────────────────────────────────────────────────────────────────────

    $courseCode = '';
    $courseName = '';
    if ($courseId) {
        $cs = $db->prepare('SELECT course_code, course_name, college, program FROM courses WHERE id = ?');
        $cs->execute([$courseId]);
        if ($cRow = $cs->fetch(PDO::FETCH_ASSOC)) {
            $courseCode = $cRow['course_code'];
            $courseName = $cRow['course_name'];
            $program    = $program ?: trim($cRow['program'] ?? ''); // ✅ FIX: auto-fill program from courses table
            if (!$college) {
                $dbCollege = strtoupper(trim($cRow['college'] ?? ''));
                $dbProgram = strtoupper(trim($cRow['program'] ?? ''));
                $college   = $dbCollege ?: $dbProgram;
            }
        }
    }
    $roomName = clean($body['roomName'] ?? $body['room_name'] ?? '');
    if ($roomId) {
        $rs = $db->prepare('SELECT name FROM rooms WHERE id = ?');
        $rs->execute([$roomId]);
        if ($rRow = $rs->fetch(PDO::FETCH_ASSOC)) $roomName = $rRow['name'];
    }

    // ── Time slot boundary validation (7:00 AM – 9:00 PM) ───────────────────
    if ($timeSlot) {
        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)/i', $timeSlot, $sm)) {
            $sh = (int)$sm[1]; $smn = (int)$sm[2]; $sap = strtoupper($sm[3]);
            if ($sap === 'PM' && $sh !== 12) $sh += 12;
            if ($sap === 'AM' && $sh === 12) $sh = 0;
            $startMins = $sh * 60 + $smn;
            if ($startMins < 420) jsonResponse(false, 'Time slot must start at 7:00 AM or later.');
        }
        if (preg_match('/[-–]\s*(\d{1,2}):(\d{2})\s*(AM|PM)\s*$/i', $timeSlot, $em)) {
            $eh = (int)$em[1]; $emn = (int)$em[2]; $eap = strtoupper($em[3]);
            if ($eap === 'PM' && $eh !== 12) $eh += 12;
            if ($eap === 'AM' && $eh === 12) $eh = 0;
            $endMins = $eh * 60 + $emn;
            if ($endMins > 1260) jsonResponse(false, 'Time slot must end by 9:00 PM.');
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

    // ── Conflict checks ──────────────────────────────────────────────────────
    $excludeId = $id;

    // Check 1: Room conflict
    if ($roomId) {
        $chk = $db->prepare("SELECT id FROM schedules
            WHERE room_id = ? AND exam_date = ? AND time_slot = ?
            AND status != 'Rejected' AND id != ? LIMIT 1");
        $chk->execute([$roomId, $examDate, $timeSlot, $excludeId]);
        if ($chk->fetch(PDO::FETCH_ASSOC)) jsonResponse(false, "Room \"{$roomName}\" is already booked on {$examDate} at {$timeSlot}.");
    }

    // Check 2: Section time overlap — scoped to college + program.
    if ($section && $examDate && $timeSlot && $college && $program) {
        $chk = $db->prepare("SELECT id, room_name, course_code FROM schedules
            WHERE LOWER(TRIM(section)) = LOWER(?) AND exam_date = ? AND time_slot = ?
            AND UPPER(TRIM(college)) = UPPER(?) AND UPPER(TRIM(program)) = UPPER(?)
            AND status != 'Rejected' AND id != ? LIMIT 1");
        $chk->execute([trim($section), $examDate, $timeSlot, trim($college), trim($program), $excludeId]);
        if ($row2 = $chk->fetch(PDO::FETCH_ASSOC)) {
            jsonResponse(false, "Section \"{$section}\" ({$college} - {$program}) is already scheduled at {$timeSlot} on {$examDate} (Course: {$row2['course_code']}, Room: {$row2['room_name']}). Students cannot be in two places at once.");
        }
    }

    // Check 3: Proctor conflict — each proctor can only be in ONE room at a time
    if ($proctorIds) {
        $conflict = checkProctorConflicts($proctorIds, $examDate, $timeSlot, $excludeId, $db);
        if ($conflict) {
            jsonResponse(false, "Proctor \"{$conflict['proctor_name']}\" is already assigned to room \"{$conflict['room_name']}\" at {$timeSlot} on {$examDate}.");
        }
    }

    // Check 4: Section + course + date uniqueness — scoped to college + program
    if ($section && $courseId && $examDate && $examType && $college && $program) {
        $chk = $db->prepare("SELECT id FROM schedules
            WHERE LOWER(TRIM(section)) = LOWER(?) AND course_id = ?
            AND exam_date = ? AND LOWER(exam_type) = LOWER(?)
            AND UPPER(TRIM(college)) = UPPER(?) AND UPPER(TRIM(program)) = UPPER(?)
            AND status != 'Rejected' AND id != ? LIMIT 1");
        $chk->execute([trim($section), $courseId, $examDate, $examType, trim($college), trim($program), $excludeId]);
        if ($chk->fetch(PDO::FETCH_ASSOC)) {
            jsonResponse(false, "Section \"{$section}\" ({$college} - {$program}) already has a {$examType} exam for this course on {$examDate}.");
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

    // Ensure optional columns exist
    try { $db->query("SELECT section FROM schedules LIMIT 1"); }
    catch (PDOException $e) { $db->exec("ALTER TABLE schedules ADD COLUMN section VARCHAR(100) DEFAULT NULL AFTER year_level"); }
    try { $db->query("SELECT semester FROM schedules LIMIT 1"); }
    catch (PDOException $e) { $db->exec("ALTER TABLE schedules ADD COLUMN semester VARCHAR(50) DEFAULT NULL AFTER exam_type"); }

    // Ensure junction table exists BEFORE opening transaction (DDL = implicit commit in MariaDB)
    ensureJunctionTable($db);

    try {
        $db->beginTransaction();

        $db->prepare("UPDATE schedules
            SET course_id=?, course_code=?, course_name=?, college=?, program=?,
                exam_type=?, semester=?, year_level=?, section=?,
                exam_date=?, time_slot=?, duration=?,
                room_id=?, room_name=?, is_online=?, proctor_id=?, proctor_name=?,
                campus=?, status=?, updated_at=NOW()
            WHERE id=?")
        ->execute([
            $courseId, $courseCode, $courseName, $college, $program,
            $examType, $semester ?: null, $yearLevel, $section,
            $examDate, $timeSlot, $duration,
            $roomId, $roomName, $isOnline, $proctorId, $proctorName ?: null,
            $campus ?: null, $status, $id
        ]);

        // Sync junction table
        syncScheduleProctors($id, $proctorIds, $db);

        $db->commit();

        $row = $db->prepare('SELECT * FROM schedules WHERE id = ?');
        $row->execute([$id]);
        $result = enrichWithProctors($row->fetch(PDO::FETCH_ASSOC), $db);
        jsonResponse(true, 'Schedule updated successfully.', $result);

    } catch (PDOException $e) {
        $db->rollBack();
        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

if ($action === 'approve') {
    if ($role !== 'Admin') jsonResponse(false, 'Forbidden.');
    $id = intOrNull($body['id'] ?? null) ?? 0;
    if (!$id) jsonResponse(false, 'Invalid schedule ID.');

    $sched = $db->prepare('SELECT * FROM schedules WHERE id = ? LIMIT 1');
    $sched->execute([$id]);
    $s = $sched->fetch(PDO::FETCH_ASSOC);
    if ($s) {
        // Check 1: Room conflict against Approved schedules (skip for online exams)
        if (!empty($s['room_id']) && empty($s['is_online'])) {
            $chk = $db->prepare("SELECT id, course_code FROM schedules
                WHERE room_id = ? AND exam_date = ? AND time_slot = ?
                AND status = 'Approved' AND id != ? LIMIT 1");
            $chk->execute([$s['room_id'], $s['exam_date'], $s['time_slot'], $id]);
            if ($clash = $chk->fetch(PDO::FETCH_ASSOC)) {
                jsonResponse(false, "Cannot approve — room \"{$s['room_name']}\" is already booked by \"{$clash['course_code']}\" at {$s['time_slot']} on {$s['exam_date']}.");
            }
        }

        // Check 2: Proctor conflict against Approved schedules (all proctors)
        // Load full proctor list for this schedule
        $schedProctorIds = [];
        try {
            $sp = $db->prepare('SELECT proctor_id FROM schedule_proctors WHERE schedule_id = ?');
            $sp->execute([$id]);
            $schedProctorIds = array_column($sp->fetchAll(PDO::FETCH_ASSOC), 'proctor_id');
        } catch (PDOException $e) {}
        // Fall back to legacy column
        if (!$schedProctorIds && !empty($s['proctor_id'])) {
            $schedProctorIds = [(int)$s['proctor_id']];
        }

        if ($schedProctorIds) {
            $placeholders = implode(',', array_fill(0, count($schedProctorIds), '?'));

            // Check junction table
            try {
                $params = array_merge($schedProctorIds, [$s['exam_date'], $s['time_slot'], $id]);
                $chk = $db->prepare(
                    "SELECT sp.proctor_id, p.name AS proctor_name, sch.course_code, sch.room_name
                     FROM schedule_proctors sp
                     JOIN schedules sch ON sch.id  = sp.schedule_id
                     JOIN proctors  p   ON p.id    = sp.proctor_id
                     WHERE sp.proctor_id IN ($placeholders)
                       AND sch.exam_date  = ?
                       AND sch.time_slot  = ?
                       AND sch.status    = 'Approved'
                       AND sch.id        != ?
                     LIMIT 1"
                );
                $chk->execute($params);
                if ($clash = $chk->fetch(PDO::FETCH_ASSOC)) {
                    jsonResponse(false, "Cannot approve — proctor \"{$clash['proctor_name']}\" is already assigned to \"{$clash['course_code']}\" (Room: {$clash['room_name']}) at {$s['time_slot']} on {$s['exam_date']}.");
                }
            } catch (PDOException $e) {}

            // Also check legacy column
            $params = array_merge($schedProctorIds, [$s['exam_date'], $s['time_slot'], $id]);
            $chk = $db->prepare(
                "SELECT proctor_id, proctor_name, course_code, room_name FROM schedules
                 WHERE proctor_id IN ($placeholders)
                   AND exam_date  = ?
                   AND time_slot  = ?
                   AND status    = 'Approved'
                   AND id        != ?
                 LIMIT 1"
            );
            $chk->execute($params);
            if ($clash = $chk->fetch(PDO::FETCH_ASSOC)) {
                jsonResponse(false, "Cannot approve — proctor \"{$clash['proctor_name']}\" is already assigned to \"{$clash['course_code']}\" (Room: {$clash['room_name']}) at {$s['time_slot']} on {$s['exam_date']}.");
            }
        }

        // Check 3: Section time overlap against Approved schedules — scoped to college + program.
        if (!empty($s['section']) && !empty($s['college']) && !empty($s['program'])) {
            $chk = $db->prepare("SELECT id, course_code, room_name FROM schedules
                WHERE LOWER(TRIM(section)) = LOWER(?) AND exam_date = ? AND time_slot = ?
                AND UPPER(TRIM(college)) = UPPER(?) AND UPPER(TRIM(program)) = UPPER(?)
                AND status = 'Approved' AND id != ? LIMIT 1");
            $chk->execute([trim($s['section']), $s['exam_date'], $s['time_slot'], trim($s['college']), trim($s['program']), $id]);
            if ($clash = $chk->fetch(PDO::FETCH_ASSOC)) {
                jsonResponse(false, "Cannot approve — section \"{$s['section']}\" ({$s['college']} - {$s['program']}) is already scheduled at {$s['time_slot']} on {$s['exam_date']} for \"{$clash['course_code']}\" (Room: {$clash['room_name']}).");
            }
        }
    }

    $db->prepare("UPDATE schedules SET status = 'Approved', updated_at = NOW() WHERE id = ?")->execute([$id]);
    $row = $db->prepare('SELECT * FROM schedules WHERE id = ?'); $row->execute([$id]);
    jsonResponse(true, 'Schedule approved.', enrichWithProctors($row->fetch(PDO::FETCH_ASSOC), $db));
}

if ($action === 'reject') {
    if ($role !== 'Admin') jsonResponse(false, 'Forbidden.');
    $id = intOrNull($body['id'] ?? null) ?? 0;
    if (!$id) jsonResponse(false, 'Invalid schedule ID.');
    $db->prepare("UPDATE schedules SET status = 'Rejected', updated_at = NOW() WHERE id = ?")->execute([$id]);
    $row = $db->prepare('SELECT * FROM schedules WHERE id = ?'); $row->execute([$id]);
    jsonResponse(true, 'Schedule rejected.', enrichWithProctors($row->fetch(PDO::FETCH_ASSOC), $db));
}

if ($action === 'delete') {
    $id = intOrNull($body['id'] ?? null) ?? 0;
    if (!$id) jsonResponse(false, 'Invalid schedule ID.');
    if ($role === 'Program Head') {
        jsonResponse(false, 'Program Heads are not permitted to delete schedules.');
    }

    $hashRow = $db->prepare('SELECT file_hash FROM schedules WHERE id = ?');
    $hashRow->execute([$id]);
    $deletedHash = ($hashRow->fetch(PDO::FETCH_ASSOC)['file_hash'] ?? null);

    // Delete junction rows first (cascade handles this if FK is set, but be safe)
    try {
        $db->prepare('DELETE FROM schedule_proctors WHERE schedule_id = ?')->execute([$id]);
    } catch (PDOException $e) {}

    $db->prepare('DELETE FROM schedules WHERE id = ?')->execute([$id]);

    if ($deletedHash) {
        $remaining = $db->prepare('SELECT COUNT(*) FROM schedules WHERE file_hash = ?');
        $remaining->execute([$deletedHash]);
        if ((int)$remaining->fetchColumn() === 0) {
            $db->prepare(
                'DELETE FROM import_locks
                  WHERE file_hash    = ?
                    AND import_type  = ?
                    AND is_permanent = 1'
            )->execute([$deletedHash, 'schedule']);
        }
    }

    jsonResponse(true, 'Schedule deleted successfully.');
}

jsonResponse(false, 'Unknown action.', [], 400);