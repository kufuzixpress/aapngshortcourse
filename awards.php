<?php
$page_title = 'Awards';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <span class="page-eyebrow">Records</span>
    <h1 class="page-title">Awards</h1>
    <p class="page-sub">Short course awards for every participant — intake, course, institution and status.</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-light" onclick="openBulkFinalise()">
      <i class="bi bi-check2-all"></i>Finalise in bulk
    </button>
    <button type="button" class="btn btn-primary" onclick="openAwardForm()">
      <i class="bi bi-plus-lg"></i>Record new award
    </button>
  </div>
</div>

<div class="page-card">
  <div class="page-card-header">
    <div class="table-toolbar">
      <div class="search-field">
        <i class="bi bi-search"></i>
        <label class="visually-hidden" for="tableSearch">Search</label>
        <input type="search" class="form-control" id="tableSearch" placeholder="Search by code, participant or course…">
      </div>
      <div class="toolbar-filter">
        <label class="visually-hidden" for="statusFilter">Scholarship status</label>
        <select class="form-select" id="statusFilter">
          <option value="">All statuses</option>
        </select>
      </div>
      <button type="button" class="btn btn-light" id="clearFilters" title="Clear search and filters">
        <i class="bi bi-arrow-counterclockwise"></i><span class="d-none d-sm-inline">Reset</span>
      </button>
    </div>
  </div>
  <div class="table-responsive">
      <table id="awards-table" class="table table-hover table-flush w-100 mb-0">
        <thead>
          <tr>
            <th>Code</th><th>AAPNG ID</th><th>Participant</th><th>Intake</th><th>Scheme</th>
            <th>Course</th><th>Institution</th><th>Status</th><th class="no-sort" style="width:250px;">Actions</th>
          </tr>
        </thead>
      </table>
  </div>
</div>

<!-- ============ Add / Edit popup ============ -->
<div class="modal fade" id="awardModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="awardForm" novalidate>
        <div class="modal-header modal-header-brand">
          <h5 class="modal-title" id="awardModalTitle"><i class="bi bi-plus-circle me-2"></i>Record new award</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id">
          <input type="hidden" name="participant_id" id="participantId">

          <!-- ── Section: Participant ── -->
          <span class="section-label"><i class="bi bi-person me-1"></i> Participant</span>
          <div class="row g-3">
            <div class="col-md-12">
              <label class="field-label">Participant <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="participantSearch" list="participantList"
                     placeholder="Type AAPNG ID or name to search…" autocomplete="off">
              <datalist id="participantList"></datalist>
              <div class="form-text" id="participantHint">Pick the person this award belongs to. If they're not listed, add them under Personal details first.</div>
            </div>
          </div>

          <hr class="section-divider">

          <!-- ── Section: Course Details ── -->
          <span class="section-label"><i class="bi bi-mortarboard me-1"></i> Course Details</span>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label">Intake year <span class="text-danger">*</span></label>
              <select class="form-select" name="intake_year_id" id="intakeYearSelect" required></select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Institution <span class="text-danger">*</span></label>
              <select class="form-select" name="institution_id" id="institutionSelect" required></select>
            </div>
            <div class="col-md-12">
              <label class="field-label">Main course name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="main_course_name" required>
            </div>
            <div class="col-md-6">
              <label class="field-label">Level of study <span class="text-danger">*</span></label>
              <select class="form-select" name="study_level_id" id="levelSelect" required></select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Field of study <span class="text-danger">*</span></label>
              <select class="form-select" name="field_of_study_id" id="fieldSelect" required></select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Study start date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="study_start_date" required>
            </div>
            <div class="col-md-6">
              <label class="field-label">Planned end date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="planned_end_date" required>
              <div class="form-text">The actual end date is recorded when the award is finalised.</div>
            </div>
          </div>

          <hr class="section-divider">

          <!-- ── Section: Scholarship ── -->
          <span class="section-label"><i class="bi bi-cash-coin me-1"></i> Scholarship</span>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label">Scheme <span class="text-danger">*</span></label>
              <select class="form-select" name="scheme" id="schemeSelect" required>
                <option value="">— Select —</option>
                <option value="SCA">SCA</option>
                <option value="HEP">HEP</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Scholarship status <span class="text-danger">*</span></label>
              <select class="form-select" name="status_id" id="statusSelect" required></select>
              <div class="form-text" id="statusHint">Use <strong>Finalise</strong> on the awards list to mark an award as completed.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-save-brand"><i class="bi bi-check-lg me-1"></i>Save award</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============ Finalise popup ============ -->
