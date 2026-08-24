<?php
$current  = basename($_SERVER['PHP_SELF']);
$_role    = $_SESSION['user']['role'] ?? '';

function nav_active(array $pages, string $current): string {
    return in_array($current, $pages, true) ? ' active' : '';
}

$logoSrc = APP_URL . '/assets/img/logo.png';

// Keep admin section open when on any admin page
$adminPages    = ['import-participants.php','import-awards.php','export-awards.php','users.php','user-form.php','settings.php','lookup.php'];
$adminMenuOpen = in_array($current, $adminPages, true);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --navy:               #003150;
    --teal:               #3CB6CE;
    --teal-dark:          #2a9ab0;
    --sidebar-bg:         #002540;
    --sidebar-bg-end:     #001a2e;
    --sidebar-hover:      rgba(60,182,206,0.25);
    --sidebar-active:     #3CB6CE;
    --text-sidebar:       rgba(255,255,255,0.85);
    --text-sidebar-muted: rgba(255,255,255,0.45);
}

/* ── Sidebar shell ── */
.app-sidebar {
    background: linear-gradient(180deg, var(--sidebar-bg) 0%, var(--sidebar-bg-end) 100%) !important;
    border-right: 1px solid rgba(0,0,0,0.2);
    font-family: 'DM Sans', sans-serif !important;
}

/* ── Brand: matches workplan sidebar exactly ── */
.app-sidebar .sidebar-brand {
    background: rgba(0,0,0,0.12) !important;
    border-bottom: 1px solid rgba(255,255,255,0.20) !important;
    padding: 0 !important;
}
.app-sidebar .brand-link {
    display: flex !important; align-items: center !important;
    justify-content: center !important; padding: 18px 20px !important;
    transition: background 0.2s;
}
.app-sidebar .brand-link:hover { background: rgba(0,0,0,0.20) !important; }
.app-sidebar .brand-link img {
    height: 62px; width: auto; object-fit: contain;
    filter: brightness(0) invert(1); opacity: .95;
}

/* ── Sidebar body ── */
.app-sidebar .sidebar-wrapper { padding-bottom: 20px; }

.sidebar-section-label {
    font-size: 0.65rem; font-weight: 700; letter-spacing: 0.14em;
    text-transform: uppercase; color: var(--text-sidebar-muted);
    padding: 18px 20px 6px; display: block; pointer-events: none; user-select: none;
}

