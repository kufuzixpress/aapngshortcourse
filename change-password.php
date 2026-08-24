<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = $_POST['current_password'] ?? '';
    $p1 = $_POST['password'] ?? '';
    $p2 = $_POST['password_confirm'] ?? '';

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([current_user()['id']]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        flash('error', 'Your current password is incorrect.');
    } elseif (strlen($p1) < 8) {
        flash('error', 'New password must be at least 8 characters.');
    } elseif ($p1 !== $p2) {
        flash('error', 'New passwords do not match.');
    } else {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($p1, PASSWORD_DEFAULT), current_user()['id']]);
        flash('success', 'Password changed successfully.');
        redirect('index.php');
    }
    redirect('change-password.php');
}

$page_title = 'Change password';
require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card">
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Current password</label>
            <input type="password" name="current_password" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">New password (min 8 characters)</label>
            <input type="password" name="password" class="form-control" minlength="8" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm new password</label>
            <input type="password" name="password_confirm" class="form-control" minlength="8" required>
          </div>
          <button type="submit" class="btn btn-brand">Change password</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
