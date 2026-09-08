<?php
/**
 * Utility Functions
 * 
 * Contains all reusable helper functions used across the project.
 * Includes sanitization, CSRF protection, file upload, flash messages, etc.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

// ==========================================
// Input Sanitization
// ==========================================

/**
 * Sanitize user input to prevent XSS attacks
 * 
 * @param string $data - Raw user input
 * @return string - Sanitized input
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Sanitize output for displaying in HTML
 * 
 * @param string $data - Data to display
 * @return string - Safe HTML output
 */
function outputSafe($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// ==========================================
// CSRF Token Protection
// ==========================================

/**
 * Generate a CSRF token and store in session
 * 
 * @return string - The generated CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get the CSRF hidden input field HTML
 * 
 * @return string - HTML hidden input with CSRF token
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate CSRF token from form submission
 * 
 * @param string $token - Token from form POST data
 * @return bool - Whether token is valid
 */
function validateCSRFToken($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

// ==========================================
// Flash Messages
// ==========================================

/**
 * Set a flash message in session
 * 
 * @param string $type - Message type (success, danger, warning, info)
 * @param string $message - The message text
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Display flash message if exists, then clear it
 * 
 * @return string - HTML alert div or empty string
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = $_SESSION['flash_message']['message'];
        unset($_SESSION['flash_message']);
        return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                    ' . $message . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
    return '';
}

// ==========================================
// Redirect Helper
// ==========================================

/**
 * Redirect to a given URL
 * 
 * @param string $url - URL to redirect to
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// ==========================================
// File Upload Handler
// ==========================================

/**
 * Upload a file securely
 * 
 * @param array $file - $_FILES array element
 * @param string $uploadDir - Target upload directory path
 * @param array $allowedTypes - Allowed MIME types
 * @return array - ['success' => bool, 'filename' => string, 'error' => string]
 */
function uploadFile($file, $uploadDir, $allowedTypes = []) {
    $result = ['success' => false, 'filename' => '', 'error' => ''];
    
    // Check if file was uploaded
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'File upload failed. Please try again.';
        return $result;
    }
    
    // Check file size (2MB max)
    if ($file['size'] > MAX_FILE_SIZE) {
        $result['error'] = 'File size exceeds 2MB limit.';
        return $result;
    }
    
    // Check file type
    $fileType = mime_content_type($file['tmp_name']);
    if (!empty($allowedTypes) && !in_array($fileType, $allowedTypes)) {
        $result['error'] = 'Invalid file type. Allowed: JPG, PNG' . (in_array('application/pdf', $allowedTypes) ? ', PDF' : '');
        return $result;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . strtolower($extension);
    
    // Create directory if not exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        $result['success'] = true;
        $result['filename'] = $filename;
    } else {
        $result['error'] = 'Failed to save uploaded file.';
    }
    
    return $result;
}

// ==========================================
// Application Number Generator
// ==========================================

/**
 * Generate unique application number
 * Format: APP-YYYYMMDD-XXXX
 * 
 * @param PDO $pdo - Database connection
 * @return string - Unique application number
 */
function generateApplicationNumber($pdo) {
    $date = date('Ymd');
    
    // Count today's applications to generate sequence
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE DATE(applied_at) = CURDATE()");
    $stmt->execute();
    $count = $stmt->fetchColumn() + 1;
    
    return APP_PREFIX . '-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate unique pass number
 * Format: BP-YYYY-XXXX
 * 
 * @param PDO $pdo - Database connection
 * @return string - Unique pass number
 */
function generatePassNumber($pdo) {
    $year = date('Y');
    
    // Count total passes this year
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM passes WHERE created_at LIKE ?");
    $stmt->execute([$year . '-%']);
    $count = $stmt->fetchColumn() + 1;
    
    return PASS_PREFIX . '-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// ==========================================
// Date & Time Helpers
// ==========================================

/**
 * Format date for display
 * 
 * @param string $date - Date string
 * @param string $format - Output format
 * @return string - Formatted date
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date)) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Calculate expiry date based on pass duration
 * 
 * @param string $issueDate - Issue date
 * @param string $duration - Duration (1-month, 3-month, 6-month, 1-year)
 * @return string - Expiry date in Y-m-d format
 */
function calculateExpiryDate($issueDate, $duration) {
    $months = [
        '1-month' => 1,
        '3-month' => 3,
        '6-month' => 6,
        '1-year'  => 12
    ];
    
    $addMonths = $months[$duration] ?? 1;
    return date('Y-m-d', strtotime($issueDate . " +$addMonths months"));
}

// ==========================================
// Status Badge Helper
// ==========================================

/**
 * Get Bootstrap badge HTML for status
 * 
 * @param string $status - Status value
 * @return string - HTML badge
 */
function getStatusBadge($status) {
    $badges = [
        'pending'     => '<span class="badge bg-warning text-dark">Pending</span>',
        'approved'    => '<span class="badge bg-success">Approved</span>',
        'rejected'    => '<span class="badge bg-danger">Rejected</span>',
        'active'      => '<span class="badge bg-success">Active</span>',
        'expired'     => '<span class="badge bg-secondary">Expired</span>',
        'renewed'     => '<span class="badge bg-info">Renewed</span>',
        'cancelled'   => '<span class="badge bg-dark">Cancelled</span>',
        'inactive'    => '<span class="badge bg-secondary">Inactive</span>',
        'blocked'     => '<span class="badge bg-danger">Blocked</span>',
        'maintenance' => '<span class="badge bg-warning text-dark">Maintenance</span>',
        'new'         => '<span class="badge bg-primary">New</span>',
        'read'        => '<span class="badge bg-info">Read</span>',
        'replied'     => '<span class="badge bg-success">Replied</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

// ==========================================
// Pagination Helper
// ==========================================

/**
 * Generate pagination HTML
 * 
 * @param int $currentPage - Current page number
 * @param int $totalPages - Total number of pages
 * @param string $baseUrl - Base URL for pagination links
 * @return string - HTML pagination
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $prevDisabled . '">
                <a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage - 1) . '">Previous</a>
              </li>';
    
    // Page numbers
    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        $activeClass = ($i == $currentPage) ? 'active' : '';
        $html .= '<li class="page-item ' . $activeClass . '">
                    <a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a>
                  </li>';
    }
    
    // Next button
    $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $nextDisabled . '">
                <a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage + 1) . '">Next</a>
              </li>';
    
    $html .= '</ul></nav>';
    return $html;
}

// ==========================================
// Activity Logger
// ==========================================

/**
 * Log user/admin activity
 * 
 * @param PDO $pdo - Database connection
 * @param string $userType - 'user' or 'admin'
 * @param int $userId - User ID
 * @param string $action - Action description
 */
function logActivity($pdo, $userType, $userId, $action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_type, user_id, action, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userType, $userId, $action, $ip]);
}

// ==========================================
// Notification Helper
// ==========================================

/**
 * Create a notification for a user
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User ID
 * @param string $title - Notification title
 * @param string $message - Notification message
 */
function createNotification($pdo, $userId, $title, $message) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $title, $message]);
}

