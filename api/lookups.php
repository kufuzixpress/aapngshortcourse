<?php
require_once __DIR__ . '/../includes/api.php';

/**
 * Lookup tables (system settings).
 * GET  options  — active items for dropdowns (all logged-in users)
 * GET  list     — all items incl. inactive (admin)
 * POST save / toggle_active / delete (admin)
 */

$TABLES = [
    'provinces'             => ['label' => 'Province'],
    'districts'             => ['label' => 'District', 'has_province' => true],
    'disability_types'      => ['label' => 'Disability type'],
    'employment_categories' => ['label' => 'Employment category'],
    'organisations'         => ['label' => 'Organisation'],
    'institutions'          => ['label' => 'Institution'],
    'study_levels'          => ['label' => 'Level of study'],
    'fields_of_study'       => ['label' => 'Field of study'],
    'scholarship_statuses'  => ['label' => 'Scholarship status'],
    'intake_years'          => ['label' => 'Intake year', 'is_year' => true],
];

// Tables referencing each lookup, for safe-delete checks: [table, column]
$USAGE = [
    'provinces'             => [['participants','province_id'], ['districts','province_id']],
    'districts'             => [['participants','district_id']],
    'disability_types'      => [['participants','disability_type_id']],
    'employment_categories' => [['participants','employment_category_id']],
    'organisations'         => [['participants','organisation_id']],
    'institutions'          => [['scholarships','institution_id']],
    'study_levels'          => [['scholarships','study_level_id']],
    'fields_of_study'       => [['scholarships','field_of_study_id']],
    'scholarship_statuses'  => [['scholarships','status_id']],
    'intake_years'          => [['scholarships','intake_year_id']],
];

$table = $_GET['table'] ?? '';
if (!isset($TABLES[$table])) json_out(false, null, 'Unknown lookup table.');

$meta   = $TABLES[$table];
$col    = !empty($meta['is_year']) ? 'year' : 'name';
$action = $_GET['action'] ?? 'options';

// ---------------------------------------------------------------
// GET actions
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if ($action === 'options') {
        if (!empty($meta['has_province'])) {
            $rows = db()->query(
                "SELECT d.id, d.name, d.province_id, p.name AS province
                 FROM districts d JOIN provinces p ON p.id = d.province_id
                 WHERE d.is_active = 1 ORDER BY p.name, d.name"
            )->fetchAll();
        } else {
            $rows = db()->query("SELECT id, `$col` AS name FROM `$table` WHERE is_active = 1 ORDER BY `$col`")->fetchAll();
        }
        json_out(true, $rows);
    }

    if ($action === 'list') {
        api_require_admin();
        if (!empty($meta['has_province'])) {
            $rows = db()->query(
                "SELECT d.id, d.name, d.is_active, d.province_id, p.name AS province
                 FROM districts d JOIN provinces p ON p.id = d.province_id
                 ORDER BY p.name, d.name"
            )->fetchAll();
        } else {
            $rows = db()->query("SELECT id, `$col` AS name, is_active FROM `$table` ORDER BY `$col`")->fetchAll();
        }
        json_out(true, $rows);
    }

    json_out(false, null, 'Unknown action.');
}

// ---------------------------------------------------------------
// POST actions — admin only, CSRF required
// ---------------------------------------------------------------
api_require_admin();
api_verify_csrf();
$in = api_input();

if ($action === 'save') {
    $id   = (int)($in['id'] ?? 0);
    $name = trim((string)($in['name'] ?? ''));

    if ($name === '') json_out(false, null, $meta['label'] . ' name is required.');
    if (!empty($meta['is_year']) && (!ctype_digit($name) || (int)$name < 1990 || (int)$name > 2100)) {
        json_out(false, null, 'Enter a valid 4-digit year.');
    }

    $provinceId = null;
    if (!empty($meta['has_province'])) {
        $provinceId = (int)($in['province_id'] ?? 0);
        if (!$provinceId) json_out(false, null, 'Select the province this district belongs to.');
    }

    try {
        if ($id) {
            if (!empty($meta['has_province'])) {
                db()->prepare('UPDATE districts SET name = ?, province_id = ? WHERE id = ?')
                    ->execute([$name, $provinceId, $id]);
            } else {
                db()->prepare("UPDATE `$table` SET `$col` = ? WHERE id = ?")->execute([$name, $id]);
            }
            json_out(true, ['id' => $id], $meta['label'] . ' updated.');
        }
        if (!empty($meta['has_province'])) {
            db()->prepare('INSERT INTO districts (name, province_id) VALUES (?, ?)')
                ->execute([$name, $provinceId]);
        } else {
            db()->prepare("INSERT INTO `$table` (`$col`) VALUES (?)")->execute([$name]);
        }
        json_out(true, ['id' => (int)db()->lastInsertId()], $meta['label'] . ' added.');
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) json_out(false, null, 'That value already exists.');
        throw $e;
    }
}

if ($action === 'toggle_active') {
    $id = (int)($in['id'] ?? 0);
    db()->prepare("UPDATE `$table` SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    json_out(true, null, 'Status updated. Inactive items stay on old records but disappear from dropdowns.');
}

if ($action === 'delete') {
    $id = (int)($in['id'] ?? 0);

    foreach ($USAGE[$table] as [$refTable, $refCol]) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM `$refTable` WHERE `$refCol` = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            json_out(false, null, 'This item is used by existing records and cannot be deleted. Deactivate it instead.');
        }
    }
    db()->prepare("DELETE FROM `$table` WHERE id = ?")->execute([$id]);
    json_out(true, null, $meta['label'] . ' deleted.');
}

json_out(false, null, 'Unknown action.');