<div class="modal fade" id="finaliseModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="finaliseForm" novalidate>
        <div class="modal-header modal-header-brand">
          <h5 class="modal-title" id="finaliseModalTitle"><i class="bi bi-flag me-2"></i>Finalise award</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id">
          <div class="finalise-summary" id="finaliseSummary"></div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label" for="finaliseEndDate">Actual end date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="study_end_date" id="finaliseEndDate" required>
            </div>
            <div class="col-md-6">
              <label class="field-label" for="finaliseCost">Scholarship cost (AUD) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">A$</span>
                <input type="number" step="0.01" min="0" class="form-control" name="scholarship_cost" id="finaliseCost" required>
              </div>
            </div>
          </div>

          <div class="alert alert-info d-flex gap-2 mt-3 mb-0 py-2 small">
            <i class="bi bi-info-circle"></i>
            <span id="finaliseNote">Status will change to <strong>Completed</strong>. After this, only an administrator can amend the end date or cost.</span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-save-brand" id="finaliseSubmit"><i class="bi bi-check2-circle me-1"></i>Finalise award</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============ Bulk finalise popup ============ -->
<style>
  /* Kept on this page so the list scrolls even if an older theme.css is cached. */
  #bulkModal .modal-content > form { display: flex; flex-direction: column; min-height: 0; max-height: 100%; overflow: hidden; }
  #bulkModal .modal-content > form > .modal-body { overflow-y: auto; }
  #bulkModal .bulk-list { max-height: 45vh; overflow: auto; border: 1px solid var(--border, #dee2e6); border-radius: 8px; }
  #bulkModal .bulk-list thead th { position: sticky; top: 0; z-index: 1; background: var(--surface, #fff); }
</style>
<div class="modal fade" id="bulkModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form id="bulkForm" novalidate>
        <div class="modal-header modal-header-brand">
          <h5 class="modal-title"><i class="bi bi-check2-all me-2"></i>Finalise awards in bulk</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Step 1: pick the group -->
          <span class="section-label"><i class="bi bi-funnel me-1"></i> Choose the course</span>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="field-label" for="bulkIntake">Intake year <span class="text-danger">*</span></label>
              <select class="form-select" id="bulkIntake"></select>
            </div>
            <div class="col-md-8">
              <label class="field-label" for="bulkCourse">Course name <span class="text-danger">*</span></label>
              <select class="form-select" id="bulkCourse" disabled>
                <option value="">Select an intake year first</option>
              </select>
            </div>
          </div>

          <!-- Step 2: dates, costs and which awards -->
          <div id="bulkStep2" class="d-none">
            <hr class="section-divider">
            <span class="section-label"><i class="bi bi-flag me-1"></i> Finalise details</span>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="field-label" for="bulkEndDate">Actual end date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="study_end_date" id="bulkEndDate" required>
                <div class="form-text">Applies to every ticked award.</div>
              </div>
              <div class="col-md-5">
                <label class="field-label" for="bulkCostAll">Cost per participant (AUD)</label>
                <div class="input-group">
                  <span class="input-group-text">A$</span>
                  <input type="number" step="0.01" min="0" class="form-control" id="bulkCostAll">
                  <button type="button" class="btn btn-light" id="bulkApplyCost">Apply to all</button>
                </div>
                <div class="form-text">Fills the cost for every ticked award. You can still change single rows below.</div>
              </div>
            </div>

            <!-- The list scrolls on its own, so the end date and the footer buttons stay in reach. -->
            <div class="bulk-list mt-3">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width:36px;"><input type="checkbox" class="form-check-input" id="bulkCheckAll" checked aria-label="Select all"></th>
                    <th>Code</th><th>Participant</th><th>Start</th><th>Planned end</th>
                    <th style="width:170px;">Cost (AUD) <span class="text-danger">*</span></th>
                  </tr>
                </thead>
                <tbody id="bulkRows"></tbody>
              </table>
            </div>
          </div>

          <div id="bulkEmpty" class="text-muted text-center py-4 d-none"></div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto" id="bulkSummary"></span>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-save-brand" id="bulkSubmit" disabled>
            <i class="bi bi-check2-all me-1"></i>Finalise selected
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============ View popup ============ -->
<div class="modal fade" id="awardViewModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header modal-header-brand">
        <h5 class="modal-title"><i class="bi bi-award me-2"></i>Award details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="awardViewBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-save-brand" id="awardViewEditBtn"><i class="bi bi-pencil me-1"></i>Edit</button>
      </div>
    </div>
  </div>
