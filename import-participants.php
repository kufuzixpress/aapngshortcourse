<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

// ---------------------------------------------------------------
// Downloadable CSV template (?template=1)
// ---------------------------------------------------------------
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="participants-import-template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['AAPNG ID','Given Names','Family Name','Gender','Date of Birth','Disability Flag',
                   'Disability type','Home Phone','Work Phone','Email address','Province of Residence',
                   'District of Residence','Organisation','Employment category']);
    fputcsv($out, ['', 'John', 'Kila', 'Male', '12/03/1985', 'No', '', '7123 4567', '',
                   'jkila@example.com', 'Morobe', 'Lae', 'Department of Health', 'Public sector']);
    fclose($out);
    exit;
}

// ---------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------

/** Normalise a header cell: lowercase letters/digits only. */
function norm_header(string $h): string {
    return preg_replace('/[^a-z0-9]/', '', strtolower($h));
}

/** Map of normalised header -> internal field name. */
function header_map(): array {
    return [
        'aapngid'              => 'aapng_id',
        'givennames'           => 'given_names',
        'givenname'            => 'given_names',
        'familyname'           => 'family_name',
        'surname'              => 'family_name',
        'gender'               => 'gender',
        'sex'                  => 'gender',
        'dateofbirth'          => 'dob',
        'dob'                  => 'dob',
        'disabilityflag'       => 'disability_flag',
        'disability'           => 'disability_flag',
        'disabilitytype'       => 'disability_type',
        'typeofdisability'     => 'disability_type',
        'homephone'            => 'home_phone',
        'workphone'            => 'work_phone',
        'emailaddress'         => 'email',
        'email'                => 'email',
        'provinceofresidence'  => 'province',
        'province'             => 'province',
        'districtofresidence'  => 'district',
        'district'             => 'district',
        'organisation'         => 'organisation',
        'organization'         => 'organisation',
        'employmentcategory'   => 'employment_category',
    ];
}

/** Parse a date in many common formats; returns Y-m-d or null.
 *  Numeric dates are read day-first (PNG convention): 5/3/1985 = 5 March. */
function parse_dob(string $s): ?string {
    $s = trim(str_replace("\xC2\xA0", ' ', $s));   // also strip non-breaking spaces
    if ($s === '') return null;
    $minY = 1900; $maxY = (int)date('Y');

    // Excel serial number (days since 1899-12-30), e.g. 31118 = 12/03/1985
    if (ctype_digit($s) && (int)$s >= 2000 && (int)$s <= 55000) {
        $d = (new DateTime('1899-12-30'))->modify('+' . (int)$s . ' days');
        $y = (int)$d->format('Y');
        return ($y >= $minY && $y <= $maxY) ? $d->format('Y-m-d') : null;
    }

    // Numeric day-first with / - or . separators, any padding, 2- or 4-digit year
    if (preg_match('~^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{2,4})$~', $s, $m)) {
        $day = (int)$m[1]; $mon = (int)$m[2]; $y = (int)$m[3];
        if ($y < 100) $y += ($y <= (int)date('y')) ? 2000 : 1900;   // 85 -> 1985, 02 -> 2002
        if ($y >= $minY && $y <= $maxY && checkdate($mon, $day, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $mon, $day);
        }
        return null;
    }

    // ISO year-first: 1985-03-12 or 1985/3/12
    if (preg_match('~^(\d{4})[/\-.](\d{1,2})[/\-.](\d{1,2})$~', $s, $m)) {
        $y = (int)$m[1]; $mon = (int)$m[2]; $day = (int)$m[3];
        if ($y >= $minY && $y <= $maxY && checkdate($mon, $day, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $mon, $day);
        }
        return null;
    }

    // Month-name formats: 12-Mar-1985, 12 March 85, March 12, 1985 ...
    $ts = strtotime($s);
    if ($ts !== false) {
        $y = (int)date('Y', $ts);
        if ($y >= $minY && $y <= $maxY) return date('Y-m-d', $ts);
    }
    return null;
}

