<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

// ---------------------------------------------------------------
// Statistics queries
// ---------------------------------------------------------------
$pdo = db();

$kpi = [
    'participants' => (int)$pdo->query('SELECT COUNT(*) FROM participants')->fetchColumn(),
    'awards'       => (int)$pdo->query('SELECT COUNT(*) FROM scholarships')->fetchColumn(),
    'total_cost'   => (float)$pdo->query('SELECT COALESCE(SUM(scholarship_cost),0) FROM scholarships')->fetchColumn(),
    'disability'   => (int)$pdo->query('SELECT COUNT(*) FROM participants WHERE disability_flag = 1')->fetchColumn(),
];

// Gender breakdown of participants
$gender = $pdo->query(
    'SELECT gender, COUNT(*) AS n FROM participants GROUP BY gender ORDER BY gender'
)->fetchAll();

// Scholarship status distribution
$statuses = $pdo->query(
    'SELECT st.name, COUNT(s.id) AS n
     FROM scholarship_statuses st
     LEFT JOIN scholarships s ON s.status_id = st.id
     GROUP BY st.id, st.name
     HAVING n > 0
     ORDER BY n DESC'
)->fetchAll();

// Awards and expenditure by intake year
$byYear = $pdo->query(
    'SELECT iy.year, COUNT(s.id) AS n, COALESCE(SUM(s.scholarship_cost),0) AS cost
     FROM intake_years iy
     JOIN scholarships s ON s.intake_year_id = iy.id
     GROUP BY iy.id, iy.year
     ORDER BY iy.year'
)->fetchAll();

// Participants by province with gender and disability counts
$byProvince = $pdo->query(
    "SELECT pr.name,
            COUNT(p.id) AS total,
            SUM(CASE WHEN p.gender = 'Female' THEN 1 ELSE 0 END) AS female_n,
            SUM(CASE WHEN p.gender = 'Male'   THEN 1 ELSE 0 END) AS male_n,
            SUM(CASE WHEN p.disability_flag = 1 THEN 1 ELSE 0 END) AS disability_n
     FROM provinces pr
     JOIN participants p ON p.province_id = pr.id
     GROUP BY pr.id, pr.name
     ORDER BY total DESC, pr.name"
)->fetchAll();

// Awards by institution, with gender and disability counts of the participants holding them
$byInstitution = $pdo->query(
    "SELECT i.name,
            COUNT(s.id) AS total,
            SUM(CASE WHEN p.gender = 'Female' THEN 1 ELSE 0 END) AS female_n,
            SUM(CASE WHEN p.gender = 'Male'   THEN 1 ELSE 0 END) AS male_n,
            SUM(CASE WHEN p.disability_flag = 1 THEN 1 ELSE 0 END) AS disability_n
     FROM institutions i
     JOIN scholarships s  ON s.institution_id = i.id
     JOIN participants p  ON p.id = s.participant_id
     GROUP BY i.id, i.name
     ORDER BY total DESC, i.name"
)->fetchAll();

// Awards by course, with gender and disability counts of the participants holding them
$byCourse = $pdo->query(
    "SELECT s.main_course_name AS name,
            COUNT(s.id) AS total,
            SUM(CASE WHEN p.gender = 'Female' THEN 1 ELSE 0 END) AS female_n,
            SUM(CASE WHEN p.gender = 'Male'   THEN 1 ELSE 0 END) AS male_n,
            SUM(CASE WHEN p.disability_flag = 1 THEN 1 ELSE 0 END) AS disability_n
     FROM scholarships s
     JOIN participants p ON p.id = s.participant_id
     WHERE s.main_course_name IS NOT NULL AND s.main_course_name <> ''
     GROUP BY s.main_course_name
     ORDER BY total DESC, s.main_course_name"
)->fetchAll();

$femaleN = 0; $maleN = 0;
foreach ($gender as $g) {
    if ($g['gender'] === 'Female') $femaleN = (int)$g['n'];
    if ($g['gender'] === 'Male')   $maleN   = (int)$g['n'];
}
$femalePct = $kpi['participants'] ? round($femaleN / $kpi['participants'] * 100) : 0;

function fmt_aud(float $v): string {
    if ($v >= 1000000) return 'A$ ' . number_format($v / 1000000, 2) . 'M';
    if ($v >= 1000)    return 'A$ ' . number_format($v / 1000, 1) . 'k';
    return 'A$ ' . number_format($v, 2);
}

