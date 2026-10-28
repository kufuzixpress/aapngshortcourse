<?php
require_once __DIR__ . '/auth.php';
require_login();
$u = current_user();
$page_title = ($page_title ?? '') ?: 'Dashboard';

// Initials for the avatar: first letter of the first and last name.
$nameParts = preg_split('/\s+/', trim($u['full_name'] ?? 'User'));
$initials  = strtoupper(substr($nameParts[0], 0, 1) . (count($nameParts) > 1 ? substr(end($nameParts), 0, 1) : ''));
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.0.8/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/brand.css">
  <!-- Theme last, so it has the final say over the libraries above. -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/theme.css?v=3">
  <script>
    const APP_URL    = '<?= APP_URL ?>';
    const CSRF_TOKEN = '<?= e(csrf_token()) ?>';
    const IS_ADMIN   = <?= is_admin() ? 'true' : 'false' ?>;
  </script>
</head>
<body class="layout-fixed sidebar-expand-lg">
<div class="app-wrapper">

  <!-- Top bar -->
  <nav class="app-header navbar navbar-expand">
    <div class="container-fluid">

      <ul class="navbar-nav align-items-center">
        <li class="nav-item">
          <a class="nav-link sidebar-toggle" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
          </a>
        </li>
        <li class="nav-item d-none d-md-flex">
          <ol class="topbar-crumbs" aria-label="Breadcrumb">
            <li><a href="<?= APP_URL ?>/index.php"><i class="bi bi-house-door"></i></a></li>
            <li class="sep"><i class="bi bi-chevron-right"></i></li>
            <li class="current" aria-current="page"><?= e($page_title) ?></li>
          </ol>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item dropdown">
          <a class="user-menu-btn dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
            <span class="user-avatar"><?= e($initials) ?></span>
            <span class="d-none d-sm-block text-start">
              <span class="user-menu-name d-block"><?= e($u['full_name']) ?></span>
              <span class="user-menu-role d-block"><?= e(ucfirst($u['role'])) ?></span>
            </span>
            <i class="bi bi-chevron-down d-none d-sm-inline"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" style="min-width:220px;">
            <li class="user-menu-card">
              <strong><?= e($u['full_name']) ?></strong>
              <span><?= e(ucfirst($u['role'])) ?> account</span>
            </li>
            <li><hr class="dropdown-divider"></li>
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
    <div class="app-content">
      <div class="container-fluid">
        <?php show_flashes(); /* e.g. change-password errors, "no permission" redirects */ ?>
