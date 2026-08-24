<?php
require_once __DIR__ . '/../includes/api.php';
api_require_admin();

$action = $_GET['action'] ?? 'list';

// ---------------------------------------------------------------
// GET actions
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if ($action === 'list') {
        $rows = db()->query(
            'SELECT id, username, email, full_name, role, is_active, failed_attempts,
                    locked_until, last_login, created_at,
                    ext_user_id
             FROM users ORDER BY full_name'
        )->fetchAll();
        json_out(true, $rows);
    }

    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = db()->prepare(
            'SELECT id, username, email, full_name, role, is_active, ext_user_id
             FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if (!$u) json_out(false, null, 'User not found.');
        json_out(true, $u);
    }

    json_out(false, null, 'Unknown action.');
}

// ---------------------------------------------------------------
// POST actions — require CSRF
// ---------------------------------------------------------------
api_verify_csrf();
$in  = api_input();
$me  = current_user()['id'];

if ($action === 'save') {
    $id       = (int)($in['id'] ?? 0);
    $username = trim($in['username'] ?? '');
    $email    = trim($in['email'] ?? '');
    $name     = trim($in['full_name'] ?? '');
    $role     = ($in['role'] ?? 'staff') === 'admin' ? 'admin' : 'staff';
    $password = $in['password'] ?? '';
    $errors   = [];

    if ($username === '') $errors['username'] = 'Username is required.';
    if ($name === '')     $errors['full_name'] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (!$id && strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    if ($id && $password !== '' && strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';

    // Uniqueness.
    $stmt = db()->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ?');
    $stmt->execute([$username, $email, $id]);
    if ($stmt->fetch()) $errors['username'] = 'Username or email is already in use.';

    // Don't allow demoting yourself out of admin.
    if ($id === $me && $role !== 'admin') $errors['role'] = 'You cannot remove your own admin role.';

    if ($errors) json_out(false, ['errors' => $errors], 'Please fix the highlighted fields.');

    if ($id) {
        // LMS shadow users: only update role, not password (password is managed by LMS).
        $stmt = db()->prepare('SELECT ext_user_id FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $isLmsUser = (bool)$stmt->fetchColumn();

        if ($isLmsUser) {
            // LMS users — only role can be changed locally.
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')
                ->execute([$role, $id]);
        } elseif ($password !== '') {
            db()->prepare('UPDATE users SET username=?, email=?, full_name=?, role=?, password_hash=? WHERE id=?')
                ->execute([$username, $email, $name, $role,
                           password_hash($password, PASSWORD_DEFAULT), $id]);
        } else {
            db()->prepare('UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?')
                ->execute([$username, $email, $name, $role, $id]);
        }
        json_out(true, ['id' => $id], 'User updated successfully.');
    }

    // New local user (LMS users are created automatically on login, not here).
    db()->prepare('INSERT INTO users (username, email, full_name, role, password_hash) VALUES (?,?,?,?,?)')
        ->execute([$username, $email, $name, $role,
                   password_hash($password, PASSWORD_DEFAULT)]);
    json_out(true, ['id' => (int)db()->lastInsertId()], 'User created successfully.');
}

if ($action === 'unlock') {
    $id = (int)($in['id'] ?? 0);
    db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?')->execute([$id]);
    json_out(true, null, 'Account unlocked.');
}

if ($action === 'toggle_active') {
    $id = (int)($in['id'] ?? 0);
    if ($id === $me) json_out(false, null, 'You cannot deactivate your own account.');
    db()->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    json_out(true, null, 'User status updated.');
}

if ($action === 'delete') {
    $id = (int)($in['id'] ?? 0);
    if ($id === $me) json_out(false, null, 'You cannot delete your own account.');
    try {
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        json_out(true, null, 'User deleted.');
    } catch (PDOException $e) {
        json_out(false, null, 'This user has created records and cannot be deleted. Deactivate the account instead.');
    }
}

json_out(false, null, 'Unknown action.');