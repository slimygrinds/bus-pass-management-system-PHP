<?php
/**
 * Logout Script
 * 
 * Destroys the current session, clears cookies,
 * and redirects to the login page.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

session_start();

require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Log the logout activity before destroying session
if (isset($_SESSION['user_id'])) {
    logActivity($pdo, 'user', $_SESSION['user_id'], 'User logged out');
} elseif (isset($_SESSION['admin_id'])) {
    logActivity($pdo, 'admin', $_SESSION['admin_id'], 'Admin logged out');
}

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start new session for flash message
session_start();
setFlashMessage('success', 'You have been logged out successfully.');

// Redirect to login page
header("Location: " . BASE_URL . "login.php");
exit();
?>
