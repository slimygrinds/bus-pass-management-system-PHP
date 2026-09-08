<?php
/**
 * Admin Settings
 * 
 * Admin can update their profile and change password.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Settings';
$currentPage = 'settings';

require_once '../includes/header.php';
requireAdmin();
require_once '../includes/admin_sidebar.php';

$adminId = $_SESSION['admin_id'];
$errors = [];

// Fetch admin data
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && validateCSRFToken($_POST['csrf_token'])) {
    $action = $_POST['form_action'] ?? '';
    
    // Update Profile
    if ($action === 'update_profile') {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        
        if (!validateName($fullName)) $errors[] = "Invalid name.";
        if (!validateEmail($email)) $errors[] = "Invalid email.";
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE admins SET full_name=?, email=?, phone=? WHERE id=?");
            $stmt->execute([$fullName, $email, $phone, $adminId]);
            $_SESSION['user_name'] = $fullName;
            logActivity($pdo, 'admin', $adminId, 'Admin profile updated');
            setFlashMessage('success', 'Profile updated!');
            redirect(BASE_URL . 'admin/settings.php');
        }
    }
    
    // Change Password
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword)) $errors[] = "Current password required.";
        if (!validatePassword($newPassword)) $errors[] = "New password must be at least 8 characters.";
        if ($newPassword !== $confirmPassword) $errors[] = "Passwords do not match.";
        
        if (empty($errors)) {
            if (password_verify($currentPassword, $admin['password'])) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([$hash, $adminId]);
                logActivity($pdo, 'admin', $adminId, 'Admin password changed');
                setFlashMessage('success', 'Password changed!');
                redirect(BASE_URL . 'admin/settings.php');
            } else {
                $errors[] = "Current password is incorrect.";
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>

<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Settings</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?php echo outputSafe($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row">
    <!-- Admin Profile -->
    <div class="col-md-6 mb-4">
        <div class="card form-card">
            <div class="card-header"><i class="bi bi-person me-2"></i>Admin Profile</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="form_action" value="update_profile">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo outputSafe($admin['username']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo outputSafe($admin['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" value="<?php echo outputSafe($admin['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo outputSafe($admin['phone'] ?? ''); ?>" maxlength="15">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Update Profile</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="col-md-6 mb-4">
        <div class="card form-card">
            <div class="card-header"><i class="bi bi-key me-2"></i>Change Password</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="form_action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="input-group-text password-toggle" type="button" data-target="current_password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                            <button class="input-group-text password-toggle" type="button" data-target="new_password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button class="input-group-text password-toggle" type="button" data-target="confirm_password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-key me-1"></i>Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- System Info -->
<div class="card form-card">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>System Information</div>
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr><td class="text-muted" width="30%">Project</td><td><?php echo SITE_NAME; ?></td></tr>
            <tr><td class="text-muted">Version</td><td><?php echo SITE_VERSION; ?></td></tr>
            <tr><td class="text-muted">PHP Version</td><td><?php echo phpversion(); ?></td></tr>
            <tr><td class="text-muted">Server</td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td></tr>
            <tr><td class="text-muted">Database</td><td>MySQL (PDO)</td></tr>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
