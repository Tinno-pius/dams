<?php
/**
 * Shared notifications page used by all three roles.
 * The role page sets $page_title, calls require_role(...), then includes this.
 */

// Mark one / all as read.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $uid = current_user()['id'];
    if (($_POST['action'] ?? '') === 'read_all') {
        db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$uid]);
    } elseif (($_POST['action'] ?? '') === 'read') {
        db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([(int) $_POST['id'], $uid]);
    }
    redirect(current_role() . '/notifications.php');
}

$stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC');
$stmt->execute([current_user()['id']]);
$notifs = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0"><i class="bi bi-bell me-2"></i>Notifications</h6>
  <?php if ($notifs): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="btn btn-sm btn-outline-secondary">Mark all read</button></form>
  <?php endif; ?>
</div>

<div class="card">
  <div class="list-group list-group-flush">
    <?php if (!$notifs): ?>
      <div class="list-group-item text-center text-muted py-4">No notifications.</div>
    <?php else: foreach ($notifs as $n): ?>
      <div class="list-group-item d-flex justify-content-between align-items-start <?= $n['is_read'] ? '' : 'bg-light' ?>">
        <div>
          <div class="fw-semibold"><?= e($n['title']) ?> <?php if (!$n['is_read']): ?><span class="badge bg-primary ms-1">new</span><?php endif; ?></div>
          <div class="small text-muted"><?= e($n['message']) ?></div>
          <div class="small text-muted"><?= date('d M Y H:i', strtotime($n['created_at'])) ?></div>
        </div>
        <?php if (!$n['is_read']): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= $n['id'] ?>"><button class="btn btn-sm btn-link">Mark read</button></form>
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
