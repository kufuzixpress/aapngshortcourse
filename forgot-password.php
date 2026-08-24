<?php
require_once __DIR__ . '/includes/auth.php';
start_session();
if (is_logged_in()) redirect('index.php');

$debug_link = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
                       VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))')
            ->execute([$user['id'], hash('sha256', $token), RESET_TOKEN_MINUTES]);

        $link = APP_URL . '/reset-password.php?token=' . $token;

        if (MAIL_DEBUG) {
            $debug_link = $link;   // shown on screen for development only
        } else {
            send_reset_email($user['email'], $user['full_name'], $link);
        }
    }
    // Same message whether or not the email exists — avoids revealing accounts.
    flash('info', 'If that email address is registered, a password reset link has been sent.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot password | <?= e(APP_NAME) ?></title>
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
      <p class="login-box-msg">Enter your email address and we'll send you a link to reset your password.</p>
      <?php show_flashes(); ?>
      <?php if ($debug_link): ?>
        <div class="alert alert-warning small">
          <strong>Development mode:</strong> reset link:<br>
          <a href="<?= e($debug_link) ?>"><?= e($debug_link) ?></a>
        </div>
      <?php endif; ?>
      <form method="post" action="forgot-password.php">
        <?= csrf_field() ?>
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email address" required autofocus>
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
        </div>
        <button type="submit" class="btn btn-brand w-100 mb-3">Send reset link</button>
      </form>
      <p class="mb-0"><a href="login.php">Back to login</a></p>
    </div>
  </div>
</div>
</body>
</html>
