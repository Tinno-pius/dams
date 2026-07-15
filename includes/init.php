<?php
/**
 * init.php - the file every page includes first.
 *
 * It starts the session securely and loads all the shared helper files
 * so the rest of the code can just call the functions it needs.
 */

// Secure session cookie settings.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,                 // JavaScript cannot read the cookie
        'samesite' => 'Lax',                // helps against CSRF
    ]);
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/lang.php';

// Allow switching language from any page with ?lang=sw or ?lang=en
if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    // Redirect back to the same page without the ?lang parameter.
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $url);
    exit;
}
