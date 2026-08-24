<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

// ---------------------------------------------------------------
// CSV export (?export=1)
// ---------------------------------------------------------------
if (isset($_GET['export'])) {
    $pdo = db();

    // Build filters from GET params
    $where  = [];
    $params = [];

    if (!empty($_GET['intake_year'])) {
        $where[]  = 'iy.id = ?';
        $params[] = (int)$_GET['intake_year'];
    }
    if (!empty($_GET['scheme'])) {
        $where[]  = 's.scheme = ?';
        $params[] = $_GET['scheme'];
    }
    if (!empty($_GET['status'])) {
        $where[]  = 'ss.id = ?';
        $params[] = (int)$_GET['status'];
    }
    if (!empty($_GET['province'])) {
        $where[]  = 'pr.id = ?';
        $params[] = (int)$_GET['province'];
    }
    if (!empty($_GET['gender'])) {
        $where[]  = 'p.gender = ?';
        $params[] = $_GET['gender'];
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            p.aapng_id              AS 'AAPNG ID',
            p.family_name           AS 'Family Name',
            p.given_names           AS 'Given Names',
            p.gender                AS 'Gender',
            p.date_of_birth         AS 'Date of Birth',
            CASE WHEN p.disability_flag = 1 THEN 'Yes' ELSE 'No' END AS 'Disability',
            dt.name                 AS 'Disability Type',
            p.home_phone            AS 'Home Phone',
            p.work_phone            AS 'Work Phone',
            p.email                 AS 'Email',
            pr.name                 AS 'Province',
            d.name                  AS 'District',
            o.name                  AS 'Organisation',
            ec.name                 AS 'Employment Category',
            s.scholarship_code      AS 'Scholarship Code',
            iy.year                 AS 'Intake Year',
            s.scheme                AS 'Scheme',
            i.name                  AS 'Institution',
            s.main_course_name      AS 'Main Course Name',
            sl.name                 AS 'Level of Study',
            fos.name                AS 'Field of Study',
            s.study_start_date      AS 'Study Start Date',
            s.study_end_date        AS 'Study End Date',
            ss.name                 AS 'Scholarship Status',
            s.scholarship_cost      AS 'Scholarship Cost (AUD)'
        FROM scholarships s
        JOIN participants p             ON p.id   = s.participant_id
        LEFT JOIN intake_years iy       ON iy.id  = s.intake_year_id
        LEFT JOIN institutions i        ON i.id   = s.institution_id
        LEFT JOIN study_levels sl       ON sl.id  = s.study_level_id
        LEFT JOIN fields_of_study fos   ON fos.id = s.field_of_study_id
        LEFT JOIN scholarship_statuses ss ON ss.id = s.status_id
        LEFT JOIN provinces pr          ON pr.id  = p.province_id
        LEFT JOIN districts d           ON d.id   = p.district_id
        LEFT JOIN organisations o       ON o.id   = p.organisation_id
        LEFT JOIN employment_categories ec ON ec.id = p.employment_category_id
        LEFT JOIN disability_types dt   ON dt.id  = p.disability_type_id
        $whereClause
        ORDER BY p.family_name, p.given_names, iy.year DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = 'aapng-awards-export-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM so Excel opens it correctly
    fwrite($out, "\xEF\xBB\xBF");

    if ($rows) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) fputcsv($out, $row);
    } else {
        fputcsv($out, ['No records matched the selected filters.']);
    }
    fclose($out);
    exit;
}

// ---------------------------------------------------------------
// Page render — load filter options
// ---------------------------------------------------------------
$pdo = db();
$intakeYears = $pdo->query('SELECT id, year FROM intake_years ORDER BY year DESC')->fetchAll();
$statuses    = $pdo->query('SELECT id, name FROM scholarship_statuses ORDER BY name')->fetchAll();
$provinces   = $pdo->query('SELECT id, name FROM provinces ORDER BY name')->fetchAll();

// Summary counts for the preview strip
$totalAwards       = (int)$pdo->query('SELECT COUNT(*) FROM scholarships')->fetchColumn();
$totalParticipants = (int)$pdo->query('SELECT COUNT(DISTINCT participant_id) FROM scholarships')->fetchColumn();

$page_title = 'Export awards';
require_once __DIR__ . '/includes/header.php';
?>

