<?php
// ============================================================
// api/colleges.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db     = getDB();

// ── LIST (public — no auth required) ─────────────────────────────────────────
if ($action === 'list') {
    $rows = $db->query('SELECT * FROM colleges ORDER BY name')->fetchAll();
    foreach ($rows as &$row) {
        $stmt = $db->prepare('SELECT program FROM college_programs WHERE college_id = ? ORDER BY program');
        $stmt->execute([$row['id']]);
        $row['programs'] = array_column($stmt->fetchAll(), 'program');
    }
    jsonResponse(true, 'OK', $rows);
}

// ── Auth guard — Admin only for all write actions ─────────────────────────────
requireAuth();
$role = $_SESSION['user']['role'];
if ($role !== 'Admin') jsonResponse(false, 'Forbidden', [], 403);
$body = getBody();

// ── Helper: extract acronym from "ACRONYM=Full Name" or plain "ACRONYM" ──────
function extractAcronym(string $prog): string {
    $pos = strpos($prog, '=');
    return $pos !== false ? trim(substr($prog, 0, $pos)) : trim($prog);
}

// ── CREATE ────────────────────────────────────────────────────────────────────
if ($action === 'create') {
    $name        = clean($body['name']        ?? '');
    $code        = strtoupper(clean($body['code'] ?? ''));
    $description = clean($body['description'] ?? '');
    $campus      = clean($body['campus']      ?? '');
    $programs    = $body['programs'] ?? [];
    if (!is_array($programs)) {
        $programs = array_filter(array_map('trim', explode(',', (string)$programs)));
    }
    $programs = array_values(array_filter(array_map('trim', $programs)));

    if (!$name || !$code) jsonResponse(false, 'College name and code are required.');

    // Find existing college with same name + campus
    $existingCollege = $db->prepare("SELECT id FROM colleges WHERE name = ? AND campus = ? LIMIT 1");
    $existingCollege->execute([$name, $campus]);
    $existingRow = $existingCollege->fetch();

    if ($existingRow) {
        // College already exists — just add the new programs to it
        $collegeId = (int)$existingRow['id'];

        if ($programs) {
            foreach ($programs as $prog) {
                if ($prog === '') continue;
                $acronym = extractAcronym($prog);
                $dupProg = $db->prepare(
                    'SELECT id FROM college_programs
                      WHERE college_id = ? AND (program = ? OR program LIKE ?)'
                );
                $dupProg->execute([$collegeId, $prog, $acronym . '=%']);
                if ($dupProg->fetch()) {
                    jsonResponse(false, "Program '{$acronym}' already exists in college \"{$name}\" at the {$campus} campus.");
                }
                $db->prepare('INSERT INTO college_programs (college_id, program) VALUES (?, ?)')->execute([$collegeId, $prog]);
            }
        }

        $stmt = $db->prepare('SELECT * FROM colleges WHERE id = ?');
        $stmt->execute([$collegeId]);
        $collegeData = $stmt->fetch();
        $pStmt2 = $db->prepare('SELECT program FROM college_programs WHERE college_id = ? ORDER BY program');
        $pStmt2->execute([$collegeId]);
        $collegeData['programs'] = array_column($pStmt2->fetchAll(), 'program');
        jsonResponse(true, 'Program added to existing college successfully.', $collegeData);
    }

    // No existing college — create new one
    $createdBy = null;
    $rawId = (int)($_SESSION['user']['id'] ?? 0);
    if ($rawId > 0) {
        $chk = $db->prepare('SELECT id FROM users WHERE id = ?');
        $chk->execute([$rawId]);
        if ($chk->fetch()) $createdBy = $rawId;
    }
    if ($createdBy === null) {
        $adminRow = $db->query("SELECT id FROM users WHERE role='Admin' ORDER BY id LIMIT 1")->fetch();
        $createdBy = $adminRow ? (int)$adminRow['id'] : null;
    }

    try {
        $db->prepare(
            'INSERT INTO colleges (name, code, description, campus, created_by) VALUES (?, ?, ?, ?, ?)'
        )->execute([$name, $code, $description ?: null, $campus ?: null, $createdBy]);
        $collegeId = (int)$db->lastInsertId();

        if ($programs) {
            $pStmt = $db->prepare('INSERT INTO college_programs (college_id, program) VALUES (?, ?)');
            foreach ($programs as $prog) {
                if ($prog !== '') $pStmt->execute([$collegeId, $prog]);
            }
        }

        $stmt = $db->prepare('SELECT * FROM colleges WHERE id = ?');
        $stmt->execute([$collegeId]);
        $collegeData = $stmt->fetch();
        $collegeData['programs'] = $programs;
        jsonResponse(true, 'College created successfully.', $collegeData);

    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            // Race condition — college was just inserted, add programs to it
            $existing = $db->prepare("SELECT id FROM colleges WHERE name = ? AND campus = ? LIMIT 1");
            $existing->execute([$name, $campus]);
            $existingRow2 = $existing->fetch();
            if ($existingRow2) {
                $collegeId = (int)$existingRow2['id'];
                foreach ($programs as $prog) {
                    if ($prog === '') continue;
                    $acronym = extractAcronym($prog);
                    $dupProg = $db->prepare(
                        'SELECT id FROM college_programs WHERE college_id = ? AND (program = ? OR program LIKE ?)'
                    );
                    $dupProg->execute([$collegeId, $prog, $acronym . '=%']);
                    if ($dupProg->fetch()) {
                        jsonResponse(false, "Program '{$acronym}' already exists in \"{$name}\" at the {$campus} campus.");
                    }
                    $db->prepare('INSERT INTO college_programs (college_id, program) VALUES (?, ?)')->execute([$collegeId, $prog]);
                }
                $pStmt2 = $db->prepare('SELECT program FROM college_programs WHERE college_id = ? ORDER BY program');
                $pStmt2->execute([$collegeId]);
                $collegeData = $existingRow2;
                $collegeData['programs'] = array_column($pStmt2->fetchAll(), 'program');
                jsonResponse(true, 'Program added to existing college successfully.', $collegeData);
            }
        }
        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($action === 'update') {
    $id          = (int)($body['id']          ?? 0);
    $name        = clean($body['name']        ?? '');
    $code        = strtoupper(clean($body['code'] ?? ''));
    $description = clean($body['description'] ?? '');
    $campus      = clean($body['campus']      ?? '');
    $programs    = $body['programs'] ?? [];
    if (!is_array($programs)) {
        $programs = array_filter(array_map('trim', explode(',', (string)$programs)));
    }
    $programs = array_values(array_filter(array_map('trim', $programs)));

    if (!$id || !$name || !$code) jsonResponse(false, 'ID, name and code are required.');

    // Check for duplicate programs within the same campus (excluding current college)
    if ($programs && $campus) {
        foreach ($programs as $prog) {
            $acronym  = extractAcronym($prog);
            $dupCheck = $db->prepare(
                'SELECT cp.program, c.name
                   FROM college_programs cp
                   JOIN colleges c ON c.id = cp.college_id
                  WHERE c.campus = ? AND c.name = ? AND c.id != ?'
            );
            $dupCheck->execute([$campus, $name, $id]);
            $existingProgs = $dupCheck->fetchAll();
            foreach ($existingProgs as $ep) {
                if (extractAcronym($ep['program']) === $acronym) {
                    jsonResponse(false, "Program '{$acronym}' already exists in {$campus} campus under '{$ep['name']}'.");
                }
            }
        }
    }

    try {
        $db->prepare(
            'UPDATE colleges SET name=?, code=?, description=?, campus=?, updated_at=NOW() WHERE id=?'
        )->execute([$name, $code, $description ?: null, $campus ?: null, $id]);

        $db->prepare('DELETE FROM college_programs WHERE college_id = ?')->execute([$id]);
        if ($programs) {
            $pStmt = $db->prepare('INSERT INTO college_programs (college_id, program) VALUES (?, ?)');
            foreach ($programs as $prog) {
                if ($prog !== '') $pStmt->execute([$id, $prog]);
            }
        }
        jsonResponse(true, 'College updated successfully.');
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            jsonResponse(false, "A college with these details already exists in the {$campus} campus. Please check for duplicates.");
        }
        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(false, 'College ID is required.');

    // Get file_hash BEFORE deleting so we can check if lock should be cleared
    $hashRow = $db->prepare('SELECT file_hash FROM colleges WHERE id = ?');
    $hashRow->execute([$id]);
    $deletedHash = ($hashRow->fetch()['file_hash'] ?? null);

    // Delete the college (cascades to college_programs via FK)
    $db->prepare('DELETE FROM colleges WHERE id = ?')->execute([$id]);

    // Auto-clear the permanent import lock if NO colleges remain from that same file.
    // This means: all data from this file is gone → file can be re-imported.
    if ($deletedHash) {
        $remaining = $db->prepare('SELECT COUNT(*) FROM colleges WHERE file_hash = ?');
        $remaining->execute([$deletedHash]);
        if ((int)$remaining->fetchColumn() === 0) {
            $db->prepare(
                'DELETE FROM import_locks
                  WHERE file_hash    = ?
                    AND import_type  = ?
                    AND is_permanent = 1'
            )->execute([$deletedHash, 'colleges']);
        }
    }

    jsonResponse(true, 'College deleted successfully.');
}

jsonResponse(false, 'Unknown action.', [], 400);