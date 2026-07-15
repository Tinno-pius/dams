<?php
/**
 * Patient list for the health worker, with quick links to the RCH4 card.
 */
$page_title = 'Patient List';
require_once __DIR__ . '/../includes/init.php';
require_role('healthworker');

$search = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM patients';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE full_name LIKE ? OR registration_number LIKE ? OR phone LIKE ?';
    $like = "%$search%";
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-6"><input type="text" name="q" class="form-control" placeholder="Search patients..." value="<?= e($search) ?>"></div>
  <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div>
  <div class="col-auto ms-auto"><a href="<?= BASE_URL ?>/healthworker/register_patient.php" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Register</a></div>
</form>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light"><tr><th>Reg. No.</th><th>Name</th><th>Age</th><th>EDD</th><th>Risk</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$patients): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No patients found.</td></tr>
        <?php else: foreach ($patients as $p): ?>
          <tr>
            <td><?= e($p['registration_number']) ?></td>
            <td><?= e($p['full_name']) ?></td>
            <td><?= e($p['age']) ?></td>
            <td class="small"><?= $p['edd'] ? date('d M Y', strtotime($p['edd'])) : '-' ?></td>
            <td><span class="badge <?= $p['risk_status'] === 'high' ? 'badge-high' : 'badge-low' ?>"><?= $p['risk_status'] === 'high' ? t('high_risk') : t('low_risk') ?></span></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/modules/rch4/edit.php?patient_id=<?= (int) $p['id'] ?>"><i class="bi bi-pencil-square"></i> RCH4</a>
              <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/modules/rch4/view.php?patient_id=<?= (int) $p['id'] ?>"><i class="bi bi-eye"></i></a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
