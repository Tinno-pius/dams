<?php
/**
 * logout.php - ends the session and returns to the login page.
 */
require_once __DIR__ . '/includes/init.php';

logout_user();

// Start a fresh session just to carry the flash message.
session_start();
set_flash('success', 'You have been logged out.');
redirect('login.php');
