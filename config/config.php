<?php
/**
 * DAMS - Digital Antenatal Monitoring System
 * Main configuration file
 *
 * This file holds the general settings for the whole system.
 * It is included by almost every page through includes/init.php.
 */

// Show errors while developing on XAMPP (turn off on the live server).
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone (Tanzania).
date_default_timezone_set('Africa/Dar_es_Salaam');

// ---- Application details ----
define('APP_NAME', 'Digital Antenatal Monitoring System');
define('APP_SHORT', 'DAMS');

// Absolute path to the project root folder on the disk.
define('BASE_PATH', dirname(__DIR__));

/*
 * BASE_URL is the web address where the project runs.
 * On XAMPP the project usually sits in htdocs/dams so the URL is:
 *   http://localhost/dams
 *
 * We work it out from the document root so it stays correct no matter
 * how deep the current page is (login.php, admin/..., modules/rch4/...).
 */
$docRoot  = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
$basePath = str_replace('\\', '/', BASE_PATH);
$subDir = '';
if ($docRoot !== '' && strpos($basePath, $docRoot) === 0) {
    $subDir = substr($basePath, strlen($docRoot)); // e.g. "/dams" or ""
}
$subDir = rtrim('/' . trim($subDir, '/'), '/'); // "/dams" or ""
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
define('BASE_URL', $protocol . '://' . $host . $subDir);

// Upload folder (for profile photos, health article images, etc.).
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

// ---- Database settings (change these on the live cPanel server) ----
define('DB_HOST', getenv('DAMS_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DAMS_DB_NAME') ?: 'dams_db');
define('DB_USER', getenv('DAMS_DB_USER') ?: 'root');
define('DB_PASS', getenv('DAMS_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ---- SMS gateway settings ----
// Fill these in from your Africa's Talking or Beem account.
define('SMS_PROVIDER', getenv('DAMS_SMS_PROVIDER') ?: 'africastalking'); // africastalking | beem
define('AT_USERNAME', getenv('DAMS_AT_USERNAME') ?: 'sandbox');
define('AT_API_KEY', getenv('DAMS_AT_API_KEY') ?: '');
define('AT_SENDER_ID', getenv('DAMS_AT_SENDER_ID') ?: 'DAMS');
define('BEEM_API_KEY', getenv('DAMS_BEEM_API_KEY') ?: '');
define('BEEM_SECRET_KEY', getenv('DAMS_BEEM_SECRET_KEY') ?: '');
define('BEEM_SENDER_ID', getenv('DAMS_BEEM_SENDER_ID') ?: 'DAMS');