/** Render one of the "X by Y" breakdown tables — shared markup for province/institution/course. */
function render_breakdown_table(string $id, array $rows, string $firstColLabel, string $emptyMsg): void {
    $sumF = 0; $sumM = 0; $sumD = 0; $sumT = 0;
    foreach ($rows as $r) {
        $sumF += (int)$r['female_n']; $sumM += (int)$r['male_n'];
        $sumD += (int)$r['disability_n']; $sumT += (int)$r['total'];
    }
    ?>
    <div class="table-responsive breakdown-scroll">
      <table class="table mb-0 breakdown-table" id="<?= e($id) ?>">
        <thead>
          <tr>
            <th><?= e($firstColLabel) ?></th>
            <th class="text-center">Female</th>
            <th class="text-center">Male</th>
            <th class="text-center">Disability</th>
            <th class="text-center">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="text-muted p-3"><?= e($emptyMsg) ?></td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e($r['name']) ?></td>
              <td class="text-center"><span class="count-pill pill-female"><?= number_format($r['female_n']) ?></span></td>
              <td class="text-center"><span class="count-pill pill-male"><?= number_format($r['male_n']) ?></span></td>
              <td class="text-center"><span class="count-pill pill-dis"><?= number_format($r['disability_n']) ?></span></td>
              <td class="text-center"><span class="count-pill pill-total"><?= number_format($r['total']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <?php if ($rows): ?>
        <tfoot>
          <tr>
            <td>Total</td>
            <td class="text-center"><?= number_format($sumF) ?></td>
            <td class="text-center"><?= number_format($sumM) ?></td>
            <td class="text-center"><?= number_format($sumD) ?></td>
            <td class="text-center"><?= number_format($sumT) ?></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
    <?php
}
?>

<div class="page-header">
  <div>
    <span class="page-eyebrow">Overview</span>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-sub">Participants, awards and expenditure across every intake at a glance.</p>
  </div>
  <div class="page-actions">
    <a href="participants.php" class="btn btn-outline-brand"><i class="bi bi-people"></i>Participants</a>
    <a href="awards.php" class="btn btn-primary"><i class="bi bi-award"></i>Awards</a>
  </div>
</div>

<!-- ============ Info banner ============ -->
<div class="info-banner mb-3">
  <div class="info-banner-icon-wrap"><i class="bi bi-info-circle-fill"></i></div>
  <span>The number of <strong>participants</strong> will usually be less than the number of <strong>awards</strong> — the same person can attend more than one short course.</span>
</div>

<!-- ============ KPI cards ============ -->
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <a href="participants.php" class="kpi-card kpi-navy">
      <div class="kpi-value"><?= number_format($kpi['participants']) ?></div>
      <div class="kpi-label">Participants</div>
      <i class="kpi-icon bi bi-people"></i>
      <span class="kpi-link">View records <i class="bi bi-arrow-right"></i></span>
    </a>
  </div>
  <div class="col-md-4">
    <a href="awards.php" class="kpi-card kpi-teal">
      <div class="kpi-value"><?= number_format($kpi['awards']) ?></div>
      <div class="kpi-label">Awards</div>
      <i class="kpi-icon bi bi-award"></i>
      <span class="kpi-link">View records <i class="bi bi-arrow-right"></i></span>
    </a>
  </div>
  <div class="col-md-4">
    <a href="awards.php" class="kpi-card kpi-slate">
      <div class="kpi-value"><?= fmt_aud($kpi['total_cost']) ?></div>
      <div class="kpi-label">Total expenditure (AUD)</div>
      <i class="kpi-icon bi bi-cash-stack"></i>
      <span class="kpi-link">View awards <i class="bi bi-arrow-right"></i></span>
    </a>
  </div>
</div>

<!-- ============ Secondary stat strip ============ -->
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="stat-card">
      <div>
        <span class="stat-label">Female participants</span>
        <div class="stat-value"><?= number_format($femaleN) ?> <small>(<?= $femalePct ?>%)</small></div>
      </div>
      <div class="stat-icon stat-icon-teal"><i class="bi bi-gender-female"></i></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div>
        <span class="stat-label">Male participants</span>
        <div class="stat-value"><?= number_format($maleN) ?> <small>(<?= 100 - $femalePct ?>%)</small></div>
      </div>
      <div class="stat-icon stat-icon-navy"><i class="bi bi-gender-male"></i></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div>
        <span class="stat-label">Participants with disability</span>
        <div class="stat-value"><?= number_format($kpi['disability']) ?></div>
      </div>
      <div class="stat-icon stat-icon-slate"><i class="bi bi-universal-access"></i></div>
    </div>
  </div>
</div>

<!-- ============ Charts row 1: awards by year + status ============ -->
<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="page-card h-100">
      <div class="page-card-header"><h5><i class="bi bi-bar-chart me-2"></i>Awards &amp; expenditure by intake year</h5></div>
      <div class="chart-box"><canvas id="chartYear" height="110"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="page-card h-100">
      <div class="page-card-header"><h5><i class="bi bi-flag me-2"></i>Scholarship status</h5></div>
      <div class="chart-box d-flex align-items-center justify-content-center"><canvas id="chartStatus" style="max-height:260px"></canvas></div>
    </div>
  </div>
</div>

<!-- ============ Row 2: gender + province table ============ -->
<div class="row g-3 mb-3">
  <div class="col-lg-3">
    <div class="page-card h-100">
      <div class="page-card-header"><h5><i class="bi bi-people me-2"></i>Gender breakdown</h5></div>
      <div class="chart-box d-flex align-items-center justify-content-center"><canvas id="chartGender" style="max-height:240px"></canvas></div>
    </div>
  </div>
  <div class="col-lg-9">
    <div class="page-card h-100">
      <div class="page-card-header"><h5><i class="bi bi-geo-alt me-2"></i>Participants by province</h5></div>
      <div class="p-0">
        <?php render_breakdown_table('provinceTable', $byProvince, 'Province', 'No participant records yet.'); ?>
      </div>
    </div>
  </div>
</div>

<!-- ============ Row 3: awards by institution + awards by course ============ -->
<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="page-card h-100">
      <div class="page-card-header"><h5><i class="bi bi-bank me-2"></i>Awards by institution</h5></div>
      <div class="p-0">
        <?php render_breakdown_table('institutionTable', $byInstitution, 'Institution', 'No award records yet.'); ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="page-card h-100">
      <div class="page-card-header"><h5><i class="bi bi-book me-2"></i>Awards by course</h5></div>
      <div class="p-0">
        <?php render_breakdown_table('courseTable', $byCourse, 'Course', 'No award records yet.'); ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// Brand palette: navy and teal lead, then tints and the neutral greys.
const NAVY = '#003150', CYAN = '#3CB6CE';
const PALETTE = ['#003150', '#3CB6CE', '#747678', '#8FD3E1', '#4D7590', '#C9CAC5', '#1A6F80', '#B9CBD6'];

Chart.defaults.color = '#747678';
Chart.defaults.borderColor = '#E0E1DD';
Chart.defaults.font.family = 'Arial, "Helvetica Neue", Helvetica, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.boxWidth = 8;
Chart.defaults.plugins.tooltip.backgroundColor = NAVY;
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.cornerRadius = 6;

// ---- Awards & expenditure by intake year (bar + line, dual axis) ----
const byYear = <?= json_encode($byYear) ?>;
new Chart(document.getElementById('chartYear'), {
  data: {
    labels: byYear.map(r => r.year),
    datasets: [
      { type: 'bar',  label: 'Awards', data: byYear.map(r => +r.n),
        backgroundColor: CYAN, borderRadius: 4, yAxisID: 'y' },
      { type: 'line', label: 'Expenditure (AUD)', data: byYear.map(r => +r.cost),
        borderColor: NAVY, backgroundColor: NAVY, tension: 0.3, yAxisID: 'y1' },
    ],
  },
  options: {
    responsive: true,
    interaction: { mode: 'index', intersect: false },
    scales: {
      y:  { beginAtZero: true, title: { display: true, text: 'Awards' }, ticks: { precision: 0 } },
      y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false },
            title: { display: true, text: 'AUD' },
            ticks: { callback: v => 'A$' + (v >= 1e6 ? (v/1e6)+'M' : v >= 1e3 ? (v/1e3)+'k' : v) } },
    },
  },
});

// ---- Scholarship status (doughnut) ----
const statuses = <?= json_encode($statuses) ?>;
new Chart(document.getElementById('chartStatus'), {
  type: 'doughnut',
  data: {
    labels: statuses.map(r => r.name),
    datasets: [{ data: statuses.map(r => +r.n), backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff' }],
  },
  options: { plugins: { legend: { position: 'bottom' } }, cutout: '62%' },
});

// ---- Gender breakdown (doughnut) ----
const gender = <?= json_encode($gender) ?>;
new Chart(document.getElementById('chartGender'), {
  type: 'doughnut',
  data: {
    labels: gender.map(r => r.gender),
    datasets: [{ data: gender.map(r => +r.n),
                 backgroundColor: gender.map(r => r.gender === 'Female' ? CYAN : NAVY),
                 borderWidth: 2, borderColor: '#fff' }],
  },
  options: { plugins: { legend: { position: 'bottom' } }, cutout: '62%' },
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>