</div>

<script>
let awardsTable, awardModal, awardViewModal, finaliseModal, bulkModal;
let participants = [];   // {id, aapng_id, name, date_of_birth}
let currentAwardId = null;

document.addEventListener('DOMContentLoaded', async () => {
  awardModal = new bootstrap.Modal('#awardModal');
  awardViewModal = new bootstrap.Modal('#awardViewModal');
  finaliseModal = new bootstrap.Modal('#finaliseModal');
  bulkModal = new bootstrap.Modal('#bulkModal');

  const [iy, inst, lvl, fld, st, ppl] = await Promise.all([
    api('lookups.php?table=intake_years&action=options'),
    api('lookups.php?table=institutions&action=options'),
    api('lookups.php?table=study_levels&action=options'),
    api('lookups.php?table=fields_of_study&action=options'),
    api('lookups.php?table=scholarship_statuses&action=options'),
    api('participants.php?action=options'),
  ]);
  fillSelect(document.getElementById('intakeYearSelect'), iy.data || []);
  fillSelect(document.getElementById('bulkIntake'), iy.data || []);
  fillSelect(document.getElementById('institutionSelect'), inst.data || []);
  fillSelect(document.getElementById('levelSelect'), lvl.data || []);
  fillSelect(document.getElementById('fieldSelect'), fld.data || []);
  fillSelect(document.getElementById('statusSelect'), st.data || []);
  lockCompletedOption();

  // Status filter above the table (re-uses the same lookup data).
  const statusFilter = document.getElementById('statusFilter');
  statusFilter.innerHTML += (st.data || [])
    .map(s => `<option value="${esc(s.name)}">${esc(s.name)}</option>`).join('');

  participants = ppl.data || [];
  const dl = document.getElementById('participantList');
  dl.innerHTML = participants.map(p =>
    `<option value="${esc(p.aapng_id)} | ${esc(p.name)}"></option>`).join('');

  // Resolve typed/selected value to a participant id.
  document.getElementById('participantSearch').addEventListener('change', (e) => {
    const code = e.target.value.split('|')[0].trim();
    const match = participants.find(p => p.aapng_id === code);
    document.getElementById('participantId').value = match ? match.id : '';
    e.target.classList.toggle('is-invalid', !match && e.target.value !== '');
  });

  // DataTable. Default search box is hidden — the filter bar handles it.
  awardsTable = new DataTable('#awards-table', {
    ajax: { url: `${APP_URL}/api/awards.php?action=list`, dataSrc: 'data' },
    layout: {
      topStart: null,
      topEnd: null,
    },
    columns: [
      { data: 'scholarship_code', render: v => `<span class="id-chip">${esc(v)}</span>` },
      { data: 'aapng_id', visible: false },
      { data: 'participant' },
      { data: 'intake_year' },
      { data: 'scheme', render: v => v
          ? `<span class="sc-badge sc-${esc(v)}">${esc(v)}</span>`
          : '<span class="empty-dash">—</span>' },
      { data: 'main_course_name' },
      { data: 'institution' },
      { data: 'status', render: s => s
          ? `<span class="st-badge st-${esc(String(s).replace(/ /g, '-'))}">${esc(s)}</span>`
          : '<span class="empty-dash">—</span>' },
      { data: null, orderable: false, className: 'text-nowrap', render: row => `
          <div class="row-actions">
            <button class="btn btn-view" onclick="viewAward(${row.id})"><i class="bi bi-eye"></i> View</button>
            <button class="btn btn-edit-r" onclick="openAwardForm(${row.id})"><i class="bi bi-pencil"></i> Edit</button>
            ${finaliseButton(row)}
            ${IS_ADMIN ? `<button class="btn btn-del" onclick="deleteAward(${row.id})"><i class="bi bi-trash"></i> Del</button>` : ''}
          </div>`
      },
    ],
    order: [[0, 'desc']],
    pageLength: 25,
    language: { emptyTable: 'No awards found.' },
  });

  // Wire the filter-bar search box to the DataTable global search.
  document.getElementById('tableSearch').addEventListener('input', (e) => {
    awardsTable.search(e.target.value).draw();
  });

  // Status filter — exact match on the Status column (index 7, after adding Scheme).
  statusFilter.addEventListener('change', () => {
    applyStatusFilter(statusFilter.value);
  });

  function applyStatusFilter(val) {
    awardsTable.column(7)
      .search(val ? `^${val.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}$` : '', true, false)
      .draw();
  }

  // Clear filters.
  document.getElementById('clearFilters').addEventListener('click', () => {
    document.getElementById('tableSearch').value = '';
    statusFilter.value = '';
    awardsTable.search('').column(7).search('').draw();
  });

  // Status filter via ?status=, preselected participant via ?add=1&participant_id=
  const params = new URLSearchParams(window.location.search);
  if (params.get('status')) {
    statusFilter.value = params.get('status');
    applyStatusFilter(params.get('status'));
  }
  if (params.get('add')) openAwardForm(null, parseInt(params.get('participant_id') || 0));
});

