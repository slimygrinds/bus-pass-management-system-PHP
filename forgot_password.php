<?php
/**
 * Forgot Password Page
 * 
 * Allows users to reset their password using a security question.
 * No email API required - works completely offline.
 * 
 * Flow:
 * 1. Enter username
 * 2. Answer security question
 * 3. Set new password
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

session_start();

require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$errors = [];
$step = 1; // Step 1: Enter username, Step 2: Answer question, Step 3: Reset password
$username = '';
$securityQuestion = '';
$userId = null;

// ==========================================
// Handle form submissions for each step
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        
        $action = $_POST['action'] ?? '';
        
        // Step 1: Find user by username
        if ($action === 'find_user') {
            $username = sanitize($_POST['username'] ?? '');
            
            if (empty($username)) {
                $errors[] = "Username is required.";
            } else {
                $stmt = $pdo->prepare("SELECT id, security_question FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $step = 2;
                    $securityQuestion = $user['security_question'];
                    $userId = $user['id'];
                    $_SESSION['reset_user_id'] = $user['id'];
                    $_SESSION['reset_username'] = $username;
                } else {
                    $errors[] = "No account found with this username.";
                }
            }
        }
        
        // Step 2: Verify security answer
        elseif ($action === 'verify_answer') {
            $answer = strtolower(sanitize($_POST['security_answer'] ?? ''));
            $userId = $_SESSION['reset_user_id'] ?? null;
            $username = $_SESSION['reset_username'] ?? '';
            
            if (empty($answer)) {
                $errors[] = "Security answer is required.";
                $step = 2;
                // Re-fetch question
                $stmt = $pdo->prepare("SELECT security_question FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $securityQuestion = $stmt->fetchColumn();
            } else {
                $stmt = $pdo->prepare("SELECT security_answer, security_question FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if ($user && $user['security_answer'] === $answer) {
                    $step = 3;
                } else {
                    $errors[] = "Incorrect security answer. Please try again.";
                    $step = 2;
                    $securityQuestion = $user['security_question'] ?? '';
                }
            }
        }
        
        // Step 3: Reset password
        elseif ($action === 'reset_password') {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $userId = $_SESSION['reset_user_id'] ?? null;
            
            if (!validatePassword($newPassword)) {
                $errors[] = "Password must be at least 8 characters.";
                $step = 3;
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = "Passwords do not match.";
                $step = 3;
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                
                // Clean up session
                unset($_SESSION['reset_user_id'], $_SESSION['reset_username']);
                
                // Log activity
                logActivity($pdo, 'user', $userId, 'Password reset via security question');
                
                setFlashMessage('success', 'Password reset successful! You can now login with your new password.');
                redirect(BASE_URL . 'login.php');
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
    <title>Forgot Password | <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-key fs-1"></i>
            <h3>Forgot Password</h3>
            <p>
                <?php if ($step == 1): ?>Enter your username to continue
                <?php elseif ($step == 2): ?>Answer your security question
                <?php else: ?>Set your new password
                <?php endif; ?>
            </p>
        </div>
        
        <div class="login-body">
            <!-- Progress Indicator -->
            <div class="d-flex justify-content-center mb-4">
                <span class="badge <?php echo ($step >= 1) ? 'bg-primary' : 'bg-secondary'; ?> me-1 px-3 py-2">1. Username</span>
                <span class="badge <?php echo ($step >= 2) ? 'bg-primary' : 'bg-secondary'; ?> me-1 px-3 py-2">2. Verify</span>
                <span class="badge <?php echo ($step >= 3) ? 'bg-primary' : 'bg-secondary'; ?> px-3 py-2">3. Reset</span>
            </div>
            
            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo outputSafe($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Step 1: Enter Username -->
            <?php if ($step == 1): ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="find_user">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Enter your registered username"
                               value="<?php echo outputSafe($username); ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-login">
                    <i class="bi bi-arrow-right me-1"></i> Continue
                </button>
            </form>
            <?php endif; ?>
            
            <!-- Step 2: Security Question -->
            <?php if ($step == 2): ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="verify_answer">
                
                <div class="mb-3">
                    <label class="form-label">Security Question</label>
                    <p class="form-control bg-light"><?php echo outputSafe($securityQuestion); ?></p>
                </div>
                
                <div class="mb-3">
                    <label for="security_answer" class="form-label">Your Answer</label>
                    <input type="text" class="form-control" id="security_answer" name="security_answer" 
                           placeholder="Enter your answer" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-login">
                    <i class="bi bi-check-circle me-1"></i> Verify
                </button>
            </form>
            <?php endif; ?>
            
            <!-- Step 3: New Password -->
            <?php if ($step == 3): ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="reset_password">
                
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               placeholder="Minimum 8 characters" minlength="8" required>
                        <button class="input-group-text password-toggle" type="button" data-target="new_password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Re-enter new password" required>
                        <button class="input-group-text password-toggle" type="button" data-target="confirm_password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success w-100 btn-login">
                    <i class="bi bi-shield-check me-1"></i> Reset Password
                </button>
            </form>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="login.php" class="text-primary"><i class="bi bi-arrow-left me-1"></i>Back to Login</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>