/**
 * Get unread notification count
 * 
 * @param PDO $pdo - Database connection
 * @param int $userId - User ID
 * @return int - Count of unread notifications
 */
function getUnreadNotificationCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

// ==========================================
// Server-Side Validation Functions
// ==========================================

/**
 * Validate name (only alphabets and spaces)
 */
function validateName($name) {
    return preg_match('/^[a-zA-Z\s]{2,100}$/', $name);
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate mobile number (10 digits)
 */
function validateMobile($mobile) {
    return preg_match('/^[0-9]{10}$/', $mobile);
}

/**
 * Validate pincode (6 digits)
 */
function validatePincode($pincode) {
    return preg_match('/^[0-9]{6}$/', $pincode);
}

/**
 * Validate Aadhar number (12 digits)
 */
function validateAadhar($aadhar) {
    return preg_match('/^[0-9]{12}$/', $aadhar);
}

/**
 * Validate age (1-100)
 */
function validateAge($age) {
    return is_numeric($age) && $age >= 1 && $age <= 100;
}

/**
 * Validate username (alphanumeric, dots, underscores, 4-50 chars)
 */
function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9._]{4,50}$/', $username);
}

/**
 * Validate password strength (min 8 chars)
 */
function validatePassword($password) {
    return strlen($password) >= 8;
}
?>
