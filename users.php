<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
$page_title = 'User management';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <span class="page-eyebrow">Administration</span>
    <h1 class="page-title">User management</h1>
    <p class="page-sub">Add staff accounts and manage roles, access and passwords.</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" onclick="openUserForm()">
      <i class="bi bi-person-plus"></i>Add new user
    </button>
  </div>
</div>

<div class="page-card">
  <div class="page-card-header">
    <div class="table-toolbar">
      <div class="search-field">
        <i class="bi bi-search"></i>
        <label class="visually-hidden" for="tableSearch">Search</label>
        <input type="search" class="form-control" id="tableSearch" placeholder="Search by name, username or email…">
      </div>
      <div class="toolbar-filter">
        <label class="visually-hidden" for="roleFilter">Role</label>
        <select class="form-select" id="roleFilter">
          <option value="">All roles</option>
          <option value="Admin">Admin</option>
          <option value="Staff">Staff</option>
        </select>
      </div>
      <div class="toolbar-filter">
        <label class="visually-hidden" for="statusFilter">Status</label>
        <select class="form-select" id="statusFilter">
          <option value="">All statuses</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
          <option value="Locked">Locked</option>
        </select>
      </div>
      <button type="button" class="btn btn-light" id="clearFilters" title="Clear search and filters">
        <i class="bi bi-arrow-counterclockwise"></i><span class="d-none d-sm-inline">Reset</span>
      </button>
    </div>
  </div>
  <div class="table-responsive">
      <table id="users-table" class="table table-hover table-flush w-100 mb-0">
        <thead>
          <tr>
            <th>Full name</th><th>Username</th><th>Email</th><th>Role</th>
            <th>Status</th><th>Last login</th><th class="no-sort" style="width:190px;">Actions</th>
          </tr>
        </thead>
      </table>
  </div>
</div>

<!-- ============ Add / Edit popup ============ -->
<div class="modal fade" id="userModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="userForm" novalidate>
        <div class="modal-header modal-header-brand">
          <h5 class="modal-title" id="userModalTitle"><i class="bi bi-person-plus me-2"></i>Add new user</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
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
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-save-brand"><i class="bi bi-check-lg me-1"></i>Save</button>
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
          <div class="row-actions">
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