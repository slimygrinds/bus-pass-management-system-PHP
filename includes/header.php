<?php
/**
 * Header Template
 * 
 * Contains the HTML head section, Bootstrap CDN links,
 * and the top navigation bar. Included in all pages.
 * 
 * Variables expected:
 * - $pageTitle (string) - Page title for <title> tag
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

// Start output buffering to prevent "headers already sent" errors
ob_start();

// Include auth functions
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db.php';

// Default page title
$pageTitle = $pageTitle ?? 'Bus Pass Management System';

// Get notification count for users
$notificationCount = 0;
if (isUser()) {
    $notificationCount = getUnreadNotificationCount($pdo, $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bus Pass Management System - Apply, manage, and track bus passes for students">
    <title><?php echo outputSafe($pageTitle); ?> | <?php echo SITE_NAME; ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php if (isLoggedIn()): ?>
<!-- ==========================================
     Top Navigation Bar (shown when logged in)
     ========================================== -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top top-navbar">
    <div class="container-fluid">
        <!-- Sidebar Toggle Button -->
        <button class="btn btn-link text-white sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
            <i class="bi bi-list fs-3"></i>
        </button>

        <!-- Vertical Divider spacer between hamburger and brand -->
        <div class="navbar-divider"></div>

        <!-- Brand -->
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
            <i class="bi bi-bus-front me-1"></i> <?php echo SITE_SHORT_NAME; ?>
        </a>
        
        <!-- Right Side Items -->
        <div class="ms-auto d-flex align-items-center">
            
            <?php if (isUser()): ?>
            <!-- Notifications Dropdown (Users Only) -->
            <div class="dropdown me-3">
                <a class="btn btn-link text-white position-relative" href="#" id="notifDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-5"></i>
                    <?php if ($notificationCount > 0): ?>
                    <span class="position-absolute top-0 start-50 ms-3 translate-middle badge rounded-pill bg-danger" id="notifBadge" style="font-size: 0.65rem;">
                        <?php echo $notificationCount; ?>
                    </span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notifDropdown" id="notificationList">
                    <li><h6 class="dropdown-header">Notifications</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="text-center p-2 text-muted" id="notifLoading">Loading...</li>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- User Menu Dropdown -->
            <div class="dropdown">
                <a class="btn btn-link text-white dropdown-toggle" href="#" id="userDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo outputSafe(getCurrentUserName()); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php if (isUser()): ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>user/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>user/change_password.php"><i class="bi bi-key me-2"></i>Change Password</a></li>
                    <?php elseif (isAdmin()): ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var notifDropdown = document.getElementById('notifDropdown');
    var notifBadge = document.getElementById('notifBadge');
    if (notifDropdown && notifBadge) {
        notifDropdown.addEventListener('show.bs.dropdown', function () {
            notifBadge.style.display = 'none';
        });
    }
});
</script>