function setParticipantField(participantId, locked) {
  const input = document.getElementById('participantSearch');
  const hidden = document.getElementById('participantId');
  const p = participants.find(x => x.id === Number(participantId));
  input.value = p ? `${p.aapng_id} | ${p.name}` : '';
  hidden.value = p ? p.id : '';
  input.readOnly = locked;   // lock the person when editing an existing award
}

const isCompleted = (status) => String(status || '').trim().toLowerCase() === 'completed';

/**
 * "Completed" can't be picked in the add/edit form — finalising is its own step.
 * The option stays in the list (disabled) so a finalised award still shows its status.
 */
function lockCompletedOption() {
  for (const opt of document.getElementById('statusSelect').options) {
    if (isCompleted(opt.text)) opt.disabled = true;
  }
}

/** Finalise for open awards; on finalised awards only admins get "Amend". */
function finaliseButton(row) {
  if (!isCompleted(row.status)) {
    return `<button class="btn btn-finalise" onclick="openFinalise(${row.id})"><i class="bi bi-flag"></i> Finalise</button>`;
  }
  return IS_ADMIN
    ? `<button class="btn btn-finalise is-done" onclick="openFinalise(${row.id})" title="Amend end date or cost"><i class="bi bi-check2-circle"></i> Amend</button>`
    : '';
}

async function openAwardForm(id = null, preselectParticipant = null) {
  const form = document.getElementById('awardForm');
  form.reset();
  setInvalid(form, {});
  document.getElementById('awardModalTitle').innerHTML = id
    ? '<i class="bi bi-pencil me-2"></i>Edit award'
    : '<i class="bi bi-plus-circle me-2"></i>Record new award';
  form.elements.id.value = id || '';

  const statusSelect = document.getElementById('statusSelect');
  let finalised = false;

  if (id) {
    const res = await api(`awards.php?action=get&id=${id}`);
    if (!res.success) return toast('error', res.message);
    fillForm(form, res.data);
    setParticipantField(res.data.participant_id, true);
    finalised = statusSelect.selectedIndex >= 0 && isCompleted(statusSelect.options[statusSelect.selectedIndex].text);
  } else {
    setParticipantField(preselectParticipant, false);
  }

  // A finalised award keeps its status; the server enforces the same rule.
  statusSelect.disabled = finalised;
  document.getElementById('statusHint').innerHTML = finalised
    ? 'This award is finalised. ' + (IS_ADMIN ? 'Use <strong>Amend</strong> to change the end date or cost.' : 'Ask an administrator to amend the end date or cost.')
    : 'Use <strong>Finalise</strong> on the awards list to mark an award as completed.';
  awardModal.show();
}

