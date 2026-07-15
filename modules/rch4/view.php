<?php
/**
 * Digital RCH4 Card - read only + printable version.
 * Used by the patient (their own card), the health worker and the admin.
 * Print or "Save as PDF" is done with the browser print dialog while the
 * official RCH4 layout is preserved (see the @media print rules).
 */
$page_title = 'RCH4 Card';
require_once __DIR__ . '/../../includes/init.php';
require_login();
require_once __DIR__ . '/rch4_functions.php';

$role = current_role();
$patientId = (int) ($_GET['patient_id'] ?? 0);

// A patient can only see their own card.
if ($role === 'patient') {
    $stmt = db()->prepare('SELECT id FROM patients WHERE user_id = ?');
    $stmt->execute([current_user()['id']]);
    $mine = $stmt->fetch();
    $patientId = $mine ? (int) $mine['id'] : 0;
}

$data = rch4_load($patientId);
if (!$data) {
    set_flash('error', 'RCH4 card not found.');
    redirect(dashboard_for_role($role));
}

$patient = $data['patient'];
$card = $data['card'];
$risk = $data['risk'];
$lab  = $data['lab'];
$visits = $data['visits'];
$riskFields = rch4_risk_fields();
$visitRows  = rch4_visit_rows();

function show($arr, $key) { $v = $arr[$key] ?? ''; return $v !== '' ? e($v) : '&mdash;'; }

require_once __DIR__ . '/../../includes/header.php';
?>
<link href="<?= BASE_URL ?>/assets/css/rch4.css" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  <div class="d-flex gap-2">
    <?php if ($role === 'healthworker'): ?>
      <a href="<?= BASE_URL ?>/modules/rch4/edit.php?patient_id=<?= $patientId ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> Edit</a>
    <?php endif; ?>
    <button onclick="window.print()" class="btn btn-sm btn-primary"><i class="bi bi-printer"></i> Print / Save PDF</button>
  </div>
</div>

<?php if (!empty($risk['is_high_risk'])): ?>
  <div class="high-risk-banner no-print"><i class="bi bi-exclamation-triangle-fill fs-4"></i> HIGH RISK PREGNANCY</div>
