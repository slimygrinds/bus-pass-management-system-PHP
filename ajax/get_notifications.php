<?php
/**
 * AJAX: Get Notifications
 * 
 * Fetches the latest notifications for the logged-in user.
 * Marks fetched notifications as read.
 * Returns JSON array of notifications.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

session_start();

require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isUser()) {
    echo json_encode([]);
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch latest 10 notifications
$stmt = $pdo->prepare("SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Format time_ago for each notification
foreach ($notifications as &$notif) {
    $time = strtotime($notif['created_at']);
    $diff = time() - $time;
    
    if ($diff < 60) {
        $notif['time_ago'] = 'Just now';
    } elseif ($diff < 3600) {
        $notif['time_ago'] = floor($diff / 60) . ' min ago';
    } elseif ($diff < 86400) {
        $notif['time_ago'] = floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        $notif['time_ago'] = floor($diff / 86400) . ' days ago';
    } else {
        $notif['time_ago'] = date('d M Y', $time);
    }
}

// Mark all as read
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);

echo json_encode($notifications);
?>
