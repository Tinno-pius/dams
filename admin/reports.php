<?php
/**
 * Admin reports & analytics page.
 */
$page_title = 'Reports & Analytics';
require_once __DIR__ . '/../includes/init.php';
require_role('admin');
require_once __DIR__ . '/../includes/header.php';
require __DIR__ . '/../modules/reports/report_view.php';
require_once __DIR__ . '/../includes/footer.php';