<?php endif; ?>

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
      <button type="button" class="m-tab active" onclick="showMTab('vTab1', this)">Habari za Mama</button>
      <button type="button" class="m-tab" onclick="showMTab('vTab2', this)">Chunguza / Dalili</button>
      <button type="button" class="m-tab" onclick="showMTab('vTab3', this)">Rekodi ya Mahudhurio</button>
    </div>

    <!-- TAB 1 -->
    <div class="m-page active" id="vTab1">
      <div class="m-info-grid">
        <div class="m-info-field"><div class="m-info-label">Jina la Kliniki</div><div class="m-info-value"><?= show($patient,'clinic_name') ?></div></div>
        <div class="m-info-field"><div class="m-info-label">Namba ya Uandikishaji</div><div class="m-info-value"><?= show($patient,'registration_number') ?></div></div>
        <div class="m-info-field" style="grid-column:1/-1;"><div class="m-info-label">Namba ya Hati Punguzo</div><div class="m-info-value"><?= show($patient,'discount_card_number') ?></div></div>
      </div>
      <div class="section-heading">Habari za Mama</div>
      <div class="m-sub-grid" style="margin-bottom:14px;">
        <div class="m-sub-field"><div class="m-sub-label">Jina la Mama</div><div class="m-sub-value"><?= show($patient,'full_name') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Umri</div><div class="m-sub-value"><?= show($patient,'age') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Kimo (CM)</div><div class="m-sub-value"><?= show($patient,'height_cm') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Elimu</div><div class="m-sub-value"><?= show($patient,'education') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Kazi</div><div class="m-sub-value"><?= show($patient,'occupation') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Kimo</div><div class="m-sub-value"><?= show($patient,'height_category') ?></div></div>
      </div>
      <div class="section-heading">Habari za Mume / Mwenzi</div>
      <div class="m-sub-grid" style="margin-bottom:16px;">
        <div class="m-sub-field"><div class="m-sub-label">Jina la Mume/Mwenzi</div><div class="m-sub-value"><?= show($patient,'partner_name') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Kijiji / Mtaa</div><div class="m-sub-value"><?= show($patient,'village') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Wilaya</div><div class="m-sub-value"><?= show($patient,'district') ?></div></div>
      </div>
      <hr class="m-section-divider">
      <div class="section-heading">Uzazi Uliotangulia & Mimba ya Sasa</div>
      <div class="m-sub-grid">
        <div class="m-sub-field"><div class="m-sub-label">Gravida</div><div class="m-sub-value"><?= show($patient,'gravida') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Parity</div><div class="m-sub-value"><?= show($patient,'parity') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Watoto Hai</div><div class="m-sub-value"><?= show($patient,'living_children') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">LNMP</div><div class="m-sub-value"><?= show($patient,'lnmp') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">EDD</div><div class="m-sub-value"><?= show($patient,'edd') ?></div></div>
      </div>
    </div>

    <!-- TAB 2 -->
    <div class="m-page" id="vTab2">
      <?php foreach (['A','B','C'] as $sec): ?>
        <div class="m-section">
          <div class="m-section-head"><div class="m-letter-badge"><?= $sec ?></div><div class="m-section-title">Sehemu <?= $sec ?></div></div>
          <div class="m-grid">
            <?php foreach ($riskFields[$sec] as $col => $label): ?>
              <label class="m-field"><span class="f-label"><?= e($label) ?></span>
                <span><?= !empty($risk[$col]) ? '&#10004;' : '&mdash;' ?></span></label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <div class="mb-2"><b style="font-size:11px;">Ameshauriwa azalie:</b> <?= show($risk,'advised_delivery_place') ?></div>
      <hr class="m-section-divider">
      <div class="section-heading">Maabara & PMTCT</div>
      <div class="m-sub-grid">
        <div class="m-sub-field"><div class="m-sub-label">Blood Group</div><div class="m-sub-value"><?= show($lab,'blood_group') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Syphilis</div><div class="m-sub-value"><?= show($lab,'syphilis_status') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Hb</div><div class="m-sub-value"><?= show($lab,'hb_level') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">ART Status</div><div class="m-sub-value"><?= show($card,'art_status') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">CTC Number</div><div class="m-sub-value"><?= show($card,'ctc_number') ?></div></div>
        <div class="m-sub-field"><div class="m-sub-label">Tarehe ya Kurudi</div><div class="m-sub-value"><?= show($card,'return_date') ?></div></div>
      </div>
    </div>

    <!-- TAB 3 -->
    <div class="m-page" id="vTab3">
      <div style="overflow-x:auto;">
        <table class="m-hudhurio-table">
          <thead><tr><th style="width:36%;">Kipimo</th><th>H1</th><th>H2</th><th>H3</th><th>H4</th></tr></thead>
          <tbody>
            <tr><td>Tarehe ya Hudhurio</td>
              <?php for ($n = 1; $n <= 4; $n++): ?><td><?= show($visits[$n] ?? [], 'visit_date') ?></td><?php endfor; ?>
            </tr>
            <?php foreach ($visitRows as $col => $label): ?>
              <tr><td><?= e($label) ?></td>
                <?php for ($n = 1; $n <= 4; $n++): ?><td><?= show($visits[$n] ?? [], $col) ?></td><?php endfor; ?>
              </tr>
            <?php endforeach; ?>
            <tr><td><b>Tarehe ya Kurudi</b></td>
              <?php for ($n = 1; $n <= 4; $n++): ?><td><?= show($visits[$n] ?? [], 'next_appointment') ?></td><?php endfor; ?>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="m-footer-note">Kadi hii ni mali ya serikali &middot; Haiuzwi &middot; RCH 4</div>
</div>

<script>
function showMTab(id, btn) {
  document.querySelectorAll('.m-page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.m-tab').forEach(b => b.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  btn.classList.add('active');
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