/** Detect the delimiter from the first line of the file. */
function detect_delimiter(string $firstLine): string {
    $best = ','; $bestCount = 0;
    foreach ([',', ';', "\t"] as $d) {
        $c = substr_count($firstLine, $d);
        if ($c > $bestCount) { $best = $d; $bestCount = $c; }
    }
    return $best;
}

/** Find or (optionally) create a lookup item; returns id or null. Cache by lc name. */
function resolve_lookup(PDO $pdo, string $table, string $name, array &$cache, bool $create, array &$created): ?int {
    $key = strtolower(trim($name));
    if ($key === '') return null;
    if (!isset($cache[$table])) {
        $cache[$table] = [];
        foreach ($pdo->query("SELECT id, name FROM `$table`") as $r) {
            $cache[$table][strtolower(trim($r['name']))] = (int)$r['id'];
        }
    }
    if (isset($cache[$table][$key])) return $cache[$table][$key];
    if (!$create) return null;
    $pdo->prepare("INSERT INTO `$table` (name) VALUES (?)")->execute([trim($name)]);
    $id = (int)$pdo->lastInsertId();
    $cache[$table][$key] = $id;
    $created[] = "$table: " . trim($name);
    return $id;
}

/** Find or create a district under a province. */
function resolve_district(PDO $pdo, string $name, int $provinceId, array &$cache, array &$created): ?int {
    $key = $provinceId . '|' . strtolower(trim($name));
    if ($name === '' || !$provinceId) return null;
    if (!isset($cache['districts'])) {
        $cache['districts'] = [];
        foreach ($pdo->query('SELECT id, name, province_id FROM districts') as $r) {
            $cache['districts'][$r['province_id'] . '|' . strtolower(trim($r['name']))] = (int)$r['id'];
        }
    }
    if (isset($cache['districts'][$key])) return $cache['districts'][$key];
    $pdo->prepare('INSERT INTO districts (name, province_id) VALUES (?, ?)')->execute([trim($name), $provinceId]);
    $id = (int)$pdo->lastInsertId();
    $cache['districts'][$key] = $id;
    $created[] = 'districts: ' . trim($name);
    return $id;
}

/** Same 12-char ID generator used by the participants API. Missing DOB -> 000000. */
function import_generate_aapng_id(PDO $pdo, string $family, string $given, ?string $dob, array &$usedIds): string {
    $part = function (string $s): string {
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $s));
        return substr(str_pad($letters, 3, 'X'), 0, 3);
    };
    $base = $part($family) . $part($given);
    $ddmm = $dob ? date('dm', strtotime($dob)) : '0000';
    $candidates = [$dob ? date('dmy', strtotime($dob)) : '000000'];
    for ($i = 1; $i <= 99; $i++) $candidates[] = $ddmm . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
    foreach (range('A', 'Z') as $L) for ($d = 0; $d <= 9; $d++) $candidates[] = $ddmm . $L . $d;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM participants WHERE aapng_id = ?');
    foreach ($candidates as $suffix) {
        $code = $base . $suffix;
        if (isset($usedIds[$code])) continue;
        $stmt->execute([$code]);
        if (!$stmt->fetchColumn()) { $usedIds[$code] = true; return $code; }
    }
    throw new RuntimeException('Could not generate a unique AAPNG ID.');
}

