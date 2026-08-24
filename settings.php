<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
$page_title = '';
require_once __DIR__ . '/includes/header.php';

$lookups = [
    'provinces'             => ['label' => 'Provinces',             'icon' => 'bi-map'],
    'districts'             => ['label' => 'Districts',             'icon' => 'bi-geo-alt'],
    'disability_types'      => ['label' => 'Disability types',      'icon' => 'bi-universal-access'],
    'employment_categories' => ['label' => 'Employment categories', 'icon' => 'bi-briefcase'],
    'organisations'         => ['label' => 'Organisations',         'icon' => 'bi-building'],
    'institutions'          => ['label' => 'Institutions',          'icon' => 'bi-bank'],
    'study_levels'          => ['label' => 'Levels of study',       'icon' => 'bi-bar-chart-steps'],
    'fields_of_study'       => ['label' => 'Fields of study',       'icon' => 'bi-book'],
    'scholarship_statuses'  => ['label' => 'Scholarship statuses',  'icon' => 'bi-flag'],
    'intake_years'          => ['label' => 'Intake years',          'icon' => 'bi-calendar3'],
];

// Item counts per lookup table (keys above are a fixed whitelist, safe to interpolate).
$pdo = db();
$counts = [];
foreach (array_keys($lookups) as $t) {
    $counts[$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
}
?>

<style>
  :root {
    --primary:   #3CB6CE;
    --secondary: #003150;
  }
  .page-title { font-size:22px; font-weight:800; color:var(--secondary); }
  .page-sub   { font-size:13px; color:#64748B; margin-bottom:16px; max-width:720px; }

  /* Lookup tiles */
  .lookup-tile { background:#fff; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.05); padding:16px; display:flex; align-items:center; gap:14px; height:100%; transition:transform .15s ease, box-shadow .15s ease; }
  .lookup-tile:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,.10); }
  .lookup-tile .tile-icon { width:44px; height:44px; border-radius:10px; background:#E0F5F9; color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0; }
  .lookup-tile .tile-info { flex:1 1 auto; min-width:0; }
  .lookup-tile .tile-label { font-size:13px; font-weight:700; color:var(--secondary); }
  .lookup-tile .tile-count { font-size:11px; color:#94A3B8; font-weight:500; }
  .btn-manage { background:#EFF6FF; color:#1D4ED8; border:none; border-radius:6px; font-size:12px; font-weight:600; padding:5px 12px; white-space:nowrap; }
  .btn-manage:hover { background:#DBEAFE; color:#1D4ED8; }

  /* Modal */
  .modal-header-brand { background:var(--secondary); color:#fff; }
  .modal .form-control, .modal .form-select { border-radius:7px; border-color:#DDE5EC; font-size:13px; }
  .modal .form-control:focus, .modal .form-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(60,182,206,.15); }
  .btn-primary-custom { background:var(--primary); border-color:var(--primary); color:#fff; border-radius:7px; font-weight:600; font-size:13px; }
  .btn-primary-custom:hover { background:#2A96AC; border-color:#2A96AC; color:#fff; }

  /* Lookup table inside the modal */
  #lookupTable thead th { font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#8A9DB0; font-weight:600; border-top:none; background:#FAFBFC; position:sticky; top:0; z-index:1; }
  #lookupTable tbody td { font-size:13px; color:#334155; vertical-align:middle; }
  #lookupTable tbody tr:hover { background:#F8FAFC; }

  /* Status + action pills */
  .lk-active   { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#DCFCE7; color:#15803D; }
  .lk-inactive { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#F1F5F9; color:#64748B; }
  .btn-edit-r { background:#FFF7ED; color:#C2410C; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
  .btn-power  { background:#F1F5F9; color:#475569; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
  .btn-del    { background:#FEF2F2; color:#DC2626; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
</style>

<div class="page-title">System settings</div>
<div class="page-sub">These lists power the dropdowns staff see when entering records.
Items in use by existing records can't be deleted — deactivate them instead and they
disappear from dropdowns while remaining on old records.</div>

<div class="row g-3">
  <?php foreach ($lookups as $key => $l): ?>
  <div class="col-lg-4 col-sm-6">
    <div class="lookup-tile">
      <div class="tile-icon"><i class="bi <?= $l['icon'] ?>"></i></div>
      <div class="tile-info">
        <div class="tile-label"><?= e($l['label']) ?></div>
        <div class="tile-count"><?= number_format($counts[$key]) ?> item<?= $counts[$key] === 1 ? '' : 's' ?></div>
      </div>
      <button class="btn btn-manage" onclick="openLookup('<?= $key ?>', '<?= e($l['label']) ?>')">
        <i class="bi bi-sliders me-1"></i>Manage
      </button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ============ Lookup manager popup ============ -->
<div class="modal fade" id="lookupModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <div class="modal-header modal-header-brand">
        <h5 class="modal-title" id="lookupModalTitle"><i class="bi bi-sliders me-2"></i>Manage</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="background:#FAFBFC;">
        <form id="lookupForm" class="row g-2 mb-3">
          <input type="hidden" id="lookupItemId">
          <div class="col-md-4 d-none" id="lookupProvinceWrap">
            <select class="form-select" id="lookupProvince"></select>
          </div>
          <div class="col">
            <input type="text" class="form-control" id="lookupName" placeholder="New item name…" required>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary-custom" id="lookupSaveBtn"><i class="bi bi-plus-lg me-1"></i>Add</button>
            <button type="button" class="btn btn-light d-none" id="lookupCancelBtn" style="border-radius:7px;">Cancel</button>
          </div>
        </form>
        <div class="table-responsive" style="max-height:420px;overflow-y:auto;background:#fff;border-radius:8px;border:1px solid #EEF2F7;">
          <table class="table table-sm align-middle mb-0" id="lookupTable">
            <thead><tr id="lookupHeadRow"></tr></thead>
            <tbody id="lookupTableBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer" style="background:#F8FAFC;border-top:1px solid #EEF2F7;">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:7px;">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
let lookupModal, currentTable = null, currentLabel = '', currentItems = [];
const HAS_PROVINCE = { districts: true };
const IS_YEAR = { intake_years: true };

document.addEventListener('DOMContentLoaded', () => {
  lookupModal = new bootstrap.Modal('#lookupModal');
});

async function openLookup(table, label) {
  currentTable = table;
  currentLabel = label;
  document.getElementById('lookupModalTitle').innerHTML = '<i class="bi bi-sliders me-2"></i>Manage ' + esc(label.toLowerCase());
  document.getElementById('lookupName').placeholder = IS_YEAR[table] ? 'e.g. 2029' : 'New item name…';
  resetLookupForm();

  // Province select shown only for districts.
  const wrap = document.getElementById('lookupProvinceWrap');
  if (HAS_PROVINCE[table]) {
    wrap.classList.remove('d-none');
    const res = await api('lookups.php?table=provinces&action=options');
    fillSelect(document.getElementById('lookupProvince'), res.data || [], 'id', 'name', '-- Province --');
  } else {
    wrap.classList.add('d-none');
  }

  document.getElementById('lookupHeadRow').innerHTML = HAS_PROVINCE[table]
    ? '<th>Province</th><th>Name</th><th>Status</th><th class="text-end">Actions</th>'
    : '<th>Name</th><th>Status</th><th class="text-end">Actions</th>';

  await reloadLookup();
  lookupModal.show();
}

async function reloadLookup() {
  const res = await api(`lookups.php?table=${currentTable}&action=list`);
  currentItems = res.data || [];
  const body = document.getElementById('lookupTableBody');
  body.innerHTML = currentItems.map(item => {
    const status = Number(item.is_active)
      ? '<span class="lk-active">Active</span>'
      : '<span class="lk-inactive">Inactive</span>';
    const provCell = HAS_PROVINCE[currentTable] ? `<td>${esc(item.province)}</td>` : '';
    return `<tr>${provCell}<td>${esc(item.name)}</td><td>${status}</td>
      <td class="text-end text-nowrap">
        <button class="btn btn-edit-r" onclick="editLookupItem(${item.id})" title="Edit"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-power" onclick="toggleLookupItem(${item.id})" title="Activate/deactivate"><i class="bi bi-power"></i></button>
        <button class="btn btn-del" onclick="deleteLookupItem(${item.id})" title="Delete"><i class="bi bi-trash"></i></button>
      </td></tr>`;
  }).join('') || '<tr><td colspan="4" class="text-muted p-3">No items yet.</td></tr>';
}

function resetLookupForm() {
  document.getElementById('lookupItemId').value = '';
  document.getElementById('lookupName').value = '';
  document.getElementById('lookupSaveBtn').innerHTML = '<i class="bi bi-plus-lg me-1"></i>Add';
  document.getElementById('lookupCancelBtn').classList.add('d-none');
}

function editLookupItem(id) {
  const item = currentItems.find(i => i.id === id);
  if (!item) return;
  document.getElementById('lookupItemId').value = id;
  document.getElementById('lookupName').value = item.name;
  if (HAS_PROVINCE[currentTable]) document.getElementById('lookupProvince').value = item.province_id;
  document.getElementById('lookupSaveBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Update';
  document.getElementById('lookupCancelBtn').classList.remove('d-none');
  document.getElementById('lookupName').focus();
}

document.getElementById('lookupCancelBtn').addEventListener('click', resetLookupForm);

document.getElementById('lookupForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const payload = {
    id: document.getElementById('lookupItemId').value || 0,
    name: document.getElementById('lookupName').value.trim(),
  };
  if (HAS_PROVINCE[currentTable]) payload.province_id = document.getElementById('lookupProvince').value;

  const res = await api(`lookups.php?table=${currentTable}&action=save`, payload);
  toast(res.success ? 'success' : 'warning', res.message);
  if (res.success) {
    resetLookupForm();
    reloadLookup();
  }
});

async function toggleLookupItem(id) {
  const res = await api(`lookups.php?table=${currentTable}&action=toggle_active`, { id });
  toast(res.success ? 'success' : 'error', res.message);
  if (res.success) reloadLookup();
}

async function deleteLookupItem(id) {
  if (!confirmDelete('Delete this item? If it is used by records, deactivate instead.')) return;
  const res = await api(`lookups.php?table=${currentTable}&action=delete`, { id });
  toast(res.success ? 'success' : 'error', res.message);
  if (res.success) reloadLookup();
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>