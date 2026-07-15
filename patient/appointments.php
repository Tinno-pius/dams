<?php
/**
 * Patient appointments (read only).
 */
$page_title = 'My Appointments';
require_once __DIR__ . '/../includes/init.php';
require_role('patient');

$stmt = db()->prepare('SELECT id FROM patients WHERE user_id = ?');
$stmt->execute([current_user()['id']]);
$row = $stmt->fetch();
$appts = [];
if ($row) {
    $a = db()->prepare('SELECT * FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC');
    $a->execute([(int) $row['id']]);
    $appts = $a->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-header"><i class="bi bi-calendar-check me-2"></i>My Appointments</div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light"><tr><th>Date</th><th>Reason</th><th>Status</th></tr></thead>
      <tbody>
        <?php if (!$appts): ?>
          <tr><td colspan="3" class="text-center text-muted py-4">No appointments yet.</td></tr>
        <?php else: foreach ($appts as $a): ?>
          <tr>
            <td><?= date('d M Y', strtotime($a['appointment_date'])) ?></td>
            <td><?= e($a['reason']) ?></td>
            <td><span class="badge bg-<?= ['scheduled'=>'info text-dark','rescheduled'=>'warning text-dark','cancelled'=>'secondary','completed'=>'success'][$a['status']] ?>"><?= e($a['status']) ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
