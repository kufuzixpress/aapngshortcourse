<?php
require_once __DIR__ . '/../includes/api.php';

$action = $_GET['action'] ?? 'list';

// ---------------------------------------------------------------
// GET actions
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if ($action === 'list') {
        $rows = db()->query(
            "SELECT s.id, s.scholarship_code, s.main_course_name, s.scheme,
                    s.study_start_date, s.study_end_date, s.scholarship_cost,
                    p.aapng_id, CONCAT(p.family_name, ', ', p.given_names) AS participant,
                    iy.year AS intake_year, i.name AS institution,
                    sl.name AS study_level, f.name AS field_of_study, st.name AS status
             FROM scholarships s
             JOIN participants p  ON p.id  = s.participant_id
             LEFT JOIN intake_years iy ON iy.id = s.intake_year_id
             LEFT JOIN institutions i  ON i.id  = s.institution_id
             LEFT JOIN study_levels sl ON sl.id = s.study_level_id
             LEFT JOIN fields_of_study f ON f.id = s.field_of_study_id
             LEFT JOIN scholarship_statuses st ON st.id = s.status_id
             ORDER BY iy.year DESC, p.family_name"
        )->fetchAll();
        json_out(true, $rows);
    }

    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = db()->prepare(
            "SELECT s.*, p.aapng_id, CONCAT(p.family_name, ', ', p.given_names) AS participant
             FROM scholarships s JOIN participants p ON p.id = s.participant_id
             WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) json_out(false, null, 'Record not found.');
        json_out(true, $row);
    }

    json_out(false, null, 'Unknown action.');
}

// ---------------------------------------------------------------
// POST actions — require CSRF
// ---------------------------------------------------------------
api_verify_csrf();
$in = api_input();

if ($action === 'save') {
    $id = (int)($in['id'] ?? 0);
    $errors = [];

    $participantId = (int)($in['participant_id'] ?? 0);
    $intakeYearId  = (int)($in['intake_year_id'] ?? 0) ?: null;
    $institutionId = (int)($in['institution_id'] ?? 0) ?: null;
    $course        = trim($in['main_course_name'] ?? '');
    $scheme        = trim($in['scheme'] ?? '');
    $levelId       = (int)($in['study_level_id'] ?? 0) ?: null;
    $fieldId       = (int)($in['field_of_study_id'] ?? 0) ?: null;
    $startDate     = $in['study_start_date'] ?: null;
    $endDate       = $in['study_end_date'] ?: null;
    $statusId      = (int)($in['status_id'] ?? 0) ?: null;
    $cost          = ($in['scholarship_cost'] !== '' && $in['scholarship_cost'] !== null)
                        ? (float)$in['scholarship_cost'] : null;

    if (!$participantId) $errors['participant_id'] = 'Select the person this award belongs to.';
    if ($course === '')  $errors['main_course_name'] = 'Course name is required.';
    if ($scheme === '') {
        $errors['scheme'] = 'Select a scheme.';
    } elseif (!in_array($scheme, ['SCA', 'HEP', 'Other'], true)) {
        $errors['scheme'] = 'Scheme must be SCA, HEP or Other.';
    }
    if ($startDate && !strtotime($startDate)) $errors['study_start_date'] = 'Invalid date.';
    if ($endDate && !strtotime($endDate))     $errors['study_end_date'] = 'Invalid date.';
    if ($startDate && $endDate && strtotime($endDate) < strtotime($startDate)) {
        $errors['study_end_date'] = 'End date cannot be before the start date.';
    }
    if ($cost !== null && $cost < 0) $errors['scholarship_cost'] = 'Cost cannot be negative.';

    // Cost is mandatory when the scholarship status is "Completed".
    if ($statusId && $cost === null) {
        $stmt = db()->prepare('SELECT name FROM scholarship_statuses WHERE id = ?');
        $stmt->execute([$statusId]);
        if (strcasecmp((string)$stmt->fetchColumn(), 'Completed') === 0) {
            $errors['scholarship_cost'] = 'Scholarship cost is required when the status is Completed.';
        }
    }

    // Participant must exist.
    if ($participantId) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM participants WHERE id = ?');
        $stmt->execute([$participantId]);
        if (!$stmt->fetchColumn()) $errors['participant_id'] = 'Selected person was not found.';
    }

    if ($errors) json_out(false, ['errors' => $errors], 'Please fix the highlighted fields.');

    $uid = current_user()['id'];
    $pdo = db();

    if ($id) {
        $stmt = $pdo->prepare(
            'UPDATE scholarships SET participant_id=?, intake_year_id=?, institution_id=?,
                    main_course_name=?, scheme=?, study_level_id=?, field_of_study_id=?,
                    study_start_date=?, study_end_date=?, status_id=?, scholarship_cost=?, updated_by=?
             WHERE id = ?'
        );
        $stmt->execute([$participantId,$intakeYearId,$institutionId,$course,$scheme,$levelId,$fieldId,
                        $startDate,$endDate,$statusId,$cost,$uid,$id]);
        json_out(true, ['id' => $id], 'Award updated successfully.');
    }

    // Insert + generate code SCH-{intake year}-{seq} in one transaction.
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO scholarships
                (participant_id, intake_year_id, institution_id, main_course_name, scheme,
                 study_level_id, field_of_study_id, study_start_date, study_end_date,
                 status_id, scholarship_cost, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([$participantId,$intakeYearId,$institutionId,$course,$scheme,$levelId,$fieldId,
                        $startDate,$endDate,$statusId,$cost,$uid]);
        $newId = (int)$pdo->lastInsertId();

        $year = '0000';
        if ($intakeYearId) {
            $stmt = $pdo->prepare('SELECT year FROM intake_years WHERE id = ?');
            $stmt->execute([$intakeYearId]);
            $year = $stmt->fetchColumn() ?: '0000';
        }

        $code = 'SCH-' . $year . '-' . str_pad((string)$newId, 4, '0', STR_PAD_LEFT);
        $pdo->prepare('UPDATE scholarships SET scholarship_code = ? WHERE id = ?')->execute([$code, $newId]);
        $pdo->commit();
        json_out(true, ['id' => $newId, 'scholarship_code' => $code], "Award recorded successfully with code $code.");
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_out(false, null, 'Could not save the record. ' . $e->getMessage());
    }
}

if ($action === 'delete') {
    api_require_admin();   // deleting awards is admin-only
    $id = (int)($in['id'] ?? 0);
    db()->prepare('DELETE FROM scholarships WHERE id = ?')->execute([$id]);
    json_out(true, null, 'Award record deleted.');
}

json_out(false, null, 'Unknown action.');