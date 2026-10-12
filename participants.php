<?php
$page_title = 'Participants';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <span class="page-eyebrow">Records</span>
    <h1 class="page-title">Participants</h1>
    <p class="page-sub">Search, add and update the people who have taken part in short courses.</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" onclick="openParticipantForm()">
      <i class="bi bi-person-plus"></i>Add new person
    </button>
  </div>
</div>

<div class="page-card">
  <div class="page-card-header">
    <div class="table-toolbar">
      <div class="search-field">
        <i class="bi bi-search"></i>
        <label class="visually-hidden" for="tableSearch">Search</label>
        <input type="search" class="form-control" id="tableSearch" placeholder="Search by name, ID or district…">
      </div>
      <div class="toolbar-filter">
        <label class="visually-hidden" for="provinceFilter">Province</label>
        <select class="form-select" id="provinceFilter">
          <option value="">All provinces</option>
        </select>
      </div>
      <button type="button" class="btn btn-light" id="clearFilters" title="Clear search and filters">
        <i class="bi bi-arrow-counterclockwise"></i><span class="d-none d-sm-inline">Reset</span>
      </button>
    </div>
  </div>
  <div class="table-responsive">
      <table id="participants-table" class="table table-hover table-flush w-100 mb-0">
        <thead>
          <tr>
            <th>AAPNG ID</th><th>Family name</th><th>Given names</th><th>Gender</th>
            <th>Date of birth</th><th>Province</th><th>District</th><th class="no-sort" style="width:170px;">Actions</th>
          </tr>
        </thead>
      </table>
  </div>
</div>

<!-- ============ Add / Edit popup ============ -->
<div class="modal fade" id="participantModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="participantForm" novalidate>
        <div class="modal-header modal-header-brand">
          <h5 class="modal-title" id="participantModalTitle"><i class="bi bi-person-plus me-2"></i>Add new person</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
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
              <label class="field-label">Date of birth <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="date_of_birth" required>
                          </div>

            <div class="col-md-6">
              <label class="field-label d-block">Disability</label>
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

          <!-- ── Section: Contact ── -->
          <span class="section-label"><i class="bi bi-telephone me-1"></i> Contact Details</span>
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

          <!-- ── Section: Location & Work ── -->
          <span class="section-label"><i class="bi bi-geo-alt me-1"></i> Location &amp; Employment</span>
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
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-save-brand"><i class="bi bi-check-lg me-1"></i>Save person</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============ View popup ============ -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header modal-header-brand">
        <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Person details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="viewBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-outline-brand" id="viewAddAwardBtn">
          <i class="bi bi-award me-1"></i>Record new award
        </button>
        <button type="button" class="btn btn-save-brand" id="viewEditBtn">
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
          : '<span class="empty-dash">—</span>' },
      { data: 'date_of_birth' },
      { data: 'province' },
      { data: 'district' },
      { data: null, orderable: false, className: 'text-nowrap', render: row => `
          <div class="row-actions">
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
        <td>${esc(a.study_level)}</td><td><span class="st-badge st-${esc((a.status || '').replace(/ /g, '-'))}">${esc(a.status) || '—'}</span></td></tr>`).join('');

  document.getElementById('viewBody').innerHTML = `
    <div class="mb-4">
      <h3 class="view-title">${esc(p.family_name)}, ${esc(p.given_names)}</h3>
      <span class="id-chip">${esc(p.aapng_id)}</span>
    </div>
    <dl class="detail-list">
      <dt>Gender</dt><dd>${esc(p.gender)}</dd>
      <dt>Date of birth</dt><dd>${esc(p.date_of_birth)}</dd>
      <dt>Disability</dt><dd>${Number(p.disability_flag) ? 'Yes' : 'No'}</dd>
      <dt>Home phone</dt><dd>${esc(p.home_phone) || '—'}</dd>
      <dt>Work phone</dt><dd>${esc(p.work_phone) || '—'}</dd>
      <dt>Email</dt><dd>${esc(p.email) || '—'}</dd>
    </dl>
    <hr class="section-divider">
    <span class="section-label"><i class="bi bi-award"></i> Awards (${(p.awards || []).length})</span>
    <div class="table-responsive">
      <table class="table table-sm table-hover" id="viewAwardsTable">
        <thead><tr><th>Code</th><th>Intake</th><th>Course</th><th>Institution</th><th>Level</th><th>Status</th></tr></thead>
        <tbody>${awardRows || '<tr><td colspan="6" class="text-muted text-center py-4">No awards recorded yet.</td></tr>'}</tbody>
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
