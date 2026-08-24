<?php
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

  /* Filter bar */
  .filter-bar { background:#fff; border-radius:10px; padding:10px 16px; margin-bottom:16px; box-shadow:0 1px 6px rgba(0,0,0,.05); }
  .filter-bar label { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#64748B; margin-bottom:3px; display:block; }
  .filter-bar .form-control, .filter-bar .form-select { border-radius:6px; font-size:12px; border-color:#DDE5EC; }
  .btn-clear-filter { background:#F1F5F9; color:#475569; border-radius:7px; font-size:13px; }

  /* Page card */
  .page-card { background:#fff; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.05); }
  .page-card-header { display:flex; justify-content:space-between; align-items:center; padding:14px 18px; border-bottom:1px solid #EEF2F7; }
  .page-card-header h5 { margin:0; font-size:15px; font-weight:700; color:var(--secondary); }
  .btn-primary-custom { background:var(--primary); border-color:var(--primary); color:#fff; border-radius:7px; font-weight:600; font-size:13px; }
  .btn-primary-custom:hover { background:#2A96AC; border-color:#2A96AC; color:#fff; }

  /* Table */
  #participants-table thead th { font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#8A9DB0; font-weight:600; border-top:none; background:#FAFBFC; }
  #participants-table tbody td { font-size:13px; color:#334155; vertical-align:middle; }
  #participants-table tbody tr:hover { background:#F8FAFC; }

  /* Action pills */
  .btn-view   { background:#EFF6FF; color:#1D4ED8; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
  .btn-edit-r { background:#FFF7ED; color:#C2410C; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
  .btn-del    { background:#FEF2F2; color:#DC2626; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }

  /* Chips / badges */
  .id-chip { background:#EEF2F7; color:var(--secondary); padding:2px 8px; border-radius:5px; font-size:11px; font-weight:700; }
  .gender-badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#F1F5F9; color:#475569; }
  .gender-Female { background:#FCE7F3; color:#9D174D; }
  .gender-Male   { background:#DBEAFE; color:#1D4ED8; }

  /* Modal */
  .field-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748B; display:block; margin-bottom:6px; }
  .modal .form-control, .modal .form-select { border-radius:7px; border-color:#DDE5EC; font-size:13px; }
  .modal .form-control:focus, .modal .form-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(60,182,206,.15); }
  .modal-header-brand { background:var(--secondary); color:#fff; }
  .section-divider { border:none; border-top:1px solid #EEF2F7; margin:18px 0 16px; }
  .section-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#94A3B8; margin-bottom:12px; display:block; }
  .btn-save-brand { background:var(--secondary); color:#fff; border-radius:7px; font-weight:600; min-width:140px; }
  .btn-save-brand:hover { background:#00263d; color:#fff; }
</style>

<div class="page-title">Participants</div>
<div class="page-sub">View, add, edit and manage all participant records</div>

<!-- Filter Bar — single compact row -->
<div class="filter-bar">
  <div class="row g-2 align-items-end">
    <div class="col-md-5">
      <label>Search</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text" style="border-color:#DDE5EC;background:#FAFBFC;"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" id="tableSearch" placeholder="Search by name, ID, district…">
      </div>
    </div>
    <div class="col-md-3">
      <label>Province</label>
      <select class="form-select form-select-sm" id="provinceFilter">
        <option value="">All Provinces</option>
      </select>
    </div>
    <div class="col-auto">
      <button type="button" class="btn btn-sm btn-clear-filter" id="clearFilters" title="Clear filters">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
  </div>
</div>

<!-- Table -->
<div class="page-card">
  <div class="page-card-header">
    <h5><i class="bi bi-people me-2" style="color:var(--primary)"></i>Participants</h5>
    <button class="btn btn-primary-custom" onclick="openParticipantForm()">
      <i class="bi bi-person-plus me-1"></i> Add New
    </button>
  </div>
  <div class="p-3">
    <div class="table-responsive">
      <table id="participants-table" class="table table-hover w-100 mb-0">
        <thead>
          <tr>
            <th>AAPNG ID</th><th>Family name</th><th>Given names</th><th>Gender</th>
            <th>Date of birth</th><th>Province</th><th>District</th><th class="no-sort" style="width:170px;">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- ============ Add / Edit popup ============ -->
<div class="modal fade" id="participantModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <form id="participantForm" novalidate>
        <div class="modal-header modal-header-brand">
          <h5 class="modal-title" id="participantModalTitle"><i class="bi bi-person-plus me-2"></i>Add new person</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="background:#FAFBFC;">
          <input type="hidden" name="id">

          <div id="dupWarning" class="alert alert-warning d-none">
            <strong>A similar person already exists:</strong>
            <ul id="dupList" class="mb-2"></ul>
            Check the list above before saving — the same person should only be recorded once.
            <div class="form-check mt-1">
              <input class="form-check-input" type="checkbox" id="forceSave">
              <label class="form-check-label" for="forceSave">This is a different person — save anyway</label>
            </div>
          </div>

          <!-- ── Section: Personal Info ── -->
          <span class="section-label"><i class="bi bi-info-circle me-1"></i> Personal Information</span>
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
                <option>Female</option>
                <option>Male</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Date of birth</label>
              <input type="date" class="form-control" name="date_of_birth">
              <div class="form-text">Leave blank if unknown.</div>
            </div>

            <div class="col-md-6">
              <label class="field-label d-block">Disability</label>
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

          <!-- ── Section: Contact ── -->
          <span class="section-label"><i class="bi bi-telephone me-1"></i> Contact Details</span>
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

          <!-- ── Section: Location & Work ── -->
          <span class="section-label"><i class="bi bi-geo-alt me-1"></i> Location &amp; Employment</span>
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
          <button type="submit" class="btn btn-save-brand"><i class="bi bi-check-lg me-1"></i>Save person</button>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:7px;">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============ View popup ============ -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <div class="modal-header modal-header-brand">
        <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Person details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewBody" style="background:#FAFBFC;"></div>
      <div class="modal-footer" style="background:#F8FAFC;border-top:1px solid #EEF2F7;">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:7px;">Close</button>
        <button type="button" class="btn btn-outline-brand" id="viewAddAwardBtn" style="border-radius:7px;">
          <i class="bi bi-award me-1"></i>Record new award
        </button>
        <button type="button" class="btn btn-save-brand" id="viewEditBtn" style="min-width:auto;">
          <i class="bi bi-pencil me-1"></i>Edit
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let table, lookups = {}, loadDistricts;
let participantModal, viewModal, currentViewId = null;

document.addEventListener('DOMContentLoaded', async () => {
  participantModal = new bootstrap.Modal('#participantModal');
  viewModal = new bootstrap.Modal('#viewModal');

  // Load dropdown options once.
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

  // Province filter above the table (re-uses the same lookup data).
  const provinceFilter = document.getElementById('provinceFilter');
  provinceFilter.innerHTML += (prov.data || [])
    .map(p => `<option value="${esc(p.name)}">${esc(p.name)}</option>`).join('');

  loadDistricts = bindDistrictCascade(
    document.getElementById('provinceSelect'),
    document.getElementById('districtSelect'));
  loadDistricts('');

  // Disability type only enabled when the flag is on.
  const flag = document.getElementById('disabilityFlag');
  const type = document.getElementById('disabilityType');
  flag.addEventListener('change', () => {
    type.disabled = !flag.checked;
    if (!flag.checked) type.value = '';
  });

  // DataTable. Default search box is hidden — the filter bar handles it.
  table = new DataTable('#participants-table', {
    ajax: { url: `${APP_URL}/api/participants.php?action=list`, dataSrc: 'data' },
    layout: {
      topStart: null,
      topEnd: null,
    },
    columns: [
      { data: 'aapng_id', visible: false, render: v => `<span class="id-chip">${esc(v)}</span>` },
      { data: 'family_name' },
      { data: 'given_names' },
      { data: 'gender', render: v => v
          ? `<span class="gender-badge gender-${esc(v)}">${esc(v)}</span>`
          : '<span style="font-size:11px;color:#CBD5E1;">—</span>' },
      { data: 'date_of_birth' },
      { data: 'province' },
      { data: 'district' },
      { data: null, orderable: false, className: 'text-nowrap', render: row => `
          <div style="display:flex;gap:4px;">
            <a href="${APP_URL}/participant_view.php?id=${row.id}" class="btn btn-view"><i class="bi bi-eye"></i> View</a>
            <button class="btn btn-edit-r" onclick="openParticipantForm(${row.id})"><i class="bi bi-pencil"></i> Edit</button>
            ${IS_ADMIN ? `<button class="btn btn-del" onclick="deleteParticipant(${row.id})"><i class="bi bi-trash"></i> Del</button>` : ''}
          </div>`
      },
    ],
    order: [[1, 'asc']],
    pageLength: 25,
    language: { emptyTable: 'No participants found.' },
  });

  // Wire the filter-bar search box to the DataTable global search.
  document.getElementById('tableSearch').addEventListener('input', (e) => {
    table.search(e.target.value).draw();
  });

  // Province filter — exact match on the Province column (index 5).
  provinceFilter.addEventListener('change', () => {
    const val = provinceFilter.value;
    table.column(5)
      .search(val ? `^${val.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}$` : '', true, false)
      .draw();
  });

  // Clear filters.
  document.getElementById('clearFilters').addEventListener('click', () => {
    document.getElementById('tableSearch').value = '';
    provinceFilter.value = '';
    table.search('').column(5).search('').draw();
  });
});

function resetDupWarning() {
  document.getElementById('dupWarning').classList.add('d-none');
  document.getElementById('forceSave').checked = false;
}

async function openParticipantForm(id = null) {
  const form = document.getElementById('participantForm');
  form.reset();
  setInvalid(form, {});
  resetDupWarning();
  document.getElementById('disabilityType').disabled = true;
  document.getElementById('participantModalTitle').innerHTML = id
    ? '<i class="bi bi-pencil me-2"></i>Edit person'
    : '<i class="bi bi-person-plus me-2"></i>Add new person';
  form.elements.id.value = id || '';

  if (id) {
    const res = await api(`participants.php?action=get&id=${id}`);
    if (!res.success) return toast('error', res.message);
    fillForm(form, res.data);
    document.getElementById('disabilityType').disabled = !Number(res.data.disability_flag);
    await loadDistricts(res.data.province_id, res.data.district_id);
  } else {
    loadDistricts('');
  }
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
    table.ajax.reload(null, false);
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

async function viewParticipant(id) {
  const res = await api(`participants.php?action=get&id=${id}`);
  if (!res.success) return toast('error', res.message);
  const p = res.data;
  currentViewId = id;

  const awardRows = (p.awards || []).map(a => `
    <tr><td>${esc(a.scholarship_code)}</td><td>${esc(a.intake_year)}</td>
        <td>${esc(a.main_course_name)}</td><td>${esc(a.institution)}</td>
        <td>${esc(a.study_level)}</td><td><span class="badge bg-secondary">${esc(a.status)}</span></td></tr>`).join('');

  document.getElementById('viewBody').innerHTML = `
    <div class="row mb-3">
      <div class="col-md-6">
        <h5 class="mb-1" style="color:var(--secondary);font-weight:800;">${esc(p.family_name)}, ${esc(p.given_names)}</h5>
        <span class="id-chip">${esc(p.aapng_id)}</span>
      </div>
    </div>
    <dl class="row" style="font-size:13px;">
      <dt class="col-sm-4 field-label">Gender</dt><dd class="col-sm-8">${esc(p.gender)}</dd>
      <dt class="col-sm-4 field-label">Date of birth</dt><dd class="col-sm-8">${esc(p.date_of_birth)}</dd>
      <dt class="col-sm-4 field-label">Disability</dt><dd class="col-sm-8">${Number(p.disability_flag) ? 'Yes' : 'No'}</dd>
      <dt class="col-sm-4 field-label">Home phone</dt><dd class="col-sm-8">${esc(p.home_phone) || '—'}</dd>
      <dt class="col-sm-4 field-label">Work phone</dt><dd class="col-sm-8">${esc(p.work_phone) || '—'}</dd>
      <dt class="col-sm-4 field-label">Email</dt><dd class="col-sm-8">${esc(p.email) || '—'}</dd>
    </dl>
    <hr class="section-divider">
    <span class="section-label"><i class="bi bi-award me-1"></i> Awards (${(p.awards || []).length})</span>
    <div class="table-responsive">
      <table class="table table-sm table-hover" id="viewAwardsTable">
        <thead><tr><th>Code</th><th>Intake</th><th>Course</th><th>Institution</th><th>Level</th><th>Status</th></tr></thead>
        <tbody>${awardRows || '<tr><td colspan="6" class="text-muted">No awards recorded yet.</td></tr>'}</tbody>
      </table>
    </div>`;
  viewModal.show();
}

document.getElementById('viewEditBtn').addEventListener('click', () => {
  viewModal.hide();
  openParticipantForm(currentViewId);
});

document.getElementById('viewAddAwardBtn').addEventListener('click', () => {
  window.location = `${APP_URL}/awards.php?add=1&participant_id=${currentViewId}`;
});

async function deleteParticipant(id) {
  if (!confirmDelete('Delete this person? This cannot be undone.')) return;
  const res = await api('participants.php?action=delete', { id });
  toast(res.success ? 'success' : 'error', res.message);
  if (res.success) table.ajax.reload(null, false);
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>