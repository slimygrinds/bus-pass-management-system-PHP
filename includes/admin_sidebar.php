<?php
/**
 * Admin Sidebar Navigation
 * 
 * Sidebar menu for the admin dashboard.
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

<!-- Admin Sidebar Navigation -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="text-center py-3">
            <i class="bi bi-shield-lock-fill text-white fs-1"></i>
            <h6 class="mt-2 mb-0 text-white"><?php echo outputSafe(getCurrentUserName()); ?></h6>
            <small class="text-white-50">Administrator</small>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
        </li>
        
        <li class="sidebar-divider"></li>
        <li class="sidebar-heading">Management</li>
        
        <li class="<?php echo ($currentPage == 'manage_users') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>admin/manage_users.php">
                <i class="bi bi-people"></i> <span>Manage Users</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'manage_applications') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>admin/manage_applications.php">
                <i class="bi bi-file-earmark-check"></i> <span>Manage Applications</span>
            </a>
        </li>
        
        <li class="sidebar-divider"></li>
        <li class="sidebar-heading">Bus Operations</li>
        
        <li class="<?php echo ($currentPage == 'manage_routes') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>admin/manage_routes.php">
                <i class="bi bi-signpost-2"></i> <span>Manage Routes</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'manage_buses') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>admin/manage_buses.php">
                <i class="bi bi-bus-front"></i> <span>Manage Buses</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'manage_timetable') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>admin/manage_timetable.php">
                <i class="bi bi-calendar3"></i> <span>Manage Timetable</span>
            </a>
        </li>
        
        <li class="sidebar-divider"></li>
        <li class="sidebar-heading">Others</li>
        
        <li class="<?php echo ($currentPage == 'reports') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>admin/reports.php">
                <i class="bi bi-bar-chart"></i> <span>Reports</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'feedback') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>admin/feedback.php">
                <i class="bi bi-chat-dots"></i> <span>Feedback</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'settings') ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>admin/settings.php">
                <i class="bi bi-gear"></i> <span>Settings</span>
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
