<?php
/**
 * Shared reports view. Included by admin/reports.php and
 * healthworker/reports.php after the header has been printed.
 */
require_once __DIR__ . '/report_data.php';

$summary      = report_summary();
$attendance   = report_monthly_attendance();
$risk         = report_risk_distribution();
$registration = report_registration_trend();
?>
<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['Patient Registrations', $summary['patients'], 'bg-soft-green'],
      ['High Risk Pregnancies', $summary['high_risk'], 'bg-soft-red'],
      ['ANC Attendance',        $summary['visits'],    'bg-soft-teal'],
      ['Appointments',          $summary['appts'],     'bg-soft-orange'],
      ['Laboratory Results',    $summary['labs'],      'bg-soft-blue'],
      ['SMS Logs',              $summary['sms'],       'bg-soft-purple'],
  ];
  foreach ($cards as $c): ?>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="stat-card">
        <div class="stat-value"><?= $c[1] ?></div>
        <div class="stat-label"><?= e($c[0]) ?></div>
        <div class="mt-2" style="height:4px;border-radius:4px;" class="<?= $c[2] ?>"></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Monthly ANC Attendance</div>
      <div class="card-body"><canvas id="attendanceChart" height="120"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Pregnancy Risk</div>
      <div class="card-body"><canvas id="riskChart" height="180"></canvas></div>
    </div>
  </div>
  <div class="col-12">
    <div class="card">
      <div class="card-header"><i class="bi bi-graph-up me-2"></i>Patient Registration Trend</div>
      <div class="card-body"><canvas id="registrationChart" height="90"></canvas></div>
    </div>
  </div>
</div>

<script>
const attendanceData   = <?= json_encode($attendance) ?>;
const riskData         = <?= json_encode($risk) ?>;
const registrationData = <?= json_encode($registration) ?>;

// Chart.js is loaded at the bottom of the page (footer.php), so wait for it.
window.addEventListener('load', function () {
new Chart(document.getElementById('attendanceChart'), {
  type: 'bar',
  data: { labels: attendanceData.labels, datasets: [{ label: 'Visits', data: attendanceData.data, backgroundColor: '#2e7d32' }] },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

new Chart(document.getElementById('riskChart'), {
  type: 'doughnut',
  data: { labels: ['Low Risk', 'High Risk'], datasets: [{ data: [riskData.low, riskData.high], backgroundColor: ['#2e7d32', '#c62828'] }] }
});

new Chart(document.getElementById('registrationChart'), {
  type: 'line',
  data: { labels: registrationData.labels, datasets: [{ label: 'Registrations', data: registrationData.data, borderColor: '#1565c0', backgroundColor: 'rgba(21,101,192,0.1)', fill: true, tension: 0.3 }] },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
});
</script>
