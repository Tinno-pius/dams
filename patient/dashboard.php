<?php
/**
 * Patient dashboard.
 * Shows a summary of the patient's own antenatal information.
 */
$page_title = 'My Dashboard';
require_once __DIR__ . '/../includes/init.php';
require_role('patient');

// Find the patient record linked to this login.
$stmt = db()->prepare('SELECT * FROM patients WHERE user_id = ?');
$stmt->execute([current_user()['id']]);
$patient = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';

if (!$patient): ?>
  <div class="alert alert-warning">Your antenatal record has not been created yet. Please visit the clinic so a health worker can register your RCH4 card.</div>
<?php
  require_once __DIR__ . '/../includes/footer.php';
  return;
endif;

$pid = (int) $patient['id'];
$visitCount = count_rows('anc_visits', 'patient_id = ?', [$pid]);
$nextAppt = db()->prepare("SELECT * FROM appointments WHERE patient_id = ? AND appointment_date >= CURDATE() AND status IN ('scheduled','rescheduled') ORDER BY appointment_date ASC LIMIT 1");
$nextAppt->execute([$pid]);
$appt = $nextAppt->fetch();
?>
<div class="row g-3 mb-4">
  <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon bg-soft-green mb-2"><i class="bi bi-person-heart"></i></div><div class="stat-value" style="font-size:1.1rem"><?= e($patient['registration_number']) ?></div><div class="stat-label">Registration No.</div></div></div>
  <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon <?= $patient['risk_status']==='high'?'bg-soft-red':'bg-soft-green' ?> mb-2"><i class="bi bi-heart-pulse"></i></div><div class="stat-value" style="font-size:1.2rem"><?= $patient['risk_status']==='high' ? t('high_risk') : t('low_risk') ?></div><div class="stat-label">Pregnancy Status</div></div></div>
  <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon bg-soft-teal mb-2"><i class="bi bi-clipboard-pulse"></i></div><div class="stat-value"><?= $visitCount ?></div><div class="stat-label"><?= t('anc_visits') ?></div></div></div>
  <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon bg-soft-orange mb-2"><i class="bi bi-calendar-event"></i></div><div class="stat-value" style="font-size:1.1rem"><?= $appt ? date('d M Y', strtotime($appt['appointment_date'])) : '—' ?></div><div class="stat-label"><?= t('next_appointment') ?></div></div></div>
</div>

<?php if ($patient['risk_status'] === 'high'): ?>
  <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Your last check-up shows danger signs. Please follow your nurse's advice and attend the clinic as directed.</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-person me-2"></i><?= t('my_profile') ?></div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tr><th>Name</th><td><?= e($patient['full_name']) ?></td></tr>
          <tr><th>Age</th><td><?= e($patient['age']) ?></td></tr>
          <tr><th>LNMP</th><td><?= $patient['lnmp'] ? date('d M Y', strtotime($patient['lnmp'])) : '—' ?></td></tr>
          <tr><th>EDD</th><td><?= $patient['edd'] ? date('d M Y', strtotime($patient['edd'])) : '—' ?></td></tr>
          <tr><th>District</th><td><?= e($patient['district']) ?></td></tr>
        </table>
        <a href="<?= BASE_URL ?>/modules/rch4/view.php" class="btn btn-primary btn-sm mt-3"><i class="bi bi-card-heading me-1"></i>View my RCH4 Card</a>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-book me-2"></i><?= t('health_education') ?></div>
      <div class="card-body">
        <?php foreach (db()->query('SELECT * FROM health_articles ORDER BY id DESC LIMIT 3') as $a): ?>
          <div class="mb-2"><b class="text-dams-green small"><?= e($a['title']) ?></b><div class="small text-muted"><?= e(mb_substr($a['body'], 0, 90)) ?>...</div></div>
        <?php endforeach; ?>
        <a href="<?= BASE_URL ?>/patient/education.php" class="btn btn-outline-secondary btn-sm">Read more</a>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
