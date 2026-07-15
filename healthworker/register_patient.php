<?php
/**
 * Register a pregnant woman (patient).
 *
 * This creates:
 *   1. a login account (users table, role = patient)
 *   2. a patient record (patients table = Tab 1 of the RCH4 card)
 *   3. an empty RCH4 card (rch4_cards table)
 * The EDD is calculated automatically from the LNMP.
 */
$page_title = 'Register Patient';
require_once __DIR__ . '/../includes/init.php';
require_role('healthworker');

$errors = [];
$old = [];
$fields = ['full_name','email','phone','age','height_cm','education','occupation',
           'partner_name','village','district','gravida','parity','living_children','lnmp','clinic_name'];
foreach ($fields as $f) { $old[$f] = ''; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($fields as $f) { $old[$f] = trim($_POST[$f] ?? ''); }

    if ($old['full_name'] === '') $errors[] = 'Mother name is required.';
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is not valid.';

    // Make sure the email (if given) is not already used.
    if (!$errors && $old['email'] !== '') {
        $c = db()->prepare('SELECT id FROM users WHERE email = ?');
        $c->execute([$old['email']]);
        if ($c->fetch()) $errors[] = 'That email already has an account.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $userId = null;
            // Create a login account only if an email was provided.
            if ($old['email'] !== '') {
                $tempPassword = $_POST['password'] ?: 'patient123';
                $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
                $u = $pdo->prepare('INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, "patient")');
                $u->execute([$old['full_name'], $old['email'], $old['phone'], $hash]);
                $userId = (int) $pdo->lastInsertId();
            }

            $reg = generate_registration_number();
            $edd = calculate_edd($old['lnmp'] ?: null);
            $height = $old['height_cm'] !== '' ? (float) $old['height_cm'] : null;
            $heightCat = $height !== null ? ($height < 150 ? 'Below 150cm' : 'Above 150cm') : null;

            $p = $pdo->prepare(
                'INSERT INTO patients
                 (user_id, registration_number, clinic_name, full_name, phone, age, height_cm, height_category,
                  education, occupation, partner_name, village, district, gravida, parity, living_children,
                  lnmp, edd, registered_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $p->execute([
                $userId, $reg, $old['clinic_name'] ?: get_setting('clinic_name'), $old['full_name'], $old['phone'],
                $old['age'] ?: null, $height, $heightCat, $old['education'], $old['occupation'],
                $old['partner_name'], $old['village'], $old['district'],
                $old['gravida'] ?: null, $old['parity'] ?: null, $old['living_children'] ?: null,
                $old['lnmp'] ?: null, $edd, current_user()['id'],
            ]);
            $patientId = (int) $pdo->lastInsertId();

            // Create the empty RCH4 card.
            $card = $pdo->prepare('INSERT INTO rch4_cards (patient_id, card_number, created_by) VALUES (?, ?, ?)');
            $card->execute([$patientId, 'RCH4-' . $reg, current_user()['id']]);

            $pdo->commit();
            if ($userId) {
                notify($userId, 'Welcome to DAMS', 'Your antenatal card has been created. Please attend your clinic visits.');
            }
            set_flash('success', 'Patient registered. Registration number: ' . $reg);
            redirect('modules/rch4/edit.php?patient_id=' . $patientId);
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Could not save: ' . $ex->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-header"><i class="bi bi-person-plus me-2"></i>Register Pregnant Woman</div>
  <div class="card-body">
    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
      <?= csrf_field() ?>
      <h6 class="text-dams-green fw-bold">Clinic & Mother Information</h6>
      <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label">Clinic Name</label><input name="clinic_name" class="form-control" value="<?= e($old['clinic_name'] ?: get_setting('clinic_name')) ?>"></div>
        <div class="col-md-6"><label class="form-label">Mother Full Name *</label><input name="full_name" class="form-control" value="<?= e($old['full_name']) ?>" required></div>
        <div class="col-md-3"><label class="form-label">Age</label><input type="number" name="age" class="form-control" value="<?= e($old['age']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Height (cm)</label><input type="number" step="0.1" name="height_cm" class="form-control" value="<?= e($old['height_cm']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Education</label><input name="education" class="form-control" value="<?= e($old['education']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Occupation</label><input name="occupation" class="form-control" value="<?= e($old['occupation']) ?>"></div>
      </div>

      <h6 class="text-dams-green fw-bold">Partner & Location</h6>
      <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="form-label">Partner Name</label><input name="partner_name" class="form-control" value="<?= e($old['partner_name']) ?>"></div>
        <div class="col-md-4"><label class="form-label">Village / Street</label><input name="village" class="form-control" value="<?= e($old['village']) ?>"></div>
        <div class="col-md-4"><label class="form-label">District</label><input name="district" class="form-control" value="<?= e($old['district']) ?>"></div>
      </div>

      <h6 class="text-dams-green fw-bold">Pregnancy Information</h6>
      <div class="row g-3 mb-3">
        <div class="col-md-3"><label class="form-label">Gravida</label><input type="number" name="gravida" class="form-control" value="<?= e($old['gravida']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Parity</label><input type="number" name="parity" class="form-control" value="<?= e($old['parity']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Living Children</label><input type="number" name="living_children" class="form-control" value="<?= e($old['living_children']) ?>"></div>
        <div class="col-md-3"><label class="form-label">LNMP</label><input type="date" name="lnmp" class="form-control" value="<?= e($old['lnmp']) ?>"><div class="form-text">EDD is calculated automatically.</div></div>
      </div>

      <h6 class="text-dams-green fw-bold">Patient Login (optional)</h6>
      <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($old['email']) ?>" placeholder="mother@gmail.com"></div>
        <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($old['phone']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Password</label><input type="text" name="password" class="form-control" placeholder="default: patient123"></div>
      </div>

      <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Register & Open RCH4 Card</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
