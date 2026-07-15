<?php
/**
 * Digital RCH4 Card - editable version (health worker).
 *
 * This is the core module. The layout is kept the same as the uploaded
 * "kadi ya kliniki" design, but every field is connected to the database.
 * Saving the card also:
 *   - recalculates the EDD from the LNMP,
 *   - checks the danger signs and marks the pregnancy high risk,
 *   - stores the next appointment date from the ANC visits.
 */
$page_title = 'Digital RCH4 Card';
require_once __DIR__ . '/../../includes/init.php';
require_role('healthworker');
require_once __DIR__ . '/rch4_functions.php';

$patientId = (int) ($_GET['patient_id'] ?? 0);
$data = rch4_load($patientId);
if (!$data) {
    set_flash('error', 'Patient not found.');
    redirect('healthworker/patients.php');
}

$riskFields = rch4_risk_fields();
$visitRows  = rch4_visit_rows();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // ---- 1. Patient info (Tab 1) ----
        $lnmp = $_POST['lnmp'] ?: null;
        $edd  = $_POST['edd'] ?: calculate_edd($lnmp);
        $height = $_POST['height_cm'] !== '' ? (float) $_POST['height_cm'] : null;
        $heightCat = $height !== null ? ($height < 150 ? 'Below 150cm' : 'Above 150cm') : null;

        $up = $pdo->prepare(
            'UPDATE patients SET clinic_name=?, registration_number=?, discount_card_number=?,
             full_name=?, age=?, height_cm=?, height_category=?, education=?, occupation=?,
             partner_name=?, partner_age=?, partner_education=?, partner_occupation=?,
             village=?, chairperson_name=?, district=?, gravida=?, parity=?, living_children=?,
             lnmp=?, edd=? WHERE id=?'
        );
        $up->execute([
            $_POST['clinic_name'] ?: null, $_POST['registration_number'], $_POST['discount_card_number'] ?: null,
            $_POST['full_name'], $_POST['age'] ?: null, $height, $heightCat,
            $_POST['education'] ?: null, $_POST['occupation'] ?: null,
            $_POST['partner_name'] ?: null, $_POST['partner_age'] ?: null,
            $_POST['partner_education'] ?: null, $_POST['partner_occupation'] ?: null,
            $_POST['village'] ?: null, $_POST['chairperson_name'] ?: null, $_POST['district'] ?: null,
            $_POST['gravida'] ?: null, $_POST['parity'] ?: null, $_POST['living_children'] ?: null,
            $lnmp, $edd, $patientId,
        ]);

        // ---- 2. RCH4 card (PMTCT + appointment / worker details) ----
        $cardCols = ['art_status','drug_regimen','ctx','ctc_number','infant_feeding','adherence',
                     'family_planning','birth_preparedness','sti_counselling','worker_name',
                     'worker_position','worker_signature'];
        $cardVals = [];
        foreach ($cardCols as $col) { $cardVals[$col] = $_POST[$col] ?? null; }
        $returnDate = $_POST['return_date'] ?: null;

        if (!empty($data['card']['id'])) {
            $sql = 'UPDATE rch4_cards SET art_status=?, drug_regimen=?, ctx=?, ctc_number=?, infant_feeding=?,
                    adherence=?, family_planning=?, birth_preparedness=?, sti_counselling=?, return_date=?,
                    worker_name=?, worker_position=?, worker_signature=? WHERE id=?';
            $pdo->prepare($sql)->execute([
                $cardVals['art_status'], $cardVals['drug_regimen'], $cardVals['ctx'], $cardVals['ctc_number'],
                $cardVals['infant_feeding'], $cardVals['adherence'], $cardVals['family_planning'],
                $cardVals['birth_preparedness'], $cardVals['sti_counselling'], $returnDate,
                $cardVals['worker_name'], $cardVals['worker_position'], $cardVals['worker_signature'],
                $data['card']['id'],
            ]);
        } else {
            $pdo->prepare('INSERT INTO rch4_cards (patient_id, card_number, created_by) VALUES (?, ?, ?)')
                ->execute([$patientId, 'RCH4-' . $_POST['registration_number'], current_user()['id']]);
        }

        // ---- 3. Risk assessment (Tab 2, sections A/B/C) ----
        $riskCols = rch4_all_risk_columns();
        $riskValues = [];
        foreach ($riskCols as $col) { $riskValues[$col] = isset($_POST['risk'][$col]) ? 1 : 0; }
        $isHigh = rch4_is_high_risk($riskValues) ? 1 : 0;
        $advised = $_POST['advised_delivery_place'] ?? null;

        $setParts = [];
        $params = [];
        foreach ($riskCols as $col) { $setParts[] = "$col = ?"; $params[] = $riskValues[$col]; }
        $setParts[] = 'advised_delivery_place = ?'; $params[] = $advised;
        $setParts[] = 'is_high_risk = ?';          $params[] = $isHigh;
        $setParts[] = 'assessed_by = ?';           $params[] = current_user()['id'];

        if (!empty($data['risk']['id'])) {
            $params[] = $data['risk']['id'];
            $pdo->prepare('UPDATE risk_assessment SET ' . implode(', ', $setParts) . ' WHERE id = ?')->execute($params);
        } else {
            $cols = array_merge($riskCols, ['advised_delivery_place','is_high_risk','assessed_by','patient_id']);
            $ins = array_merge(array_values($riskValues), [$advised, $isHigh, current_user()['id'], $patientId]);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            $pdo->prepare('INSERT INTO risk_assessment (' . implode(',', $cols) . ") VALUES ($ph)")->execute($ins);
        }

        // Update the patient's overall risk status.
        $pdo->prepare('UPDATE patients SET risk_status = ? WHERE id = ?')
            ->execute([$isHigh ? 'high' : 'low', $patientId]);

        // ---- 4. Laboratory results ----
        $labHb = $_POST['hb_level'] !== '' ? (float) $_POST['hb_level'] : null;
        if (!empty($data['lab']['id'])) {
            $pdo->prepare('UPDATE laboratory_results SET blood_group=?, syphilis_status=?, hb_level=?, other_results=? WHERE id=?')
                ->execute([$_POST['blood_group'] ?: null, $_POST['syphilis_status'] ?: null, $labHb, $_POST['other_results'] ?: null, $data['lab']['id']]);
        } elseif ($_POST['blood_group'] || $_POST['syphilis_status'] || $labHb || $_POST['other_results']) {
            $pdo->prepare('INSERT INTO laboratory_results (patient_id, blood_group, syphilis_status, hb_level, other_results, recorded_by) VALUES (?,?,?,?,?,?)')
                ->execute([$patientId, $_POST['blood_group'] ?: null, $_POST['syphilis_status'] ?: null, $labHb, $_POST['other_results'] ?: null, current_user()['id']]);
        }

        // ---- 5. ANC visits (Tab 3) ----
        for ($n = 1; $n <= 4; $n++) {
            $visitDate = $_POST['visit_date'][$n] ?? '';
            $nextAppt  = $_POST['next_appointment'][$n] ?? '';
            $cells = $_POST['visit'][$n] ?? [];

            // Skip completely empty columns.
            $hasData = $visitDate !== '' || $nextAppt !== '';
            foreach ($cells as $val) { if (trim($val) !== '') { $hasData = true; break; } }
            if (!$hasData) { continue; }

            $vc = array_keys($visitRows); // column names
            $vv = [];
            foreach ($vc as $col) { $vv[$col] = $cells[$col] ?? null; }

            $existing = $data['visits'][$n] ?? null;
            if ($existing) {
                $set = ['visit_date = ?'];
                $vp = [$visitDate ?: null];
                foreach ($vc as $col) { $set[] = "$col = ?"; $vp[] = $vv[$col] ?: null; }
                $set[] = 'next_appointment = ?'; $vp[] = $nextAppt ?: null;
                $vp[] = $existing['id'];
                $pdo->prepare('UPDATE anc_visits SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($vp);
            } else {
                $cols = array_merge(['patient_id','visit_number','visit_date'], $vc, ['next_appointment','recorded_by']);
                $vals = array_merge([$patientId, $n, $visitDate ?: null], array_map(fn($c) => $vv[$c] ?: null, $vc), [$nextAppt ?: null, current_user()['id']]);
                $ph = implode(',', array_fill(0, count($cols), '?'));
                $pdo->prepare('INSERT INTO anc_visits (' . implode(',', $cols) . ") VALUES ($ph)")->execute($vals);
            }

            // Store the next appointment (auto scheduling).
            if ($nextAppt !== '') {
                $chk = $pdo->prepare('SELECT id FROM appointments WHERE patient_id = ? AND appointment_date = ?');
                $chk->execute([$patientId, $nextAppt]);
                if (!$chk->fetch()) {
                    $pdo->prepare('INSERT INTO appointments (patient_id, appointment_date, reason, status, created_by) VALUES (?, ?, ?, "scheduled", ?)')
                        ->execute([$patientId, $nextAppt, 'ANC Visit ' . ($n + 1), current_user()['id']]);
                }
            }
        }

        $pdo->commit();

        // Notify the patient (if they have a login) about the update / risk.
        if (!empty($data['patient']['user_id'])) {
            if ($isHigh) {
                notify($data['patient']['user_id'], 'High Risk Alert', 'Your last check-up shows danger signs. Please follow the advice of your nurse and attend the clinic.');
            } else {
                notify($data['patient']['user_id'], 'RCH4 Card Updated', 'Your antenatal card has been updated by the clinic.');
            }
        }

        set_flash('success', $isHigh ? 'Card saved. This pregnancy is marked HIGH RISK.' : 'Card saved successfully.');
        redirect('modules/rch4/edit.php?patient_id=' . $patientId);
    } catch (Throwable $ex) {
        $pdo->rollBack();
        set_flash('error', 'Could not save: ' . $ex->getMessage());
        redirect('modules/rch4/edit.php?patient_id=' . $patientId);
    }
}

