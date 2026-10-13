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
<div class="page-header">
  <div>
    <span class="page-eyebrow">My account</span>
    <h1 class="page-title">Change password</h1>
    <p class="page-sub">Choose a new password of at least 8 characters. You'll stay signed in.</p>
  </div>
</div>

<div class="row">
  <div class="col-lg-6 col-xl-5">
    <div class="page-card">
      <div class="page-card-header"><h5><i class="bi bi-shield-lock"></i>Update your password</h5></div>
      <div class="page-card-body">
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="field-label" for="currentPassword">Current password <span class="text-danger">*</span></label>
            <input type="password" name="current_password" id="currentPassword" class="form-control" autocomplete="current-password" required autofocus>
          </div>
          <div class="mb-3">
            <label class="field-label" for="newPassword">New password <span class="text-danger">*</span></label>
            <input type="password" name="password" id="newPassword" class="form-control" minlength="8" autocomplete="new-password" required>
            <div class="form-text">At least 8 characters.</div>
          </div>
          <div class="mb-4">
            <label class="field-label" for="confirmPassword">Confirm new password <span class="text-danger">*</span></label>
            <input type="password" name="password_confirm" id="confirmPassword" class="form-control" minlength="8" autocomplete="new-password" required>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i>Change password</button>
            <a href="index.php" class="btn btn-light">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
