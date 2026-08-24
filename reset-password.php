<?php
require_once __DIR__ . '/includes/auth.php';
start_session();
if (is_logged_in()) redirect('index.php');

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$reset = null;

if ($token) {
    $stmt = db()->prepare('SELECT pr.*, u.full_name FROM password_resets pr
                           JOIN users u ON u.id = pr.user_id
                           WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()
                           LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $reset = $stmt->fetch();
}

if (!$reset) {
    flash('error', 'This password reset link is invalid or has expired. Please request a new one.');
    redirect('forgot-password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $p1 = $_POST['password'] ?? '';
    $p2 = $_POST['password_confirm'] ?? '';

    if (strlen($p1) < 8) {
        flash('error', 'Password must be at least 8 characters long.');
    } elseif ($p1 !== $p2) {
        flash('error', 'Passwords do not match.');
    } else {
        db()->prepare('UPDATE users SET password_hash = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?')
            ->execute([password_hash($p1, PASSWORD_DEFAULT), $reset['user_id']]);
        db()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
            ->execute([$reset['id']]);
        flash('success', 'Your password has been reset. You can now log in.');
        redirect('login.php');
    }
    redirect('reset-password.php?token=' . urlencode($token));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset password | <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/brand.css">
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
  <div class="card card-outline">
    <div class="card-header login-card-header text-center">
      <h4 class="mb-0"><?= e(APP_NAME) ?></h4>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Set a new password for <strong><?= e($reset['full_name']) ?></strong></p>
      <?php show_flashes(); ?>
      <form method="post" action="reset-password.php">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="New password (min 8 characters)" minlength="8" required autofocus>
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password_confirm" class="form-control" placeholder="Confirm new password" minlength="8" required>
          <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
        </div>
        <button type="submit" class="btn btn-brand w-100">Reset password</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