// ---- Load fresh values for the form ----
$patient = $data['patient'];
$card = $data['card'];
$risk = $data['risk'];
$lab  = $data['lab'];
$visits = $data['visits'];

/** Small helpers for form values. */
function pv($arr, $key) { return e($arr[$key] ?? ''); }
function checked($arr, $key) { return !empty($arr[$key]) ? 'checked' : ''; }

$isHighNow = !empty($risk['is_high_risk']);

require_once __DIR__ . '/../../includes/header.php';
?>
<link href="<?= BASE_URL ?>/assets/css/rch4.css" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <a href="<?= BASE_URL ?>/healthworker/patients.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  <div>
    <a href="<?= BASE_URL ?>/modules/rch4/view.php?patient_id=<?= $patientId ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> View / Print</a>
  </div>
</div>

<?php if ($isHighNow): ?>
  <div class="high-risk-banner"><i class="bi bi-exclamation-triangle-fill fs-4"></i> This pregnancy is marked <u>HIGH RISK</u>. Refer / monitor closely.</div>
<?php endif; ?>

<form method="post">
<?= csrf_field() ?>
<div class="rch4-wrap">
  <div class="rch4-header">
    <div class="rch4-header-top">
      <div>
        <div class="rch4-title">KADI YA KLINIKI YA WAJA WAZITO</div>
        <div class="rch4-subtitle">Jamhuri ya Muungano wa Tanzania &middot; Wizara ya Afya na Ustawi wa Jamii</div>
      </div>
      <div>
        <div class="rch4-badge">RCH 4</div>
        <div style="font-size:11px; opacity:0.8; margin-top:4px; text-align:center;">KADI HAIUZWI</div>
      </div>
    </div>
  </div>

  <div class="rch4-body">
    <div class="m-tabs no-print">
      <button type="button" class="m-tab active" onclick="showMTab('mTab1', this)">Habari za Mama</button>
      <button type="button" class="m-tab" onclick="showMTab('mTab2', this)">Chunguza / Dalili</button>
      <button type="button" class="m-tab" onclick="showMTab('mTab3', this)">Rekodi ya Mahudhurio</button>
    </div>

    <!-- TAB 1 -->
    <div class="m-page active" id="mTab1">
      <div class="m-info-grid">
        <div class="m-info-field"><div class="m-info-label">Jina la Kliniki</div><div class="m-info-value"><input class="rch4-input" name="clinic_name" value="<?= pv($patient,'clinic_name') ?>"></div></div>
        <div class="m-info-field"><div class="m-info-label">Namba ya Uandikishaji</div><div class="m-info-value"><input class="rch4-input" name="registration_number" value="<?= pv($patient,'registration_number') ?>"></div></div>
        <div class="m-info-field" style="grid-column:1/-1;"><div class="m-info-label">Namba ya Hati Punguzo</div><div class="m-info-value"><input class="rch4-input" name="discount_card_number" value="<?= pv($patient,'discount_card_number') ?>"></div></div>
      </div>

      <div class="section-heading">Habari za Mama</div>
      <div class="m-sub-grid" style="margin-bottom:14px;">
        <div class="m-sub-field"><div class="m-sub-label">Jina la Mama</div><div class="m-sub-value"><input class="rch4-input" name="full_name" value="<?= pv($patient,'full_name') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Umri</div><div class="m-sub-value"><input class="rch4-input" name="age" value="<?= pv($patient,'age') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Kimo (CM)</div><div class="m-sub-value"><input class="rch4-input" name="height_cm" value="<?= pv($patient,'height_cm') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Elimu</div><div class="m-sub-value"><input class="rch4-input" name="education" value="<?= pv($patient,'education') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Kazi</div><div class="m-sub-value"><input class="rch4-input" name="occupation" value="<?= pv($patient,'occupation') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Kimo Category</div><div class="m-sub-value"><?= pv($patient,'height_category') ?></div></div>
      </div>

      <div class="section-heading">Habari za Mume / Mwenzi</div>
      <div class="m-sub-grid" style="margin-bottom:16px;">
        <div class="m-sub-field"><div class="m-sub-label">Jina la Mume/Mwenzi</div><div class="m-sub-value"><input class="rch4-input" name="partner_name" value="<?= pv($patient,'partner_name') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Umri</div><div class="m-sub-value"><input class="rch4-input" name="partner_age" value="<?= pv($patient,'partner_age') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Elimu</div><div class="m-sub-value"><input class="rch4-input" name="partner_education" value="<?= pv($patient,'partner_education') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Kazi</div><div class="m-sub-value"><input class="rch4-input" name="partner_occupation" value="<?= pv($patient,'partner_occupation') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Kijiji / Mtaa</div><div class="m-sub-value"><input class="rch4-input" name="village" value="<?= pv($patient,'village') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Jina la Mwenyekiti</div><div class="m-sub-value"><input class="rch4-input" name="chairperson_name" value="<?= pv($patient,'chairperson_name') ?>"></div></div>
        <div class="m-sub-field" style="grid-column:1/-1;"><div class="m-sub-label">Wilaya</div><div class="m-sub-value"><input class="rch4-input" name="district" value="<?= pv($patient,'district') ?>"></div></div>
      </div>

      <hr class="m-section-divider">
      <div class="section-heading">Habari Kuhusu Uzazi Uliotangulia</div>
      <div class="m-sub-grid" style="margin-bottom:12px;">
        <div class="m-sub-field"><div class="m-sub-label">Mimba ya Ngapi (Gravida)</div><div class="m-sub-value"><input class="rch4-input" name="gravida" value="<?= pv($patient,'gravida') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Amezaa Mara Ngapi (Parity)</div><div class="m-sub-value"><input class="rch4-input" name="parity" value="<?= pv($patient,'parity') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Watoto Walio Hai</div><div class="m-sub-value"><input class="rch4-input" name="living_children" value="<?= pv($patient,'living_children') ?>"></div></div>
      </div>

      <div class="m-sub-grid">
        <div class="m-sub-field" style="grid-column:1/3;"><div class="m-sub-label">LNMP (Tarehe ya Hedhi ya Mwisho)</div><div class="m-sub-value"><input type="date" class="rch4-input" name="lnmp" value="<?= pv($patient,'lnmp') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">EDD (Tarehe ya Kujifungua)</div><div class="m-sub-value"><input type="date" class="rch4-input" name="edd" value="<?= pv($patient,'edd') ?>"></div></div>
      </div>
    </div>

    <!-- TAB 2 -->
    <div class="m-page" id="mTab2">
      <?php foreach (['A' => 'MPELEKE KITUO CHA AFYA KWA UCHUNGUZI ZAIDI ENDAPO MAMA ANA:',
                      'B' => 'MSHAURI AENDE HOSPITALI KWA KUJIFUNGUA ENDAPO ANA:',
                      'C' => 'DALILI ZA HATARI ZA KUANGALIA KILA HUDHURIO (ukiona yoyote = HATARI KUBWA):'] as $sec => $title): ?>
        <div class="m-section">
          <div class="m-section-head"><div class="m-letter-badge"><?= $sec ?></div><div class="m-section-title"><?= $title ?></div></div>
          <div class="m-grid">
            <?php foreach ($riskFields[$sec] as $col => $label): ?>
              <label class="m-field"><span class="f-label"><?= e($label) ?></span>
                <input type="checkbox" name="risk[<?= $col ?>]" value="1" style="width:18px;height:18px;" <?= checked($risk,$col) ?>></label>
            <?php endforeach; ?>
          </div>
        </div>
        <hr class="m-section-divider">
      <?php endforeach; ?>

      <div class="mb-3">
        <div style="font-size:11px; font-weight:bold; margin-bottom:4px;">Mama ameshauriwa azalie wapi:</div>
        <input class="rch4-input" name="advised_delivery_place" value="<?= pv($risk,'advised_delivery_place') ?>">
      </div>

      <div class="section-heading">Vipimo Maalum vya Maabara</div>
      <div class="m-sub-grid" style="margin-bottom:12px;">
        <div class="m-sub-field"><div class="m-sub-label">Damu: Group</div><div class="m-sub-value"><input class="rch4-input" name="blood_group" value="<?= pv($lab,'blood_group') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Syphilis Status</div><div class="m-sub-value"><input class="rch4-input" name="syphilis_status" value="<?= pv($lab,'syphilis_status') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Hb (gm/dl)</div><div class="m-sub-value"><input class="rch4-input" name="hb_level" value="<?= pv($lab,'hb_level') ?>"></div></div>
        <div class="m-sub-field" style="grid-column:1/-1;"><div class="m-sub-label">Vipimo Vingine</div><div class="m-sub-value"><input class="rch4-input" name="other_results" value="<?= pv($lab,'other_results') ?>"></div></div>
      </div>

      <hr class="m-section-divider">
      <div class="section-heading" style="color:#c62828;">PMTCT</div>
      <div class="m-sub-grid" style="margin-bottom:12px;">
        <div class="m-sub-field"><div class="m-sub-label">ART Status</div><div class="m-sub-value"><input class="rch4-input" name="art_status" value="<?= pv($card,'art_status') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Dawa (Regimen)</div><div class="m-sub-value"><input class="rch4-input" name="drug_regimen" value="<?= pv($card,'drug_regimen') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">CTX</div><div class="m-sub-value"><input class="rch4-input" name="ctx" value="<?= pv($card,'ctx') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">CTC Number</div><div class="m-sub-value"><input class="rch4-input" name="ctc_number" value="<?= pv($card,'ctc_number') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Lishe ya Mtoto</div><div class="m-sub-value"><input class="rch4-input" name="infant_feeding" value="<?= pv($card,'infant_feeding') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Ufuasi (Adherence)</div><div class="m-sub-value"><input class="rch4-input" name="adherence" value="<?= pv($card,'adherence') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Uzazi wa Mpango</div><div class="m-sub-value"><input class="rch4-input" name="family_planning" value="<?= pv($card,'family_planning') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Maandalizi ya Kujifungua</div><div class="m-sub-value"><input class="rch4-input" name="birth_preparedness" value="<?= pv($card,'birth_preparedness') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Ushauri wa STI</div><div class="m-sub-value"><input class="rch4-input" name="sti_counselling" value="<?= pv($card,'sti_counselling') ?>"></div></div>
      </div>

      <hr class="m-section-divider">
      <div class="section-heading">Miadi / Mhudumu</div>
      <div class="m-sub-grid">
        <div class="m-sub-field"><div class="m-sub-label">Tarehe ya Kurudi</div><div class="m-sub-value"><input type="date" class="rch4-input" name="return_date" value="<?= pv($card,'return_date') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Jina la Mhudumu</div><div class="m-sub-value"><input class="rch4-input" name="worker_name" value="<?= pv($card,'worker_name') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Cheo cha Mhudumu</div><div class="m-sub-value"><input class="rch4-input" name="worker_position" value="<?= pv($card,'worker_position') ?>"></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Saini</div><div class="m-sub-value"><input class="rch4-input" name="worker_signature" value="<?= pv($card,'worker_signature') ?>"></div></div>
      </div>
    </div>

    <!-- TAB 3 -->
    <div class="m-page" id="mTab3">
      <div style="font-size:11px; color:#666; margin-bottom:14px;">Mimba isiyo na matatizo mama anahitaji mahudhurio 4: chini ya wiki 16, wiki 20-24, 28-32, 36-40.</div>
      <div style="overflow-x:auto;">
        <table class="m-hudhurio-table">
          <thead>
            <tr><th style="width:36%;">Kipimo</th><th>Hudhurio 1</th><th>Hudhurio 2</th><th>Hudhurio 3</th><th>Hudhurio 4</th></tr>
          </thead>
          <tbody>
            <tr>
              <td>Tarehe ya Hudhurio</td>
              <?php for ($n = 1; $n <= 4; $n++): ?>
                <td><input type="date" class="rch4-input" name="visit_date[<?= $n ?>]" value="<?= e($visits[$n]['visit_date'] ?? '') ?>"></td>
              <?php endfor; ?>
            </tr>
            <?php foreach ($visitRows as $col => $label): ?>
              <tr>
                <td><?= e($label) ?></td>
                <?php for ($n = 1; $n <= 4; $n++): ?>
                  <td><input class="rch4-input" name="visit[<?= $n ?>][<?= $col ?>]" value="<?= e($visits[$n][$col] ?? '') ?>"></td>
                <?php endfor; ?>
              </tr>
            <?php endforeach; ?>
            <tr>
              <td><b>Tarehe ya Kurudi (Next)</b></td>
              <?php for ($n = 1; $n <= 4; $n++): ?>
                <td><input type="date" class="rch4-input" name="next_appointment[<?= $n ?>]" value="<?= e($visits[$n]['next_appointment'] ?? '') ?>"></td>
              <?php endfor; ?>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="m-notice"><strong>Kumbuka:</strong> Baada ya wiki 40 mama ahudhurie kliniki kila wiki.</div>
    </div>

  </div>
  <div class="m-footer-note">Kadi hii ni mali ya serikali &middot; Haiuzwi &middot; RCH 4</div>
</div>

<div class="text-center my-4 no-print">
  <button type="submit" class="btn btn-primary btn-lg px-5"><i class="bi bi-save me-2"></i>Save RCH4 Card</button>
</div>
</form>

<script>
function showMTab(id, btn) {
  document.querySelectorAll('.m-page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.m-tab').forEach(b => b.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  btn.classList.add('active');
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
