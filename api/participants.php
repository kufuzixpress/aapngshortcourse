<?php
require_once __DIR__ . '/../includes/api.php';

$action = $_GET['action'] ?? 'list';

// ---------------------------------------------------------------
// GET actions
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if ($action === 'list') {
        $rows = db()->query(
            "SELECT p.id, p.aapng_id, p.given_names, p.family_name, p.gender,
                    p.date_of_birth, p.disability_flag, p.email,
                    pr.name AS province, d.name AS district,
                    o.name AS organisation,
                    (SELECT COUNT(*) FROM scholarships s WHERE s.participant_id = p.id) AS awards_count
             FROM participants p
             LEFT JOIN provinces pr     ON pr.id = p.province_id
             LEFT JOIN districts d      ON d.id  = p.district_id
             LEFT JOIN organisations o  ON o.id  = p.organisation_id
             ORDER BY p.family_name, p.given_names"
        )->fetchAll();
        json_out(true, $rows);
    }

    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = db()->prepare(
            "SELECT p.*,
                    pr.name  AS province,
                    d.name   AS district,
                    o.name   AS organisation,
                    ec.name  AS employment_category,
                    dt.name  AS disability_type
             FROM participants p
             LEFT JOIN provinces            pr ON pr.id = p.province_id
             LEFT JOIN districts            d  ON d.id  = p.district_id
             LEFT JOIN organisations        o  ON o.id  = p.organisation_id
             LEFT JOIN employment_categories ec ON ec.id = p.employment_category_id
             LEFT JOIN disability_types     dt ON dt.id = p.disability_type_id
             WHERE p.id = ?"
        );
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) json_out(false, null, 'Record not found.');

        // Awards held by this person (for the view page).
        $stmt = db()->prepare(
            "SELECT s.id, s.scholarship_code, s.scheme, s.scholarship_cost,
                    iy.year  AS intake_year,
                    i.name   AS institution,
                    s.main_course_name,
                    sl.name  AS study_level,
                    st.name  AS status
             FROM scholarships s
             LEFT JOIN intake_years          iy ON iy.id = s.intake_year_id
             LEFT JOIN institutions          i  ON i.id  = s.institution_id
             LEFT JOIN study_levels          sl ON sl.id = s.study_level_id
             LEFT JOIN scholarship_statuses  st ON st.id = s.status_id
             WHERE s.participant_id = ?
             ORDER BY iy.year DESC, s.id DESC"
        );
        $stmt->execute([$id]);
        $p['awards'] = $stmt->fetchAll();
        json_out(true, $p);
    }

    // Lightweight list for award form participant picker.
    if ($action === 'options') {
        $rows = db()->query(
            "SELECT id, aapng_id, CONCAT(family_name, ', ', given_names) AS name, date_of_birth
             FROM participants ORDER BY family_name, given_names"
        )->fetchAll();
        json_out(true, $rows);
    }

    json_out(false, null, 'Unknown action.');
}

// ---------------------------------------------------------------
// POST actions (mutating) — require CSRF
// ---------------------------------------------------------------
api_verify_csrf();
$in = api_input();

