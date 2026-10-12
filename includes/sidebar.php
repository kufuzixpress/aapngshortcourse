<?php
$current = basename($_SERVER['PHP_SELF']);
$_role   = $_SESSION['user']['role'] ?? '';

function nav_active(array $pages, string $current): string {
    return in_array($current, $pages, true) ? ' active' : '';
}

$logoSrc = APP_URL . '/assets/img/logo.png';
?>
<aside class="app-sidebar" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="<?= APP_URL ?>/index.php" class="brand-link">
      <img src="<?= $logoSrc ?>" alt="Australia Awards PNG"
           onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
      <span class="brand-fallback">
        <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
        <span class="brand-name">AAPNG Awards</span>
      </span>
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav aria-label="Main navigation">
      <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">

        <li class="sidebar-section-label">Overview</li>
        <li class="nav-item">
          <a href="<?= APP_URL ?>/index.php" class="nav-link<?= nav_active(['index.php'], $current) ?>">
            <i class="nav-icon bi bi-grid-1x2"></i><p>Dashboard</p>
          </a>
        </li>

        <li class="sidebar-section-label">Records</li>
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
        <li class="nav-item">
          <button type="button" class="nav-group-toggle" id="adminToggle" aria-expanded="true" aria-controls="adminMenuItems">
            <span>Administration</span><i class="bi bi-chevron-down"></i>
          </button>
          <div class="nav-group-items" id="adminMenuItems">
            <ul class="nav sidebar-menu flex-column">
              <li class="nav-item">
                <a href="<?= APP_URL ?>/import-participants.php" class="nav-link<?= nav_active(['import-participants.php'], $current) ?>">
                  <i class="nav-icon bi bi-person-up"></i><p>Import participants</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= APP_URL ?>/import-awards.php" class="nav-link<?= nav_active(['import-awards.php'], $current) ?>">
                  <i class="nav-icon bi bi-cloud-arrow-up"></i><p>Import awards</p>
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
                  <i class="nav-icon bi bi-sliders"></i><p>System settings</p>
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
// Collapse / expand the Administration group.
(function () {
  const toggle = document.getElementById('adminToggle');
  const items  = document.getElementById('adminMenuItems');
  if (!toggle || !items) return;
  toggle.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!open));
    items.classList.toggle('collapsed', open);
  });
})();
</script>
