<?php
/**
 * Database connection using PDO.
 *
 * We use PDO because it supports prepared statements which protect the
 * system from SQL injection (see the security phase of the project).
 * The connection is returned as a single shared object ($pdo).
 */

require_once __DIR__ . '/config.php';

function db()
{
    // Keep one connection for the whole request.
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw errors so we notice bugs
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // rows come back as associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                   // use real prepared statements
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // A friendly message so students know what to fix in XAMPP.
        die(
            '<div style="font-family:Arial;padding:30px;color:#c62828;">'
            . '<h2>Database connection failed</h2>'
            . '<p>Please make sure MySQL is running in XAMPP and that the database '
            . '<b>' . DB_NAME . '</b> has been imported.</p>'
            . '<p><small>' . htmlspecialchars($e->getMessage()) . '</small></p>'
            . '</div>'
        );
    }

    return $pdo;
}
