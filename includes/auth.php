<?php
/**
 * Authentication and role-based access control.
 */

/**
 * Is somebody logged in?
 */
function is_logged_in()
{
    return !empty($_SESSION['user']);
}

/**
 * Get the logged-in user (array) or null.
 */
function current_user()
{
    return $_SESSION['user'] ?? null;
}

/**
 * Get the role of the logged-in user.
 */
function current_role()
{
    return $_SESSION['user']['role'] ?? null;
}

/**
 * Log a user in by saving their details in the session.
 * We store only safe fields, never the password.
 */
function login_user($user)
{
    // Regenerate the session id to prevent session fixation attacks.
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'        => $user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
    ];
}

/**
 * Log the current user out.
 */
function logout_user()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Require a logged-in user, otherwise send to the login page.
 */
function require_login()
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

/**
 * Require a specific role (or one of several roles).
 * Example: require_role('admin');  require_role(['admin','healthworker']);
 */
function require_role($roles)
{
    require_login();
    $roles = (array) $roles;
    if (!in_array(current_role(), $roles, true)) {
        http_response_code(403);
        die('Access denied. You do not have permission to view this page.');
    }
}

/**
 * The dashboard URL that matches a role.
 */
function dashboard_for_role($role)
{
    switch ($role) {
        case 'admin':        return 'admin/dashboard.php';
        case 'healthworker': return 'healthworker/dashboard.php';
        case 'patient':      return 'patient/dashboard.php';
        default:             return 'login.php';
    }
}
