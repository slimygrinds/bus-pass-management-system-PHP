<?php
/**
 * Application Constants
 * 
 * Defines global constants used across the project.
 * Includes site settings, paths, and configuration values.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

// ==========================================
// Site Settings
// ==========================================
define('SITE_NAME', 'Bus Pass Management System');
define('SITE_SHORT_NAME', 'BPMS');
define('SITE_VERSION', '1.0.0');

// ==========================================
// Base URL - Change this based on your setup
// ==========================================
// Local XAMPP: project sits in a subfolder, so BASE_URL must include it.
// When you deploy to InfinityFree (files sit at htdocs root), change this
// back to '/'.
define('BASE_URL', '/bus-pass-management-system/');

// ==========================================
// Directory Paths
// ==========================================
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('PHOTOS_PATH', UPLOADS_PATH . 'photos/');
define('DOCUMENTS_PATH', UPLOADS_PATH . 'documents/');

// ==========================================
// Upload Settings
// ==========================================
define('MAX_FILE_SIZE', 2 * 1024 * 1024);  // 2MB max file size
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('ALLOWED_DOC_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']);

// ==========================================
// Session Settings
// ==========================================
define('SESSION_TIMEOUT', 1800);  // 30 minutes in seconds

// ==========================================
// Pagination
// ==========================================
define('RECORDS_PER_PAGE', 10);

// ==========================================
// Pass Number Prefix
// ==========================================
define('PASS_PREFIX', 'BP');
define('APP_PREFIX', 'APP');

// ==========================================
// Security Questions List
// ==========================================
define('SECURITY_QUESTIONS', [
    'What is your pet name?',
    'What is your birth city?',
    'What is your school name?',
    'What is your favorite color?',
    'What is your mother\'s maiden name?',
    'What is your best friend\'s name?'
]);
?>
