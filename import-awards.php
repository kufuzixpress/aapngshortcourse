<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

// ---------------------------------------------------------------
// Downloadable CSV template (?template=1)
// ---------------------------------------------------------------
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="awards-import-template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['AAPNG ID','Scholarship code','Intake Year','Scheme','Institution name','Main Course Name',
                   'Level of study','Field of study','Study Start date','Study End date',
                   'Scholarship Status','Scholarship cost']);
    fputcsv($out, ['KILJOH120385', '', '2024', 'SCA', 'APTC', 'Certificate IV in Leadership',
                   'Short course', 'Business & Management', '05/02/2024', '28/06/2024', 'Completed', '15000']);
    fclose($out);
    exit;
}

// ---------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------
function norm_header(string $h): string {
    return preg_replace('/[^a-z0-9]/', '', strtolower($h));
}

function header_map(): array {
    return [
        'aapngid'           => 'aapng_id',
        'scholarshipcode'   => 'code',
        'intakeyear'        => 'intake_year',
        'intake'            => 'intake_year',
        'scheme'            => 'scheme',
        'scholarshipscheme' => 'scheme',
        'institutionname'   => 'institution',
        'institution'       => 'institution',
        'maincoursename'    => 'course',
        'coursename'        => 'course',
        'course'            => 'course',
        'levelofstudy'      => 'level',
        'studylevel'        => 'level',
        'fieldofstudy'      => 'field',
        'studystartdate'    => 'start_date',
        'startdate'         => 'start_date',
        'studyenddate'      => 'end_date',
        'enddate'           => 'end_date',
        'scholarshipstatus' => 'status',
        'status'            => 'status',
        'scholarshipcost'   => 'cost',
        'cost'              => 'cost',
    ];
}

/** Normalise a scheme value to SCA / HEP / Other. Returns null if unrecognised. */
function parse_scheme(string $s): ?string {
    $k = strtolower(trim($s));
    return ['sca' => 'SCA', 'hep' => 'HEP', 'other' => 'Other'][$k] ?? null;
}

/** Tolerant date parser (same rules as the participants import). */
function parse_date(string $s): ?string {
    $s = trim(str_replace("\xC2\xA0", ' ', $s));
    if ($s === '') return null;
    $minY = 1950; $maxY = (int)date('Y') + 10;   // future end dates are fine for awards

    if (ctype_digit($s) && (int)$s >= 2000 && (int)$s <= 65000) {
        $d = (new DateTime('1899-12-30'))->modify('+' . (int)$s . ' days');
        $y = (int)$d->format('Y');
        return ($y >= $minY && $y <= $maxY) ? $d->format('Y-m-d') : null;
    }
    if (preg_match('~^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{2,4})$~', $s, $m)) {
        $day = (int)$m[1]; $mon = (int)$m[2]; $y = (int)$m[3];
        if ($y < 100) $y += ($y <= (int)date('y') + 10) ? 2000 : 1900;
        if ($y >= $minY && $y <= $maxY && checkdate($mon, $day, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $mon, $day);
        }
        return null;
    }
    if (preg_match('~^(\d{4})[/\-.](\d{1,2})[/\-.](\d{1,2})$~', $s, $m)) {
        $y = (int)$m[1]; $mon = (int)$m[2]; $day = (int)$m[3];
        if ($y >= $minY && $y <= $maxY && checkdate($mon, $day, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $mon, $day);
        }
        return null;
    }
    $ts = strtotime($s);
    if ($ts !== false) {
        $y = (int)date('Y', $ts);
        if ($y >= $minY && $y <= $maxY) return date('Y-m-d', $ts);
    }
    return null;
}

/** Parse an AUD amount: strips A$, $, K, commas, spaces. Returns float or null. */
function parse_cost(string $s): ?float {
    $s = preg_replace('/(A\$|\$|[Kk,\s])/', '', trim($s));
    if ($s === '' || !is_numeric($s)) return null;
    $v = (float)$s;
    return $v >= 0 ? $v : null;
}