document.getElementById('awardForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const data = formData(form);

  if (!data.participant_id) {
    document.getElementById('participantSearch').classList.add('is-invalid');
    return toast('warning', 'Select a participant from the list.');
  }

  const res = await api('awards.php?action=save', data);
  if (res.success) {
    toast('success', res.message);
    awardModal.hide();
    awardsTable.ajax.reload(null, false);
    return;
  }
  if (res.data?.errors) setInvalid(form, res.data.errors);
  toast('warning', res.message);
});

async function openFinalise(id) {
  const form = document.getElementById('finaliseForm');
  form.reset();
  setInvalid(form, {});

  const res = await api(`awards.php?action=get&id=${id}`);
  if (!res.success) return toast('error', res.message);
  const a = res.data;
  const amending = isCompleted(a.status);

  form.elements.id.value = a.id;
  form.elements.study_end_date.value = a.study_end_date || a.planned_end_date || '';
  form.elements.scholarship_cost.value = a.scholarship_cost ?? '';

  // Suggest the planned end date; the actual one has to fall between the start date and today.
  const endInput = form.elements.study_end_date;
  endInput.min = a.study_start_date || '';
  endInput.max = new Date().toLocaleDateString('en-CA');   // en-CA formats as YYYY-MM-DD

  document.getElementById('finaliseSummary').innerHTML = `
    <div class="d-flex align-items-center gap-2 mb-1">
      <span class="id-chip">${esc(a.scholarship_code)}</span>
      <strong>${esc(a.participant)}</strong>
    </div>
    <div class="text-muted small">${esc(a.main_course_name)} · ${esc(a.study_start_date) || '—'} to ${esc(a.planned_end_date) || '—'} (planned)</div>`;

  document.getElementById('finaliseModalTitle').innerHTML = amending
    ? '<i class="bi bi-pencil-square me-2"></i>Amend finalised award'
    : '<i class="bi bi-flag me-2"></i>Finalise award';
  document.getElementById('finaliseNote').innerHTML = amending
    ? 'This award is already <strong>Completed</strong>. Your changes to the end date and cost will be saved.'
    : 'Status will change to <strong>Completed</strong>. After this, only an administrator can amend the end date or cost.';
  document.getElementById('finaliseSubmit').innerHTML = amending
    ? '<i class="bi bi-check-lg me-1"></i>Save changes'
    : '<i class="bi bi-check2-circle me-1"></i>Finalise award';

  finaliseModal.show();
}

document.getElementById('finaliseForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const data = formData(form);

  // Quick client-side check; the API validates the same rules.
  const errors = {};
  if (!data.study_end_date) errors.study_end_date = 'Study end date is required.';
  if (data.scholarship_cost === '' || data.scholarship_cost == null) errors.scholarship_cost = 'Scholarship cost is required.';
  setInvalid(form, errors);
  if (Object.keys(errors).length) return toast('warning', 'Enter the end date and cost to finalise.');

  const res = await api('awards.php?action=finalise', data);
  if (res.success) {
    toast('success', res.message);
    finaliseModal.hide();
    awardsTable.ajax.reload(null, false);
    return;
  }
  if (res.data?.errors) setInvalid(form, res.data.errors);
  toast('warning', res.message);
});

// ---------------------------------------------------------------
// Bulk finalise: one course in one intake year, shared end date, cost per award
// ---------------------------------------------------------------
const bulkTodayIso = () => new Date().toLocaleDateString('en-CA');   // YYYY-MM-DD

function openBulkFinalise() {
  document.getElementById('bulkForm').reset();
  document.getElementById('bulkIntake').value = '';
  resetBulkCourse('Select an intake year first');
  bulkModal.show();
}

function resetBulkCourse(placeholder) {
  const course = document.getElementById('bulkCourse');
  course.innerHTML = `<option value="">${esc(placeholder)}</option>`;
  course.disabled = true;
  showBulkAwards(null);
}

document.getElementById('bulkIntake').addEventListener('change', async (e) => {
  const intakeId = e.target.value;
  if (!intakeId) return resetBulkCourse('Select an intake year first');

  resetBulkCourse('Loading courses…');
  const res = await api(`awards.php?action=open_courses&intake_year_id=${encodeURIComponent(intakeId)}`);
  if (!res.success) return toast('error', res.message);

  const course = document.getElementById('bulkCourse');
  if (!res.data.length) {
    course.innerHTML = '<option value="">No open awards in this intake</option>';
    return;
  }
  course.innerHTML = '<option value="">— Select a course —</option>' + res.data.map(c =>
    `<option value="${esc(c.course)}">${esc(c.course)} (${Number(c.open_count)} open)</option>`).join('');
  course.disabled = false;
});

