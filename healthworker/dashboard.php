<?php
/**
 * Health worker (nurse) dashboard.
 */
$page_title = 'Health Worker Dashboard';
require_once __DIR__ . '/../includes/init.php';
require_role('healthworker');

$me = current_user();
$myPatients = count_rows('patients', 'registered_by = ?', [$me['id']]);
$totalPatients = count_rows('patients');
$highRisk = count_rows('patients', "risk_status = 'high'");
$todayAppts = count_rows('appointments', "appointment_date = CURDATE() AND status IN ('scheduled','rescheduled')");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['My Patients', $myPatients, 'bi-person-heart', 'bg-soft-green'],
      [t('total_patients'), $totalPatients, 'bi-people-fill', 'bg-soft-blue'],
      [t('high_risk'), $highRisk, 'bi-exclamation-triangle-fill', 'bg-soft-red'],
      ["Today's Appointments", $todayAppts, 'bi-calendar-day', 'bg-soft-orange'],
  ];
  foreach ($cards as $c): ?>
    <div class="col-6 col-xl-3">
      <div class="stat-card d-flex flex-column gap-2">
        <div class="stat-icon <?= $c[3] ?>"><i class="bi <?= $c[2] ?>"></i></div>
        <div class="stat-value"><?= $c[1] ?></div>
        <div class="stat-label"><?= e($c[0]) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-people me-2"></i>My Recent Patients</span>
        <a href="<?= BASE_URL ?>/healthworker/register_patient.php" class="btn btn-sm btn-primary"><i class="bi bi-person-plus me-1"></i>Register</a>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light"><tr><th>Reg. No.</th><th>Name</th><th>Risk</th><th></th></tr></thead>
          <tbody>
            <?php
            $stmt = db()->prepare('SELECT * FROM patients WHERE registered_by = ? ORDER BY id DESC LIMIT 6');
            $stmt->execute([$me['id']]);
            $rows = $stmt->fetchAll();
            if (!$rows): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">You have not registered any patient yet.</td></tr>
            <?php else: foreach ($rows as $p): ?>
              <tr>
                <td><?= e($p['registration_number']) ?></td>
                <td><?= e($p['full_name']) ?></td>
                <td><span class="badge <?= $p['risk_status'] === 'high' ? 'badge-high' : 'badge-low' ?>"><?= $p['risk_status'] === 'high' ? t('high_risk') : t('low_risk') ?></span></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/modules/rch4/edit.php?patient_id=<?= (int) $p['id'] ?>"><i class="bi bi-pencil-square"></i> RCH4</a></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-calendar-check me-2"></i>Upcoming Appointments</div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0 align-middle">
          <thead class="table-light"><tr><th>Date</th><th>Patient</th><th>Status</th></tr></thead>
          <tbody>
            <?php
            $appts = db()->query(
                "SELECT a.*, p.full_name FROM appointments a JOIN patients p ON p.id = a.patient_id
                 WHERE a.appointment_date >= CURDATE() AND a.status IN ('scheduled','rescheduled')
                 ORDER BY a.appointment_date ASC LIMIT 6"
            )->fetchAll();
            if (!$appts): ?>
              <tr><td colspan="3" class="text-center text-muted py-4">No upcoming appointments.</td></tr>
            <?php else: foreach ($appts as $a): ?>
              <tr>
                <td class="small"><?= date('d M Y', strtotime($a['appointment_date'])) ?></td>
                <td class="small"><?= e($a['full_name']) ?></td>
                <td><span class="badge bg-info text-dark"><?= e($a['status']) ?></span></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
