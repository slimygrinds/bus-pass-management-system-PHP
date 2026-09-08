<?php
/**
 * Feedback Management
 * 
 * Admin can view user feedback and send replies.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Feedback';
$currentPage = 'feedback';

require_once '../includes/header.php';
requireAdmin();
require_once '../includes/admin_sidebar.php';

// Handle reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && validateCSRFToken($_POST['csrf_token'])) {
    $feedbackId = intval($_POST['feedback_id'] ?? 0);
    $reply = sanitize($_POST['admin_reply'] ?? '');
    
    if ($feedbackId > 0 && !empty($reply)) {
        $stmt = $pdo->prepare("UPDATE feedback SET admin_reply = ?, status = 'replied' WHERE id = ?");
        $stmt->execute([$reply, $feedbackId]);
        
        // Get user_id to send notification
        $stmt = $pdo->prepare("SELECT user_id, subject FROM feedback WHERE id = ?");
        $stmt->execute([$feedbackId]);
        $fb = $stmt->fetch();
        if ($fb) {
            createNotification($pdo, $fb['user_id'], 'Feedback Reply', "Admin replied to your feedback: \"{$fb['subject']}\"");
        }
        
        setFlashMessage('success', 'Reply sent!');
        redirect(BASE_URL . 'admin/feedback.php');
    }
}

// Fetch all feedback
$stmt = $pdo->query("SELECT f.*, u.full_name, u.email FROM feedback f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC");
$feedbacks = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
?>

<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Feedback</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<div class="card table-card">
    <div class="card-header"><i class="bi bi-chat-dots me-2"></i>User Feedback (<?php echo count($feedbacks); ?>)</div>
    <div class="card-body">
        <?php if (empty($feedbacks)): ?>
            <div class="text-center py-4 text-muted"><i class="bi bi-chat-dots fs-1"></i><p class="mt-2">No feedback received yet.</p></div>
        <?php else: ?>
            <?php foreach ($feedbacks as $fb): ?>
            <div class="card mb-3 border-start border-4 <?php echo $fb['status'] === 'new' ? 'border-primary' : ($fb['status'] === 'replied' ? 'border-success' : 'border-info'); ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <strong><?php echo outputSafe($fb['full_name']); ?></strong>
                            <small class="text-muted ms-2"><?php echo outputSafe($fb['email']); ?></small>
                        </div>
                        <div>
                            <?php echo getStatusBadge($fb['status']); ?>
                            <small class="text-muted ms-2"><?php echo formatDate($fb['created_at']); ?></small>
                        </div>
                    </div>
                    <h6 class="mb-1"><?php echo outputSafe($fb['subject']); ?></h6>
                    <p class="text-muted mb-2"><?php echo outputSafe($fb['message']); ?></p>
                    
                    <?php if ($fb['admin_reply']): ?>
                        <div class="bg-success-light p-2 rounded">
                            <small class="fw-bold text-success"><i class="bi bi-reply me-1"></i>Admin Reply:</small>
                            <p class="mb-0 small"><?php echo outputSafe($fb['admin_reply']); ?></p>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="feedback_id" value="<?php echo $fb['id']; ?>">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" name="admin_reply" placeholder="Type your reply..." required>
                                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-send me-1"></i>Reply</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
