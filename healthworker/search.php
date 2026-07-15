<?php
/**
 * Search module (Phase 11).
 * Search patients by name, registration number, phone, clinic number or district.
 */
$page_title = 'Search';
require_once __DIR__ . '/../includes/init.php';
require_role('healthworker');

$q = trim($_GET['q'] ?? '');
$results = [];
if ($q !== '') {
    $like = "%$q%";
    $stmt = db()->prepare(
        'SELECT * FROM patients
         WHERE full_name LIKE ? OR registration_number LIKE ? OR phone LIKE ?
            OR discount_card_number LIKE ? OR district LIKE ?
         ORDER BY full_name'
    );
    $stmt->execute([$like, $like, $like, $like, $like]);
    $results = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>
<form class="row g-2 mb-4" method="get">
  <div class="col-md-8"><input type="text" name="q" class="form-control form-control-lg" placeholder="Name, registration no, phone, clinic no or district" value="<?= e($q) ?>" autofocus></div>
  <div class="col-auto"><button class="btn btn-primary btn-lg"><i class="bi bi-search me-1"></i>Search</button></div>
</form>

<?php if ($q !== ''): ?>
  <p class="text-muted"><?= count($results) ?> result(s) for "<b><?= e($q) ?></b>"</p>
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light"><tr><th>Reg. No.</th><th>Name</th><th>Phone</th><th>District</th><th>Risk</th><th></th></tr></thead>
        <tbody>
          <?php if (!$results): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No matches found.</td></tr>
          <?php else: foreach ($results as $p): ?>
            <tr>
              <td><?= e($p['registration_number']) ?></td>
              <td><?= e($p['full_name']) ?></td>
              <td><?= e($p['phone']) ?></td>
              <td><?= e($p['district']) ?></td>
              <td><span class="badge <?= $p['risk_status'] === 'high' ? 'badge-high' : 'badge-low' ?>"><?= $p['risk_status'] === 'high' ? t('high_risk') : t('low_risk') ?></span></td>
              <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/modules/rch4/edit.php?patient_id=<?= (int) $p['id'] ?>">Open RCH4</a></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
