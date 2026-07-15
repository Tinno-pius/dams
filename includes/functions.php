<?php
/**
 * Helper functions used all over the system.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Escape output to prevent XSS (Cross Site Scripting).
 * Always use e() when printing data that came from a user or the database.
 */
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect the browser to another page and stop the script.
 */
function redirect($path)
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

/**
 * Show a one-time flash message on the next page (success / error / info).
 */
function set_flash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash()
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Read a value from the settings table.
 */
function get_setting($key, $default = null)
{
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

/**
 * Save (insert or update) a setting.
 */
function set_setting($key, $value)
{
    $stmt = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

/**
 * Work out the expected date of delivery (EDD) from the LNMP.
 * Naegele's rule: LNMP + 280 days (40 weeks).
 */
function calculate_edd($lnmp)
{
    if (empty($lnmp)) {
        return null;
    }
    $date = new DateTime($lnmp);
    $date->modify('+280 days');
    return $date->format('Y-m-d');
}

/**
 * Add a notification for a user.
 */
function notify($userId, $title, $message)
{
    if (empty($userId)) {
        return;
    }
    $stmt = db()->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $title, $message]);
}

/**
 * Count rows in a table using an optional WHERE clause.
 */
function count_rows($table, $where = '', $params = [])
{
    $allowed = [
        'users', 'patients', 'rch4_cards', 'anc_visits', 'appointments',
        'laboratory_results', 'risk_assessment', 'notifications',
        'health_articles', 'sms_logs', 'settings',
    ];
    if (!in_array($table, $allowed, true)) {
        return 0;
    }
    $sql = "SELECT COUNT(*) AS total FROM {$table}";
    if ($where !== '') {
        $sql .= " WHERE {$where}";
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetch()['total'];
}

/**
 * Make a simple next registration number, e.g. ANC-2026-0007.
 */
function generate_registration_number()
{
    $year = date('Y');
    $count = count_rows('patients') + 1;
    return 'ANC-' . $year . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
}
