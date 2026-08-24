<?php
$page_title = 'Participant details';
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flash('error', 'No participant specified.');
    redirect('participants.php');
}
?>

<style>
  :root { --primary:#3CB6CE; --secondary:#003150; }

  /* ── Hero banner ─────────────────────────────────── */
  .hero {
    background: linear-gradient(135deg, var(--secondary) 0%, #00466e 100%);
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    overflow: hidden;
  }
  .hero::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(60,182,206,.08);
    pointer-events: none;
  }
  .hero-avatar {
    width: 56px; height: 56px; border-radius: 50%;
    background: rgba(60,182,206,.25);
    border: 2px solid rgba(60,182,206,.5);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; font-weight: 800; color: #fff;
    flex-shrink: 0;
  }
  .hero-name {
    font-size: 18px; font-weight: 800; color: #fff; line-height: 1.2;
  }
  .hero-id {
    background: rgba(60,182,206,.2);
    border: 1px solid rgba(60,182,206,.4);
    color: var(--primary);
    padding: 2px 10px; border-radius: 5px;
    font-size: 11px; font-weight: 700;
    display: inline-block; margin-top: 4px;
  }
  .hero-meta {
    display: flex; flex-wrap: wrap; gap: 16px;
    margin-top: 10px;
  }
  .hero-meta-item {
    display: flex; align-items: center; gap: 6px;
    font-size: 12px; color: rgba(255,255,255,.7);
  }
  .hero-meta-item i { color: var(--primary); font-size: 12px; }
  .hero-meta-item strong { color: #fff; }
  .hero-actions { margin-left: auto; display: flex; gap: 8px; flex-shrink: 0; }
  .btn-hero-edit {
    background: var(--primary); border: none; color: #fff;
    border-radius: 7px; font-size: 12px; font-weight: 600;
    padding: 7px 14px; cursor: pointer;
    transition: background .15s;
  }
  .btn-hero-edit:hover { background: #2a9ab0; color:#fff; }
  .btn-hero-del {
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
    color: rgba(255,255,255,.7); border-radius: 7px;
    font-size: 12px; font-weight: 600; padding: 7px 14px; cursor: pointer;
    transition: background .15s;
  }
  .btn-hero-del:hover { background: rgba(220,38,38,.3); color:#fff; border-color:rgba(220,38,38,.5); }

  /* ── Info strip below hero ───────────────────────── */
  .info-strip {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 10px; margin-bottom: 16px;
  }
  .info-card {
    background: #fff; border-radius: 10px;
    box-shadow: 0 1px 6px rgba(0,0,0,.05);
    padding: 12px 16px;
  }
  .info-card .info-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; color: #94A3B8; margin-bottom: 4px;
  }
  .info-card .info-val {
    font-size: 13px; font-weight: 600; color: var(--secondary);
  }
  .info-card .info-val.empty { color: #CBD5E1; font-weight: 400; font-style: italic; }

  /* ── Page card (awards) ──────────────────────────── */
  .page-card { background:#fff; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.05); }
  .page-card-header { display:flex; justify-content:space-between; align-items:center; padding:14px 18px; border-bottom:1px solid #EEF2F7; }
  .page-card-header h5 { margin:0; font-size:13px; font-weight:700; color:var(--secondary); text-transform:uppercase; letter-spacing:.05em; }
  .page-card-header h5 i { color:var(--primary); }

  /* ── Awards table ────────────────────────────────── */
  #awardsTable thead th { font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#8A9DB0; font-weight:600; border-top:none; background:#FAFBFC; }
  #awardsTable tbody td { font-size:13px; color:#334155; vertical-align:middle; }
  #awardsTable tbody tr:hover { background:#F8FAFC; }
  .code-chip { background:#EEF2F7; color:var(--secondary); padding:2px 8px; border-radius:5px; font-size:11px; font-weight:700; }
  .st-badge     { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#F1F5F9; color:#475569; white-space:nowrap; }
  .st-Active     { background:#DCFCE7; color:#15803D; }
  .st-Completed  { background:#EDE9FE; color:#6D28D9; }
  .st-Ongoing    { background:#DBEAFE; color:#1D4ED8; }
  .st-In-Progress { background:#DBEAFE; color:#1D4ED8; }
  .st-On-Hold    { background:#FEF3C7; color:#92400E; }
  .st-Withdrawn  { background:#FEE2E2; color:#991B1B; }
  .st-Terminated { background:#FEE2E2; color:#991B1B; }
  .sc-badge { padding:3px 8px; border-radius:20px; font-size:11px; font-weight:600; }
  .sc-SCA   { background:#E0F5F9; color:#0E7490; }
  .sc-HEP   { background:#EDE9FE; color:#6D28D9; }
  .sc-Other { background:#F1F5F9; color:#475569; }

  /* ── Action pills ────────────────────────────────── */
  .btn-view   { background:#EFF6FF; color:#1D4ED8; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
  .btn-edit-r { background:#FFF7ED; color:#C2410C; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
  .btn-primary-custom { background:var(--primary); border-color:var(--primary); color:#fff; border-radius:7px; font-weight:600; font-size:12px; }
  .btn-primary-custom:hover { background:#2A96AC; color:#fff; }

  /* ── Gender badge ───────────────────────────────── */
  .gender-Female { background:#FCE7F3; color:#9D174D; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:600; }
  .gender-Male   { background:#DBEAFE; color:#1D4ED8; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:600; }

  /* ── Edit modal ─────────────────────────────────── */
  .field-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748B; display:block; margin-bottom:6px; }
  .modal .form-control, .modal .form-select { border-radius:7px; border-color:#DDE5EC; font-size:13px; }
  .modal .form-control:focus, .modal .form-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(60,182,206,.15); }
  .section-divider { border:none; border-top:1px solid #EEF2F7; margin:18px 0 16px; }
  .section-label-sm { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#94A3B8; margin-bottom:12px; display:block; }
  .btn-save-brand { background:var(--secondary); color:#fff; border-radius:7px; font-weight:600; min-width:120px; }
  .btn-save-brand:hover { background:#00263d; color:#fff; }

  @media (max-width:768px) {
    .info-strip { grid-template-columns:1fr 1fr; }
    .hero { flex-wrap:wrap; }
    .hero-actions { margin-left:0; width:100%; }
  }
  @media (max-width:480px) {
    .info-strip { grid-template-columns:1fr; }
  }
</style>

<!-- Back link -->
<div class="mb-3">
  <a href="participants.php" class="btn btn-sm" style="background:#F1F5F9;color:#475569;border-radius:7px;font-size:13px;font-weight:600;">
    <i class="bi bi-arrow-left me-1"></i>Back to participants
  </a>
</div>

<!-- Loading state -->
<div id="loadingState" class="text-center py-5 text-muted" style="font-size:13px;">
  <div class="spinner-border spinner-border-sm me-2" style="color:var(--primary)"></div>
  Loading participant…
</div>

<!-- Main content (hidden until loaded) -->
<div id="mainContent" class="d-none">

  <!-- ── Hero banner ── -->
  <div class="hero" id="heroBanner">
    <div class="hero-avatar" id="heroAvatar"></div>
    <div style="flex:1;min-width:0;">
      <div class="hero-name" id="heroName"></div>
      <div id="heroId"></div>
      <div class="hero-meta" id="heroMeta"></div>
    </div>
    <div class="hero-actions" id="heroActions"></div>
  </div>

  <!-- ── Info strip ── -->
  <div class="info-strip" id="infoStrip"></div>

  <!-- ── Awards table ── -->
  <div class="page-card">
    <div class="page-card-header">
      <h5><i class="bi bi-award me-2"></i>Awards (<span id="awardsCount">0</span>)</h5>
      <a href="#" class="btn btn-primary-custom btn-sm" id="addAwardBtn">
        <i class="bi bi-plus-lg me-1"></i>Record new award
      </a>
    </div>
    <div class="p-3">
      <div class="table-responsive">
        <table class="table table-hover mb-0" id="awardsTable">
          <thead>
            <tr>
              <th>Code</th><th>Intake</th><th>Scheme</th><th>Course</th>
              <th>Institution</th><th>Level</th><th>Cost (AUD)</th><th>Status</th>
              <th class="no-sort" style="width:120px;">Actions</th>
            </tr>
          </thead>
          <tbody id="awardsBody"></tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- ============ Award edit popup ============ -->
<div class="modal fade" id="awardModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <form id="awardForm" novalidate>
        <div class="modal-header" style="background:var(--secondary);color:#fff;">
          <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit award</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="background:#FAFBFC;">
          <input type="hidden" name="id">
          <input type="hidden" name="participant_id" value="<?= $id ?>">

          <span class="section-label-sm"><i class="bi bi-mortarboard me-1"></i>Course Details</span>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label">Intake year <span class="text-danger">*</span></label>
              <select class="form-select" name="intake_year_id" id="awardIntakeYear" required></select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Institution <span class="text-danger">*</span></label>
              <select class="form-select" name="institution_id" id="awardInstitution" required></select>
            </div>
            <div class="col-md-12">
              <label class="field-label">Main course name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="main_course_name" required>
            </div>
            <div class="col-md-6">
              <label class="field-label">Level of study <span class="text-danger">*</span></label>
              <select class="form-select" name="study_level_id" id="awardLevel" required></select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Field of study <span class="text-danger">*</span></label>
              <select class="form-select" name="field_of_study_id" id="awardField" required></select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Study start date</label>
              <input type="date" class="form-control" name="study_start_date">
            </div>
            <div class="col-md-6">
              <label class="field-label">Study end date</label>
              <input type="date" class="form-control" name="study_end_date">
            </div>
          </div>

          <hr class="section-divider">
          <span class="section-label-sm"><i class="bi bi-cash-coin me-1"></i>Scholarship</span>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label">Scheme <span class="text-danger">*</span></label>
              <select class="form-select" name="scheme" id="awardScheme" required>
                <option value="">— Select —</option>
                <option value="SCA">SCA</option>
                <option value="HEP">HEP</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Scholarship status <span class="text-danger">*</span></label>
              <select class="form-select" name="status_id" id="awardStatus" required></select>
            </div>
            <div class="col-md-6 d-none" id="awardCostWrap">
              <label class="field-label">Scholarship cost (AUD) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text" style="border-color:#DDE5EC;background:#FAFBFC;">A$</span>
                <input type="number" step="0.01" min="0" class="form-control" name="scholarship_cost" id="awardCost">
              </div>
              <div class="form-text">Required when the scholarship is completed.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="background:#F8FAFC;border-top:1px solid #EEF2F7;">
          <button type="submit" class="btn btn-save-brand"><i class="bi bi-check-lg me-1"></i>Save award</button>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:7px;">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============ Edit participant popup ============ -->
<div class="modal fade" id="participantModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <form id="participantForm" novalidate>
        <div class="modal-header" style="background:var(--secondary);color:#fff;">
          <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit person</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="background:#FAFBFC;">
          <input type="hidden" name="id">

          <div id="dupWarning" class="alert alert-warning d-none">
            <strong>A similar person already exists:</strong>
            <ul id="dupList" class="mb-2"></ul>
            <div class="form-check mt-1">
              <input class="form-check-input" type="checkbox" id="forceSave">
              <label class="form-check-label" for="forceSave">This is a different person — save anyway</label>
            </div>
          </div>

          <span class="section-label-sm"><i class="bi bi-info-circle me-1"></i>Personal Information</span>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label">Given names <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="given_names" required>
            </div>
            <div class="col-md-6">
              <label class="field-label">Family name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="family_name" required>
            </div>
            <div class="col-md-6">
              <label class="field-label">Gender <span class="text-danger">*</span></label>
              <select class="form-select" name="gender" required>
                <option value="">-- Select --</option>
                <option>Female</option><option>Male</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Date of birth</label>
              <input type="date" class="form-control" name="date_of_birth">
            </div>
            <div class="col-md-6">
              <label class="field-label">Disability</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="disability_flag" id="disabilityFlag">
                <label class="form-check-label" for="disabilityFlag" style="font-size:13px;">Person has a disability</label>
              </div>
            </div>
            <div class="col-md-6">
              <label class="field-label">Disability type</label>
              <select class="form-select" name="disability_type_id" id="disabilityType" disabled></select>
            </div>
          </div>

          <hr class="section-divider">
          <span class="section-label-sm"><i class="bi bi-telephone me-1"></i>Contact Details</span>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label">Home phone</label>
              <input type="text" class="form-control" name="home_phone">
            </div>
            <div class="col-md-6">
              <label class="field-label">Work phone</label>
              <input type="text" class="form-control" name="work_phone">
            </div>
            <div class="col-md-12">
              <label class="field-label">Email address</label>
              <input type="text" class="form-control" name="email">
            </div>
          </div>

          <hr class="section-divider">
          <span class="section-label-sm"><i class="bi bi-geo-alt me-1"></i>Location &amp; Employment</span>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label">Province of residence</label>
              <select class="form-select" name="province_id" id="provinceSelect"></select>
            </div>
            <div class="col-md-6">
              <label class="field-label">District of residence</label>
              <select class="form-select" name="district_id" id="districtSelect"></select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Organisation <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="organisation" id="organisationInput"
                     list="organisationList" placeholder="Type the organisation name" required>
              <datalist id="organisationList"></datalist>
            </div>
            <div class="col-md-6">
              <label class="field-label">Employment category</label>
              <select class="form-select" name="employment_category_id" id="employmentSelect"></select>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="background:#F8FAFC;border-top:1px solid #EEF2F7;">
          <button type="submit" class="btn btn-save-brand"><i class="bi bi-check-lg me-1"></i>Save</button>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:7px;">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const PARTICIPANT_ID = <?= $id ?>;
let participantModal, awardModal, loadDistricts;
let awardLookupsDone = false;

document.addEventListener('DOMContentLoaded', async () => {
  participantModal = new bootstrap.Modal('#participantModal');
  awardModal       = new bootstrap.Modal('#awardModal');

  const [prov, dis, org, emp] = await Promise.all([
    api('lookups.php?table=provinces&action=options'),
    api('lookups.php?table=disability_types&action=options'),
    api('lookups.php?table=organisations&action=options'),
    api('lookups.php?table=employment_categories&action=options'),
  ]);
  fillSelect(document.getElementById('provinceSelect'), prov.data || []);
  fillSelect(document.getElementById('disabilityType'), dis.data || []);
  document.getElementById('organisationList').innerHTML =
    (org.data || []).map(o => `<option value="${esc(o.name)}"></option>`).join('');
  fillSelect(document.getElementById('employmentSelect'), emp.data || []);

  loadDistricts = bindDistrictCascade(
    document.getElementById('provinceSelect'),
    document.getElementById('districtSelect'));

  document.getElementById('disabilityFlag').addEventListener('change', function () {
    const type = document.getElementById('disabilityType');
    type.disabled = !this.checked;
    if (!this.checked) type.value = '';
  });

  // Award status → show/hide cost field
  document.getElementById('awardStatus').addEventListener('change', toggleAwardCost);

  await loadParticipant();
});

async function loadParticipant() {
  const res = await api(`participants.php?action=get&id=${PARTICIPANT_ID}`);
  if (!res.success) {
    document.getElementById('loadingState').innerHTML =
      `<div class="text-danger p-3"><i class="bi bi-exclamation-circle me-1"></i>${esc(res.message)}</div>`;
    return;
  }
  renderPage(res.data);
  document.getElementById('loadingState').classList.add('d-none');
  document.getElementById('mainContent').classList.remove('d-none');
}

function renderPage(p) {
  // ── Avatar & name ──
  const initials = ((p.given_names || '').charAt(0) + (p.family_name || '').charAt(0)).toUpperCase();
  document.getElementById('heroAvatar').textContent = initials;
  document.getElementById('heroName').textContent   = `${p.family_name}, ${p.given_names}`;
  document.getElementById('heroId').innerHTML       = `<span class="hero-id">${esc(p.aapng_id)}</span>`;

  // ── Hero meta row ──
  const genderBg    = p.gender === 'Female' ? 'rgba(252,231,243,.5)' : 'rgba(219,234,254,.5)';
  const genderColor = p.gender === 'Female' ? '#9D174D' : '#1D4ED8';
  const genderHtml  = p.gender
    ? `<span style="padding:1px 8px;border-radius:20px;font-size:11px;font-weight:600;background:${genderBg};color:${genderColor};">${esc(p.gender)}</span>`
    : '<span style="color:rgba(255,255,255,.4);">—</span>';

  const provinceTxt = p.province
    ? esc(p.province) + (p.district ? ', ' + esc(p.district) : '')
    : '<span style="color:rgba(255,255,255,.4);">Province not recorded</span>';

  document.getElementById('heroMeta').innerHTML = `
    <div class="hero-meta-item"><i class="bi bi-calendar3"></i><strong>${p.date_of_birth || '<span style="color:rgba(255,255,255,.4);">DOB unknown</span>'}</strong></div>
    <div class="hero-meta-item"><i class="bi bi-gender-ambiguous"></i>${genderHtml}</div>
    <div class="hero-meta-item"><i class="bi bi-geo-alt"></i><strong>${provinceTxt}</strong></div>
    ${Number(p.disability_flag) ? '<div class="hero-meta-item"><i class="bi bi-universal-access"></i><strong style="color:#FCD34D;">Has disability</strong></div>' : ''}`;
  // ── Hero actions ──
  document.getElementById('heroActions').innerHTML = `
    <button class="btn-hero-edit" onclick="openEdit()"><i class="bi bi-pencil me-1"></i>Edit</button>
    ${IS_ADMIN ? `<button class="btn-hero-del" onclick="deletePerson()"><i class="bi bi-trash me-1"></i>Delete</button>` : ''}`;

  // ── Info strip (4 cards) ──
  document.getElementById('infoStrip').innerHTML = [
    ['bi-telephone',       'Home phone',          p.home_phone           || null],
    ['bi-telephone-fill',  'Work phone',          p.work_phone           || null],
    ['bi-envelope',        'Email',               p.email                || null],
    ['bi-briefcase',       'Employment category', p.employment_category  || null],
  ].map(([icon, label, val]) => `
    <div class="info-card">
      <div class="info-label"><i class="bi ${icon} me-1"></i>${label}</div>
      <div class="info-val ${val ? '' : 'empty'}">${val ? esc(val) : 'Not recorded'}</div>
    </div>`).join('');

  // ── Awards ──
  document.getElementById('addAwardBtn').href =
    `${APP_URL}/awards.php?add=1&participant_id=${PARTICIPANT_ID}`;

  const awards = p.awards || [];
  document.getElementById('awardsCount').textContent = awards.length;
  document.getElementById('awardsBody').innerHTML = awards.length
    ? awards.map(a => `
        <tr>
          <td><span class="code-chip">${esc(a.scholarship_code)}</span></td>
          <td>${esc(a.intake_year) || '—'}</td>
          <td>${a.scheme ? `<span class="sc-badge sc-${esc(a.scheme)}">${esc(a.scheme)}</span>` : '<span style="color:#CBD5E1;">—</span>'}</td>
          <td>${esc(a.main_course_name)}</td>
          <td>${esc(a.institution) || '—'}</td>
          <td>${esc(a.study_level) || '—'}</td>
          <td>${a.scholarship_cost ? 'A$ ' + Number(a.scholarship_cost).toLocaleString('en-AU',{minimumFractionDigits:2}) : '<span style="color:#CBD5E1;">—</span>'}</td>
          <td><span class="st-badge st-${esc((a.status||'').replace(/ /g,'-'))}">${esc(a.status) || '—'}</span></td>
          <td>${esc(a.status) === 'On Scholarship'
              ? `<div style="display:flex;gap:4px;"><button class="btn btn-edit-r" onclick="openAwardEdit(${a.id})"><i class="bi bi-pencil"></i> Edit</button></div>`
              : ''}</td>
        </tr>`).join('')
    : '<tr><td colspan="9" class="text-muted p-3 text-center" style="font-size:13px;">No awards recorded yet.</td></tr>';
}

async function openEdit() {
  const form = document.getElementById('participantForm');
  form.reset(); setInvalid(form, {});
  document.getElementById('dupWarning').classList.add('d-none');
  document.getElementById('disabilityType').disabled = true;
  form.elements.id.value = PARTICIPANT_ID;

  const res = await api(`participants.php?action=get&id=${PARTICIPANT_ID}`);
  if (!res.success) return toast('error', res.message);
  fillForm(form, res.data);
  document.getElementById('disabilityType').disabled = !Number(res.data.disability_flag);
  await loadDistricts(res.data.province_id, res.data.district_id);
  participantModal.show();
}

document.getElementById('participantForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const data = formData(form);
  data.force = document.getElementById('forceSave').checked ? 1 : 0;

  const res = await api('participants.php?action=save', data);
  if (res.success) {
    toast('success', res.message);
    participantModal.hide();
    await loadParticipant();
    return;
  }
  if (res.data?.errors) setInvalid(form, res.data.errors);
  if (res.data?.duplicates) {
    const list = document.getElementById('dupList');
    list.innerHTML = res.data.duplicates.map(d =>
      `<li><strong>${esc(d.aapng_id)}</strong> — ${esc(d.family_name)}, ${esc(d.given_names)} (DOB ${esc(d.date_of_birth)})
       <span class="badge text-bg-warning">matched on: ${esc(d.matched_on)}</span></li>`).join('');
    document.getElementById('dupWarning').classList.remove('d-none');
    document.getElementById('dupWarning').scrollIntoView({ behavior: 'smooth' });
  }
  toast('warning', res.message);
});

async function loadAwardLookups() {
  if (awardLookupsDone) return;
  const [iy, inst, lvl, fld, st] = await Promise.all([
    api('lookups.php?table=intake_years&action=options'),
    api('lookups.php?table=institutions&action=options'),
    api('lookups.php?table=study_levels&action=options'),
    api('lookups.php?table=fields_of_study&action=options'),
    api('lookups.php?table=scholarship_statuses&action=options'),
  ]);
  fillSelect(document.getElementById('awardIntakeYear'), iy.data   || []);
  fillSelect(document.getElementById('awardInstitution'), inst.data || []);
  fillSelect(document.getElementById('awardLevel'),       lvl.data  || []);
  fillSelect(document.getElementById('awardField'),       fld.data  || []);
  fillSelect(document.getElementById('awardStatus'),      st.data   || []);
  awardLookupsDone = true;
}

function toggleAwardCost() {
  const sel  = document.getElementById('awardStatus');
  const wrap = document.getElementById('awardCostWrap');
  const cost = document.getElementById('awardCost');
  const name = sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex].text.trim() : '';
  const isCompleted = name.toLowerCase() === 'completed';
  wrap.classList.toggle('d-none', !isCompleted);
  cost.required = isCompleted;
  if (!isCompleted) { cost.value = ''; cost.classList.remove('is-invalid'); }
}

async function openAwardEdit(awardId) {
  await loadAwardLookups();
  const form = document.getElementById('awardForm');
  form.reset();
  setInvalid(form, {});

  const res = await api(`awards.php?action=get&id=${awardId}`);
  if (!res.success) return toast('error', res.message);
  fillForm(form, res.data);
  toggleAwardCost();
  awardModal.show();
}

document.getElementById('awardForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const data = formData(form);

  // Client-side cost validation
  const costInput = document.getElementById('awardCost');
  if (costInput.required && !costInput.value) {
    costInput.classList.add('is-invalid');
    return toast('warning', 'Scholarship cost is required when the status is Completed.');
  }
  costInput.classList.remove('is-invalid');

  const res = await api('awards.php?action=save', data);
  if (res.success) {
    toast('success', res.message);
    awardModal.hide();
    await loadParticipant();   // refresh awards table
    return;
  }
  if (res.data?.errors) setInvalid(form, res.data.errors);
  toast('warning', res.message);
});

async function deletePerson() {
  if (!confirmDelete('Delete this person and all their awards? This cannot be undone.')) return;
  const res = await api('participants.php?action=delete', { id: PARTICIPANT_ID });
  toast(res.success ? 'success' : 'error', res.message);
  if (res.success) setTimeout(() => window.location = `${APP_URL}/participants.php`, 1200);
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>