<?php
/**
 * topbar.php
 *
 * Navigation bar for the dashboard.
 * Usually included in each page’s header.
 *
 * Expects optional variables:
 *   $user_name
 */
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
      <i class="bi bi-activity"></i> IoT Dashboard
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMenu">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item me-3">
          <a class="nav-link" href="account.php">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user_name ?? 'User') ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-danger btn-sm" href="logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