function detect_delimiter(string $firstLine): string {
    $best = ','; $bestCount = 0;
    foreach ([',', ';', "\t"] as $d) {
        $c = substr_count($firstLine, $d);
        if ($c > $bestCount) { $best = $d; $bestCount = $c; }
    }
    return $best;
}

function resolve_lookup(PDO $pdo, string $table, string $name, array &$cache, array &$created): ?int {
    $key = strtolower(trim($name));
    if ($key === '') return null;
    if (!isset($cache[$table])) {
        $cache[$table] = [];
        foreach ($pdo->query("SELECT id, name FROM `$table`") as $r) {
            $cache[$table][strtolower(trim($r['name']))] = (int)$r['id'];
        }
    }
    if (isset($cache[$table][$key])) return $cache[$table][$key];
    $pdo->prepare("INSERT INTO `$table` (name) VALUES (?)")->execute([trim($name)]);
    $id = (int)$pdo->lastInsertId();
    $cache[$table][$key] = $id;
    $created[] = "$table: " . trim($name);
    return $id;
}

function resolve_intake_year(PDO $pdo, string $raw, array &$cache, array &$created): ?int {
    $raw = trim($raw);
    if (!preg_match('/(\d{4})/', $raw, $m)) return null;
    $year = (int)$m[1];
    if ($year < 1990 || $year > 2100) return null;
    if (!isset($cache['intake_years'])) {
        $cache['intake_years'] = [];
        foreach ($pdo->query('SELECT id, year FROM intake_years') as $r) {
            $cache['intake_years'][(int)$r['year']] = (int)$r['id'];
        }
    }
    if (isset($cache['intake_years'][$year])) return $cache['intake_years'][$year];
    $pdo->prepare('INSERT INTO intake_years (year) VALUES (?)')->execute([$year]);
    $id = (int)$pdo->lastInsertId();
    $cache['intake_years'][$year] = $id;
    $created[] = 'intake_years: ' . $year;
    return $id;
}

