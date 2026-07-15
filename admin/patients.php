<?php
/**
 * Admin view of all registered patients (read only).
 */
$page_title = 'Patients';
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$search = trim($_GET['q'] ?? '');
$sql = 'SELECT p.*, u.full_name AS nurse FROM patients p LEFT JOIN users u ON u.id = p.registered_by';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE p.full_name LIKE ? OR p.registration_number LIKE ? OR p.phone LIKE ? OR p.district LIKE ?';
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}
$sql .= ' ORDER BY p.id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-6">
    <input type="text" name="q" class="form-control" placeholder="Search by name, reg. no, phone or district" value="<?= e($search) ?>">
  </div>
  <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div>
</form>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light"><tr><th>Reg. No.</th><th>Name</th><th>Age</th><th>Phone</th><th>District</th><th>Nurse</th><th>Risk</th><th></th></tr></thead>
      <tbody>
        <?php if (!$patients): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No patients found.</td></tr>
        <?php else: foreach ($patients as $p): ?>
          <tr>
            <td><?= e($p['registration_number']) ?></td>
            <td><?= e($p['full_name']) ?></td>
            <td><?= e($p['age']) ?></td>
            <td><?= e($p['phone']) ?></td>
            <td><?= e($p['district']) ?></td>
            <td class="small text-muted"><?= e($p['nurse']) ?></td>
            <td><span class="badge <?= $p['risk_status'] === 'high' ? 'badge-high' : 'badge-low' ?>"><?= $p['risk_status'] === 'high' ? t('high_risk') : t('low_risk') ?></span></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/modules/rch4/view.php?patient_id=<?= (int) $p['id'] ?>"><i class="bi bi-card-heading"></i> RCH4</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
