<?php
require_once __DIR__ . '/../includes/api.php';

$action = $_GET['action'] ?? 'list';

/** Id of the "Completed" scholarship status, or null if the lookup is missing. */
function completed_status_id(): ?int {
    $stmt = db()->prepare("SELECT id FROM scholarship_statuses WHERE LOWER(name) = 'completed' LIMIT 1");
    $stmt->execute();
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

/** Strict Y-m-d check — strtotime() is too forgiving for user input. */
function is_valid_date(?string $value): bool {
    if (!$value) return false;
    $d = DateTime::createFromFormat('!Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
}

/** Validates a cost typed into a form. Returns an error message, or null when it's fine. */
function cost_error(string $raw): ?string {
    if ($raw === '')        return 'Scholarship cost is required.';
    if (!is_numeric($raw))  return 'Enter the cost as a number.';
    if ((float)$raw < 0)    return 'Cost cannot be negative.';
    return null;
}

// ---------------------------------------------------------------
// GET actions
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if ($action === 'list') {
        $rows = db()->query(
            "SELECT s.id, s.scholarship_code, s.main_course_name, s.scheme,
                    s.study_start_date, s.planned_end_date, s.study_end_date, s.scholarship_cost,
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
            "SELECT s.*, p.aapng_id, CONCAT(p.family_name, ', ', p.given_names) AS participant,
                    st.name AS status
             FROM scholarships s
             JOIN participants p ON p.id = s.participant_id
             LEFT JOIN scholarship_statuses st ON st.id = s.status_id
             WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) json_out(false, null, 'Record not found.');
        json_out(true, $row);
    }

    // Bulk finalise, step 1: courses in an intake year that still have open awards.
    if ($action === 'open_courses') {
        $stmt = db()->prepare(
            "SELECT s.main_course_name AS course, COUNT(*) AS open_count
             FROM scholarships s
             LEFT JOIN scholarship_statuses st ON st.id = s.status_id
             WHERE s.intake_year_id = ? AND LOWER(COALESCE(st.name, '')) <> 'completed'
             GROUP BY s.main_course_name
             ORDER BY s.main_course_name"
        );
        $stmt->execute([(int)($_GET['intake_year_id'] ?? 0)]);
        json_out(true, $stmt->fetchAll());
    }

    // Bulk finalise, step 2: the open awards for one course in that intake year.
    if ($action === 'open_awards') {
        $stmt = db()->prepare(
            "SELECT s.id, s.scholarship_code, s.study_start_date, s.planned_end_date, s.scholarship_cost,
                    p.aapng_id, CONCAT(p.family_name, ', ', p.given_names) AS participant
             FROM scholarships s
             JOIN participants p ON p.id = s.participant_id
             LEFT JOIN scholarship_statuses st ON st.id = s.status_id
             WHERE s.intake_year_id = ? AND s.main_course_name = ?
               AND LOWER(COALESCE(st.name, '')) <> 'completed'
             ORDER BY p.family_name, p.given_names"
        );
        $stmt->execute([(int)($_GET['intake_year_id'] ?? 0), (string)($_GET['course'] ?? '')]);
        json_out(true, $stmt->fetchAll());
    }

    json_out(false, null, 'Unknown action.');
}

// ---------------------------------------------------------------
// POST actions — require CSRF
// ---------------------------------------------------------------
api_verify_csrf();
$in = api_input();

if ($action === 'save') {
    // Add / edit covers the course details and the planned end date. The actual end
    // date, the cost and the move to "Completed" belong to the finalise action below,
    // so they are never written from here.
    $id = (int)($in['id'] ?? 0);
    $errors = [];

    $participantId = (int)($in['participant_id'] ?? 0);
    $intakeYearId  = (int)($in['intake_year_id'] ?? 0) ?: null;
    $institutionId = (int)($in['institution_id'] ?? 0) ?: null;
    $course        = trim($in['main_course_name'] ?? '');
    $scheme        = trim($in['scheme'] ?? '');
    $levelId       = (int)($in['study_level_id'] ?? 0) ?: null;
    $fieldId       = (int)($in['field_of_study_id'] ?? 0) ?: null;
    $startDate     = trim((string)($in['study_start_date'] ?? '')) ?: null;
    $plannedEnd    = trim((string)($in['planned_end_date'] ?? '')) ?: null;
    $statusId      = (int)($in['status_id'] ?? 0) ?: null;

    $completedId = completed_status_id();
    $pdo = db();

    // When editing, load the stored record so a finalised award keeps its status.
    $existing = null;
    if ($id) {
        $stmt = $pdo->prepare('SELECT id, status_id, study_end_date FROM scholarships WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) json_out(false, null, 'Record not found.');
    }
    $isFinalised = $existing && $completedId && (int)$existing['status_id'] === $completedId;

    if (!$participantId) $errors['participant_id'] = 'Select the person this award belongs to.';
    if ($course === '')  $errors['main_course_name'] = 'Course name is required.';
    if ($scheme === '') {
        $errors['scheme'] = 'Select a scheme.';
    } elseif (!in_array($scheme, ['SCA', 'HEP', 'Other'], true)) {
        $errors['scheme'] = 'Scheme must be SCA, HEP or Other.';
    }
    if (!$startDate)                        $errors['study_start_date'] = 'Study start date is required.';
    elseif (!is_valid_date($startDate))     $errors['study_start_date'] = 'Invalid date.';

    elseif ($existing && $existing['study_end_date'] && $startDate > $existing['study_end_date']) {
        $errors['study_start_date'] = 'Start date cannot be after the actual end date (' . $existing['study_end_date'] . ').';
    }

    if (!$plannedEnd)                         $errors['planned_end_date'] = 'Planned end date is required.';
    elseif (!is_valid_date($plannedEnd))      $errors['planned_end_date'] = 'Invalid date.';
    elseif ($startDate && $plannedEnd < $startDate) {
        $errors['planned_end_date'] = 'Planned end date cannot be before the start date.';
    }

    if ($isFinalised) {
        $statusId = $completedId;   // locked — only the finalise action changes it
    } elseif (!$statusId) {
        $errors['status_id'] = 'Select a status.';
    } elseif ($statusId === $completedId) {
        $errors['status_id'] = 'Use the Finalise button to mark an award as completed.';
    }

    // Participant must exist.
    if ($participantId) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM participants WHERE id = ?');
        $stmt->execute([$participantId]);
        if (!$stmt->fetchColumn()) $errors['participant_id'] = 'Selected person was not found.';
    }

    if ($errors) json_out(false, ['errors' => $errors], 'Please fix the highlighted fields.');

    $uid = current_user()['id'];

    if ($id) {
        $stmt = $pdo->prepare(
            'UPDATE scholarships SET participant_id=?, intake_year_id=?, institution_id=?,
                    main_course_name=?, scheme=?, study_level_id=?, field_of_study_id=?,
                    study_start_date=?, planned_end_date=?, status_id=?, updated_by=?
             WHERE id = ?'
        );
        $stmt->execute([$participantId,$intakeYearId,$institutionId,$course,$scheme,$levelId,$fieldId,
                        $startDate,$plannedEnd,$statusId,$uid,$id]);
        json_out(true, ['id' => $id], 'Award updated successfully.');
    }

    // Insert + generate code SCH-{intake year}-{seq} in one transaction.
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO scholarships
                (participant_id, intake_year_id, institution_id, main_course_name, scheme,
                 study_level_id, field_of_study_id, study_start_date, planned_end_date,
                 status_id, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([$participantId,$intakeYearId,$institutionId,$course,$scheme,$levelId,$fieldId,
                        $startDate,$plannedEnd,$statusId,$uid]);
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
        error_log('Award save failed: ' . $e->getMessage());
        json_out(false, null, 'Could not save the record. Please try again.');
    }
}

if ($action === 'finalise') {
    // Closes off an award: records the actual end date and cost, and sets the
    // status to "Completed". Staff can finalise once; amending a finalised award
    // is reserved for administrators.
    $id      = (int)($in['id'] ?? 0);
    $endDate = trim((string)($in['study_end_date'] ?? ''));
    $costRaw = trim((string)($in['scholarship_cost'] ?? ''));
    $errors  = [];

    $completedId = completed_status_id();
    if (!$completedId) {
        json_out(false, null, 'There is no "Completed" status set up. Add it under Settings → Scholarship statuses first.');
    }

    $pdo  = db();
    $stmt = $pdo->prepare('SELECT id, status_id, study_start_date FROM scholarships WHERE id = ?');
    $stmt->execute([$id]);
    $award = $stmt->fetch();
    if (!$award) json_out(false, null, 'Record not found.');

    if ((int)$award['status_id'] === $completedId && !is_admin()) {
        http_response_code(403);
        json_out(false, null, 'This award is already finalised. Ask an administrator to amend it.');
    }

    if ($endDate === '')                  $errors['study_end_date'] = 'Study end date is required.';
    elseif (!is_valid_date($endDate))     $errors['study_end_date'] = 'Invalid date.';
    elseif ($award['study_start_date'] && $endDate < $award['study_start_date']) {
        $errors['study_end_date'] = 'End date cannot be before the start date (' . $award['study_start_date'] . ').';
    } elseif ($endDate > date('Y-m-d')) {
        $errors['study_end_date'] = 'An award can only be finalised once the course has ended.';
    }

    if ($err = cost_error($costRaw)) $errors['scholarship_cost'] = $err;

    if ($errors) json_out(false, ['errors' => $errors], 'Please fix the highlighted fields.');

    $stmt = $pdo->prepare(
        'UPDATE scholarships SET study_end_date = ?, scholarship_cost = ?, status_id = ?, updated_by = ?
         WHERE id = ?'
    );
    $stmt->execute([$endDate, round((float)$costRaw, 2), $completedId, current_user()['id'], $id]);
    json_out(true, ['id' => $id], 'Award finalised and marked as completed.');
}

if ($action === 'bulk_finalise') {
    // Finalises a group of awards (one course in one intake year) in one go: they all
    // share the actual end date, each keeps its own cost. All or nothing — if any award
    // fails a check, none are changed.
    $endDate = trim((string)($in['study_end_date'] ?? ''));
    $ids     = array_values(array_unique(array_filter(array_map('intval', (array)($in['ids'] ?? [])))));
    $costs   = is_array($in['costs'] ?? null) ? $in['costs'] : [];
    $errors  = [];

    if (!$ids)              json_out(false, null, 'Tick at least one award to finalise.');
    if (count($ids) > 500)  json_out(false, null, 'Finalise at most 500 awards at a time.');

    $completedId = completed_status_id();
    if (!$completedId) {
        json_out(false, null, 'There is no "Completed" status set up. Add it under Settings → Scholarship statuses first.');
    }

    if ($endDate === '')                  $errors['study_end_date'] = 'Study end date is required.';
    elseif (!is_valid_date($endDate))     $errors['study_end_date'] = 'Invalid date.';
    elseif ($endDate > date('Y-m-d'))     $errors['study_end_date'] = 'Awards can only be finalised once the course has ended.';

    $costErrors = [];
    foreach ($ids as $id) {
        if ($err = cost_error(trim((string)($costs[$id] ?? '')))) $costErrors[$id] = $err;
    }
    if ($costErrors) $errors['costs'] = $costErrors;
    if ($errors) json_out(false, ['errors' => $errors], 'Please fix the highlighted fields.');

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Lock the rows so two people can't finalise the same awards at once.
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt  = $pdo->prepare(
            "SELECT id, scholarship_code, status_id, study_start_date
             FROM scholarships WHERE id IN ($marks) FOR UPDATE"
        );
        $stmt->execute($ids);
        $awards = $stmt->fetchAll();

        if (count($awards) !== count($ids)) {
            $pdo->rollBack();
            json_out(false, null, 'Some of the selected awards no longer exist. Reload the list and try again.');
        }
        foreach ($awards as $a) {
            if ((int)$a['status_id'] === $completedId) {
                $pdo->rollBack();
                json_out(false, null, "{$a['scholarship_code']} has already been finalised. Reload the list and try again.");
            }
            if ($a['study_start_date'] && $endDate < $a['study_start_date']) {
                $pdo->rollBack();
                json_out(false, ['errors' => ['study_end_date' => "End date is before {$a['scholarship_code']}'s start date ({$a['study_start_date']})."]],
                         'Please fix the highlighted fields.');
            }
        }

        $update = $pdo->prepare(
            'UPDATE scholarships SET study_end_date = ?, scholarship_cost = ?, status_id = ?, updated_by = ?
             WHERE id = ?'
        );
        $uid = current_user()['id'];
        foreach ($ids as $id) {
            $update->execute([$endDate, round((float)$costs[$id], 2), $completedId, $uid, $id]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Bulk finalise failed: ' . $e->getMessage());
        json_out(false, null, 'Could not finalise the awards. Please try again.');
    }

    $n = count($ids);
    json_out(true, ['count' => $n], $n === 1 ? '1 award finalised.' : "$n awards finalised and marked as completed.");
}

if ($action === 'delete') {
    api_require_admin();   // deleting awards is admin-only
    $id = (int)($in['id'] ?? 0);
    db()->prepare('DELETE FROM scholarships WHERE id = ?')->execute([$id]);
    json_out(true, null, 'Award record deleted.');
}

json_out(false, null, 'Unknown action.');