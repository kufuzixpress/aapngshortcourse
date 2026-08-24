<?php
require_once __DIR__ . '/auth.php';
require_login();
$u = current_user();
$page_title = $page_title ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> | <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/brand.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.0.8/css/dataTables.bootstrap5.min.css">
  <script>
    const APP_URL   = '<?= APP_URL ?>';
    const CSRF_TOKEN = '<?= e(csrf_token()) ?>';
    const IS_ADMIN  = <?= is_admin() ? 'true' : 'false' ?>;
  </script>
  <style>
    /* ── Global font ── */
    body, .app-header, .dropdown-menu, .app-content, .app-content-header {
      font-family: 'DM Sans', sans-serif !important;
    }

    /* ── Navbar ── */
    .app-header.navbar {
      background: #3CB6CE !important;
      border-bottom: 1px solid rgba(0,49,80,0.12) !important;
      box-shadow: 0 2px 8px rgba(0,49,80,0.12) !important;
      min-height: 56px;
    }

    /* Sidebar toggle icon */
    .app-header .nav-link[data-lte-toggle="sidebar"] {
      color: rgba(255,255,255,0.85) !important;
      font-size: 20px;
      padding: 8px 12px;
      border-radius: 8px;
      transition: background 0.15s;
    }
    .app-header .nav-link[data-lte-toggle="sidebar"]:hover {
      background: rgba(255,255,255,0.15) !important;
      color: #fff !important;
    }

    /* Page title in navbar (breadcrumb area) */
    .navbar-page-title {
      font-size: 14px;
      font-weight: 600;
      color: rgba(255,255,255,0.75);
      margin-left: 4px;
    }
    .navbar-page-title i { color: rgba(255,255,255,0.5); margin: 0 6px; font-size: 11px; }

    /* User dropdown toggle */
    .user-nav-btn {
      display: flex; align-items: center; gap: 9px;
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.25);
      border-radius: 8px;
      padding: 6px 12px;
      color: #fff !important;
      font-size: 13px; font-weight: 600;
      text-decoration: none;
      transition: background 0.15s;
    }
    .user-nav-btn:hover, .user-nav-btn.show {
      background: rgba(255,255,255,0.25) !important;
      color: #fff !important;
    }
    .user-nav-btn::after { display: none; } /* remove default caret */

    /* Avatar circle */
    .user-avatar {
      width: 30px; height: 30px; border-radius: 50%;
      background: rgba(0,49,80,0.35);
      border: 1.5px solid rgba(255,255,255,0.4);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 800; color: #fff;
      flex-shrink: 0;
    }

    .user-nav-info { line-height: 1.2; }
    .user-nav-name { font-size: 13px; font-weight: 600; color: #fff; }
    .user-nav-role {
      font-size: 10px; font-weight: 600;
      color: rgba(255,255,255,0.65);
      text-transform: uppercase; letter-spacing: .05em;
    }

    .user-nav-chevron { font-size: 10px; color: rgba(255,255,255,0.6); margin-left: 2px; }

    /* Dropdown menu */
    .user-dropdown-menu {
      border: none;
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0,49,80,0.18);
      padding: 6px;
      min-width: 200px;
      margin-top: 6px !important;
    }
    .user-dropdown-menu .dropdown-header {
      font-size: 11px; font-weight: 700; text-transform: uppercase;
      letter-spacing: .06em; color: #94A3B8; padding: 6px 10px 4px;
    }
    .user-dropdown-menu .dropdown-item {
      border-radius: 7px; font-size: 13px; font-weight: 500;
      color: #334155; padding: 8px 12px;
      display: flex; align-items: center; gap: 8px;
      transition: background 0.12s;
    }
    .user-dropdown-menu .dropdown-item i { font-size: 14px; color: #64748B; }
    .user-dropdown-menu .dropdown-item:hover { background: #F1F5F9; color: #003150; }
    .user-dropdown-menu .dropdown-item:hover i { color: #003150; }
    .user-dropdown-menu .dropdown-item.text-danger { color: #DC2626; }
    .user-dropdown-menu .dropdown-item.text-danger i { color: #DC2626; }
    .user-dropdown-menu .dropdown-item.text-danger:hover { background: #FEF2F2; }
    .user-dropdown-menu .dropdown-divider { margin: 4px 0; border-color: #EEF2F7; }

    /* ── Page title bar ── */
    .app-content-header {
      background: #fff !important;
      border-bottom: 1px solid #EEF2F7 !important;
      padding: 10px 0 !important;
      box-shadow: none !important;
    }
    .app-content-header .container-fluid {
      display: flex; align-items: center; gap: 10px;
    }
    .page-title-bar {
      font-size: 16px; font-weight: 800;
      color: #003150; margin: 0; line-height: 1;
    }
    .page-title-bar::before {
      content: '';
      display: inline-block;
      width: 4px; height: 18px;
      background: #3CB6CE;
      border-radius: 3px;
      margin-right: 10px;
      vertical-align: middle;
    }

    /* ── Content area ── */
    .app-content { background: #F0F4F8 !important; }
  </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

  <!-- ── Navbar ── -->
  <nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">

      <!-- Left: sidebar toggle + page breadcrumb -->
      <ul class="navbar-nav align-items-center">
        <li class="nav-item">
          <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
            <i class="bi bi-list"></i>
          </a>
        </li>
        <li class="nav-item d-none d-md-flex align-items-center">
          <span class="navbar-page-title">
            <i class="bi bi-chevron-right"></i><?= e($page_title) ?>
          </span>
        </li>
      </ul>

      <!-- Right: user menu -->
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item dropdown">
          <?php
            $initials = strtoupper(substr($u['full_name'] ?? 'U', 0, 1));
            $nameParts = explode(' ', $u['full_name'] ?? '');
            if (count($nameParts) > 1) {
              $initials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
            }
          ?>
          <a class="user-nav-btn dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
            <div class="user-avatar"><?= $initials ?></div>
            <div class="user-nav-info">
              <div class="user-nav-name"><?= e($u['full_name']) ?></div>
              <div class="user-nav-role"><?= e(ucfirst($u['role'])) ?></div>
            </div>
            <i class="bi bi-chevron-down user-nav-chevron"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu">
            <li><h6 class="dropdown-header">My account</h6></li>
            <li>
              <a class="dropdown-item" href="<?= APP_URL ?>/change-password.php">
                <i class="bi bi-key"></i>Change password
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php">
                <i class="bi bi-box-arrow-right"></i>Log out
              </a>
            </li>
          </ul>
        </li>
      </ul>

    </div>
  </nav>

  <?php require __DIR__ . '/sidebar.php'; ?>

  <!-- Main content -->
  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <h3 class="page-title-bar"><?= e($page_title) ?></h3>
      </div>
    </div>
    <div class="app-content">
      <div class="container-fluid">
        <?php show_flashes(); ?>