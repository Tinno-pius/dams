<?php
/**
 * Sidebar navigation. The links shown depend on the user's role.
 */
$role = current_role();

// Work out the current file so we can highlight the active link.
$currentFile = basename($_SERVER['SCRIPT_NAME']);

/**
 * Small helper to print one sidebar link.
 */
function nav_link($file, $icon, $label, $role, $currentFile)
{
    $active = ($file === $currentFile) ? 'active' : '';
    $url = BASE_URL . '/' . $role . '/' . $file;
    echo '<a href="' . $url . '" class="dams-nav-link ' . $active . '">'
        . '<i class="bi ' . $icon . '"></i><span>' . e($label) . '</span></a>';
}

// Menu items per role.
$menus = [
    'admin' => [
        ['dashboard.php',       'bi-speedometer2',   t('dashboard')],
        ['users.php',           'bi-people',         t('manage_users')],
        ['register_nurse.php',  'bi-person-plus',    t('register_nurse')],
        ['patients.php',        'bi-heart-pulse',    t('view_patients')],
        ['articles.php',        'bi-journal-medical',t('health_articles')],
        ['reports.php',         'bi-bar-chart',      t('reports')],
        ['sms.php',             'bi-chat-dots',      t('sms_sent')],
        ['settings.php',        'bi-gear',           t('settings')],
        ['backup.php',          'bi-database',       t('backup')],
    ],
    'healthworker' => [
        ['dashboard.php',        'bi-speedometer2',  t('dashboard')],
        ['register_patient.php', 'bi-person-plus',   t('register_patient')],
        ['patients.php',         'bi-people',        t('patient_list')],
        ['appointments.php',     'bi-calendar-check',t('appointments')],
        ['reports.php',          'bi-bar-chart',     t('reports')],
        ['search.php',           'bi-search',        t('search')],
    ],
    'patient' => [
        ['dashboard.php',     'bi-speedometer2',    t('dashboard')],
        ['profile.php',       'bi-person',          t('my_profile')],
        ['rch4.php',          'bi-card-heading',    t('my_rch4')],
        ['visits.php',        'bi-clipboard-pulse', t('anc_history')],
        ['appointments.php',  'bi-calendar-check',  t('appointments')],
        ['education.php',     'bi-book',            t('health_education')],
        ['notifications.php', 'bi-bell',            t('notifications')],
    ],
];
$menu = $menus[$role] ?? [];
?>
<aside class="dams-sidebar" id="damsSidebar">
  <div class="dams-brand">
    <span class="dams-brand-icon"><i class="bi bi-heart-pulse-fill"></i></span>
    <div>
      <div class="dams-brand-title">DAMS</div>
      <div class="dams-brand-sub">Antenatal Monitoring</div>
    </div>
  </div>

  <nav class="dams-nav">
    <?php foreach ($menu as $item): ?>
      <?php nav_link($item[0], $item[1], $item[2], $role, $currentFile); ?>
    <?php endforeach; ?>
  </nav>

  <div class="dams-sidebar-footer">
    <a href="<?= BASE_URL ?>/logout.php" class="dams-nav-link">
      <i class="bi bi-box-arrow-right"></i><span><?= t('logout') ?></span>
    </a>
  </div>
</aside>
