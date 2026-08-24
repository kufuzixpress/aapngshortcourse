<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
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
  #users-table thead th { font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#8A9DB0; font-weight:600; border-top:none; background:#FAFBFC; }
  #users-table tbody td { font-size:13px; color:#334155; vertical-align:middle; }
  #users-table tbody tr:hover { background:#F8FAFC; }

  /* Action pills */
  .btn-edit-r  { background:#FFF7ED; color:#C2410C; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
  .btn-unlock  { background:#FEF9C3; color:#854D0E; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
  .btn-power   { background:#F1F5F9; color:#475569; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }
  .btn-del     { background:#FEF2F2; color:#DC2626; border:none; border-radius:5px; font-size:11px; font-weight:600; padding:4px 10px; }

  /* Role + status badges */
  .role-badge   { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
  .role-admin   { background:#EDE9FE; color:#6D28D9; }
  .role-staff   { background:#EEF2F7; color:var(--secondary); }
  .us-active    { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#DCFCE7; color:#15803D; }
  .us-inactive  { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#F1F5F9; color:#64748B; }
  .us-locked    { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#FEE2E2; color:#991B1B; }

  /* Modal */
  .field-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748B; display:block; margin-bottom:6px; }
  .modal .form-control, .modal .form-select { border-radius:7px; border-color:#DDE5EC; font-size:13px; }
  .modal .form-control:focus, .modal .form-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(60,182,206,.15); }
  .modal-header-brand { background:var(--secondary); color:#fff; }
  .btn-save-brand { background:var(--secondary); color:#fff; border-radius:7px; font-weight:600; min-width:120px; }
  .btn-save-brand:hover { background:#00263d; color:#fff; }
</style>

<div class="page-title">User management</div>
<div class="page-sub">Add staff accounts and manage roles, access and passwords</div>

<!-- Filter Bar -->
<div class="filter-bar">
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <label>Search</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text" style="border-color:#DDE5EC;background:#FAFBFC;"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" id="tableSearch" placeholder="Search by name, username, email…">
      </div>
    </div>
    <div class="col-md-3">
      <label>Role</label>
      <select class="form-select form-select-sm" id="roleFilter">
        <option value="">All Roles</option>
        <option value="Admin">Admin</option>
        <option value="Staff">Staff</option>
      </select>
    </div>
    <div class="col-md-3">
      <label>Status</label>
      <select class="form-select form-select-sm" id="statusFilter">
        <option value="">All Statuses</option>
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
        <option value="Locked">Locked</option>
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
    <h5><i class="bi bi-person-gear me-2" style="color:var(--primary)"></i>Users</h5>
    <button class="btn btn-primary-custom" onclick="openUserForm()">
      <i class="bi bi-person-plus me-1"></i> Add New
    </button>
  </div>
  <div class="p-3">
    <div class="table-responsive">
      <table id="users-table" class="table table-hover w-100 mb-0">
        <thead>
          <tr>
            <th>Full name</th><th>Username</th><th>Email</th><th>Role</th>
            <th>Status</th><th>Last login</th><th class="no-sort" style="width:190px;">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- ============ Add / Edit popup ============ -->
<div class="modal fade" id="userModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">
      <form id="userForm" novalidate>
        <div class="modal-header modal-header-brand">
          <h5 class="modal-title" id="userModalTitle"><i class="bi bi-person-plus me-2"></i>Add new user</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="background:#FAFBFC;">
          <input type="hidden" name="id">
          <div class="mb-3">
            <label class="field-label">Full name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="full_name" required>
          </div>
          <div class="mb-3">
            <label class="field-label">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="field-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="field-label">Role <span class="text-danger">*</span></label>
            <select class="form-select" name="role">
              <option value="staff">Staff — records only</option>
              <option value="admin">Admin — full access</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="field-label" id="passwordLabel">Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="password" minlength="8">
            <div class="form-text" id="passwordHint">Minimum 8 characters.</div>
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
let usersTable, userModal;

// Locked check shared by the status and actions columns.
function isLocked(row) {
  return row.locked_until && new Date(row.locked_until.replace(' ', 'T')) > new Date();
}
function userStatusText(row) {
  if (!Number(row.is_active)) return 'Inactive';
  if (isLocked(row)) return 'Locked';
  return 'Active';
}

document.addEventListener('DOMContentLoaded', () => {
  userModal = new bootstrap.Modal('#userModal');

  // DataTable. Default search box is hidden — the filter bar handles it.
  usersTable = new DataTable('#users-table', {
    ajax: { url: `${APP_URL}/api/users.php?action=list`, dataSrc: 'data' },
    layout: {
      topStart: null,
      topEnd: null,
    },
    columns: [
      { data: 'full_name' },
      { data: 'username' },
      { data: 'email' },
      { data: 'role', render: (r, type) => {
          const label = r === 'admin' ? 'Admin' : 'Staff';
          if (type !== 'display') return label;   // plain text for filtering/sorting
          return `<span class="role-badge role-${r === 'admin' ? 'admin' : 'staff'}">${label}</span>`; } },
      { data: null, render: (row, type) => {
          const s = userStatusText(row);
          if (type !== 'display') return s;       // plain text for filtering/sorting
          const cls = s === 'Active' ? 'us-active' : s === 'Locked' ? 'us-locked' : 'us-inactive';
          return `<span class="${cls}">${s}</span>`; } },
      { data: 'last_login', render: v => v || '—' },
      { data: null, orderable: false, className: 'text-nowrap', render: row => `
          <div style="display:flex;gap:4px;">
            <button class="btn btn-edit-r" onclick="openUserForm(${row.id})" title="Edit"><i class="bi bi-pencil"></i> Edit</button>
            ${isLocked(row) ? `<button class="btn btn-unlock" onclick="unlockUser(${row.id})" title="Unlock"><i class="bi bi-unlock"></i></button>` : ''}
            <button class="btn btn-power" onclick="toggleUser(${row.id})" title="${Number(row.is_active) ? 'Deactivate' : 'Activate'}"><i class="bi bi-power"></i></button>
            <button class="btn btn-del" onclick="deleteUser(${row.id})" title="Delete"><i class="bi bi-trash"></i></button>
          </div>` },
    ],
    order: [[0, 'asc']],
    language: { emptyTable: 'No users found.' },
  });

  // Wire the filter-bar search box to the DataTable global search.
  document.getElementById('tableSearch').addEventListener('input', (e) => {
    usersTable.search(e.target.value).draw();
  });

  // Role filter — exact match on the Role column (index 3).
  const roleFilter = document.getElementById('roleFilter');
  roleFilter.addEventListener('change', () => {
    usersTable.column(3)
      .search(roleFilter.value ? `^${roleFilter.value}$` : '', true, false)
      .draw();
  });

  // Status filter — exact match on the Status column (index 4).
  const statusFilter = document.getElementById('statusFilter');
  statusFilter.addEventListener('change', () => {
    usersTable.column(4)
      .search(statusFilter.value ? `^${statusFilter.value}$` : '', true, false)
      .draw();
  });

  // Clear filters.
  document.getElementById('clearFilters').addEventListener('click', () => {
    document.getElementById('tableSearch').value = '';
    roleFilter.value = '';
    statusFilter.value = '';
    usersTable.search('').column(3).search('').column(4).search('').draw();
  });
});

async function openUserForm(id = null) {
  const form = document.getElementById('userForm');
  form.reset();
  setInvalid(form, {});
  form.elements.id.value = id || '';
  document.getElementById('userModalTitle').innerHTML = id
    ? '<i class="bi bi-pencil me-2"></i>Edit user'
    : '<i class="bi bi-person-plus me-2"></i>Add new user';
  document.getElementById('passwordLabel').innerHTML = id ? 'New password' : 'Password <span class="text-danger">*</span>';
  document.getElementById('passwordHint').textContent = id
    ? 'Leave blank to keep the current password. Filling this resets the user\'s password.'
    : 'Minimum 8 characters.';
  form.elements.password.required = !id;

  if (id) {
    const res = await api(`users.php?action=get&id=${id}`);
    if (!res.success) return toast('error', res.message);
    fillForm(form, res.data);
    form.elements.password.value = '';
  }
  userModal.show();
}

document.getElementById('userForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const res = await api('users.php?action=save', formData(e.target));
  if (res.success) {
    toast('success', res.message);
    userModal.hide();
    usersTable.ajax.reload(null, false);
    return;
  }
  if (res.data?.errors) setInvalid(e.target, res.data.errors);
  toast('warning', res.message);
});

async function unlockUser(id) {
  const res = await api('users.php?action=unlock', { id });
  toast(res.success ? 'success' : 'error', res.message);
  if (res.success) usersTable.ajax.reload(null, false);
}

async function toggleUser(id) {
  const res = await api('users.php?action=toggle_active', { id });
  toast(res.success ? 'success' : 'error', res.message);
  if (res.success) usersTable.ajax.reload(null, false);
}

async function deleteUser(id) {
  if (!confirmDelete('Delete this user account? If they have created records, deactivate instead.')) return;
  const res = await api('users.php?action=delete', { id });
  toast(res.success ? 'success' : 'error', res.message);
  if (res.success) usersTable.ajax.reload(null, false);
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>