document.getElementById('bulkCourse').addEventListener('change', async (e) => {
  if (!e.target.value) return showBulkAwards(null);
  const intakeId = document.getElementById('bulkIntake').value;
  const res = await api(`awards.php?action=open_awards&intake_year_id=${encodeURIComponent(intakeId)}&course=${encodeURIComponent(e.target.value)}`);
  if (!res.success) return toast('error', res.message);
  showBulkAwards(res.data);
});

/** Renders step 2 for the given awards (null hides it). */
function showBulkAwards(awards) {
  const step2 = document.getElementById('bulkStep2');
  const empty = document.getElementById('bulkEmpty');
  step2.classList.toggle('d-none', !awards || !awards.length);
  empty.classList.toggle('d-none', !awards || awards.length > 0);
  empty.textContent = 'No open awards found for this course.';
  document.getElementById('bulkCheckAll').checked = true;
  document.getElementById('bulkCostAll').value = '';
  setInvalid(document.getElementById('bulkForm'), {});

  if (!awards || !awards.length) {
    document.getElementById('bulkRows').innerHTML = '';
    return updateBulkSummary();
  }

  document.getElementById('bulkRows').innerHTML = awards.map(a => `
    <tr data-id="${Number(a.id)}" data-start="${esc(a.study_start_date || '')}">
      <td><input type="checkbox" class="form-check-input bulk-pick" checked aria-label="Finalise ${esc(a.scholarship_code)}"></td>
      <td><span class="id-chip">${esc(a.scholarship_code)}</span></td>
      <td>${esc(a.participant)} <span class="text-muted small">${esc(a.aapng_id)}</span></td>
      <td>${esc(a.study_start_date) || '—'}</td>
      <td>${esc(a.planned_end_date) || '—'}</td>
      <td>
        <input type="number" step="0.01" min="0" class="form-control form-control-sm bulk-cost"
               value="${a.scholarship_cost != null ? esc(a.scholarship_cost) : ''}" aria-label="Cost for ${esc(a.scholarship_code)}">
        <div class="invalid-feedback"></div>
      </td>
    </tr>`).join('');

  // Suggest the most common planned end date as the actual end date.
  const counts = {};
  awards.forEach(a => { if (a.planned_end_date) counts[a.planned_end_date] = (counts[a.planned_end_date] || 0) + 1; });
  const suggested = Object.keys(counts).sort((x, y) => counts[y] - counts[x])[0] || '';
  const endInput = document.getElementById('bulkEndDate');
  endInput.value = suggested && suggested <= bulkTodayIso() ? suggested : '';
  endInput.max = bulkTodayIso();

  updateBulkSummary();
}

function bulkPickedRows() {
  return [...document.querySelectorAll('#bulkRows tr')].filter(tr => tr.querySelector('.bulk-pick').checked);
}

/** Keeps the footer count, the submit button and the end date's lower limit in step with the ticks. */
function updateBulkSummary() {
  const rows  = bulkPickedRows();
  const total = document.querySelectorAll('#bulkRows tr').length;
  document.getElementById('bulkSummary').textContent = total ? `${rows.length} of ${total} selected` : '';
  const btn = document.getElementById('bulkSubmit');
  btn.disabled = rows.length === 0;
  btn.innerHTML = `<i class="bi bi-check2-all me-1"></i>Finalise ${rows.length || ''} ${rows.length === 1 ? 'award' : 'awards'}`;

  // The end date can't be before the latest start date among the ticked awards.
  const latestStart = rows.map(tr => tr.dataset.start).filter(Boolean).sort().pop() || '';
  document.getElementById('bulkEndDate').min = latestStart;
  document.getElementById('bulkCheckAll').checked = total > 0 && rows.length === total;
}

document.getElementById('bulkRows').addEventListener('change', (e) => {
  if (e.target.classList.contains('bulk-pick')) updateBulkSummary();
});

document.getElementById('bulkCheckAll').addEventListener('change', (e) => {
  document.querySelectorAll('#bulkRows .bulk-pick').forEach(cb => { cb.checked = e.target.checked; });
  updateBulkSummary();
});

