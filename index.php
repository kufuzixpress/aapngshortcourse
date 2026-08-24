<?php
$page_title = '';
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

<style>
  :root {
    --primary:   #3CB6CE;
    --secondary: #003150;
  }
  .page-title { font-size:22px; font-weight:800; color:var(--secondary); }
  .page-sub   { font-size:13px; color:#64748B; margin-bottom:16px; }

  /* Page card */
  .page-card { background:#fff; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.05); border:none; }
  .page-card-header { display:flex; justify-content:space-between; align-items:center; padding:14px 18px; border-bottom:1px solid #EEF2F7; }
  .page-card-header h5 { margin:0; font-size:13px; font-weight:700; color:var(--secondary); text-transform:uppercase; letter-spacing:.05em; }
  .page-card-header h5 i { color:var(--primary); }

  /* KPI cards */
  .kpi-card { border-radius:10px; padding:18px; color:#fff; position:relative; overflow:hidden; box-shadow:0 1px 6px rgba(0,0,0,.08); display:block; text-decoration:none; transition:transform .15s ease, box-shadow .15s ease; }
  .kpi-card:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,.14); color:#fff; }
  .kpi-card .kpi-value { font-size:26px; font-weight:800; line-height:1.1; }
  .kpi-card .kpi-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.08em; opacity:.85; margin-top:2px; }
  .kpi-card .kpi-icon  { position:absolute; right:14px; top:14px; font-size:34px; opacity:.25; }
  .kpi-card .kpi-link  { display:inline-flex; align-items:center; gap:4px; margin-top:12px; font-size:11px; font-weight:600; color:rgba(255,255,255,.85); }
  .kpi-navy  { background:linear-gradient(135deg, #003150 0%, #00263d 100%); }
  .kpi-teal  { background:linear-gradient(135deg, #3CB6CE 0%, #2a9ab0 100%); }
  .kpi-green { background:linear-gradient(135deg, #16A34A 0%, #15803D 100%); }

  /* Secondary stat strip */
  .stat-card { background:#fff; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.05); padding:12px 16px; display:flex; align-items:center; justify-content:space-between; height:100%; }
  .stat-card .stat-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:#64748B; }
  .stat-card .stat-value { font-size:19px; font-weight:800; color:var(--secondary); }
  .stat-card .stat-value small { font-size:12px; font-weight:500; color:#94A3B8; }
  .stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0; }

  /* Breakdown tables (province / institution / course) */
  .breakdown-scroll { max-height: 340px; overflow-y: auto; }
  .breakdown-table thead th { font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#8A9DB0; font-weight:600; border-top:none; background:#FAFBFC; position:sticky; top:0; z-index:1; }
  .breakdown-table tbody td { font-size:13px; color:#334155; vertical-align:middle; }
  .breakdown-table tbody tr:hover { background:#F8FAFC; }
  .breakdown-table tfoot td { font-size:12px; font-weight:700; color:var(--secondary); background:#FAFBFC; border-top:2px solid #EEF2F7; }
  .count-pill { display:inline-block; min-width:34px; text-align:center; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:700; }
  .pill-female  { background:#FCE7F3; color:#9D174D; }
  .pill-male    { background:#DBEAFE; color:#1D4ED8; }
  .pill-dis     { background:#DCFCE7; color:#15803D; }
  .pill-total   { background:#EEF2F7; color:var(--secondary); }

  /* Info banner */
  .info-banner {
    background: linear-gradient(135deg, #FFEDD5 0%, #FFE4C4 100%);
    border: 1px solid #FDBA74;
    border-left: 5px solid #F59E0B;
    border-radius: 10px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 500;
    color: #7C2D12;
    box-shadow: 0 2px 10px rgba(245,158,11,0.18);
  }
  .info-banner-icon-wrap {
    width: 34px; height: 34px; border-radius: 50%;
    background: #F59E0B;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(245,158,11,0.4);
  }
  .info-banner-icon { color: #fff; font-size: 17px; }
  .info-banner strong { color: #9A3412; font-weight: 800; }
</style>

<div class="page-title">Dashboard</div>
<div class="page-sub">Overview of participants, awards and expenditure</div>

<!-- ============ Info banner ============ -->
<div class="info-banner mb-3">
  <div class="info-banner-icon-wrap">
    <i class="bi bi-exclamation-triangle-fill info-banner-icon"></i>
  </div>
  <span>The number of <strong>participants</strong> will usually be less than the number of <strong>awards</strong> — the same person can attend more than one short course.</span>
</div>

<!-- ============ KPI cards ============ -->
<div class="row g-3 mb-3">
  <div class="col-lg-4 col-6">
    <a href="participants.php" class="kpi-card kpi-navy">
      <div class="kpi-value"><?= number_format($kpi['participants']) ?></div>
      <div class="kpi-label">Participants</div>
      <i class="kpi-icon bi bi-people"></i>
      <span class="kpi-link">View records <i class="bi bi-arrow-right-circle"></i></span>
    </a>
  </div>
  <div class="col-lg-4 col-6">
    <a href="awards.php" class="kpi-card kpi-teal">
      <div class="kpi-value"><?= number_format($kpi['awards']) ?></div>
      <div class="kpi-label">Awards</div>
      <i class="kpi-icon bi bi-award"></i>
      <span class="kpi-link">View records <i class="bi bi-arrow-right-circle"></i></span>
    </a>
  </div>
  <div class="col-lg-4 col-6">
    <a href="awards.php" class="kpi-card kpi-green">
      <div class="kpi-value"><?= fmt_aud($kpi['total_cost']) ?></div>
      <div class="kpi-label">Total expenditure (AUD)</div>
      <i class="kpi-icon bi bi-cash-stack"></i>
      <span class="kpi-link">View awards <i class="bi bi-arrow-right-circle"></i></span>
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
      <div class="stat-icon" style="background:#E0F5F9;color:#3CB6CE;"><i class="bi bi-gender-female"></i></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div>
        <span class="stat-label">Male participants</span>
        <div class="stat-value"><?= number_format($maleN) ?> <small>(<?= 100 - $femalePct ?>%)</small></div>
      </div>
      <div class="stat-icon" style="background:#E7EDF2;color:#003150;"><i class="bi bi-gender-male"></i></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div>
        <span class="stat-label">Participants with disability</span>
        <div class="stat-value"><?= number_format($kpi['disability']) ?></div>
      </div>
      <div class="stat-icon" style="background:#DCFCE7;color:#15803D;"><i class="bi bi-universal-access"></i></div>
    </div>
  </div>
</div>

<!-- ============ Charts row 1: awards by year + status ============ -->
<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="page-card h-100">
      <div class="page-card-header"><h5><i class="bi bi-bar-chart me-2"></i>Awards &amp; expenditure by intake year</h5></div>
      <div class="p-3"><canvas id="chartYear" height="110"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="page-card h-100">
      <div class="page-card-header"><h5><i class="bi bi-flag me-2"></i>Scholarship status</h5></div>
      <div class="p-3 d-flex align-items-center justify-content-center"><canvas id="chartStatus" style="max-height:260px"></canvas></div>
    </div>
  </div>
</div>

<!-- ============ Row 2: gender + province table ============ -->
<div class="row g-3 mb-3">
  <div class="col-lg-3">
    <div class="page-card h-100">
      <div class="page-card-header"><h5><i class="bi bi-people me-2"></i>Gender breakdown</h5></div>
      <div class="p-3 d-flex align-items-center justify-content-center"><canvas id="chartGender" style="max-height:240px"></canvas></div>
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
const NAVY = '#003150', CYAN = '#3CB6CE';
const PALETTE = ['#003150', '#3CB6CE', '#16A34A', '#F59E0B', '#DC2626', '#64748B', '#6D28D9', '#FD7E14'];

Chart.defaults.color = '#64748B';
Chart.defaults.borderColor = '#EEF2F7';
Chart.defaults.font.size = 11;

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