<?php
require_once __DIR__ . '/includes/auth.php';
start_session();

if (is_logged_in()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $msg] = attempt_login(trim($_POST['username'] ?? ''), $_POST['password'] ?? '');
    flash($ok ? 'success' : 'error', $msg);
    redirect($ok ? 'index.php' : 'login.php');
}

// Pull the first flash message (if any) for the inline notification.
$notifType = '';
$notifMessage = '';
if (!empty($_SESSION['flash'])) {
    $f = array_shift($_SESSION['flash']);
    $notifType    = $f['type'];      // success | error | warning | info
    $notifMessage = $f['message'];
    if (empty($_SESSION['flash'])) unset($_SESSION['flash']);
}
$isLockout = ($notifType === 'error' && stripos($notifMessage, 'locked') !== false);
if ($isLockout) $notifType = 'warning';
$inputErrorClass = ($notifType === 'error') ? 'input-error' : '';

$bgImage = APP_URL . '/assets/img/background.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | Short Course Database</title>
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

    /* ── LOGIN CARD ───────────────────────────────────────────── */
    .login-panel {
      width: 100%; max-width: 420px; background: var(--white);
      border-radius: 16px; box-shadow: 0 24px 64px rgba(0,0,0,0.35);
      padding: 44px 40px 32px; position: relative; z-index: 1;
    }

    .login-heading { margin-bottom: 28px; text-align: center; }
    .login-heading h2 { font-size: 24px; font-weight: 800; color: var(--text); margin-bottom: 6px; letter-spacing: -0.4px; }
    .login-heading p  { font-size: 14px; color: var(--muted); }

    /* ── INLINE NOTIFICATION ──────────────────────────────────── */
    .notif-inline {
      display: flex; align-items: flex-start; gap: 10px;
      border-radius: 10px; padding: 12px 14px; margin-bottom: 20px;
      font-size: 13.5px; font-weight: 500; line-height: 1.5;
      opacity: 0; transform: translateY(-6px);
      transition: opacity 0.3s ease, transform 0.3s ease;
      pointer-events: none;
    }
    .notif-inline.show { opacity: 1; transform: translateY(0); pointer-events: auto; }

    .notif-inline.success { background: rgba(21,128,61,0.08);  border: 1px solid rgba(21,128,61,0.28);  color: #14532d; }
    .notif-inline.error   { background: rgba(185,28,28,0.07);  border: 1px solid rgba(185,28,28,0.25);  color: #991b1b; }
    .notif-inline.warning { background: rgba(180,83,9,0.08);   border: 1px solid rgba(180,83,9,0.25);   color: #92400e; }
    .notif-inline.info    { background: rgba(60,182,206,0.10); border: 1px solid rgba(60,182,206,0.32); color: #0d5d6e; }
    .notif-inline .ni-icon { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
    .notif-inline.success .ni-icon { color: #16a34a; }
    .notif-inline.error   .ni-icon { color: #dc2626; }
    .notif-inline.warning .ni-icon { color: #d97706; }
    .notif-inline.info    .ni-icon { color: var(--primary-dark); }
    .notif-inline .ni-text { flex: 1; }
    .notif-inline .ni-close {
      background: none; border: none; cursor: pointer;
      font-size: 14px; line-height: 1; padding: 0; margin-top: 1px; flex-shrink: 0;
      opacity: 0.5; transition: opacity 0.15s; color: inherit;
    }
    .notif-inline .ni-close:hover { opacity: 1; }

    /* ── FORM ELEMENTS ────────────────────────────────────────── */
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 12px; font-weight: 700; color: #556070; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 8px; }
    .input-wrap { position: relative; }
    .input-wrap .field-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 14px; pointer-events: none; transition: color 0.2s; }
    .input-wrap input {
      width: 100%; padding: 12px 42px 12px 40px;
      border: 1.5px solid var(--border); border-radius: 8px;
      font-size: 14px; font-family: 'Inter', sans-serif; color: var(--text);
      background: var(--off-white); transition: border-color 0.2s, box-shadow 0.2s, background 0.2s; outline: none;
    }
    .input-wrap input:focus { border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 3px var(--primary-glow); }
    .input-wrap input.input-error { border-color: #dc2626; background: #fff8f8; box-shadow: 0 0 0 3px rgba(220,38,38,0.10); }
    .input-wrap:focus-within .field-icon { color: var(--primary); }
    .pw-toggle { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 14px; cursor: pointer; background: none; border: none; transition: color 0.2s; }
    .pw-toggle:hover { color: var(--primary); }

    .form-options { display: flex; justify-content: flex-end; margin-bottom: 24px; }
    .form-options a { font-size: 13px; color: var(--primary); font-weight: 600; text-decoration: none; transition: color 0.2s; }
    .form-options a:hover { color: var(--secondary); }

    .btn-signin {
      width: 100%; padding: 14px;
      background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-mid) 100%);
      color: var(--white); border: none; border-radius: 8px;
      font-size: 15px; font-weight: 700; font-family: 'Inter', sans-serif;
      letter-spacing: 0.3px; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 10px;
      transition: transform 0.2s, box-shadow 0.2s;
      box-shadow: 0 4px 16px rgba(0,49,80,0.35); position: relative; overflow: hidden;
    }
    .btn-signin::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, rgba(60,182,206,0.18), transparent); transform: translateX(-100%); transition: transform 0.5s; }
    .btn-signin:hover::after  { transform: translateX(100%); }
    .btn-signin:hover  { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,49,80,0.45); }
    .btn-signin:active { transform: translateY(0); }
    .btn-signin.loading { opacity: 0.75; pointer-events: none; }

    .login-footer { text-align: center; font-size: 11.5px; color: #B0BEC8; margin-top: 28px; line-height: 1.6; }

    @media (max-width: 480px) {
      .login-panel { padding: 32px 24px 24px; }
    }
  </style>
</head>
<body>

  <div class="login-panel">

    <div class="login-heading">
      <h2>Login to Short Course Database</h2>
    </div>

    <?php if ($notifType && $notifMessage): ?>
    <div class="notif-inline <?= e($notifType) ?>" id="notifInline">
      <i class="ni-icon fas <?php
        echo $notifType === 'success' ? 'fa-check-circle'
           : ($notifType === 'error' ? 'fa-exclamation-circle'
           : ($notifType === 'info' ? 'fa-info-circle' : 'fa-exclamation-triangle'));
      ?>"></i>
      <span class="ni-text"><?= e($notifMessage) ?></span>
      <button class="ni-close" onclick="closeNotif()" aria-label="Dismiss">&times;</button>
    </div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate id="loginForm">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="username">Username or Email</label>
        <div class="input-wrap">
          <i class="fas fa-user field-icon"></i>
          <input type="text" id="username" name="username"
                 placeholder="Enter your username or email"
                 autocomplete="username" required autofocus
                 class="<?= $inputErrorClass ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock field-icon"></i>
          <input type="password" id="password" name="password"
                 placeholder="Enter your password"
                 autocomplete="current-password" required
                 class="<?= $inputErrorClass ?>">
          <button type="button" class="pw-toggle" id="togglePw" aria-label="Toggle password">
            <i class="fas fa-eye" id="togglePwIcon"></i>
          </button>
        </div>
      </div>

      <div class="form-options">
        <a href="forgot-password.php">Forgot password?</a>
      </div>

      <button type="submit" class="btn-signin" id="btnSignin">
        <i class="fas fa-sign-in-alt" id="signinIcon"></i>
        <span id="signinLabel">Sign In</span>
      </button>
    </form>

    <div class="login-footer">
      &copy; <?= date('Y') ?> Australia Awards Short Course Database
    </div>
  </div>

<script>
// ── Password toggle ───────────────────────────────────────────
document.getElementById('togglePw').addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon  = document.getElementById('togglePwIcon');
    const isHidden = input.type === 'password';
    input.type     = isHidden ? 'text' : 'password';
    icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
});

// ── Clear error styling on input ──────────────────────────────
document.querySelectorAll('.input-wrap input').forEach(function (inp) {
    inp.addEventListener('input', function () {
        this.classList.remove('input-error');
    });
});

// ── Loading state on submit ───────────────────────────────────
document.getElementById('loginForm').addEventListener('submit', function () {
    const btn   = document.getElementById('btnSignin');
    const icon  = document.getElementById('signinIcon');
    const label = document.getElementById('signinLabel');
    btn.classList.add('loading');
    icon.className    = 'fas fa-spinner fa-spin';
    label.textContent = 'Signing in…';
});

// ── Notification ──────────────────────────────────────────────
function closeNotif() {
    const el = document.getElementById('notifInline');
    if (!el) return;
    el.style.opacity   = '0';
    el.style.transform = 'translateY(-6px)';
    setTimeout(() => el.remove(), 300);
}

document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('notifInline');
    if (!el) return;
    requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('show')));
    <?php if ($notifType === 'success' || $notifType === 'info'): ?>
    setTimeout(closeNotif, 5000);
    <?php endif; ?>
    // Error and lockout messages stay until dismissed or the user starts typing.
});
</script>

</body>
</html>
