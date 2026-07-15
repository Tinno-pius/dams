<?php
/**
 * Administrator dashboard.
 * Shows totals and the main system statistics.
 */
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$totalPatients = count_rows('patients');
$totalNurses   = count_rows('users', "role = 'healthworker'");
$totalVisits   = count_rows('anc_visits');
$totalAppts    = count_rows('appointments');
$smsSent       = count_rows('sms_logs');
$highRisk      = count_rows('patients', "risk_status = 'high'");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-4">
  <?php
  $cards = [
      [t('total_patients'), $totalPatients, 'bi-people-fill',      'bg-soft-green'],
      [t('total_nurses'),   $totalNurses,   'bi-person-badge-fill','bg-soft-blue'],
      [t('anc_visits'),     $totalVisits,   'bi-clipboard-pulse',  'bg-soft-teal'],
      [t('appointments'),   $totalAppts,    'bi-calendar-check',   'bg-soft-orange'],
      [t('sms_sent'),       $smsSent,       'bi-chat-dots-fill',   'bg-soft-purple'],
      [t('high_risk'),      $highRisk,      'bi-exclamation-triangle-fill', 'bg-soft-red'],
  ];
  foreach ($cards as $c): ?>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="stat-card d-flex flex-column gap-2">
        <div class="stat-icon <?= $c[3] ?>"><i class="bi <?= $c[2] ?>"></i></div>
        <div class="stat-value"><?= $c[1] ?></div>
        <div class="stat-label"><?= e($c[0]) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-2"></i>Recent Patients</span>
        <a href="<?= BASE_URL ?>/admin/patients.php" class="btn btn-sm btn-outline-secondary">View all</a>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light"><tr><th>Reg. No.</th><th>Name</th><th>District</th><th>Risk</th></tr></thead>
          <tbody>
            <?php
            $recent = db()->query('SELECT * FROM patients ORDER BY id DESC LIMIT 6')->fetchAll();
            if (!$recent): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">No patients registered yet.</td></tr>
            <?php else: foreach ($recent as $p): ?>
              <tr>
                <td><?= e($p['registration_number']) ?></td>
                <td><?= e($p['full_name']) ?></td>
                <td><?= e($p['district']) ?></td>
                <td><span class="badge <?= $p['risk_status'] === 'high' ? 'badge-high' : 'badge-low' ?>"><?= $p['risk_status'] === 'high' ? t('high_risk') : t('low_risk') ?></span></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-activity me-2"></i><?= t('system_status') ?></div>
      <div class="card-body">
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex justify-content-between px-0">Database <span class="badge bg-success">Online</span></li>
          <li class="list-group-item d-flex justify-content-between px-0">SMS Gateway <span class="badge bg-secondary"><?= e(strtoupper(get_setting('sms_provider', SMS_PROVIDER))) ?></span></li>
          <li class="list-group-item d-flex justify-content-between px-0">Clinic <span class="text-muted small"><?= e(get_setting('clinic_name', 'DAMS Clinic')) ?></span></li>
          <li class="list-group-item d-flex justify-content-between px-0">System Date <span class="text-muted small"><?= date('d M Y') ?></span></li>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