// ---------------------------------------------------------------
// Process upload
// ---------------------------------------------------------------
$results = null;
$summary = null;
$createdLookups = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $dryRun = !empty($_POST['dry_run']);

    if (empty($_FILES['csv']['tmp_name']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Please choose a CSV file to upload.');
    } else {
        $pdo = db();
        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        $firstLine = fgets($fh);
        if ($firstLine === false) {
            flash('error', 'The uploaded file is empty.');
        } else {
            $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
            $delim = detect_delimiter($firstLine);
            $headers = array_map('strval', str_getcsv(trim($firstLine), $delim));
            $map = header_map();

            $cols = [];
            foreach ($headers as $i => $h) {
                $n = norm_header($h);
                if (isset($map[$n])) $cols[$i] = $map[$n];
            }

            if (!in_array('aapng_id', $cols, true)) {
                flash('error', 'Could not find the AAPNG ID column — it is required to link each award to its participant. Use the template headers.');
            } else {
                $results = [];
                $counts = ['imported' => 0, 'skipped' => 0, 'error' => 0];
                $cache = []; $usedCodes = [];
                $uid = current_user()['id'];
                $rowNum = 1;

                // Participants by AAPNG ID (the link key).
                $participants = [];
                foreach ($pdo->query('SELECT id, aapng_id FROM participants WHERE aapng_id IS NOT NULL') as $r) {
                    $participants[strtoupper(trim($r['aapng_id']))] = (int)$r['id'];
                }

                // Existing codes and participant+course+year combos for duplicate skipping.
                $existingCodes = [];
                foreach ($pdo->query('SELECT scholarship_code FROM scholarships WHERE scholarship_code IS NOT NULL') as $r) {
                    $existingCodes[strtoupper($r['scholarship_code'])] = true;
                }
                $existingCombos = [];
                foreach ($pdo->query('SELECT participant_id, main_course_name, intake_year_id FROM scholarships') as $r) {
                    $k = $r['participant_id'] . '|' . strtolower(trim($r['main_course_name'])) . '|' . ($r['intake_year_id'] ?? '');
                    $existingCombos[$k] = true;
                }

                // Highest existing per-year sequence for generated codes.
                $seqByYear = [];

                $insert = $pdo->prepare(
                    'INSERT INTO scholarships
                        (scholarship_code, participant_id, intake_year_id, scheme, institution_id, main_course_name,
                         study_level_id, field_of_study_id, study_start_date, study_end_date,
                         status_id, scholarship_cost, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
                );

                $pdo->beginTransaction();
                try {
                    while (($row = fgetcsv($fh, 0, $delim)) !== false) {
                        $rowNum++;
                        if (count(array_filter($row, fn($c) => trim((string)$c) !== '')) === 0) continue;

                        $v = ['aapng_id'=>'','code'=>'','intake_year'=>'','scheme'=>'','institution'=>'','course'=>'',
                              'level'=>'','field'=>'','start_date'=>'','end_date'=>'','status'=>'','cost'=>''];
                        foreach ($cols as $i => $field) $v[$field] = trim((string)($row[$i] ?? ''));

                        $label = $v['aapng_id'] . ($v['course'] !== '' ? ' — ' . $v['course'] : '');

                        // ---- Link to participant (required) ----
                        $errs = [];
                        $pid = null;
                        if ($v['aapng_id'] === '') {
                            $errs[] = 'missing AAPNG ID — cannot link the award to a person';
                        } else {
                            $pid = $participants[strtoupper($v['aapng_id'])] ?? null;
                            if (!$pid) $errs[] = 'no participant found with AAPNG ID "' . $v['aapng_id'] . '"';
                        }

                        // ---- Scheme (SCA / HEP / Other; blank stays blank) ----
                        $scheme = $v['scheme'] !== '' ? parse_scheme($v['scheme']) : null;
                        if ($v['scheme'] !== '' && $scheme === null) {
                            $errs[] = 'invalid scheme "' . $v['scheme'] . '" — must be SCA, HEP or Other';
                        }

                        // ---- Dates and cost ----
                        $start = $v['start_date'] !== '' ? parse_date($v['start_date']) : null;
                        if ($v['start_date'] !== '' && !$start) $errs[] = 'invalid start date "' . $v['start_date'] . '"';
                        $end = $v['end_date'] !== '' ? parse_date($v['end_date']) : null;
                        if ($v['end_date'] !== '' && !$end) $errs[] = 'invalid end date "' . $v['end_date'] . '"';
                        if ($start && $end && $end < $start) $errs[] = 'end date is before start date';

                        $cost = $v['cost'] !== '' ? parse_cost($v['cost']) : null;
                        if ($v['cost'] !== '' && $cost === null) $errs[] = 'invalid cost "' . $v['cost'] . '"';

                        if (mb_strlen($v['course']) > 255) $errs[] = 'course name is too long (max 255 characters)';
                        if (mb_strlen($v['code']) > 25)    $errs[] = 'scholarship code is too long (max 25 characters)';

                        if ($errs) {
                            $counts['error']++;
                            $results[] = ['row'=>$rowNum, 'status'=>'error', 'name'=>$label, 'message'=>implode('; ', $errs)];
                            continue;
                        }

                        // ---- Lookups (auto-created when new; blank stays blank) ----
                        $intakeId = $v['intake_year'] !== '' ? resolve_intake_year($pdo, $v['intake_year'], $cache, $createdLookups) : null;
                        if ($v['intake_year'] !== '' && !$intakeId) {
                            $counts['error']++;
                            $results[] = ['row'=>$rowNum, 'status'=>'error', 'name'=>$label,
                                          'message'=>'invalid intake year "' . $v['intake_year'] . '"'];
                            continue;
                        }
                        $instId   = $v['institution'] !== '' ? resolve_lookup($pdo, 'institutions', $v['institution'], $cache, $createdLookups) : null;
                        $levelId  = $v['level'] !== ''       ? resolve_lookup($pdo, 'study_levels', $v['level'], $cache, $createdLookups) : null;
                        $fieldId  = $v['field'] !== ''       ? resolve_lookup($pdo, 'fields_of_study', $v['field'], $cache, $createdLookups) : null;
                        $statusId = $v['status'] !== ''      ? resolve_lookup($pdo, 'scholarship_statuses', $v['status'], $cache, $createdLookups) : null;

                        // ---- Duplicate skipping ----
                        $legacyCode = strtoupper($v['code']);
                        if ($legacyCode !== '' && (isset($existingCodes[$legacyCode]) || isset($usedCodes[$legacyCode]))) {
                            $counts['skipped']++;
                            $results[] = ['row'=>$rowNum, 'status'=>'skipped', 'name'=>$label,
                                          'message'=>"scholarship code $legacyCode already exists"];
                            continue;
                        }
                        $combo = $pid . '|' . strtolower($v['course']) . '|' . ($intakeId ?? '');
                        if ($legacyCode === '' && isset($existingCombos[$combo])) {
                            $counts['skipped']++;
                            $results[] = ['row'=>$rowNum, 'status'=>'skipped', 'name'=>$label,
                                          'message'=>'this person already has this course for this intake year'];
                            continue;
                        }

                        // ---- Code: keep legacy, else generate SCH-{year}-{seq} ----
                        if ($legacyCode !== '') {
                            $code = $legacyCode;
                        } else {
                            $yr = $intakeId ? array_search($intakeId, $cache['intake_years'], true) : '0000';
                            if (!isset($seqByYear[$yr])) {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM scholarships s
                                                       LEFT JOIN intake_years iy ON iy.id = s.intake_year_id
                                                       WHERE COALESCE(iy.year, '0000') = ?");
                                $stmt->execute([$yr]);
                                $seqByYear[$yr] = (int)$stmt->fetchColumn();
                            }
                            do {
                                $seqByYear[$yr]++;
                                $code = 'SCH-' . $yr . '-' . str_pad((string)$seqByYear[$yr], 4, '0', STR_PAD_LEFT);
                            } while (isset($existingCodes[strtoupper($code)]) || isset($usedCodes[strtoupper($code)]));
                        }
                        $usedCodes[strtoupper($code)] = true;

                        $insert->execute([
                            $code, $pid, $intakeId, $scheme, $instId, $v['course'],
                            $levelId, $fieldId, $start, $end, $statusId, $cost, $uid,
                        ]);
                        $existingCombos[$combo] = true;

                        $counts['imported']++;
                        $results[] = ['row'=>$rowNum, 'status'=>'imported', 'name'=>$label, 'message'=>"code $code"];
                    }

                    if ($dryRun) $pdo->rollBack(); else $pdo->commit();
                    $summary = $counts + ['dry_run' => $dryRun];
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    flash('error', 'Import failed and nothing was saved: ' . $e->getMessage());
                    $results = null;
                }
            }
        }
        fclose($fh);
    }
}

$page_title = '';
require_once __DIR__ . '/includes/header.php';
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

  .btn-primary-custom { background:var(--primary); border-color:var(--primary); color:#fff; border-radius:7px; font-weight:600; font-size:13px; }
  .btn-primary-custom:hover { background:#2A96AC; border-color:#2A96AC; color:#fff; }

  .import-notes { font-size:12px; color:#64748B; padding-left:18px; }
  .import-notes li { margin-bottom:5px; }
  .import-notes code { background:#F1F5F9; color:#0E7490; padding:1px 5px; border-radius:4px; font-size:11px; }

  .form-control, .form-select { border-radius:7px; border-color:#DDE5EC; font-size:13px; }
  .form-control:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(60,182,206,.15); }

  /* Summary pills */
  .sum-pill { display:inline-block; padding:5px 14px; border-radius:20px; font-size:13px; font-weight:700; margin-right:6px; }
  .sum-imported { background:#DCFCE7; color:#15803D; }
  .sum-skipped  { background:#FEF3C7; color:#92400E; }
  .sum-error    { background:#FEE2E2; color:#991B1B; }

  /* Results table */
  #resultsTable thead th { font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#8A9DB0; font-weight:600; border-top:none; background:#FAFBFC; position:sticky; top:0; z-index:1; }
  #resultsTable tbody td { font-size:13px; color:#334155; vertical-align:middle; }
  #resultsTable tbody tr:hover { background:#F8FAFC; }
  .row-imported { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#DCFCE7; color:#15803D; }
  .row-skipped  { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#FEF3C7; color:#92400E; }
  .row-error    { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#FEE2E2; color:#991B1B; }
</style>

<div class="page-title">Import awards</div>
<div class="page-sub">Bulk-load award records from a CSV file, linked to participants by AAPNG ID</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="page-card">
      <div class="page-card-header"><h5><i class="bi bi-upload me-2"></i>Upload awards CSV</h5></div>
      <div class="p-3">
        <p class="text-muted" style="font-size:13px;">
          Each row must contain the person's <strong>AAPNG ID</strong> — that's how the award links to
          the participant. Import all participants first.
          <a href="import-awards.php?template=1" style="color:var(--primary);font-weight:600;">Download the template</a> to check the headers.
        </p>
        <ul class="import-notes">
          <li>Rows with a <strong>Scholarship code</strong> keep it; blank codes are generated (SCH-2024-0001).</li>
          <li>Rows whose code — or person + course + intake year — already exist are <strong>skipped</strong>, so re-runs are safe.</li>
          <li>An AAPNG ID that doesn't match any participant is an <strong>error</strong> — fix the ID or import that person first.</li>
          <li><strong>Scheme</strong> must be <code>SCA</code>, <code>HEP</code> or <code>Other</code> (any letter case); blank stays blank to update later.</li>
          <li>New institutions, levels, fields, and statuses are created automatically; blank values stay blank to update later.</li>
          <li>Costs are in AUD and accept formats like <code>15000</code>, <code>A$15,000</code>, <code>15 000.50</code>.</li>
        </ul>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <input type="file" name="csv" class="form-control" accept=".csv,text/csv" required>
          </div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="dry_run" id="dryRun" value="1" checked>
            <label class="form-check-label" for="dryRun" style="font-size:13px;">
              <strong>Dry run</strong> — validate and preview only, don't save anything yet
            </label>
          </div>
          <button type="submit" class="btn btn-primary-custom"><i class="bi bi-play-fill me-1"></i>Run import</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <?php if ($summary): ?>
    <div class="page-card">
      <div class="page-card-header">
        <h5>
          <i class="bi bi-clipboard-check me-2"></i>
          <?= $summary['dry_run'] ? 'Dry run results — nothing was saved' : 'Import results' ?>
        </h5>
      </div>
      <div class="p-3">
        <p class="mb-3">
          <span class="sum-pill sum-imported"><?= $summary['imported'] ?> <?= $summary['dry_run'] ? 'ready to import' : 'imported' ?></span>
          <span class="sum-pill sum-skipped"><?= $summary['skipped'] ?> skipped</span>
          <span class="sum-pill sum-error"><?= $summary['error'] ?> errors</span>
        </p>
        <?php if ($summary['dry_run'] && $summary['imported'] > 0): ?>
          <div class="alert alert-info py-2" style="font-size:13px;border-radius:8px;">Looks good? Untick <strong>Dry run</strong> and upload the same file again to import for real.</div>
        <?php endif; ?>
        <?php if (!$summary['dry_run'] && $createdLookups): ?>
          <div class="alert alert-secondary py-2" style="font-size:12px;border-radius:8px;">
            <strong>New lookup items created:</strong> <?= e(implode(' · ', array_unique($createdLookups))) ?>
          </div>
        <?php endif; ?>
        <div class="table-responsive" style="max-height:480px;overflow-y:auto;background:#fff;border-radius:8px;border:1px solid #EEF2F7;">
          <table class="table table-sm align-middle mb-0" id="resultsTable">
            <thead><tr><th>Row</th><th>Award</th><th>Status</th><th>Detail</th></tr></thead>
            <tbody>
              <?php foreach ($results as $r): ?>
              <tr>
                <td><?= $r['row'] ?></td>
                <td><?= e($r['name']) ?></td>
                <td><span class="row-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                <td style="font-size:12px;color:#64748B;"><?= e($r['message']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="page-card">
      <div class="p-4 text-muted" style="font-size:13px;">
        <i class="bi bi-info-circle me-1" style="color:var(--primary);"></i>
        Upload a file to see the validation preview here. 
        confirms each AAPNG ID matches a participant, and shows exactly what would happen.
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>