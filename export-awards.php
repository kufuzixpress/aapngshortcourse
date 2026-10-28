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
            s.planned_end_date      AS 'Planned End Date',
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

<div class="page-header">
  <div>
    <span class="page-eyebrow">Administration</span>
    <h1 class="page-title">Export awards</h1>
    <p class="page-sub">Download a flat CSV of participant and award details for Excel or similar tools. One row per award, so people with several awards appear on several rows.</p>
  </div>
</div>

<!-- Summary strip -->
<div class="summary-strip">
  <div class="sum-card">
    <div class="sum-icon stat-icon-teal"><i class="bi bi-award"></i></div>
    <div>
      <div class="sum-label">Total awards</div>
      <div class="sum-value"><?= number_format($totalAwards) ?></div>
    </div>
  </div>
  <div class="sum-card">
    <div class="sum-icon stat-icon-navy"><i class="bi bi-people"></i></div>
    <div>
      <div class="sum-label">Participants with awards</div>
      <div class="sum-value"><?= number_format($totalParticipants) ?></div>
    </div>
  </div>
  <div class="sum-card">
    <div class="sum-icon stat-icon-slate"><i class="bi bi-file-earmark-spreadsheet"></i></div>
    <div>
      <div class="sum-label">Export format</div>
      <div class="sum-value sum-value-sm">CSV / Excel</div>
    </div>
  </div>
</div>

<!-- Filter + export form -->
<div class="page-card">
  <div class="page-card-header">
    <h5><i class="bi bi-funnel"></i>Filter before exporting</h5>
  </div>
  <div class="page-card-body">
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
        <div class="filter-label">Columns included in every export</div>
        <div class="col-preview">
          <?php foreach (['AAPNG ID','Family Name','Given Names','Gender','Date of Birth','Disability',
                          'Disability Type','Home Phone','Work Phone','Email','Province','District',
                          'Organisation','Employment Category','Scholarship Code','Intake Year','Scheme',
                          'Institution','Main Course Name','Level of Study','Field of Study',
                          'Study Start Date','Planned End Date','Study End Date','Scholarship Status','Scholarship Cost (AUD)'] as $col): ?>
            <span class="col-tag"><?= $col ?></span>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="d-flex gap-2 align-items-center flex-wrap">
        <button type="submit" class="btn btn-export btn-lg">
          <i class="bi bi-download"></i>Download CSV
        </button>
        <a href="export-awards.php" class="btn btn-reset btn-lg">Clear filters</a>
        <span class="text-muted small ms-sm-2">
          Leave all filters blank to export everything.
        </span>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