// ---------------------------------------------------------------
// Process upload
// ---------------------------------------------------------------
$results = null;        // [['row'=>n,'status'=>'imported|skipped|error','name'=>..,'message'=>..],..]
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
            // Strip UTF-8 BOM and detect delimiter.
            $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
            $delim = detect_delimiter($firstLine);
            $headers = array_map('strval', str_getcsv(trim($firstLine), $delim));
            $map = header_map();

            // Build column index -> field map.
            $cols = [];
            foreach ($headers as $i => $h) {
                $n = norm_header($h);
                if (isset($map[$n])) $cols[$i] = $map[$n];
            }

            if (!in_array('given_names', $cols, true) || !in_array('family_name', $cols, true)) {
                flash('error', 'Could not find the Given Names / Family Name columns. Use the template headers.');
            } else {
                $results = [];
                $counts = ['imported' => 0, 'skipped' => 0, 'error' => 0];
                $cache = []; $usedIds = [];
                $uid = current_user()['id'];
                $rowNum = 1;

                // Existing IDs and name+DOB combos for duplicate skipping.
                $existingIds = [];
                foreach ($pdo->query('SELECT aapng_id FROM participants WHERE aapng_id IS NOT NULL') as $r) {
                    $existingIds[strtoupper($r['aapng_id'])] = true;
                }
                $existingNameDob = [];
                foreach ($pdo->query('SELECT given_names, family_name, date_of_birth FROM participants') as $r) {
                    $k = strtolower(preg_replace('/\s+/', '', $r['given_names'] . $r['family_name'])) . '|' . $r['date_of_birth'];
                    $existingNameDob[$k] = true;
                }

                $insert = $pdo->prepare(
                    'INSERT INTO participants
                        (aapng_id, given_names, family_name, gender, date_of_birth, disability_flag,
                         disability_type_id, home_phone, work_phone, email, province_id, district_id,
                         organisation_id, employment_category_id, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                );

                $pdo->beginTransaction();
                try {
                    while (($row = fgetcsv($fh, 0, $delim)) !== false) {
                        $rowNum++;
                        if (count(array_filter($row, fn($c) => trim((string)$c) !== '')) === 0) continue; // blank line

                        // Assemble field values.
                        $v = ['aapng_id'=>'','given_names'=>'','family_name'=>'','gender'=>'','dob'=>'',
                              'disability_flag'=>'','disability_type'=>'','home_phone'=>'','work_phone'=>'',
                              'email'=>'','province'=>'','district'=>'','organisation'=>'','employment_category'=>''];
                        foreach ($cols as $i => $field) $v[$field] = trim((string)($row[$i] ?? ''));

                        $name = $v['family_name'] . ', ' . $v['given_names'];

                        // ---- Validate ----
                        $errs = [];
                        if ($v['given_names'] === '') $errs[] = 'missing given names';
                        if ($v['family_name'] === '') $errs[] = 'missing family name';

                        $gender = ucfirst(strtolower($v['gender']));
                        if (in_array($gender, ['F','Female'], true)) $gender = 'Female';
                        elseif (in_array($gender, ['M','Male'], true)) $gender = 'Male';
                        else $errs[] = 'gender must be Female or Male';

                        $dob = $v['dob'] !== '' ? parse_dob($v['dob']) : null;
                        if ($v['dob'] !== '' && !$dob) $errs[] = 'invalid date of birth "' . $v['dob'] . '"';

                        $email = $v['email'] !== '' ? $v['email'] : null;

                        // Guard column lengths so one bad cell can't abort the whole import.
                        $limits = ['given_names' => 100, 'family_name' => 100, 'home_phone' => 100,
                                   'work_phone' => 100, 'email' => 150, 'organisation' => 200,
                                   'district' => 100, 'disability_type' => 100, 'employment_category' => 100];
                        foreach ($limits as $fld => $max) {
                            if (mb_strlen($v[$fld]) > $max) {
                                $errs[] = str_replace('_', ' ', $fld) . ' is too long (max ' . $max . ' characters)';
                            }
                        }

                        $flagRaw = strtolower($v['disability_flag']);
                        $disFlag = in_array($flagRaw, ['yes','y','1','true'], true) ? 1 : 0;

                        $provId = null; $distId = null;
                        if ($v['province'] !== '') {
                            $provId = resolve_lookup($pdo, 'provinces', $v['province'], $cache, false, $createdLookups);
                            if (!$provId) $errs[] = 'unknown province "' . $v['province'] . '"';
                        }
                        if ($provId && $v['district'] !== '') {
                            $distId = resolve_district($pdo, $v['district'], $provId, $cache, $createdLookups);
                        }
                        // Empty province/district is allowed — import the person and update later.

                        if ($errs) {
                            $counts['error']++;
                            $results[] = ['row'=>$rowNum, 'status'=>'error', 'name'=>$name, 'message'=>implode('; ', $errs)];
                            continue;
                        }

                        // ---- Duplicate skipping ----
                        $legacyId = strtoupper($v['aapng_id']);
                        if ($legacyId !== '' && (isset($existingIds[$legacyId]) || isset($usedIds[$legacyId]))) {
                            $counts['skipped']++;
                            $results[] = ['row'=>$rowNum, 'status'=>'skipped', 'name'=>$name,
                                          'message'=>"AAPNG ID $legacyId already exists"];
                            continue;
                        }
                        $ndKey = strtolower(preg_replace('/\s+/', '', $v['given_names'] . $v['family_name'])) . '|' . ($dob ?? '');
                        if (isset($existingNameDob[$ndKey])) {
                            $counts['skipped']++;
                            $results[] = ['row'=>$rowNum, 'status'=>'skipped', 'name'=>$name,
                                          'message'=>'a person with the same name and date of birth already exists'];
                            continue;
                        }

                        // ---- Resolve remaining lookups (auto-created if new) ----
                        $disTypeId = $disFlag && $v['disability_type'] !== ''
                            ? resolve_lookup($pdo, 'disability_types', $v['disability_type'], $cache, true, $createdLookups) : null;
                        $orgId = $v['organisation'] !== ''
                            ? resolve_lookup($pdo, 'organisations', $v['organisation'], $cache, true, $createdLookups) : null;
                        $empId = $v['employment_category'] !== ''
                            ? resolve_lookup($pdo, 'employment_categories', $v['employment_category'], $cache, true, $createdLookups) : null;

                        // ---- AAPNG ID: keep the legacy one if provided, else generate new format ----
                        $code = $legacyId !== '' ? $legacyId
                              : import_generate_aapng_id($pdo, $v['family_name'], $v['given_names'], $dob, $usedIds);
                        $usedIds[$code] = true;

                        $insert->execute([
                            $code, $v['given_names'], $v['family_name'], $gender, $dob, $disFlag,
                            $disTypeId, $v['home_phone'] ?: null, $v['work_phone'] ?: null, $email,
                            $provId, $distId, $orgId, $empId, $uid,
                        ]);
                        $existingNameDob[$ndKey] = true;

                        $counts['imported']++;
                        $results[] = ['row'=>$rowNum, 'status'=>'imported', 'name'=>$name, 'message'=>"ID $code"];
                    }

                    if ($dryRun) {
                        $pdo->rollBack();   // dry run: validate everything, save nothing
                    } else {
                        $pdo->commit();
                    }
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

<div class="page-title">Import personal details</div>
<div class="page-sub">Bulk-load participant records from a CSV file</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="page-card">
      <div class="page-card-header"><h5><i class="bi bi-upload me-2"></i>Upload CSV file</h5></div>
      <div class="p-3">
        <p class="text-muted" style="font-size:13px;">
          Export your current spreadsheet as <strong>CSV</strong> and upload it here.
          The columns should match your existing format —
          <a href="import-participants.php?template=1" style="color:var(--primary);font-weight:600;">download the template</a> to check.
        </p>
        <ul class="import-notes">
          <li>Rows with an <strong>AAPNG ID</strong> keep that ID; blank IDs get a new one generated (e.g. <code>KILJOH120385</code>).</li>
          <li>Rows whose ID, or name + date of birth, already exist are <strong>skipped</strong> — safe to re-run.</li>
          <li>Provinces must match the system list; new districts, organisations, disability types, and employment categories are created automatically.</li>
          <li>Dates accept <code>DD/MM/YYYY</code> or <code>YYYY-MM-DD</code>.</li>
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
            <thead><tr><th>Row</th><th>Person</th><th>Status</th><th>Detail</th></tr></thead>
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
        Upload a file to see the validation preview here. Start with a dry run — it checks every row
        and shows exactly what would happen before anything is saved.
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>