<style>
  :root { --primary:#3CB6CE; --secondary:#003150; }
  .page-title { font-size:22px; font-weight:800; color:var(--secondary); }
  .page-sub   { font-size:13px; color:#64748B; margin-bottom:16px; }

  .page-card { background:#fff; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.05); }
  .page-card-header { padding:14px 18px; border-bottom:1px solid #EEF2F7; }
  .page-card-header h5 { margin:0; font-size:13px; font-weight:700; color:var(--secondary); text-transform:uppercase; letter-spacing:.05em; }
  .page-card-header h5 i { color:var(--primary); }

  /* Summary strip */
  .summary-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
  .sum-card { background:#fff; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.05); padding:16px 20px; display:flex; align-items:center; gap:14px; }
  .sum-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
  .sum-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#94A3B8; }
  .sum-value { font-size:22px; font-weight:800; color:var(--secondary); }

  /* Filter form */
  .filter-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#64748B; margin-bottom:5px; display:block; }
  .form-control, .form-select { border-radius:7px; border-color:#DDE5EC; font-size:13px; }
  .form-control:focus, .form-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(60,182,206,.15); }

  /* Column preview */
  .col-preview { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
  .col-tag { background:#EEF2F7; color:var(--secondary); padding:3px 10px; border-radius:5px; font-size:11px; font-weight:600; }

  .btn-export { background:var(--secondary); color:#fff; border:none; border-radius:8px; font-weight:700; font-size:14px; padding:11px 28px; display:flex; align-items:center; gap:8px; transition:background .15s; }
  .btn-export:hover { background:#00263d; color:#fff; }
  .btn-reset { background:#F1F5F9; color:#475569; border:none; border-radius:8px; font-weight:600; font-size:13px; padding:11px 20px; }
  .btn-reset:hover { background:#E2E8F0; color:#334155; }
</style>

<div class="page-title">Export awards</div>
<div class="page-sub">Download a flat CSV of all participant and award details for analysis in Excel or similar tools. One row per award — participants with multiple awards appear on multiple rows.</div>

<!-- Summary strip -->
<div class="summary-strip">
  <div class="sum-card">
    <div class="sum-icon" style="background:#E0F5F9;color:var(--primary);"><i class="bi bi-award"></i></div>
    <div>
      <div class="sum-label">Total awards</div>
      <div class="sum-value"><?= number_format($totalAwards) ?></div>
    </div>
  </div>
  <div class="sum-card">
    <div class="sum-icon" style="background:#EEF2F7;color:var(--secondary);"><i class="bi bi-people"></i></div>
    <div>
      <div class="sum-label">Participants with awards</div>
      <div class="sum-value"><?= number_format($totalParticipants) ?></div>
    </div>
  </div>
  <div class="sum-card">
    <div class="sum-icon" style="background:#DCFCE7;color:#15803D;"><i class="bi bi-file-earmark-spreadsheet"></i></div>
    <div>
      <div class="sum-label">Export format</div>
      <div class="sum-value" style="font-size:15px;font-weight:700;">CSV / Excel</div>
    </div>
  </div>
</div>

<!-- Filter + export form -->
<div class="page-card">
  <div class="page-card-header">
    <h5><i class="bi bi-funnel me-2"></i>Filter before exporting</h5>
  </div>
  <div class="p-4">
    <form method="GET" action="export-awards.php">
      <input type="hidden" name="export" value="1">

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="filter-label">Intake year</label>
          <select class="form-select" name="intake_year">
            <option value="">All intake years</option>
            <?php foreach ($intakeYears as $iy): ?>
              <option value="<?= (int)$iy['id'] ?>" <?= ($_GET['intake_year'] ?? '') == $iy['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($iy['year']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="filter-label">Scheme</label>
          <select class="form-select" name="scheme">
            <option value="">All schemes</option>
            <option value="SCA"   <?= ($_GET['scheme'] ?? '') === 'SCA'   ? 'selected' : '' ?>>SCA</option>
            <option value="HEP"   <?= ($_GET['scheme'] ?? '') === 'HEP'   ? 'selected' : '' ?>>HEP</option>
            <option value="Other" <?= ($_GET['scheme'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="filter-label">Scholarship status</label>
          <select class="form-select" name="status">
            <option value="">All statuses</option>
            <?php foreach ($statuses as $st): ?>
              <option value="<?= (int)$st['id'] ?>" <?= ($_GET['status'] ?? '') == $st['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($st['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="filter-label">Province</label>
          <select class="form-select" name="province">
            <option value="">All provinces</option>
            <?php foreach ($provinces as $pr): ?>
              <option value="<?= (int)$pr['id'] ?>" <?= ($_GET['province'] ?? '') == $pr['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($pr['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="filter-label">Gender</label>
          <select class="form-select" name="gender">
            <option value="">All genders</option>
            <option value="Female" <?= ($_GET['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Male"   <?= ($_GET['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
          </select>
        </div>
      </div>

      <!-- Column preview -->
      <div class="mb-4">
        <div class="filter-label" style="margin-bottom:8px;">Columns included in every export</div>
        <div class="col-preview">
          <?php foreach (['AAPNG ID','Family Name','Given Names','Gender','Date of Birth','Disability',
                          'Disability Type','Home Phone','Work Phone','Email','Province','District',
                          'Organisation','Employment Category','Scholarship Code','Intake Year','Scheme',
                          'Institution','Main Course Name','Level of Study','Field of Study',
                          'Study Start Date','Study End Date','Scholarship Status','Scholarship Cost (AUD)'] as $col): ?>
            <span class="col-tag"><?= $col ?></span>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="d-flex gap-2 align-items-center">
        <button type="submit" class="btn-export">
          <i class="bi bi-download"></i>Download CSV
        </button>
        <a href="export-awards.php" class="btn-reset">Clear filters</a>
        <span class="text-muted ms-2" style="font-size:12px;">
          Leave all filters blank to export everything.
        </span>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
