<?php
/**
 * Appointment module (Phase 8) for the health worker.
 * Schedule, reschedule and cancel appointments, and optionally send an
 * SMS reminder to the patient.
 */
$page_title = 'Appointments';
require_once __DIR__ . '/../includes/init.php';
require_role('healthworker');
require_once __DIR__ . '/../modules/sms/SmsService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $pid  = (int) ($_POST['patient_id'] ?? 0);
        $date = $_POST['appointment_date'] ?? '';
        $reason = trim($_POST['reason'] ?? 'ANC Visit');
        if ($pid && $date) {
            db()->prepare('INSERT INTO appointments (patient_id, appointment_date, reason, created_by) VALUES (?, ?, ?, ?)')
                ->execute([$pid, $date, $reason, current_user()['id']]);
            // Optional SMS reminder + notification.
            $p = db()->prepare('SELECT full_name, phone, user_id FROM patients WHERE id = ?');
            $p->execute([$pid]);
            $pat = $p->fetch();
            if ($pat) {
                if (!empty($_POST['send_sms']) && $pat['phone']) {
                    send_sms($pat['phone'], 'Habari ' . $pat['full_name'] . ', una miadi ya kliniki tarehe ' . date('d/m/Y', strtotime($date)) . '. DAMS', $pid);
                }
                notify($pat['user_id'] ?? null, 'New Appointment', 'You have an appointment on ' . date('d M Y', strtotime($date)) . '.');
            }
            set_flash('success', 'Appointment scheduled.');
        }
    } elseif ($action === 'reschedule') {
        $id = (int) $_POST['id'];
        $date = $_POST['appointment_date'] ?? '';
        db()->prepare("UPDATE appointments SET appointment_date = ?, status = 'rescheduled' WHERE id = ?")->execute([$date, $id]);
        set_flash('success', 'Appointment rescheduled.');
    } elseif ($action === 'cancel') {
        db()->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?")->execute([(int) $_POST['id']]);
        set_flash('success', 'Appointment cancelled.');
    } elseif ($action === 'complete') {
        db()->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?")->execute([(int) $_POST['id']]);
        set_flash('success', 'Appointment marked completed.');
    }
    redirect('healthworker/appointments.php');
}

$patients = db()->query('SELECT id, full_name, registration_number FROM patients ORDER BY full_name')->fetchAll();
$appts = db()->query(
    "SELECT a.*, p.full_name FROM appointments a JOIN patients p ON p.id = a.patient_id ORDER BY a.appointment_date DESC"
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><i class="bi bi-calendar-plus me-2"></i>Schedule Appointment</div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="form-label">Patient</label>
            <select name="patient_id" class="form-select" required>
              <option value="">-- select --</option>
              <?php foreach ($patients as $p): ?>
                <option value="<?= (int) $p['id'] ?>"><?= e($p['full_name']) ?> (<?= e($p['registration_number']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Date</label><input type="date" name="appointment_date" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Reason</label><input name="reason" class="form-control" value="ANC Visit"></div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="send_sms" id="send_sms" value="1">
            <label class="form-check-label" for="send_sms">Send SMS reminder</label>
          </div>
          <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Schedule</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-calendar-check me-2"></i>All Appointments</div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light"><tr><th>Date</th><th>Patient</th><th>Reason</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
            <?php if (!$appts): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">No appointments yet.</td></tr>
            <?php else: foreach ($appts as $a): ?>
              <tr>
                <td class="small"><?= date('d M Y', strtotime($a['appointment_date'])) ?></td>
                <td class="small"><?= e($a['full_name']) ?></td>
                <td class="small"><?= e($a['reason']) ?></td>
                <td><span class="badge bg-<?= ['scheduled'=>'info text-dark','rescheduled'=>'warning text-dark','cancelled'=>'secondary','completed'=>'success'][$a['status']] ?>"><?= e($a['status']) ?></span></td>
                <td class="text-end">
                  <?php if (!in_array($a['status'], ['cancelled','completed'], true)): ?>
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#re<?= $a['id'] ?>"><i class="bi bi-arrow-repeat"></i></button>
                    <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="complete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-outline-success"><i class="bi bi-check2"></i></button></form>
                    <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Cancel this appointment?"><i class="bi bi-x"></i></button></form>
                  <?php endif; ?>
                </td>
              </tr>
              <tr class="collapse" id="re<?= $a['id'] ?>">
                <td colspan="5">
                  <form method="post" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reschedule">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <div class="col-auto"><label class="form-label small mb-0">New date</label><input type="date" name="appointment_date" class="form-control form-control-sm" required></div>
                    <div class="col-auto"><button class="btn btn-sm btn-primary">Reschedule</button></div>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
