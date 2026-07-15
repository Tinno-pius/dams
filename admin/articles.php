<?php
/**
 * Manage health education articles (create / delete).
 * Patients read these in their "Health Education" page.
 */
$page_title = 'Health Articles';
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $body  = trim($_POST['body'] ?? '');
        if ($title !== '' && $body !== '') {
            $stmt = db()->prepare('INSERT INTO health_articles (title, body, created_by) VALUES (?, ?, ?)');
            $stmt->execute([$title, $body, current_user()['id']]);
            set_flash('success', 'Article published.');
        } else {
            set_flash('error', 'Title and body are required.');
        }
    } elseif ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM health_articles WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        set_flash('success', 'Article deleted.');
    }
    redirect('admin/articles.php');
}

$articles = db()->query('SELECT * FROM health_articles ORDER BY id DESC')->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-plus-circle me-2"></i>New Article</div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="body" rows="5" class="form-control" required></textarea>
          </div>
          <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Publish</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <?php if (!$articles): ?>
      <div class="alert alert-light border">No articles yet.</div>
    <?php else: foreach ($articles as $a): ?>
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <h6 class="fw-bold text-dams-green"><?= e($a['title']) ?></h6>
            <form method="post" onsubmit="return false;" class="d-inline"></form>
            <form method="post" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this article?"><i class="bi bi-trash"></i></button>
            </form>
          </div>
          <p class="mb-1 small text-muted"><?= nl2br(e($a['body'])) ?></p>
          <small class="text-muted"><?= date('d M Y', strtotime($a['created_at'])) ?></small>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
