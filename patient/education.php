<?php
/**
 * Health education articles for the patient to read.
 */
$page_title = 'Health Education';
require_once __DIR__ . '/../includes/init.php';
require_role('patient');

$articles = db()->query('SELECT * FROM health_articles ORDER BY id DESC')->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
  <?php if (!$articles): ?>
    <div class="col-12"><div class="alert alert-light border">No health articles yet.</div></div>
  <?php else: foreach ($articles as $a): ?>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="fw-bold text-dams-green"><i class="bi bi-heart-pulse me-1"></i><?= e($a['title']) ?></h6>
          <p class="mb-2 small"><?= nl2br(e($a['body'])) ?></p>
          <small class="text-muted"><?= date('d M Y', strtotime($a['created_at'])) ?></small>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
