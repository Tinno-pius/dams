<?php
/**
 * Manage users - list all accounts and activate / deactivate them.
 */
$page_title = 'Manage Users';
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

// Toggle status (activate / deactivate).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['user_id'] ?? 0);
    $me = current_user();
    if ($id === (int) $me['id']) {
        set_flash('error', 'You cannot change your own account status.');
    } else {
        $stmt = db()->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id = ?");
        $stmt->execute([$id]);
        set_flash('success', 'User status updated.');
    }
    redirect('admin/users.php');
}

$roleFilter = $_GET['role'] ?? '';
$sql = 'SELECT * FROM users';
$params = [];
if (in_array($roleFilter, ['admin', 'healthworker', 'patient'], true)) {
    $sql .= ' WHERE role = ?';
    $params[] = $roleFilter;
}
$sql .= ' ORDER BY role, full_name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <a href="?" class="btn btn-sm <?= $roleFilter === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
  <a href="?role=admin" class="btn btn-sm <?= $roleFilter === 'admin' ? 'btn-primary' : 'btn-outline-secondary' ?>">Admins</a>
  <a href="?role=healthworker" class="btn btn-sm <?= $roleFilter === 'healthworker' ? 'btn-primary' : 'btn-outline-secondary' ?>">Health Workers</a>
  <a href="?role=patient" class="btn btn-sm <?= $roleFilter === 'patient' ? 'btn-primary' : 'btn-outline-secondary' ?>">Patients</a>
  <a href="<?= BASE_URL ?>/admin/register_nurse.php" class="btn btn-sm btn-primary ms-auto"><i class="bi bi-person-plus me-1"></i>Register Nurse</a>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['phone']) ?></td>
            <td><span class="badge bg-light text-dark border"><?= e(t($u['role'])) ?></span></td>
            <td>
              <span class="badge <?= $u['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= e($u['status']) ?></span>
            </td>
            <td class="text-end">
              <form method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <button class="btn btn-sm btn-outline-<?= $u['status'] === 'active' ? 'danger' : 'success' ?>"
                  data-confirm="Change status of <?= e($u['full_name']) ?>?">
                  <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
