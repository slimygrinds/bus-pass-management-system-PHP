<?php
/**
 * Cancel Bus Pass
 * 
 * Allows users to cancel their active bus passes.
 */

session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$passId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$passId) {
    setFlashMessage('error', 'Invalid pass ID.');
    header("Location: my_applications.php");
    exit();
}

try {
    // Check if the pass belongs to the user and is active
    $stmt = $pdo->prepare("SELECT id FROM passes WHERE id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$passId, $userId]);
    $pass = $stmt->fetch();
    
    if ($pass) {
        // Update pass status to cancelled
        $updateStmt = $pdo->prepare("UPDATE passes SET status = 'cancelled' WHERE id = ?");
        $updateStmt->execute([$passId]);
        
        setFlashMessage('success', 'Your bus pass has been cancelled successfully.');
    } else {
        setFlashMessage('error', 'Pass not found or already cancelled.');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Error cancelling pass. Please try again.');
    error_log("Cancel Pass Error: " . $e->getMessage());
}

header("Location: my_applications.php");
exit();
