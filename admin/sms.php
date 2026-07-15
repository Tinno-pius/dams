<?php
/**
 * SMS centre - send a message and view the SMS log.
 */
$page_title = 'SMS Centre';
require_once __DIR__ . '/../includes/init.php';
require_role('admin');
require_once __DIR__ . '/../modules/sms/SmsService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($phone !== '' && $message !== '') {
        send_sms($phone, $message);
        set_flash('success', 'Message sent (or simulated if no API key is set).');
    } else {
        set_flash('error', 'Phone and message are required.');
    }
    redirect('admin/sms.php');
}

$logs = db()->query('SELECT * FROM sms_logs ORDER BY id DESC LIMIT 50')->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><i class="bi bi-send me-2"></i>Send SMS</div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message" rows="4" class="form-control" maxlength="160" required></textarea>
          </div>
          <button class="btn btn-primary"><i class="bi bi-chat-dots me-1"></i>Send</button>
        </form>
        <p class="small text-muted mt-3 mb-0">Provider: <b><?= e(strtoupper(get_setting('sms_provider', SMS_PROVIDER))) ?></b>. Configure the API key in <code>config/config.php</code>.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-clock-history me-2"></i>SMS Log</div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0 align-middle">
          <thead class="table-light"><tr><th>Date</th><th>Phone</th><th>Message</th><th>Provider</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (!$logs): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">No SMS sent yet.</td></tr>
            <?php else: foreach ($logs as $l): ?>
              <tr>
                <td class="small text-muted"><?= date('d/m H:i', strtotime($l['created_at'])) ?></td>
                <td class="small"><?= e($l['phone']) ?></td>
                <td class="small"><?= e($l['message']) ?></td>
                <td class="small"><?= e($l['provider']) ?></td>
                <td><span class="badge bg-<?= $l['status'] === 'sent' ? 'success' : 'danger' ?>"><?= e($l['status']) ?></span></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
