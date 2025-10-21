<?php
/**
 * sidebar.php
 *
 * Left sidebar navigation for dashboard layout.
 * Use Bootstrap collapsible sidebar pattern.
 */
?>

<div class="d-flex flex-column flex-shrink-0 p-3 bg-light border-end vh-100" style="width: 250px;">
  <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
    <i class="bi bi-speedometer2 me-2"></i>
    <span class="fs-5 fw-semibold">Dashboard</span>
  </a>
  <hr>
  <ul class="nav nav-pills flex-column mb-auto">
    <li><a href="dashboard.php" class="nav-link link-dark"><i class="bi bi-grid me-2"></i>Overview</a></li>
    <li><a href="devices.php" class="nav-link link-dark"><i class="bi bi-cpu me-2"></i>Devices</a></li>
    <li><a href="analytics.php" class="nav-link link-dark"><i class="bi bi-bar-chart-line me-2"></i>Analytics</a></li>
    <li><a href="boards.php" class="nav-link link-dark"><i class="bi bi-kanban me-2"></i>Boards</a></li>
    <li><a href="widgets.php" class="nav-link link-dark"><i class="bi bi-puzzle me-2"></i>Widgets</a></li>
    <li><a href="subscriptions.php" class="nav-link link-dark"><i class="bi bi-credit-card me-2"></i>Subscriptions</a></li>
    <li><a href="settings.php" class="nav-link link-dark"><i class="bi bi-gear me-2"></i>Settings</a></li>
  </ul>
  <hr>
  <div class="dropdown">
    <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
      <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name ?? 'U') ?>&background=0D8ABC&color=fff" width="32" height="32" class="rounded-circle me-2">
      <strong><?= htmlspecialchars($user_name ?? 'User') ?></strong>
    </a>
    <ul class="dropdown-menu text-small shadow">
      <li><a class="dropdown-item" href="account.php">Account</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item text-danger" href="logout.php">Sign out</a></li>
    </ul>
  </div>
</div>
