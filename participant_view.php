<?php
$page_title = 'Person profile';
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flash('error', 'No participant specified.');
    redirect('participants.php');
}
?>

<!-- Back link -->
<div class="mb-3">
  <a href="participants.php" class="btn btn-light btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Back to participants
  </a>
</div>

<!-- Loading state -->
<div id="loadingState" class="text-center py-5 text-muted">
  <div class="spinner-border spinner-border-sm me-2"></div>
  Loading participant…
</div>

<!-- Main content (hidden until loaded) -->
<div id="mainContent" class="d-none">

  <!-- ── Hero banner ── -->
  <div class="hero" id="heroBanner">
    <div class="hero-avatar" id="heroAvatar"></div>
    <div class="hero-body">
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
      <h5><i class="bi bi-award"></i>Awards <span class="count-badge" id="awardsCount">0</span></h5>
      <a href="#" class="btn btn-primary btn-sm" id="addAwardBtn">
        <i class="bi bi-plus-lg"></i>Record new award
      </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-flush mb-0" id="awardsTable">
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

<!-- ============ Award edit popup ============ -->
<div class="modal fade" id="awardModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="awardForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit award</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
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
              <label class="field-label">Study start date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="study_start_date" required>
              <div class="form-text">The end date is recorded when the award is finalised.</div>
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
              <div class="form-text">To mark an award as completed, use <strong>Finalise</strong> on the Awards page.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-save-brand"><i class="bi bi-check-lg me-1"></i>Save award</button>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============ Edit participant popup ============ -->
<div class="modal fade" id="participantModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="participantForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit person</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
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
              <label class="field-label">Date of birth <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="date_of_birth" required>
            </div>
            <div class="col-md-6">
              <label class="field-label">Disability</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="disability_flag" id="disabilityFlag">
                <label class="form-check-label" for="disabilityFlag">Person has a disability</label>
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
              <label class="field-label">Home phone <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="home_phone" required>
            </div>
            <div class="col-md-6">
              <label class="field-label">Work phone</label>
              <input type="text" class="form-control" name="work_phone">
            </div>
            <div class="col-md-12">
              <label class="field-label">Email address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" name="email" required>
            </div>
          </div>

          <hr class="section-divider">
          <span class="section-label-sm"><i class="bi bi-geo-alt me-1"></i>Location &amp; Employment</span>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label">Province of residence <span class="text-danger">*</span></label>
              <select class="form-select" name="province_id" id="provinceSelect" required></select>
            </div>
            <div class="col-md-6">
              <label class="field-label">District of residence <span class="text-danger">*</span></label>
              <select class="form-select" name="district_id" id="districtSelect" required></select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Organisation <span class="text-muted fw-normal text-lowercase">(optional)</span></label>
              <input type="text" class="form-control" name="organisation" id="organisationInput"
                     list="organisationList" placeholder="Type the organisation name">
              <datalist id="organisationList"></datalist>
            </div>
            <div class="col-md-6">
              <label class="field-label">Employment category <span class="text-danger">*</span></label>
              <select class="form-select" name="employment_category_id" id="employmentSelect" required></select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-save-brand"><i class="bi bi-check-lg me-1"></i>Save</button>
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
  const genderHtml = p.gender
    ? `<span class="hero-pill">${esc(p.gender)}</span>`
    : '<span class="hero-muted">—</span>';

  const provinceTxt = p.province
    ? esc(p.province) + (p.district ? ', ' + esc(p.district) : '')
    : '<span class="hero-muted">Province not recorded</span>';

  document.getElementById('heroMeta').innerHTML = `
    <div class="hero-meta-item"><i class="bi bi-calendar3"></i><strong>${p.date_of_birth ? esc(p.date_of_birth) : '<span class="hero-muted">DOB unknown</span>'}</strong></div>
    <div class="hero-meta-item"><i class="bi bi-gender-ambiguous"></i>${genderHtml}</div>
    <div class="hero-meta-item"><i class="bi bi-geo-alt"></i><strong>${provinceTxt}</strong></div>
    ${Number(p.disability_flag) ? '<div class="hero-meta-item"><i class="bi bi-universal-access"></i><span class="hero-pill hero-pill-accent">Has disability</span></div>' : ''}`;
  // ── Hero actions ──
  document.getElementById('heroActions').innerHTML = `
    <button class="btn btn-hero-edit" onclick="openEdit()"><i class="bi bi-pencil"></i>Edit details</button>
    ${IS_ADMIN ? `<button class="btn btn-hero-del" onclick="deletePerson()"><i class="bi bi-trash"></i>Delete</button>` : ''}`;

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
          <td>${a.scheme ? `<span class="sc-badge sc-${esc(a.scheme)}">${esc(a.scheme)}</span>` : '<span class="empty-dash">—</span>'}</td>
          <td>${esc(a.main_course_name)}</td>
          <td>${esc(a.institution) || '—'}</td>
          <td>${esc(a.study_level) || '—'}</td>
          <td>${a.scholarship_cost ? 'A$ ' + Number(a.scholarship_cost).toLocaleString('en-AU',{minimumFractionDigits:2}) : '<span class="empty-dash">—</span>'}</td>
          <td><span class="st-badge st-${esc((a.status||'').replace(/ /g,'-'))}">${esc(a.status) || '—'}</span></td>
          <td>${esc(a.status) === 'On Scholarship'
              ? `<div class="row-actions"><button class="btn btn-edit-r" onclick="openAwardEdit(${a.id})"><i class="bi bi-pencil"></i> Edit</button></div>`
              : ''}</td>
        </tr>`).join('')
    : '<tr><td colspan="9" class="text-muted py-5 text-center">No awards recorded yet.</td></tr>';
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
  // "Completed" is set through the Finalise step on the Awards page, not here.
  for (const opt of document.getElementById('awardStatus').options) {
    if (opt.text.trim().toLowerCase() === 'completed') opt.disabled = true;
  }
  awardLookupsDone = true;
}

async function openAwardEdit(awardId) {
  await loadAwardLookups();
  const form = document.getElementById('awardForm');
  form.reset();
  setInvalid(form, {});

  const res = await api(`awards.php?action=get&id=${awardId}`);
  if (!res.success) return toast('error', res.message);
  fillForm(form, res.data);
  awardModal.show();
}

document.getElementById('awardForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const data = formData(form);

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
