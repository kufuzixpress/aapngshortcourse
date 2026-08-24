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

$bgImage = APP_URL . '/assets/img/background.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password | Short Course Database</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --primary:       #3CB6CE;
      --primary-dark:  #2A96AC;
      --primary-glow:  rgba(60,182,206,0.18);
      --secondary:     #003150;
      --secondary-mid: #004470;
      --white:         #FFFFFF;
      --off-white:     #F4F8FA;
      --muted:         #8A9DB0;
      --text:          #1E3040;
      --border:        #DDE5EC;
    }

    html, body { height: 100%; font-family: 'Inter', sans-serif; }

    body {
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      padding: 24px; position: relative;
      background-image: url('<?= e($bgImage) ?>');
      background-size: cover; background-position: center; background-repeat: no-repeat;
    }
    body::before {
      content: ''; position: fixed; inset: 0;
      background: linear-gradient(160deg, rgba(0,49,80,0.85) 0%, rgba(0,49,80,0.65) 100%);
      z-index: 0;
    }

    /* ── CARD ─────────────────────────────────────────────────── */
    .login-panel {
      width: 100%; max-width: 420px; background: var(--white);
      border-radius: 16px; box-shadow: 0 24px 64px rgba(0,0,0,0.35);
      padding: 44px 40px 32px; position: relative; z-index: 1;
    }

    .login-heading { margin-bottom: 28px; text-align: center; }
    .login-heading h2 { font-size: 24px; font-weight: 800; color: var(--text); margin-bottom: 6px; letter-spacing: -0.4px; }
    .login-heading p  { font-size: 14px; color: var(--muted); line-height: 1.5; }

    /* ── ALERTS (from show_flashes / debug link) ─────────────── */
    .alert {
      display: flex; align-items: flex-start; gap: 10px;
      border-radius: 10px; padding: 12px 14px; margin-bottom: 20px;
      font-size: 13.5px; font-weight: 500; line-height: 1.5; border: 1px solid transparent;
    }
    .alert-success { background: rgba(21,128,61,0.08);  border-color: rgba(21,128,61,0.28);  color: #14532d; }
    .alert-danger  { background: rgba(185,28,28,0.07);  border-color: rgba(185,28,28,0.25);  color: #991b1b; }
    .alert-warning { background: rgba(180,83,9,0.08);   border-color: rgba(180,83,9,0.25);   color: #92400e; }
    .alert-info    { background: rgba(60,182,206,0.10); border-color: rgba(60,182,206,0.32); color: #0d5d6e; }
    .alert .btn-close { margin-left: auto; }

    /* ── FORM ELEMENTS ────────────────────────────────────────── */
    .form-group { margin-bottom: 20px; }
    .input-wrap { position: relative; }
    .input-wrap .field-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 14px; pointer-events: none; transition: color 0.2s; }
    .input-wrap input {
      width: 100%; padding: 12px 16px 12px 40px;
      border: 1.5px solid var(--border); border-radius: 8px;
      font-size: 14px; font-family: 'Inter', sans-serif; color: var(--text);
      background: var(--off-white); transition: border-color 0.2s, box-shadow 0.2s, background 0.2s; outline: none;
    }
    .input-wrap input:focus { border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 3px var(--primary-glow); }
    .input-wrap:focus-within .field-icon { color: var(--primary); }

    .btn-signin {
      width: 100%; padding: 14px;
      background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-mid) 100%);
      color: var(--white); border: none; border-radius: 8px;
      font-size: 15px; font-weight: 700; font-family: 'Inter', sans-serif;
      letter-spacing: 0.3px; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 10px;
      transition: transform 0.2s, box-shadow 0.2s;
      box-shadow: 0 4px 16px rgba(0,49,80,0.35);
    }
    .btn-signin:hover  { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,49,80,0.45); }
    .btn-signin:active { transform: translateY(0); }

    .return-link { text-align: center; margin-top: 22px; }
    .return-link a { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--muted); font-weight: 500; text-decoration: none; transition: color 0.2s; }
    .return-link a:hover { color: var(--secondary); }

    @media (max-width: 480px) {
      .login-panel { padding: 32px 24px 24px; }
    }
  </style>
</head>
<body>

  <div class="login-panel">

    <div class="login-heading">
      <h2>Forgot Password</h2>
      <p>Enter your email address and we'll send you a link to reset your password.</p>
    </div>

    <?php show_flashes(); ?>

    <?php if ($debug_link): ?>
      <div class="alert alert-warning">
        <span><strong>Development mode:</strong> reset link:<br>
        <a href="<?= e($debug_link) ?>"><?= e($debug_link) ?></a></span>
      </div>
    <?php endif; ?>

    <form method="post" action="forgot-password.php">
      <?= csrf_field() ?>
      <div class="form-group">
        <div class="input-wrap">
          <i class="fas fa-envelope field-icon"></i>
          <input type="email" name="email" placeholder="Email address" required autofocus>
        </div>
      </div>

      <button type="submit" class="btn-signin">
        <i class="fas fa-paper-plane"></i>
        <span>Send reset link</span>
      </button>
    </form>

    <div class="return-link">
      <a href="login.php"><i class="fas fa-arrow-left"></i> Back to login</a>
    </div>
  </div>

</body>
</html>
