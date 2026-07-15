<?php
/**
 * Reports & analytics - Phase 10.
 * Shared data functions used by the admin and health worker report pages.
 */
require_once __DIR__ . '/../../config/database.php';

/**
 * Summary counts for the report cards.
 */
function report_summary()
{
    return [
        'patients'    => (int) db()->query('SELECT COUNT(*) c FROM patients')->fetch()['c'],
        'high_risk'   => (int) db()->query("SELECT COUNT(*) c FROM patients WHERE risk_status='high'")->fetch()['c'],
        'visits'      => (int) db()->query('SELECT COUNT(*) c FROM anc_visits')->fetch()['c'],
        'appts'       => (int) db()->query('SELECT COUNT(*) c FROM appointments')->fetch()['c'],
        'labs'        => (int) db()->query('SELECT COUNT(*) c FROM laboratory_results')->fetch()['c'],
        'sms'         => (int) db()->query('SELECT COUNT(*) c FROM sms_logs')->fetch()['c'],
    ];
}

/**
 * Monthly ANC attendance for the last 6 months (labels + counts).
 */
function report_monthly_attendance()
{
    $rows = db()->query(
        "SELECT DATE_FORMAT(visit_date, '%Y-%m') AS ym, COUNT(*) AS total
         FROM anc_visits
         WHERE visit_date IS NOT NULL
         GROUP BY ym ORDER BY ym DESC LIMIT 6"
    )->fetchAll();
    $rows = array_reverse($rows);
    $labels = [];
    $data = [];
    foreach ($rows as $r) {
        $labels[] = date('M Y', strtotime($r['ym'] . '-01'));
        $data[] = (int) $r['total'];
    }
    return ['labels' => $labels, 'data' => $data];
}

/**
 * Pregnancy risk distribution (low vs high).
 */
function report_risk_distribution()
{
    $low  = (int) db()->query("SELECT COUNT(*) c FROM patients WHERE risk_status='low'")->fetch()['c'];
    $high = (int) db()->query("SELECT COUNT(*) c FROM patients WHERE risk_status='high'")->fetch()['c'];
    return ['low' => $low, 'high' => $high];
}

/**
 * Patient registration trend for the last 6 months.
 */
function report_registration_trend()
{
    $rows = db()->query(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total
         FROM patients GROUP BY ym ORDER BY ym DESC LIMIT 6"
    )->fetchAll();
    $rows = array_reverse($rows);
    $labels = [];
    $data = [];
    foreach ($rows as $r) {
        $labels[] = date('M Y', strtotime($r['ym'] . '-01'));
        $data[] = (int) $r['total'];
    }
    return ['labels' => $labels, 'data' => $data];
}