document.getElementById('bulkApplyCost').addEventListener('click', () => {
  const value = document.getElementById('bulkCostAll').value;
  if (value === '') return toast('warning', 'Enter a cost to apply.');
  bulkPickedRows().forEach(tr => {
    const input = tr.querySelector('.bulk-cost');
    input.value = value;
    input.classList.remove('is-invalid');
  });
});

function markBulkCost(tr, message) {
  const input = tr.querySelector('.bulk-cost');
  input.classList.toggle('is-invalid', !!message);
  input.nextElementSibling.textContent = message || '';
}

document.getElementById('bulkForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const rows = bulkPickedRows();
  const endDate = document.getElementById('bulkEndDate').value;
  if (!rows.length) return toast('warning', 'Tick at least one award to finalise.');

  // Quick client-side checks; the API validates the same rules.
  const errors = {};
  if (!endDate) errors.study_end_date = 'Study end date is required.';
  setInvalid(form, errors);

  const ids = [], costs = {};
  let missingCost = false;
  rows.forEach(tr => {
    const id = Number(tr.dataset.id);
    const cost = tr.querySelector('.bulk-cost').value;
    ids.push(id);
    costs[id] = cost;
    markBulkCost(tr, cost === '' ? 'Required' : '');
    if (cost === '') missingCost = true;
  });
  if (errors.study_end_date || missingCost) return toast('warning', 'Enter the end date and a cost for every ticked award.');

  const course = document.getElementById('bulkCourse').value;
  if (!confirm(`Mark ${ids.length} award(s) for "${course}" as Completed?\n\nAfter this, only an administrator can amend them.`)) return;

  const res = await api('awards.php?action=bulk_finalise', { ids, costs, study_end_date: endDate });
  if (res.success) {
    toast('success', res.message);
    bulkModal.hide();
    awardsTable.ajax.reload(null, false);
    return;
  }
  const errs = res.data?.errors || {};
  setInvalid(form, errs.study_end_date ? { study_end_date: errs.study_end_date } : {});
  rows.forEach(tr => markBulkCost(tr, errs.costs?.[tr.dataset.id] || ''));
  toast('warning', res.message);
});

async function viewAward(id) {
  const res = await api(`awards.php?action=get&id=${id}`);
  if (!res.success) return toast('error', res.message);
  const a = res.data;
  currentAwardId = id;

  document.getElementById('awardViewBody').innerHTML = `
    <div class="mb-3">
      <h3 class="view-title">${esc(a.main_course_name)}</h3>
      <span class="id-chip">${esc(a.scholarship_code)}</span>
    </div>
    <dl class="detail-list">
      <dt>Participant</dt><dd>${esc(a.participant)} (${esc(a.aapng_id)})</dd>
      <dt>Scheme</dt><dd>${a.scheme ? `<span class="sc-badge sc-${esc(a.scheme)}">${esc(a.scheme)}</span>` : '—'}</dd>
      <dt>Study start</dt><dd>${esc(a.study_start_date) || '—'}</dd>
      <dt>Status</dt><dd>${esc(a.status) || '—'}</dd>
      <dt>Planned end</dt><dd>${esc(a.planned_end_date) || '—'}</dd>
      <dt>Actual end</dt><dd>${a.study_end_date ? esc(a.study_end_date) : '<span class="text-muted">Not finalised yet</span>'}</dd>
      <dt>Cost</dt><dd>${a.scholarship_cost ? 'A$ ' + Number(a.scholarship_cost).toLocaleString('en-AU', { minimumFractionDigits: 2 }) : '—'}</dd>
      <dt>Created</dt><dd>${esc(a.created_at)}</dd>
      <dt>Last updated</dt><dd>${esc(a.updated_at)}</dd>
    </dl>`;
  awardViewModal.show();
}

document.getElementById('awardViewEditBtn').addEventListener('click', () => {
  awardViewModal.hide();
  openAwardForm(currentAwardId);
});

async function deleteAward(id) {
  if (!confirmDelete('Delete this award record? This cannot be undone.')) return;
  const res = await api('awards.php?action=delete', { id });
  toast(res.success ? 'success' : 'error', res.message);
  if (res.success) awardsTable.ajax.reload(null, false);
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
