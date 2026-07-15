<?php
/**
 * Register a new health worker (nurse) account.
 */
$page_title = 'Register Nurse';
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $old['phone']     = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';

    if ($old['full_name'] === '') $errors[] = 'Full name is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (!$errors) {
        $check = db()->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$old['email']]);
        if ($check->fetch()) {
            $errors[] = 'That email is already registered.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare(
            'INSERT INTO users (full_name, email, phone, password, role, status)
             VALUES (?, ?, ?, ?, "healthworker", "active")'
        );
        $stmt->execute([$old['full_name'], $old['email'], $old['phone'], $hash]);
        set_flash('success', 'Health worker account created successfully.');
        redirect('admin/users.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><i class="bi bi-person-plus me-2"></i>New Health Worker Account</div>
      <div class="card-body">
        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger py-2"><?= e($err) ?></div>
        <?php endforeach; ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label"><?= t('full_name') ?></label>
            <input type="text" name="full_name" class="form-control" value="<?= e($old['full_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label"><?= t('email') ?></label>
            <input type="email" name="email" class="form-control" value="<?= e($old['email']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label"><?= t('phone') ?></label>
            <input type="text" name="phone" class="form-control" value="<?= e($old['phone']) ?>" placeholder="07XXXXXXXX">
          </div>
          <div class="mb-3">
            <label class="form-label"><?= t('password') ?></label>
            <input type="password" name="password" class="form-control" required>
            <div class="form-text">At least 6 characters. The nurse can change it later.</div>
          </div>
          <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Create Account</button>
          <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-link"><?= t('cancel') ?></a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