/* ── Nav links ── */
.app-sidebar .sidebar-menu .nav-link {
    color: var(--text-sidebar) !important; border-radius: 8px !important;
    margin: 2px 10px !important; padding: 9px 14px !important;
    font-size: 0.875rem !important; font-weight: 500 !important;
    display: flex !important; align-items: center !important; gap: 2px !important;
    transition: background 0.18s, color 0.18s !important;
}
.app-sidebar .sidebar-menu .nav-link .nav-icon {
    color: rgba(255,255,255,0.65) !important; width: 22px !important;
    font-size: 0.95rem !important; margin-right: 10px !important;
    flex-shrink: 0; transition: color 0.18s !important;
}
.app-sidebar .sidebar-menu .nav-link:hover {
    background: var(--sidebar-hover) !important; color: #fff !important;
}
.app-sidebar .sidebar-menu .nav-link:hover .nav-icon { color: var(--teal) !important; }
.app-sidebar .sidebar-menu .nav-link.active {
    background: var(--sidebar-active) !important; color: #fff !important;
    box-shadow: 0 2px 14px rgba(60,182,206,0.35) !important; font-weight: 600 !important;
}
.app-sidebar .sidebar-menu .nav-link.active .nav-icon { color: #fff !important; }

/* ── Collapsible Administration section ── */
.admin-toggle {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 20px 6px; cursor: pointer; user-select: none;
}
.admin-toggle-label {
    font-size: 0.65rem; font-weight: 700; letter-spacing: 0.14em;
    text-transform: uppercase; color: var(--text-sidebar-muted); pointer-events: none;
}
.admin-toggle-icon {
    font-size: 0.6rem; color: var(--text-sidebar-muted);
    transition: transform 0.25s ease; pointer-events: none;
}
.admin-toggle:hover .admin-toggle-label,
.admin-toggle:hover .admin-toggle-icon { color: rgba(255,255,255,0.80); }
.admin-toggle.open .admin-toggle-icon { transform: rotate(180deg); }
.admin-menu-items { overflow: hidden; max-height: 0; transition: max-height 0.30s ease; }
.admin-menu-items.open { max-height: 600px; }
</style>

<aside class="app-sidebar elevation-4" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="<?= APP_URL ?>/index.php" class="brand-link">
      <img src="<?= $logoSrc ?>" alt="Australia Awards PNG"
           onerror="this.style.display='none';document.getElementById('_sbLogoFallback').style.display='flex';">
      <div id="_sbLogoFallback" style="display:none;align-items:center;gap:10px;">
        <div style="width:32px;height:32px;background:rgba(255,255,255,0.2);border-radius:8px;display:flex;align-items:center;justify-content:center;">
          <i class="bi bi-mortarboard-fill" style="color:#fff;font-size:15px;"></i>
        </div>
        <span style="color:#fff;font-weight:700;font-size:14px;">AAPNG Awards</span>
      </div>
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">

        <span class="sidebar-section-label">Main</span>
        <li class="nav-item">
          <a href="<?= APP_URL ?>/index.php" class="nav-link<?= nav_active(['index.php'], $current) ?>">
            <i class="nav-icon bi bi-speedometer2"></i><p>Dashboard</p>
          </a>
        </li>

        <span class="sidebar-section-label">Records</span>
        <li class="nav-item">
          <a href="<?= APP_URL ?>/participants.php" class="nav-link<?= nav_active(['participants.php','participant_view.php'], $current) ?>">
            <i class="nav-icon bi bi-people"></i><p>Personal details</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= APP_URL ?>/awards.php" class="nav-link<?= nav_active(['awards.php','award-form.php'], $current) ?>">
            <i class="nav-icon bi bi-award"></i><p>Awards</p>
          </a>
        </li>

        <?php if ($_role === 'admin'): ?>

        <!-- Collapsible Administration Section -->
        <li class="nav-item" style="list-style:none;">
          <div class="admin-toggle open" id="adminToggle">
            <span class="admin-toggle-label">Administration</span>
            <i class="bi bi-chevron-down admin-toggle-icon"></i>
          </div>
          <div class="admin-menu-items open" id="adminMenuItems">
            <ul class="nav sidebar-menu flex-column" style="margin:0;padding:0;">
              <li class="nav-item">
                <a href="<?= APP_URL ?>/import-participants.php" class="nav-link<?= nav_active(['import-participants.php'], $current) ?>">
                  <i class="nav-icon bi bi-upload"></i><p>Import participants</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= APP_URL ?>/import-awards.php" class="nav-link<?= nav_active(['import-awards.php'], $current) ?>">
                  <i class="nav-icon bi bi-cloud-upload"></i><p>Import awards</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= APP_URL ?>/export-awards.php" class="nav-link<?= nav_active(['export-awards.php'], $current) ?>">
                  <i class="nav-icon bi bi-file-earmark-spreadsheet"></i><p>Export awards</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= APP_URL ?>/users.php" class="nav-link<?= nav_active(['users.php','user-form.php'], $current) ?>">
                  <i class="nav-icon bi bi-person-gear"></i><p>User management</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= APP_URL ?>/settings.php" class="nav-link<?= nav_active(['settings.php','lookup.php'], $current) ?>">
                  <i class="nav-icon bi bi-gear"></i><p>System settings</p>
                </a>
              </li>
            </ul>
          </div>
        </li>

        <?php endif; ?>

      </ul>
    </nav>
  </div>
</aside>

<script>
(function () {
    var toggle = document.getElementById('adminToggle');
    var menu   = document.getElementById('adminMenuItems');
    if (!toggle || !menu) return;
    toggle.addEventListener('click', function () {
        var isOpen = menu.classList.contains('open');
        menu.classList.toggle('open', !isOpen);
        toggle.classList.toggle('open', !isOpen);
    });
})();
</script>