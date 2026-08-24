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

$logoImage = APP_URL . '/assets/img/loginlogo.png';
$bgImage   = APP_URL . '/assets/img/background.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Awards Database | Australia Awards</title>
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

    html, body { height: 100%; font-family: 'Inter', sans-serif; overflow: hidden; }
    body { display: flex; min-height: 100vh; flex-direction: column; }
    .main-wrap { display: flex; flex: 1; overflow: hidden; }

    /* ── LOGIN PANEL ──────────────────────────────────────────── */
    .login-panel {
      width: 460px; min-width: 460px; background: var(--white);
      display: flex; flex-direction: column; justify-content: space-between;
      padding: 44px 48px 32px; position: relative; z-index: 10;
      box-shadow: 8px 0 48px rgba(0,0,0,0.14); overflow-y: auto;
    }

    .login-logo { display: flex; align-items: center; gap: 14px; margin-bottom: 36px; }
    .login-logo img { height: 56px; width: auto; object-fit: contain; }
    .logo-fallback {
      display: none; width: 50px; height: 50px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      border-radius: 13px; align-items: center; justify-content: center;
      box-shadow: 0 6px 18px rgba(60,182,206,0.38); flex-shrink: 0;
    }
    .logo-fallback i { font-size: 21px; color: var(--white); }
    .logo-text-wrap .org-name { font-size: 16px; font-weight: 800; color: var(--secondary); line-height: 1.2; }
    .logo-text-wrap .org-sub  { font-size: 11px; font-weight: 500; color: var(--muted); letter-spacing: 0.05em; text-transform: uppercase; }

    .login-heading { margin-bottom: 28px; }
    .login-heading h2 { font-size: 26px; font-weight: 800; color: var(--text); margin-bottom: 6px; letter-spacing: -0.4px; }
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

    .divider { display: flex; align-items: center; gap: 12px; margin: 24px 0; color: var(--muted); font-size: 12px; }
    .divider::before, .divider::after { content: ''; flex: 1; border-top: 1px solid var(--border); }

    .return-link { text-align: center; }
    .return-link a { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--muted); font-weight: 500; text-decoration: none; transition: color 0.2s; }
    .return-link a:hover { color: var(--secondary); }

    .login-footer { text-align: center; font-size: 11.5px; color: #B0BEC8; margin-top: 32px; line-height: 1.6; }

    /* ── INFO PANEL ───────────────────────────────────────────── */
    .info-panel { flex: 1; position: relative; background: var(--secondary); display: flex; flex-direction: column; justify-content: flex-end; padding: 60px 56px; overflow: hidden; }
    .info-bg { position: absolute; inset: 0; background-image: url('<?= e($bgImage) ?>'); background-size: cover; background-position: center; opacity: 0.3; }
    .info-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,49,80,0.98) 0%, rgba(0,49,80,0.72) 45%, rgba(0,49,80,0.28) 100%); }
    .info-content { position: relative; z-index: 2; }
    .info-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(60,182,206,0.15); border: 1px solid rgba(60,182,206,0.35); color: var(--primary); border-radius: 20px; padding: 6px 16px; font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 22px; }
    .info-title { font-size: 40px; font-weight: 800; color: var(--white); line-height: 1.15; margin-bottom: 16px; letter-spacing: -0.6px; }
    .info-title span { color: var(--primary); }
    .info-desc { font-size: 15px; color: rgba(255,255,255,0.6); line-height: 1.75; max-width: 420px; margin-bottom: 38px; }
    .info-features { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 40px; max-width: 480px; }
    .feature-item { display: flex; align-items: flex-start; gap: 12px; }
    .feature-icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(60,182,206,0.12); border: 1px solid rgba(60,182,206,0.22); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .feature-icon i { font-size: 15px; color: var(--primary); }
    .feature-text strong { display: block; font-size: 13px; font-weight: 700; color: var(--white); margin-bottom: 2px; }
    .feature-text span { font-size: 12px; color: rgba(255,255,255,0.48); }
    .component-strip { display: flex; flex-wrap: wrap; gap: 8px; padding-top: 28px; border-top: 1px solid rgba(255,255,255,0.1); }
    .comp-tag { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.55); border-radius: 6px; padding: 5px 12px; font-size: 11px; font-weight: 600; letter-spacing: 0.3px; }

    @media (max-width: 860px) {
      html, body { overflow: auto; }
      .main-wrap { flex-direction: column; }
      .login-panel { width: 100%; min-width: unset; padding: 32px 28px; box-shadow: none; }
      .info-panel { min-height: 260px; padding: 36px 28px; }
      .info-features { grid-template-columns: 1fr; }
      .info-title { font-size: 28px; }
    }
  </style>
</head>
<body>

<div class="main-wrap">

  <!-- ── LEFT: LOGIN FORM ───────────────────────────────────── -->
  <div class="login-panel">
    <div>

      <div class="login-logo">
        <img src="<?= e($logoImage) ?>" alt="Australia Awards"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <div class="logo-fallback"><i class="fas fa-graduation-cap"></i></div>
        <div class="logo-text-wrap">
          <div class="org-name">Australia Awards</div>
          <div class="org-sub">Short Course Database</div>
        </div>
      </div>

      <div class="login-heading">
        <h2>Welcome back</h2>
        <p>Sign in to access the Awards Database</p>
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

      <div class="divider">or</div>
      <div class="return-link">
        <a href="https://mis.australiaawardspng.org">
          <i class="fas fa-arrow-left"></i> Return to MIS Homepage
        </a>
      </div>
    </div>

    <div class="login-footer">
      &copy; <?= date('Y') ?> Australia Awards<br>
      Short Course Awards Database &mdash; Powered by <strong style="color:#3CB6CE;">MIS</strong>
    </div>
  </div>

  <!-- ── RIGHT: INFO PANEL ──────────────────────────────────── -->
  <div class="info-panel">
    <div class="info-bg"></div>
    <div class="info-overlay"></div>
    <div class="info-content">
      <div class="info-badge"><i class="fas fa-award"></i> Staff Portal</div>
      <h1 class="info-title">Short Course<br> <span>Scholarships</span></h1>
      <p class="info-desc">Record short course participants, manage their awards, search across thousands of records, and keep every detail in one place.</p>
      <div class="info-features">
        <div class="feature-item">
          <div class="feature-icon"><i class="fas fa-users"></i></div>
          <div class="feature-text"><strong>Personal Details</strong><span>One record per person</span></div>
        </div>
        <div class="feature-item">
          <div class="feature-icon"><i class="fas fa-award"></i></div>
          <div class="feature-text"><strong>Awards details</strong><span>Multiple awards per person</span></div>
        </div>
        <div class="feature-item">
          <div class="feature-icon"><i class="fas fa-magnifying-glass"></i></div>
          <div class="feature-text"><strong>Smart Search</strong><span>Find anyone instantly</span></div>
        </div>
        <div class="feature-item">
          <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
          <div class="feature-text"><strong>Role-based Access</strong><span>Admin and staff levels</span></div>
        </div>
      </div>
      <div class="component-strip">
        <span class="comp-tag">Personal Details</span>
        <span class="comp-tag">Awards</span>
        <span class="comp-tag">Duplicate Detection</span>
        <span class="comp-tag">Provinces &amp; Districts</span>
        <span class="comp-tag">Reports</span>
        <span class="comp-tag">User Roles</span>
      </div>
    </div>
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