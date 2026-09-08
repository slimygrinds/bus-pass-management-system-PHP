<?php
/**
 * Authentication Helper Functions
 * 
 * Handles session management, login checks, role verification,
 * and session timeout for security.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include constants for session timeout
require_once __DIR__ . '/../config/constants.php';

// ==========================================
// Session Timeout Check
// ==========================================

/**
 * Check if the session has timed out (30 min inactivity)
 * If timed out, destroy session and redirect to login
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_TIMEOUT) {
            // Session has expired
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['flash_message'] = [
                'type' => 'warning',
                'message' => 'Your session has expired due to inactivity. Please login again.'
            ];
            header("Location: " . BASE_URL . "login.php");
            exit();
        }
    }
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// ==========================================
// Login Status Checks
// ==========================================

/**
 * Check if any user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
}

/**
 * Check if logged in user is an admin
 * 
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['admin_id']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if logged in user is a student
 * 
 * @return bool
 */
function isUser() {
    return isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'user';
}

// ==========================================
// Access Control Guards
// ==========================================

/**
 * Require user to be logged in as student
 * Redirects to login if not authenticated
 */
function requireUser() {
    checkSessionTimeout();
    if (!isUser()) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => 'Please login to access this page.'
        ];
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

/**
 * Require user to be logged in as admin
 * Redirects to login if not authenticated
 */
function requireAdmin() {
    checkSessionTimeout();
    if (!isAdmin()) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => 'Admin access required. Please login as admin.'
        ];
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

/**
 * Get the currently logged in user's ID
 * 
 * @return int|null - User ID or null
 */
function getCurrentUserId() {
    if (isAdmin()) {
        return $_SESSION['admin_id'];
    } elseif (isUser()) {
        return $_SESSION['user_id'];
    }
    return null;
}

/**
 * Get the currently logged in user's name
 * 
 * @return string
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Get the currently logged in user's role
 * 
 * @return string
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? '';
}
?>
