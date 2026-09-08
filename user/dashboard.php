<?php
/**
 * User Dashboard
 * 
 * Main landing page after user login. Shows:
 * - Welcome card
 * - Current pass status & expiry
 * - Quick action links
 * - Recent notifications
 * - Application statistics
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

require_once '../includes/header.php';
requireUser();
require_once '../includes/user_sidebar.php';

$userId = $_SESSION['user_id'];

// ==========================================
// Fetch Dashboard Data
// ==========================================

// Get user profile data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Get current active pass
$stmt = $pdo->prepare("SELECT p.*, a.boarding_point, a.destination, a.pass_duration, r.source, r.destination as route_dest 
                        FROM passes p 
                        JOIN applications a ON p.application_id = a.id 
                        JOIN bus_routes r ON a.route_id = r.id 
                        WHERE p.user_id = ? AND p.status = 'active' 
                        ORDER BY p.created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$activePass = $stmt->fetch();

// Count applications by status
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM applications WHERE user_id = ? GROUP BY status");
$stmt->execute([$userId]);
$appCounts = [];
while ($row = $stmt->fetch()) {
    $appCounts[$row['status']] = $row['count'];
}

// Get recent notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Check if pass is expiring within 15 days
$expiryWarning = false;
if ($activePass) {
    $daysLeft = (strtotime($activePass['expiry_date']) - time()) / (60 * 60 * 24);
    if ($daysLeft <= 15 && $daysLeft > 0) {
        $expiryWarning = true;
    }
}
?>

<!-- Breadcrumb -->
<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active">Dashboard</li>
    </ol>
</nav>

<!-- Flash Messages -->
<?php echo displayFlashMessage(); ?>

<!-- Welcome Card -->
<div class="welcome-card">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h4><i class="bi bi-hand-wave me-2"></i>Welcome back, <?php echo outputSafe($user['full_name']); ?>!</h4>
            <p>Manage your bus pass applications, check routes, and more from your dashboard.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="badge bg-elevated" style="color: var(--text-secondary); font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color);">
                <i class="bi bi-calendar me-1" style="color: var(--primary-color);"></i><?php echo date('d M Y'); ?>
            </span>
        </div>
    </div>
</div>

<!-- Pending Payment Warning -->
<?php if (!empty($appCounts['pending_payment']) && $appCounts['pending_payment'] > 0): ?>
<div class="alert alert-info alert-dismissible fade show border-info" role="alert">
    <div class="d-flex align-items-center">
        <i class="bi bi-credit-card fs-4 text-info me-3"></i>
        <div>
            <strong>Action Required:</strong> You have <?php echo $appCounts['pending_payment']; ?> application(s) approved and awaiting payment. 
            <a href="my_applications.php" class="alert-link">Complete payment now</a> to generate your pass.
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Renewal Warning -->
<?php if ($expiryWarning): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Renewal Reminder:</strong> Your bus pass expires on 
    <strong><?php echo formatDate($activePass['expiry_date']); ?></strong>. 
    <a href="renew_pass.php" class="alert-link">Renew Now</a>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Total Applications</div>
                        <div class="stat-number"><?php echo array_sum($appCounts); ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Pending</div>
                        <div class="stat-number"><?php echo $appCounts['pending'] ?? 0; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-clock"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Approved</div>
                        <div class="stat-number"><?php echo $appCounts['approved'] ?? 0; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Rejected</div>
                        <div class="stat-number"><?php echo $appCounts['rejected'] ?? 0; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Current Pass Status -->
    <div class="col-lg-6 mb-4">
        <div class="card table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-credit-card me-2"></i>Current Pass Status</span>
                <?php if ($activePass): ?>
                    <?php echo getStatusBadge($activePass['status']); ?>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($activePass): ?>
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Pass Number</td>
                            <td><strong><?php echo outputSafe($activePass['pass_number']); ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Route</td>
                            <td><?php echo outputSafe($activePass['source'] . ' → ' . $activePass['route_dest']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Issue Date</td>
                            <td><?php echo formatDate($activePass['issue_date']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Expiry Date</td>
                            <td>
                                <?php echo formatDate($activePass['expiry_date']); ?>
                                <?php if ($expiryWarning): ?>
                                    <span class="badge bg-warning text-dark ms-1">Expiring Soon</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Duration</td>
                            <td><?php echo outputSafe($activePass['pass_duration']); ?></td>
                        </tr>
                    </table>
                    <div class="mt-3">
                        <a href="download_pass.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-download me-1"></i>Download Pass
                        </a>
                        <a href="renew_pass.php" class="btn btn-sm btn-outline-primary ms-1">
                            <i class="bi bi-arrow-repeat me-1"></i>Renew
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-credit-card-2-front text-muted fs-1"></i>
                        <p class="text-muted mt-2">No active bus pass found.</p>
                        <a href="apply_pass.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>Apply for a Pass
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-lg-6 mb-4">
        <div class="card table-card h-100">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <a href="apply_pass.php" class="quick-action">
                            <i class="bi bi-plus-circle d-block"></i>
                            <h6>Apply New Pass</h6>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="search_routes.php" class="quick-action">
                            <i class="bi bi-search d-block"></i>
                            <h6>Search Routes</h6>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="my_applications.php" class="quick-action">
                            <i class="bi bi-file-earmark-text d-block"></i>
                            <h6>My Applications</h6>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="bus_timetable.php" class="quick-action">
                            <i class="bi bi-clock d-block"></i>
                            <h6>Bus Timetable</h6>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Profile Card & Notifications -->
<div class="row">
    <!-- Profile Summary -->
    <div class="col-lg-5 mb-4">
        <div class="card table-card h-100">
            <div class="card-header">
                <i class="bi bi-person me-2"></i>Profile Summary
            </div>
            <div class="card-body text-center">
                <?php 
                $photoPath = BASE_URL . 'uploads/photos/' . ($user['photo'] ?? 'default-avatar.png');
                ?>
                <img src="<?php echo $photoPath; ?>" alt="Profile" class="rounded-circle mb-3" width="80" height="80" style="object-fit: cover; border: 2px solid var(--primary-color);">
                <h5><?php echo outputSafe($user['full_name']); ?></h5>
                <p class="text-muted mb-1"><?php echo outputSafe($user['course'] ?? 'N/A'); ?> | Semester <?php echo outputSafe($user['semester'] ?? 'N/A'); ?></p>
                <p class="text-muted small"><?php echo outputSafe($user['college_name'] ?? 'N/A'); ?></p>
                <a href="profile.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit Profile
                </a>
            </div>
        </div>
    </div>
    
    <!-- Recent Notifications -->
    <div class="col-lg-7 mb-4">
        <div class="card table-card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-bell me-2"></i>Recent Notifications</span>
                <span class="badge bg-primary rounded-pill"><?php echo count($notifications); ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-bell-slash fs-3"></i>
                        <p class="mt-2">No notifications yet</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notif): ?>
                        <div class="list-group-item <?php echo $notif['is_read'] ? '' : 'notif-unread'; ?>">
                            <div class="d-flex justify-content-between">
                                <strong class="small <?php echo $notif['is_read'] ? 'text-muted' : 'text-yellow'; ?>">
                                    <?php echo outputSafe($notif['title']); ?>
                                </strong>
                                <small class="text-muted"><?php echo formatDate($notif['created_at'], 'd M'); ?></small>
                            </div>
                            <p class="mb-0 small text-muted mt-1"><?php echo outputSafe($notif['message']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
