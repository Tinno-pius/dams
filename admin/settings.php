<?php
/**
 * System settings - clinic name, default language and SMS provider.
 */
$page_title = 'System Settings';
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    set_setting('clinic_name', trim($_POST['clinic_name'] ?? ''));
    set_setting('system_language', in_array($_POST['system_language'] ?? 'en', ['en', 'sw'], true) ? $_POST['system_language'] : 'en');
    set_setting('sms_provider', in_array($_POST['sms_provider'] ?? 'africastalking', ['africastalking', 'beem'], true) ? $_POST['sms_provider'] : 'africastalking');
    set_flash('success', 'Settings saved.');
    redirect('admin/settings.php');
}

$clinic   = get_setting('clinic_name', 'DAMS Clinic');
$lang     = get_setting('system_language', 'en');
$provider = get_setting('sms_provider', 'africastalking');

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><i class="bi bi-gear me-2"></i>General Settings</div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Clinic Name</label>
            <input type="text" name="clinic_name" class="form-control" value="<?= e($clinic) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Default Language</label>
            <select name="system_language" class="form-select">
              <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English</option>
              <option value="sw" <?= $lang === 'sw' ? 'selected' : '' ?>>Kiswahili</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">SMS Provider</label>
            <select name="sms_provider" class="form-select">
              <option value="africastalking" <?= $provider === 'africastalking' ? 'selected' : '' ?>>Africa's Talking</option>
              <option value="beem" <?= $provider === 'beem' ? 'selected' : '' ?>>Beem SMS</option>
            </select>
          </div>
          <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Settings</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
