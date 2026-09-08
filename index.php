<?php
/**
 * Index Page
 * 
 * Entry point of the application.
 * Redirects to the appropriate dashboard or login page.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

// Start session
session_start();

// Include auth helper
require_once 'includes/auth.php';

// Redirect based on role
if (isAdmin()) {
    header("Location: admin/dashboard.php");
    exit();
} elseif (isUser()) {
    header("Location: user/dashboard.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>
