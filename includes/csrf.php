<?php
/**
 * CSRF (Cross Site Request Forgery) protection.
 *
 * Every form that changes data includes a hidden token. When the form is
 * submitted we check the token matches the one saved in the session, so
 * other websites cannot trick a logged-in user into submitting our forms.
 */

/**
 * Create the token once and keep it in the session.
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Print the hidden input for a form.
 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Check the token on a POST request. Stops the script if it is wrong.
 */
function csrf_check()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $sent = $_POST['csrf_token'] ?? '';
    if (empty($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(419);
        die('Invalid or expired form token. Please go back and try again.');
    }
}
