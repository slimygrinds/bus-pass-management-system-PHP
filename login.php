<?php
/**
 * Login Page
 * 
 * Combined User and Admin login page with pill-style toggle tabs.
 * Handles both user and admin authentication with CSRF protection.
 * 
 * Features:
 * - Pill-style tabs to switch between User and Admin login
 * - Show/Hide password toggle
 * - Remember Me functionality
 * - Client-side + Server-side validation
 * - Session management with regeneration
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

session_start();

// Include required files
require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// If already logged in, redirect
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        redirect(BASE_URL . 'admin/dashboard.php');
    } else {
        redirect(BASE_URL . 'user/dashboard.php');
    }
}

// Initialize variables
$errors = [];
$loginType = $_POST['login_type'] ?? 'user';
$username = '';

// ==========================================
// Handle Login Form Submission
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        // Sanitize inputs
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $loginType = sanitize($_POST['login_type'] ?? 'user');
        
        // Validate inputs
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($username) < 4) {
            $errors[] = "Username must be at least 4 characters.";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required.";
        }
        
        // If no validation errors, attempt login
        if (empty($errors)) {
            
            if ($loginType === 'admin') {
                // ========== Admin Login ==========
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password'])) {
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['user_name'] = $admin['full_name'];
                    $_SESSION['user_role'] = 'admin';
                    $_SESSION['last_activity'] = time();
                    
                    // Log activity
                    logActivity($pdo, 'admin', $admin['id'], 'Admin logged in');
                    
                    // Redirect to admin dashboard
                    redirect(BASE_URL . 'admin/dashboard.php');
                } else {
                    $errors[] = "Invalid admin username or password.";
                }
                
            } else {
                // ========== User Login ==========
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Check if user account is active
                    if ($user['status'] !== 'active') {
                        $errors[] = "Your account has been " . $user['status'] . ". Contact admin.";
                    } else {
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['full_name'];
                        $_SESSION['user_role'] = 'user';
                        $_SESSION['user_photo'] = $user['photo'] ?? 'default-avatar.png';
                        $_SESSION['last_activity'] = time();
                        
                        
                        // Log activity
                        logActivity($pdo, 'user', $user['id'], 'User logged in');
                        
                        // Redirect to user dashboard
                        redirect(BASE_URL . 'user/dashboard.php');
                    }
                } else {
                    $errors[] = "Invalid username or password.";
                }
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login - Bus Pass Management System">
    <title>Login | <?php echo SITE_NAME; ?></title>
    
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
<body class="login-page-body">

<div class="login-split-wrapper">

    <!-- Full-width red bottom strip — on the wrapper so it spans both panels -->
    <div class="lp-bottom-strip"></div>

    <!-- Geometric accent circles (global to the wrapper) -->
    <div class="lp-circle lp-circle-1"></div>
    <div class="lp-circle lp-circle-2"></div>
    <div class="lp-circle lp-circle-3"></div>

    <!-- ===== LEFT PANEL — Branding ===== -->
    <div class="login-panel-left">

        <div class="lp-brand">
            <div class="lp-icon-wrap">
                <i class="bi bi-bus-front"></i>
            </div>
            <h1 class="lp-title">Bus Pass<br><span>Management</span></h1>
            <p class="lp-subtitle">Your digital pass for seamless city travel. Apply, manage and track all in one place.</p>

            <!-- Feature bullets -->
            <ul class="lp-features">
                <li><i class="bi bi-check-circle-fill"></i> Apply &amp; renew bus passes instantly</li>
                <li><i class="bi bi-check-circle-fill"></i> Track application status live</li>
                <li><i class="bi bi-check-circle-fill"></i> Download &amp; print your pass</li>
            </ul>
        </div>
    </div>

    <!-- ===== RIGHT PANEL — Form ===== -->
    <div class="login-panel-right">

        <div class="lp-form-card">

            <!-- Header -->
            <div class="lp-form-header">
                <h2>Welcome Back</h2>
                <p>Sign in to continue to your dashboard</p>
            </div>

            <!-- Flash / Error Messages -->
            <?php echo displayFlashMessage(); ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo outputSafe($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Tab Switcher -->
            <div class="lp-tabs" id="loginTabs">
                <button class="lp-tab <?php echo ($loginType === 'user') ? 'active' : ''; ?>"
                        onclick="switchTab('user')" type="button" id="userTabBtn">
                    <i class="bi bi-person-fill me-1"></i> User
                </button>
                <button class="lp-tab <?php echo ($loginType === 'admin') ? 'active' : ''; ?>"
                        onclick="switchTab('admin')" type="button" id="adminTabBtn">
                    <i class="bi bi-shield-lock-fill me-1"></i> Admin
                </button>
            </div>

            <!-- Login Form -->
            <form method="POST" action="" class="lp-form" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="login_type" id="loginType" value="<?php echo outputSafe($loginType); ?>">

                <!-- Username -->
                <div class="lp-field">
                    <label for="username" class="lp-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Enter your username"
                               value="<?php echo outputSafe($username); ?>"
                               minlength="4" maxlength="50" required autocomplete="username">
                    </div>
                </div>

                <!-- Password -->
                <div class="lp-field">
                    <label for="password" class="lp-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter your password"
                               maxlength="50" required autocomplete="current-password">
                        <button class="input-group-text password-toggle" type="button" data-target="password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Sign In Button -->
                <button type="submit" class="btn lp-btn-signin w-100" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>

                <!-- Register Link (User only) -->
                <div class="text-center mt-4 lp-register" id="registerLink">
                    <span class="lp-register-text">Don't have an account?</span>
                    <a href="register.php" class="lp-register-link">Register here</a>
                </div>
            </form>

        </div>
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/validation.js"></script>

<script>
function switchTab(type) {
    document.getElementById('loginType').value = type;
    document.getElementById('userTabBtn').classList.toggle('active', type === 'user');
    document.getElementById('adminTabBtn').classList.toggle('active', type === 'admin');
    const registerLink = document.getElementById('registerLink');
    if (type === 'admin') {
        registerLink.style.display = 'none';
    } else {
        registerLink.style.display = 'block';
    }
    document.getElementById('username').value = '';
    document.getElementById('password').value = '';
    document.getElementById('username').focus();
}

document.getElementById('loginForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    let isValid = true;
    const usernameResult = ValidationRules.validateUsername(username.value);
    showValidation(username, usernameResult);
    if (!usernameResult.valid) isValid = false;
    const passwordResult = ValidationRules.validatePassword(password.value);
    showValidation(password, passwordResult);
    if (!passwordResult.valid) isValid = false;
    if (!isValid) e.preventDefault();
});

document.getElementById('username').addEventListener('blur', function() {
    showValidation(this, ValidationRules.validateUsername(this.value));
});
document.getElementById('password').addEventListener('blur', function() {
    showValidation(this, ValidationRules.validatePassword(this.value));
});

document.addEventListener('DOMContentLoaded', function() {
    const currentType = document.getElementById('loginType').value;
    switchTab(currentType);
});
</script>

</body>
</html>
