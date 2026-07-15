<?php
/**
 * index.php - entry point.
 * Sends logged-in users to their dashboard, everyone else to the login page.
 */
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect(dashboard_for_role(current_role()));
}
redirect('login.php');
