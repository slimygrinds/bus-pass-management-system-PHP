<?php
/**
 * AJAX: Check Username Availability
 * 
 * Checks if a username is already taken during registration.
 * Returns JSON response with availability status.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

session_start();

require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Set JSON response header
header('Content-Type: application/json');

$username = sanitize($_GET['username'] ?? '');

if (empty($username) || strlen($username) < 4) {
    echo json_encode(['available' => false, 'message' => 'Username too short']);
    exit();
}

// Check if username exists in database
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$exists = $stmt->fetch();

if ($exists) {
    echo json_encode(['available' => false, 'message' => 'Username is already taken']);
} else {
    echo json_encode(['available' => true, 'message' => 'Username is available']);
}
?>
