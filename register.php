<?php
/**
 * User Registration Page
 * 
 * Allows new users to create an account.
 * Includes comprehensive validation (client + server side).
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

session_start();

require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// If already logged in, redirect
if (isset($_SESSION['user_role'])) {
    redirect(BASE_URL . 'index.php');
}

$errors = [];
$formData = [
    'full_name' => '', 'username' => '', 'email' => '', 'mobile' => '',
    'security_question' => ''
];

// ==========================================
// Handle Registration Form Submission
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        // Sanitize all inputs
        $formData['full_name']    = sanitize($_POST['full_name'] ?? '');
        $formData['username']     = sanitize($_POST['username'] ?? '');
        $formData['email']        = sanitize($_POST['email'] ?? '');
        $formData['mobile']       = sanitize($_POST['mobile'] ?? '');
        $formData['security_question'] = sanitize($_POST['security_question'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $securityAnswer  = sanitize($_POST['security_answer'] ?? '');
        
        // ===== Server-Side Validation =====
        
        // Full Name
        if (!validateName($formData['full_name'])) {
            $errors[] = "Full name: only alphabets and spaces (2-100 characters).";
        }
        
        // Username
        if (!validateUsername($formData['username'])) {
            $errors[] = "Username: 4-50 characters, alphanumeric, dots and underscores only.";
        } else {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$formData['username']]);
            if ($stmt->fetch()) {
                $errors[] = "This username is already taken.";
            }
        }
        
        // Email
        if (!validateEmail($formData['email'])) {
            $errors[] = "Please enter a valid email address.";
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$formData['email']]);
            if ($stmt->fetch()) {
                $errors[] = "This email is already registered.";
            }
        }
        
        // Mobile
        if (!validateMobile($formData['mobile'])) {
            $errors[] = "Mobile number must be exactly 10 digits.";
        }
        
        // Password
        if (!validatePassword($password)) {
            $errors[] = "Password must be at least 8 characters.";
        }
        
        // Confirm Password
        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        }
        
        // Security Question & Answer
        if (empty($formData['security_question'])) {
            $errors[] = "Please select a security question.";
        }
        if (empty($securityAnswer)) {
            $errors[] = "Security answer is required.";
        }
        
        // If no errors, create account
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, mobile, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $formData['username'],
                $hashedPassword,
                $formData['full_name'],
                $formData['email'],
                $formData['mobile'],
                $formData['security_question'],
                strtolower($securityAnswer)
            ]);
            
            if ($result) {
                // Create welcome notification
                $userId = $pdo->lastInsertId();
                createNotification($pdo, $userId, 'Welcome to Bus Pass System', 'Your account has been created successfully. You can now apply for a bus pass.');
                
                // Log activity
                logActivity($pdo, 'user', $userId, 'New account registered');
                
                setFlashMessage('success', 'Registration successful! You can now login.');
                redirect(BASE_URL . 'login.php');
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register - Bus Pass Management System">
    <title>Register | <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="register-wrapper">
    <div class="register-card">
        <!-- Header -->
        <div class="login-header">
            <i class="bi bi-bus-front fs-1"></i>
            <h3>Create Account</h3>
            <p>Create a new user account</p>
        </div>
        
        <!-- Body -->
        <div class="login-body">
            
            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo outputSafe($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Registration Form -->
            <form method="POST" action="" id="registerForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <!-- Full Name -->
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           placeholder="Enter your full name"
                           value="<?php echo outputSafe($formData['full_name']); ?>"
                           onkeypress="return allowOnlyLetters(event)"
                           maxlength="100" required>
                </div>
                
                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Choose a username (e.g., rahul.sharma)"
                           value="<?php echo outputSafe($formData['username']); ?>"
                           minlength="4" maxlength="50" required>
                    <div class="form-text" id="usernameStatus"></div>
                </div>
                
                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="your.email@example.com"
                           value="<?php echo outputSafe($formData['email']); ?>"
                           required>
                </div>
                
                <!-- Mobile -->
                <div class="mb-3">
                    <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="mobile" name="mobile" 
                           placeholder="10-digit mobile number"
                           value="<?php echo outputSafe($formData['mobile']); ?>"
                           onkeypress="return allowOnlyDigits(event)"
                           oninput="limitLength(this, 10)"
                           maxlength="10" required>
                </div>
                
                <div class="row">
                    <!-- Password -->
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Minimum 8 characters" minlength="8" required>
                            <button class="input-group-text password-toggle" type="button" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Re-enter password" required>
                            <button class="input-group-text password-toggle" type="button" data-target="confirm_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Security Question -->
                <div class="mb-3">
                    <label for="security_question" class="form-label">Security Question <span class="text-danger">*</span></label>
                    <select class="form-select" id="security_question" name="security_question" required>
                        <option value="">Select a security question</option>
                        <?php foreach (SECURITY_QUESTIONS as $question): ?>
                            <option value="<?php echo outputSafe($question); ?>" 
                                    <?php echo ($formData['security_question'] === $question) ? 'selected' : ''; ?>>
                                <?php echo outputSafe($question); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Security Answer -->
                <div class="mb-3">
                    <label for="security_answer" class="form-label">Security Answer <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="security_answer" name="security_answer" 
                           placeholder="Your answer (case-insensitive)" required>
                </div>
                
                <!-- Submit -->
                <button type="submit" class="btn btn-primary w-100 btn-login">
                    <i class="bi bi-person-plus me-1"></i> Create Account
                </button>
                
                <!-- Login Link -->
                <div class="text-center mt-3">
                    <span class="text-muted">Already have an account?</span>
                    <a href="login.php" class="text-primary fw-500">Login here</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/validation.js"></script>

<script>
// AJAX: Check username availability on blur
document.getElementById('username').addEventListener('blur', function() {
    const username = this.value.trim();
    const statusDiv = document.getElementById('usernameStatus');
    
    if (username.length >= 4) {
        fetch('<?php echo BASE_URL; ?>ajax/check_username.php?username=' + encodeURIComponent(username))
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    statusDiv.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Username available</span>';
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    statusDiv.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Username taken</span>';
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            })
            .catch(err => console.error('Username check failed:', err));
    }
});

// Form validation on submit
document.getElementById('registerForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Validate each field
    const fullName = document.getElementById('full_name');
    const nameResult = ValidationRules.validateName(fullName.value);
    showValidation(fullName, nameResult);
    if (!nameResult.valid) isValid = false;
    
    const username = document.getElementById('username');
    const usernameResult = ValidationRules.validateUsername(username.value);
    showValidation(username, usernameResult);
    if (!usernameResult.valid) isValid = false;
    
    const email = document.getElementById('email');
    const emailResult = ValidationRules.validateEmail(email.value);
    showValidation(email, emailResult);
    if (!emailResult.valid) isValid = false;
    
    const mobile = document.getElementById('mobile');
    const mobileResult = ValidationRules.validateMobile(mobile.value);
    showValidation(mobile, mobileResult);
    if (!mobileResult.valid) isValid = false;
    
    const password = document.getElementById('password');
    const passwordResult = ValidationRules.validatePassword(password.value);
    showValidation(password, passwordResult);
    if (!passwordResult.valid) isValid = false;
    
    const confirmPassword = document.getElementById('confirm_password');
    const confirmResult = ValidationRules.validateConfirmPassword(password.value, confirmPassword.value);
    showValidation(confirmPassword, confirmResult);
    if (!confirmResult.valid) isValid = false;
    
    if (!isValid) {
        e.preventDefault();
    }
});
</script>

</body>
</html>
