<?php
/**
 * Patient ANC visit history and laboratory results (read only).
 */
$page_title = 'ANC History';
require_once __DIR__ . '/../includes/init.php';
require_role('patient');
require_once __DIR__ . '/../modules/rch4/rch4_functions.php';

$stmt = db()->prepare('SELECT id FROM patients WHERE user_id = ?');
$stmt->execute([current_user()['id']]);
$row = $stmt->fetch();
$pid = $row ? (int) $row['id'] : 0;

$visits = [];
$lab = [];
if ($pid) {
    $v = db()->prepare('SELECT * FROM anc_visits WHERE patient_id = ? ORDER BY visit_number');
    $v->execute([$pid]);
    $visits = $v->fetchAll();
    $l = db()->prepare('SELECT * FROM laboratory_results WHERE patient_id = ? ORDER BY id DESC LIMIT 1');
    $l->execute([$pid]);
    $lab = $l->fetch() ?: [];
}
$visitRows = rch4_visit_rows();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-clipboard-pulse me-2"></i>ANC Visit History</div>
  <div class="card-body p-0">
    <?php if (!$visits): ?>
      <p class="text-muted p-3 mb-0">No ANC visits recorded yet.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="table table-sm table-bordered mb-0 align-middle small">
        <thead class="table-light"><tr><th>Measurement</th><?php foreach ($visits as $v): ?><th>Visit <?= (int) $v['visit_number'] ?><br><small class="text-muted"><?= $v['visit_date'] ? date('d/m/y', strtotime($v['visit_date'])) : '' ?></small></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($visitRows as $col => $label): ?>
            <tr><td><?= e($label) ?></td><?php foreach ($visits as $v): ?><td><?= e($v[$col] ?? '') ?: '—' ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-droplet me-2"></i>Laboratory Results</div>
  <div class="card-body">
    <?php if (!$lab): ?>
      <p class="text-muted mb-0">No laboratory results yet.</p>
    <?php else: ?>
      <div class="row">
        <div class="col-md-3"><b>Blood Group:</b> <?= e($lab['blood_group']) ?: '—' ?></div>
        <div class="col-md-3"><b>Syphilis:</b> <?= e($lab['syphilis_status']) ?: '—' ?></div>
        <div class="col-md-3"><b>Hb:</b> <?= e($lab['hb_level']) ?: '—' ?></div>
        <div class="col-md-3"><b>Other:</b> <?= e($lab['other_results']) ?: '—' ?></div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
