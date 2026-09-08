<?php
/**
 * User Profile
 * 
 * Allows users to view and edit their profile, upload photo,
 * and see recent activity.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'My Profile';
$currentPage = 'profile';

require_once '../includes/header.php';
requireUser();
require_once '../includes/user_sidebar.php';

$userId = $_SESSION['user_id'];
$errors = [];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// ==========================================
// Handle Profile Update
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request.";
    } else {
        $fullName   = sanitize($_POST['full_name'] ?? '');
        $fatherName = sanitize($_POST['father_name'] ?? '');
        $gender     = sanitize($_POST['gender'] ?? '');
        $dob        = sanitize($_POST['dob'] ?? '');
        $age        = intval($_POST['age'] ?? 0);
        $mobile     = sanitize($_POST['mobile'] ?? '');
        $address    = sanitize($_POST['address'] ?? '');
        $city       = sanitize($_POST['city'] ?? '');
        $state      = sanitize($_POST['state'] ?? '');
        $pincode    = sanitize($_POST['pincode'] ?? '');
        $aadhar     = sanitize($_POST['aadhar_number'] ?? '');
        
        // Update $user array to repopulate form on error
        $user = array_merge($user, [
            'full_name' => $fullName,
            'father_name' => $fatherName,
            'gender' => $gender,
            'dob' => $dob,
            'age' => $age,
            'mobile' => $mobile,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode,
            'aadhar_number' => $aadhar
        ]);
        
        // Validations
        if (!validateName($fullName)) $errors[] = "Invalid full name.";
        if (!empty($fatherName) && !validateName($fatherName)) $errors[] = "Invalid father's name.";
        if (!validateMobile($mobile)) $errors[] = "Invalid mobile number.";
        if (!empty($pincode) && !validatePincode($pincode)) $errors[] = "Invalid pincode.";
        if (!empty($aadhar) && !validateAadhar($aadhar)) $errors[] = "Invalid Aadhar number.";
        
        // Handle photo upload
        $photoName = $user['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $photoResult = uploadFile($_FILES['photo'], PHOTOS_PATH, ALLOWED_IMAGE_TYPES);
            if ($photoResult['success']) {
                $photoName = $photoResult['filename'];
            } else {
                $errors[] = "Photo: " . $photoResult['error'];
            }
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, father_name=?, gender=?, dob=?, age=?, mobile=?, address=?, city=?, state=?, pincode=?, aadhar_number=?, photo=? WHERE id=?");
            $stmt->execute([$fullName, $fatherName, $gender, $dob, $age, $mobile, $address, $city, $state, $pincode, $aadhar, $photoName, $userId]);
            
            // Update session
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_photo'] = $photoName;
            
            logActivity($pdo, 'user', $userId, 'Profile updated');
            setFlashMessage('success', 'Profile updated successfully!');
            redirect(BASE_URL . 'user/profile.php');
        }
    }
}

// Fetch recent activity
$stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_type = 'user' AND user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$activities = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
?>

<!-- Breadcrumb -->
<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Profile</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $error): ?>
            <li><?php echo outputSafe($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row">
    <!-- Profile Form -->
    <div class="col-lg-8 mb-4">
        <div class="card form-card">
            <div class="card-header">
                <i class="bi bi-person me-2"></i>Edit Profile
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <!-- Photo Upload -->
                    <div class="text-center mb-4">
                        <?php $photoPath = BASE_URL . 'uploads/photos/' . ($user['photo'] ?? 'default-avatar.png'); ?>
                        <img src="<?php echo $photoPath; ?>" alt="Profile" id="profilePreview" 
                             class="rounded-circle mb-2" width="100" height="100" style="object-fit: cover; border: 3px solid #eee;">
                        <div>
                            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png" 
                                   onchange="previewImage(this, 'profilePreview')" class="form-control form-control-sm w-auto mx-auto">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo outputSafe($user['full_name']); ?>"
                                   onkeypress="return allowOnlyLetters(event)" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="father_name" class="form-label">Father's Name</label>
                            <input type="text" class="form-control" id="father_name" name="father_name" 
                                   value="<?php echo outputSafe($user['father_name'] ?? ''); ?>"
                                   onkeypress="return allowOnlyLetters(event)">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Select</option>
                                <option value="Male" <?php echo ($user['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($user['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($user['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="dob" name="dob" 
                                   value="<?php echo outputSafe($user['dob'] ?? ''); ?>"
                                   onchange="calculateAge(this.value, 'age')">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" 
                                   value="<?php echo outputSafe($user['age'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">Mobile <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="mobile" name="mobile" 
                                   value="<?php echo outputSafe($user['mobile'] ?? ''); ?>"
                                   onkeypress="return allowOnlyDigits(event)" maxlength="10">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo outputSafe($user['email']); ?>" readonly disabled>
                            <small class="text-muted">Email cannot be changed.</small>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo outputSafe($user['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo outputSafe($user['city'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?php echo outputSafe($user['state'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="pincode" class="form-label">Pincode</label>
                            <input type="text" class="form-control" id="pincode" name="pincode" 
                                   value="<?php echo outputSafe($user['pincode'] ?? ''); ?>"
                                   onkeypress="return allowOnlyDigits(event)" maxlength="6">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="aadhar_number" class="form-label">Aadhar Number</label>
                            <input type="text" class="form-control" id="aadhar_number" name="aadhar_number" 
                                   value="<?php echo outputSafe($user['aadhar_number'] ?? ''); ?>"
                                   onkeypress="return allowOnlyDigits(event)" maxlength="12">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Activity Log -->
    <div class="col-lg-4 mb-4">
        <div class="card table-card h-100">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>Recent Activity
            </div>
            <div class="card-body p-0">
                <?php if (empty($activities)): ?>
                    <div class="text-center py-4 text-muted">
                        <p>No activity recorded yet.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($activities as $act): ?>
                        <div class="list-group-item py-2">
                            <div class="d-flex justify-content-between">
                                <small><i class="bi bi-circle-fill text-primary me-1" style="font-size:6px;"></i><?php echo outputSafe($act['action']); ?></small>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i><?php echo formatDate($act['created_at'], 'd M Y, h:i A'); ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
