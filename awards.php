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
  #awards-table thead th { font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#8A9DB0; font-weight:600; border-top:none; background:#FAFBFC; }
  #awards-table tbody td { font-size:13px; color:#334155; vertical-align:middle; }
  #awards-table tbody tr:hover { background:#F8FAFC; }

  /* Action pills */
  .btn-view   { background:#EFF6FF; color:#1D4ED8; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
  .btn-edit-r { background:#FFF7ED; color:#C2410C; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
  .btn-del    { background:#FEF2F2; color:#DC2626; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }

  /* Chips / badges */
  .id-chip { background:#EEF2F7; color:var(--secondary); padding:2px 8px; border-radius:5px; font-size:11px; font-weight:700; }
  .st-badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#F1F5F9; color:#475569; }
  .st-Active     { background:#DCFCE7; color:#15803D; }
  .st-Ongoing    { background:#DBEAFE; color:#1D4ED8; }
  .st-Completed  { background:#EDE9FE; color:#6D28D9; }
  .st-On-Hold    { background:#FEF3C7; color:#92400E; }
  .st-Suspended  { background:#FEF3C7; color:#92400E; }
  .st-Withdrawn  { background:#FEE2E2; color:#991B1B; }
  .st-Terminated { background:#FEE2E2; color:#991B1B; }

  /* Scheme badges */
  .sc-badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#F1F5F9; color:#475569; }
  .sc-SCA   { background:#E0F5F9; color:#0E7490; }
  .sc-HEP   { background:#EDE9FE; color:#6D28D9; }
  .sc-Other { background:#F1F5F9; color:#475569; }

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

<div class="page-title">Awards</div>
<div class="page-sub">View, add, edit and manage all short course records</div>

<!-- Filter Bar — single compact row -->
<div class="filter-bar">
  <div class="row g-2 align-items-end">
    <div class="col-md-5">
      <label>Search</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text" style="border-color:#DDE5EC;background:#FAFBFC;"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" id="tableSearch" placeholder="Search by code, participant, course…">
      </div>
    </div>
    <div class="col-md-3">
      <label>Scholarship Status</label>
      <select class="form-select form-select-sm" id="statusFilter">
        <option value="">All Statuses</option>
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
    <h5><i class="bi bi-award me-2" style="color:var(--primary)"></i>Short course records</h5>
    <button class="btn btn-primary-custom" onclick="openAwardForm()">
      <i class="bi bi-plus-lg me-1"></i> Add Short Course
    </button>
  </div>
  <div class="p-3">
    <div class="table-responsive">
      <table id="awards-table" class="table table-hover w-100 mb-0">
        <thead>
          <tr>
            <th>Code</th><th>AAPNG ID</th><th>Participant</th><th>Intake</th><th>Scheme</th>
            <th>Course</th><th>Institution</th><th>Status</th><th class="no-sort" style="width:170px;">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- ============ Add / Edit popup ============ -->
<div class="modal fade" id="awardModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <form id="awardForm" novalidate>
        <div class="modal-header modal-header-brand">
          <h5 class="modal-title" id="awardModalTitle"><i class="bi bi-plus-circle me-2"></i>Record new award</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="background:#FAFBFC;">
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
              <label class="field-label">Study start date</label>
              <input type="date" class="form-control" name="study_start_date">
            </div>
            <div class="col-md-6">
              <label class="field-label">Study end date</label>
              <input type="date" class="form-control" name="study_end_date">
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
            </div>
            <div class="col-md-6 d-none" id="costWrap">
              <label class="field-label">Scholarship cost (PGK) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text" style="border-color:#DDE5EC;background:#FAFBFC;">K</span>
                <input type="number" step="0.01" min="0" class="form-control" name="scholarship_cost" id="costInput">
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

<!-- ============ View popup ============ -->
<div class="modal fade" id="awardViewModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <div class="modal-header modal-header-brand">
        <h5 class="modal-title"><i class="bi bi-award me-2"></i>Award details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="awardViewBody" style="background:#FAFBFC;"></div>
      <div class="modal-footer" style="background:#F8FAFC;border-top:1px solid #EEF2F7;">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:7px;">Close</button>
        <button type="button" class="btn btn-save-brand" id="awardViewEditBtn" style="min-width:auto;"><i class="bi bi-pencil me-1"></i>Edit</button>
      </div>
    </div>
  </div>
</div>

<script>
let awardsTable, awardModal, awardViewModal;
let participants = [];   // {id, aapng_id, name, date_of_birth}
let currentAwardId = null;

document.addEventListener('DOMContentLoaded', async () => {
  awardModal = new bootstrap.Modal('#awardModal');
  awardViewModal = new bootstrap.Modal('#awardViewModal');

  const [iy, inst, lvl, fld, st, ppl] = await Promise.all([
    api('lookups.php?table=intake_years&action=options'),
    api('lookups.php?table=institutions&action=options'),
    api('lookups.php?table=study_levels&action=options'),
    api('lookups.php?table=fields_of_study&action=options'),
    api('lookups.php?table=scholarship_statuses&action=options'),
    api('participants.php?action=options'),
  ]);
  fillSelect(document.getElementById('intakeYearSelect'), iy.data || []);
  fillSelect(document.getElementById('institutionSelect'), inst.data || []);
  fillSelect(document.getElementById('levelSelect'), lvl.data || []);
  fillSelect(document.getElementById('fieldSelect'), fld.data || []);
  fillSelect(document.getElementById('statusSelect'), st.data || []);

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

  // Scholarship cost is only visible (and required) when status is "Completed".
  document.getElementById('statusSelect').addEventListener('change', toggleCostField);

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
          : '<span style="font-size:11px;color:#CBD5E1;">—</span>' },
      { data: 'main_course_name' },
      { data: 'institution' },
      { data: 'status', render: s => s
          ? `<span class="st-badge st-${esc(String(s).replace(/ /g, '-'))}">${esc(s)}</span>`
          : '<span style="font-size:11px;color:#CBD5E1;">—</span>' },
      { data: null, orderable: false, className: 'text-nowrap', render: row => `
          <div style="display:flex;gap:4px;">
            <button class="btn btn-view" onclick="viewAward(${row.id})"><i class="bi bi-eye"></i> View</button>
            <button class="btn btn-edit-r" onclick="openAwardForm(${row.id})"><i class="bi bi-pencil"></i> Edit</button>
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

function toggleCostField() {
  const sel  = document.getElementById('statusSelect');
  const wrap = document.getElementById('costWrap');
  const cost = document.getElementById('costInput');
  const name = sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex].text.trim() : '';
  const isCompleted = name.toLowerCase() === 'completed';

  wrap.classList.toggle('d-none', !isCompleted);
  cost.required = isCompleted;
  if (!isCompleted) {
    cost.value = '';
    cost.classList.remove('is-invalid');
  }
}

async function openAwardForm(id = null, preselectParticipant = null) {
  const form = document.getElementById('awardForm');
  form.reset();
  setInvalid(form, {});
  document.getElementById('awardModalTitle').innerHTML = id
    ? '<i class="bi bi-pencil me-2"></i>Edit award'
    : '<i class="bi bi-plus-circle me-2"></i>Record new award';
  form.elements.id.value = id || '';

  if (id) {
    const res = await api(`awards.php?action=get&id=${id}`);
    if (!res.success) return toast('error', res.message);
    fillForm(form, res.data);
    setParticipantField(res.data.participant_id, true);
  } else {
    setParticipantField(preselectParticipant, false);
  }
  toggleCostField();
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

  // Cost is mandatory when status is "Completed".
  const costInput = document.getElementById('costInput');
  if (costInput.required && costInput.value === '') {
    costInput.classList.add('is-invalid');
    return toast('warning', 'Scholarship cost is required when the status is Completed.');
  }
  costInput.classList.remove('is-invalid');

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

async function viewAward(id) {
  const res = await api(`awards.php?action=get&id=${id}`);
  if (!res.success) return toast('error', res.message);
  const a = res.data;
  currentAwardId = id;

  document.getElementById('awardViewBody').innerHTML = `
    <div class="mb-3">
      <h5 class="mb-1" style="color:var(--secondary);font-weight:800;">${esc(a.main_course_name)}</h5>
      <span class="id-chip">${esc(a.scholarship_code)}</span>
    </div>
    <dl class="row" style="font-size:13px;">
      <dt class="col-sm-4 field-label">Participant</dt><dd class="col-sm-8">${esc(a.participant)} (${esc(a.aapng_id)})</dd>
      <dt class="col-sm-4 field-label">Scheme</dt><dd class="col-sm-8">${a.scheme ? `<span class="sc-badge sc-${esc(a.scheme)}">${esc(a.scheme)}</span>` : '—'}</dd>
      <dt class="col-sm-4 field-label">Study start</dt><dd class="col-sm-8">${esc(a.study_start_date) || '—'}</dd>
      <dt class="col-sm-4 field-label">Study end</dt><dd class="col-sm-8">${esc(a.study_end_date) || '—'}</dd>
      <dt class="col-sm-4 field-label">Cost</dt><dd class="col-sm-8">${a.scholarship_cost ? fmtMoney(a.scholarship_cost) : '—'}</dd>
      <dt class="col-sm-4 field-label">Created</dt><dd class="col-sm-8">${esc(a.created_at)}</dd>
      <dt class="col-sm-4 field-label">Last updated</dt><dd class="col-sm-8">${esc(a.updated_at)}</dd>
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