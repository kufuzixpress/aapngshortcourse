<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
$page_title = 'System settings';
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

<div class="page-header">
  <div>
    <span class="page-eyebrow">Administration</span>
    <h1 class="page-title">System settings</h1>
    <p class="page-sub">These lists power the dropdowns staff see when entering records.
      Items already used by records can't be deleted — deactivate them instead and they
      disappear from dropdowns while staying on old records.</p>
  </div>
</div>

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
        <i class="bi bi-pencil-square"></i>Manage
      </button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ============ Lookup manager popup ============ -->
<div class="modal fade" id="lookupModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header modal-header-brand">
        <h5 class="modal-title" id="lookupModalTitle"><i class="bi bi-sliders me-2"></i>Manage</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="lookupForm" class="row g-2 mb-3">
          <input type="hidden" id="lookupItemId">
          <div class="col-md-4 d-none" id="lookupProvinceWrap">
            <select class="form-select" id="lookupProvince"></select>
          </div>
          <div class="col">
            <input type="text" class="form-control" id="lookupName" placeholder="New item name…" required>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary" id="lookupSaveBtn"><i class="bi bi-plus-lg"></i>Add</button>
            <button type="button" class="btn btn-light d-none" id="lookupCancelBtn">Cancel</button>
          </div>
        </form>
        <div class="table-responsive scroll-panel">
          <table class="table table-sm align-middle mb-0" id="lookupTable">
            <thead><tr id="lookupHeadRow"></tr></thead>
            <tbody id="lookupTableBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
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