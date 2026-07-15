<?php
/**
 * Patient profile (view only + change password).
 * Patients cannot edit their medical records.
 */
$page_title = 'My Profile';
require_once __DIR__ . '/../includes/init.php';
require_role('patient');

$me = current_user();
$stmt = db()->prepare('SELECT * FROM patients WHERE user_id = ?');
$stmt->execute([$me['id']]);
$patient = $stmt->fetch();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $u = db()->prepare('SELECT password FROM users WHERE id = ?');
    $u->execute([$me['id']]);
    $row = $u->fetch();
    if (!password_verify($current, $row['password'])) {
        set_flash('error', 'Current password is incorrect.');
    } elseif (strlen($new) < 6) {
        set_flash('error', 'New password must be at least 6 characters.');
    } else {
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        set_flash('success', 'Password changed successfully.');
    }
    redirect('patient/profile.php');
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-person me-2"></i>Personal Information</div>
      <div class="card-body">
        <?php if (!$patient): ?>
          <p class="text-muted">No antenatal record yet.</p>
        <?php else: ?>
          <table class="table table-sm mb-0">
            <tr><th>Registration No.</th><td><?= e($patient['registration_number']) ?></td></tr>
            <tr><th>Full Name</th><td><?= e($patient['full_name']) ?></td></tr>
            <tr><th>Age</th><td><?= e($patient['age']) ?></td></tr>
            <tr><th>Phone</th><td><?= e($patient['phone']) ?></td></tr>
            <tr><th>Village / District</th><td><?= e($patient['village']) ?>, <?= e($patient['district']) ?></td></tr>
            <tr><th>LNMP / EDD</th><td><?= e($patient['lnmp']) ?> / <?= e($patient['edd']) ?></td></tr>
          </table>
          <p class="small text-muted mt-3 mb-0"><i class="bi bi-lock me-1"></i>Medical records can only be updated by a health worker.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-key me-2"></i>Change Password</div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required></div>
          <button class="btn btn-primary">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
