<?php
/**
 * Shared page header + top navigation bar + sidebar opening.
 *
 * A page uses it like this:
 *   $page_title = 'Dashboard';
 *   require_once __DIR__ . '/../includes/init.php';
 *   require_login();
 *   require_once __DIR__ . '/../includes/header.php';
 *   ... page content ...
 *   require_once __DIR__ . '/../includes/footer.php';
 */

if (!isset($page_title)) {
    $page_title = t('dashboard');
}
$user = current_user();
$role = current_role();

// Count unread notifications for the bell icon.
$unread = 0;
if ($user) {
    $unread = count_rows('notifications', 'user_id = ? AND is_read = 0', [$user['id']]);
}
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?> &middot; <?= APP_SHORT ?></title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="dams-layout">

  <!-- ===== Sidebar ===== -->
  <?php require __DIR__ . '/sidebar.php'; ?>

  <!-- ===== Main area ===== -->
  <div class="dams-main">

    <!-- Top navbar -->
    <nav class="navbar navbar-expand-lg dams-topbar px-3">
      <button class="btn btn-link text-dark d-lg-none me-2" id="sidebarToggle"><i class="bi bi-list fs-3"></i></button>
      <span class="navbar-brand fw-semibold mb-0 h6 text-truncate"><?= e($page_title) ?></span>

      <div class="ms-auto d-flex align-items-center gap-3">
        <!-- Language toggle -->
        <div class="dropdown">
          <a class="text-decoration-none text-secondary small dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-translate"></i> <?= strtoupper(current_lang()) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item <?= current_lang() === 'en' ? 'active' : '' ?>" href="?lang=en">English</a></li>
            <li><a class="dropdown-item <?= current_lang() === 'sw' ? 'active' : '' ?>" href="?lang=sw">Kiswahili</a></li>
          </ul>
        </div>

        <!-- Notifications -->
        <a href="<?= BASE_URL ?>/<?= $role ?>/notifications.php" class="position-relative text-secondary">
          <i class="bi bi-bell fs-5"></i>
          <?php if ($unread > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unread ?></span>
          <?php endif; ?>
        </a>

        <!-- User menu -->
        <div class="dropdown">
          <a class="d-flex align-items-center text-decoration-none text-dark dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <span class="dams-avatar"><?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?></span>
            <span class="ms-2 d-none d-sm-inline small"><?= e($user['full_name'] ?? '') ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text small text-muted"><?= e(t($role)) ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i><?= t('logout') ?></a></li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Page content -->
    <div class="dams-content p-3 p-md-4">
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?> alert-dismissible fade show" role="alert">
          <?= e($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