if ($action === 'save') {
    $id = (int)($in['id'] ?? 0);
    $errors = [];

    $given    = trim($in['given_names'] ?? '');
    $family   = trim($in['family_name'] ?? '');
    $gender   = $in['gender'] ?? '';
    $dob      = trim($in['date_of_birth'] ?? '') ?: null;
    $disFlag  = (int)($in['disability_flag'] ?? 0) ? 1 : 0;
    $disType  = $disFlag ? (int)($in['disability_type_id'] ?? 0) : null;
    $homeP    = trim($in['home_phone'] ?? '') ?: null;
    $workP    = trim($in['work_phone'] ?? '') ?: null;
    $email    = trim($in['email'] ?? '') ?: null;
    $provId   = (int)($in['province_id'] ?? 0) ?: null;
    $distId   = (int)($in['district_id'] ?? 0) ?: null;
    $orgName  = trim($in['organisation'] ?? '');
    $empId    = (int)($in['employment_category_id'] ?? 0) ?: null;

    if ($given === '')  $errors['given_names'] = 'Given names are required.';
    if ($family === '') $errors['family_name'] = 'Family name is required.';
    if (!in_array($gender, ['Female','Male'], true)) $errors['gender'] = 'Select a gender.';
    if ($dob !== null) {
        if (!strtotime($dob)) $errors['date_of_birth'] = 'Enter a valid date of birth, or leave it blank if unknown.';
        elseif (strtotime($dob) > time()) $errors['date_of_birth'] = 'Date of birth cannot be in the future.';
    }
    if ($disFlag && !$disType) $errors['disability_type_id'] = 'Select the disability type.';
    if ($orgName === '') $errors['organisation'] = 'Organisation is required.';
    if ($distId && !$provId) $errors['province_id'] = 'Select the province this district belongs to.';

    // District must belong to the selected province (when both are set).
    if ($provId && $distId) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM districts WHERE id = ? AND province_id = ?');
        $stmt->execute([$distId, $provId]);
        if (!$stmt->fetchColumn()) $errors['district_id'] = 'District does not belong to the selected province.';
    }

    if ($errors) json_out(false, ['errors' => $errors], 'Please fix the highlighted fields.');

    // Duplicate-person check, unless the user confirmed "different person".
    if (empty($in['force'])) {
        $normName  = strtolower(preg_replace('/\s+/', '', $given . $family));
        $normPhone = fn(?string $p) => $p ? preg_replace('/\D/', '', $p) : '';
        $phones    = array_values(array_filter([$normPhone($homeP), $normPhone($workP)]));

        $phoneExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(%s,''),' ',''),'-',''),'+',''),'(',''),')','')";
        $homeExpr  = sprintf($phoneExpr, 'home_phone');
        $workExpr  = sprintf($phoneExpr, 'work_phone');

        $conds  = ["LOWER(REPLACE(CONCAT(given_names, family_name), ' ', '')) = ?"];
        $params = [$normName];
        if ($dob !== null) {
            $conds[]  = 'date_of_birth = ?';
            $params[] = $dob;
        }
        if ($email) {
            $conds[]  = 'LOWER(email) = ?';
            $params[] = strtolower($email);
        }
        foreach ($phones as $ph) {
            $conds[]  = "$homeExpr = ?";  $params[] = $ph;
            $conds[]  = "$workExpr = ?";  $params[] = $ph;
        }

        $sql = 'SELECT id, aapng_id, given_names, family_name, date_of_birth, email, home_phone, work_phone
                FROM participants WHERE (' . implode(' OR ', $conds) . ')';
        if ($id) { $sql .= ' AND id <> ?'; $params[] = $id; }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $dupes = $stmt->fetchAll();

        if ($dupes) {
            foreach ($dupes as &$d) {
                $reasons = [];
                if (strtolower(preg_replace('/\s+/', '', $d['given_names'] . $d['family_name'])) === $normName) $reasons[] = 'name';
                if ($dob !== null && $d['date_of_birth'] === $dob) $reasons[] = 'date of birth';
                if ($email && strtolower((string)$d['email']) === strtolower($email)) $reasons[] = 'email';
                $existingPhones = array_filter([$normPhone($d['home_phone']), $normPhone($d['work_phone'])]);
                if ($phones && array_intersect($phones, $existingPhones)) $reasons[] = 'phone number';
                $d['matched_on'] = implode(', ', $reasons);
                unset($d['home_phone'], $d['work_phone']);
            }
            unset($d);
            json_out(false, ['duplicates' => $dupes], 'A possible duplicate person was found.');
        }
    }

    $uid = current_user()['id'];
    $pdo = db();

    // Resolve the typed organisation to its lookup row, creating it if new.
    $stmt = $pdo->prepare('SELECT id FROM organisations WHERE LOWER(name) = LOWER(?) LIMIT 1');
    $stmt->execute([$orgName]);
    $orgId = (int)$stmt->fetchColumn();
    if (!$orgId) {
        $pdo->prepare('INSERT INTO organisations (name) VALUES (?)')->execute([$orgName]);
        $orgId = (int)$pdo->lastInsertId();
    }

    if ($id) {
        $stmt = $pdo->prepare(
            'UPDATE participants SET given_names=?, family_name=?, gender=?, date_of_birth=?,
                    disability_flag=?, disability_type_id=?, home_phone=?, work_phone=?, email=?,
                    province_id=?, district_id=?, organisation_id=?, employment_category_id=?, updated_by=?
             WHERE id = ?'
        );
        $stmt->execute([$given,$family,$gender,$dob,$disFlag,$disType,$homeP,$workP,$email,
                        $provId,$distId,$orgId,$empId,$uid,$id]);
        json_out(true, ['id' => $id], 'Record updated successfully.');
    }

    // Insert with generated AAPNG ID.
    $pdo->beginTransaction();
    try {
        $code = generate_aapng_id($pdo, $family, $given, $dob);
        $stmt = $pdo->prepare(
            'INSERT INTO participants
                (aapng_id, given_names, family_name, gender, date_of_birth, disability_flag, disability_type_id,
                 home_phone, work_phone, email, province_id, district_id, organisation_id,
                 employment_category_id, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([$code,$given,$family,$gender,$dob,$disFlag,$disType,$homeP,$workP,$email,
                        $provId,$distId,$orgId,$empId,$uid]);
        $newId = (int)$pdo->lastInsertId();
        $pdo->commit();
        json_out(true, ['id' => $newId, 'aapng_id' => $code], "Person added successfully with ID $code.");
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_out(false, null, 'Could not save the record. ' . $e->getMessage());
    }
}

function generate_aapng_id(PDO $pdo, string $family, string $given, ?string $dob): string {
    $part = function (string $s): string {
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $s));
        return substr(str_pad($letters, 3, 'X'), 0, 3);
    };
    $base = $part($family) . $part($given);
    $ddmm = $dob ? date('dm', strtotime($dob)) : '0000';

    $candidates = [$dob ? date('dmy', strtotime($dob)) : '000000'];
    for ($i = 1; $i <= 99; $i++) {
        $candidates[] = $ddmm . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
    }
    foreach (range('A', 'Z') as $letter) {
        for ($d = 0; $d <= 9; $d++) $candidates[] = $ddmm . $letter . $d;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM participants WHERE aapng_id = ?');
    foreach ($candidates as $suffix) {
        $code = $base . $suffix;
        $stmt->execute([$code]);
        if (!$stmt->fetchColumn()) return $code;
    }
    throw new RuntimeException('Could not generate a unique AAPNG ID for this name.');
}

if ($action === 'delete') {
    api_require_admin();
    $id = (int)($in['id'] ?? 0);

    $stmt = db()->prepare('SELECT COUNT(*) FROM scholarships WHERE participant_id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        json_out(false, null, 'This person has award records. Delete their awards first, or keep the record.');
    }
    db()->prepare('DELETE FROM participants WHERE id = ?')->execute([$id]);
    json_out(true, null, 'Record deleted.');
}

json_out(false, null, 'Unknown action.');