<?php
/**
 * User Sidebar Navigation
 * 
 * Sidebar menu for the student/user dashboard.
 * Highlights the currently active menu item.
 * 
 * Variable expected:
 * - $currentPage (string) - Current page identifier for active state
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$currentPage = $currentPage ?? '';
?>

<!-- Sidebar Overlay (for mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Navigation -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="text-center py-3">
            <?php 
            // Display user photo
            $userPhoto = $_SESSION['user_photo'] ?? 'default-avatar.png';
            $photoPath = BASE_URL . 'uploads/photos/' . $userPhoto;
            ?>
            <img src="<?php echo $photoPath; ?>" alt="Profile" class="rounded-circle sidebar-avatar" width="70" height="70">
            <h6 class="mt-2 mb-0 text-white"><?php echo outputSafe(getCurrentUserName()); ?></h6>
            <small class="text-white-50">User</small>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>user/dashboard.php">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'apply_pass') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>user/apply_pass.php">
                <i class="bi bi-plus-circle"></i> <span>Apply New Pass</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'renew_pass') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>user/renew_pass.php">
                <i class="bi bi-arrow-repeat"></i> <span>Renew Pass</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'my_applications') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>user/my_applications.php">
                <i class="bi bi-file-earmark-text"></i> <span>My Applications</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'download_pass') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>user/download_pass.php">
                <i class="bi bi-download"></i> <span>Download Pass</span>
            </a>
        </li>
        
        <li class="sidebar-divider"></li>
        
        <li class="<?php echo ($currentPage == 'search_routes') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>user/search_routes.php">
                <i class="bi bi-search"></i> <span>Search Bus Route</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'bus_timetable') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>user/bus_timetable.php">
                <i class="bi bi-clock"></i> <span>Bus Timetable</span>
            </a>
        </li>
        
        <li class="sidebar-divider"></li>
        
        <li class="<?php echo ($currentPage == 'profile') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>user/profile.php">
                <i class="bi bi-person"></i> <span>Profile</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'change_password') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>user/change_password.php">
                <i class="bi bi-key"></i> <span>Change Password</span>
            </a>
        </li>
        
        <li class="sidebar-divider"></li>
        
        <li>
            <a href="<?php echo BASE_URL; ?>logout.php" class="text-danger">
                <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Content Wrapper Start -->
<div class="content-wrapper">
    <div class="main-content">
