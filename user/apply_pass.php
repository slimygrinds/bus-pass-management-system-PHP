<?php
/**
 * Apply for New Bus Pass
 * 
 * Complete application form with photo upload, validation,
 * and preview-before-submit functionality.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Apply New Pass';
$currentPage = 'apply_pass';

require_once '../includes/header.php';
requireUser();
require_once '../includes/user_sidebar.php';

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch user data to pre-fill form
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Fetch active routes for dropdown
$stmt = $pdo->prepare("SELECT * FROM bus_routes WHERE status = 'active' ORDER BY source, destination");
$stmt->execute();
$routes = $stmt->fetchAll();

// ==========================================
// Handle Form Submission
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request.";
    } else {
        // Sanitize all inputs
        $fullName    = sanitize($_POST['full_name'] ?? '');
        $fatherName  = sanitize($_POST['father_name'] ?? '');
        $gender      = sanitize($_POST['gender'] ?? '');
        $dob         = sanitize($_POST['dob'] ?? '');
        $age         = intval($_POST['age'] ?? 0);
        $mobile      = sanitize($_POST['mobile'] ?? '');
        $email       = sanitize($_POST['email'] ?? '');
        $collegeName = sanitize($_POST['college_name'] ?? '');
        $address     = sanitize($_POST['address'] ?? '');
        $city        = sanitize($_POST['city'] ?? '');
        $state       = sanitize($_POST['state'] ?? '');
        $pincode     = sanitize($_POST['pincode'] ?? '');
        $aadhar      = sanitize($_POST['aadhar_number'] ?? '');
        $routeId     = intval($_POST['route_id'] ?? 0);
        $boardingPoint = sanitize($_POST['boarding_point'] ?? '');
        $destination = sanitize($_POST['destination'] ?? '');
        $passDuration = sanitize($_POST['pass_duration'] ?? '');
        
        // Update $user array to repopulate form on error
        $user = array_merge($user, [
            'full_name' => $fullName,
            'father_name' => $fatherName,
            'gender' => $gender,
            'dob' => $dob,
            'age' => $age,
            'mobile' => $mobile,
            'email' => $email,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode,
            'aadhar_number' => $aadhar,
            'route_id' => $routeId,
            'boarding_point' => $boardingPoint,
            'destination' => $destination,
            'pass_duration' => $passDuration
        ]);
        
        // ===== Validations =====
        if (!validateName($fullName)) $errors[] = "Invalid full name.";
        if (!validateName($fatherName)) $errors[] = "Invalid father's name.";
        if (empty($gender)) $errors[] = "Gender is required.";
        if (empty($dob)) $errors[] = "Date of birth is required.";
        if (!validateAge($age)) $errors[] = "Invalid age.";
        if (!validateMobile($mobile)) $errors[] = "Invalid mobile number.";
        if (!validateEmail($email)) $errors[] = "Invalid email.";

        if (empty($address) || strlen($address) < 10) $errors[] = "Address must be at least 10 characters.";
        if (empty($city)) $errors[] = "City is required.";
        if (empty($state)) $errors[] = "State is required.";
        if (!validatePincode($pincode)) $errors[] = "Invalid pincode.";
        if (!empty($aadhar) && !validateAadhar($aadhar)) $errors[] = "Invalid Aadhar number.";
        if ($routeId <= 0) $errors[] = "Please select a route.";
        if (empty($boardingPoint)) $errors[] = "Boarding point is required.";
        if (empty($destination)) $errors[] = "Destination is required.";
        if (empty($passDuration)) $errors[] = "Pass duration is required.";
        
        // Handle photo upload
        $photoName = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $photoResult = uploadFile($_FILES['photo'], PHOTOS_PATH, ALLOWED_IMAGE_TYPES);
            if ($photoResult['success']) {
                $photoName = $photoResult['filename'];
            } else {
                $errors[] = "Photo: " . $photoResult['error'];
            }
        }
        
        // Handle student ID upload
        $docName = null;
        if (isset($_FILES['student_id_doc']) && $_FILES['student_id_doc']['error'] !== UPLOAD_ERR_NO_FILE) {
            $docResult = uploadFile($_FILES['student_id_doc'], DOCUMENTS_PATH, ALLOWED_DOC_TYPES);
            if ($docResult['success']) {
                $docName = $docResult['filename'];
            } else {
                $errors[] = "Student ID: " . $docResult['error'];
            }
        }
        
        // If no errors, save application
        if (empty($errors)) {
            $applicationNumber = generateApplicationNumber($pdo);
            
            $stmt = $pdo->prepare("INSERT INTO applications (user_id, route_id, application_number, full_name, father_name, gender, dob, age, mobile, email, address, city, state, pincode, aadhar_number, boarding_point, destination, pass_duration, photo, student_id_doc) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $userId, $routeId, $applicationNumber, $fullName, $fatherName, $gender,
                $dob, $age, $mobile, $email,
                $address, $city, $state, $pincode, $aadhar, $boardingPoint,
                $destination, $passDuration, $photoName, $docName
            ]);
            
            if ($result) {
                createNotification($pdo, $userId, 'Application Submitted', "Your application {$applicationNumber} has been submitted and is pending review.");
                logActivity($pdo, 'user', $userId, "Submitted pass application: {$applicationNumber}");
                setFlashMessage('success', "Application submitted successfully! Your application number is <strong>{$applicationNumber}</strong>.");
                redirect(BASE_URL . 'user/my_applications.php');
            } else {
                $errors[] = "Failed to submit application. Please try again.";
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>

<!-- Breadcrumb -->
<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Apply New Pass</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<!-- Errors -->
<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <strong>Please fix the following:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($errors as $error): ?>
            <li><?php echo outputSafe($error); ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Application Form -->
<div class="card form-card">
    <div class="card-header">
        <i class="bi bi-plus-circle me-2"></i>Bus Pass Application Form
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data" id="applicationForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <!-- Personal Information Section -->
            <h6 class="text-primary mb-3"><i class="bi bi-person me-1"></i>Personal Information</h6>
            <div class="row">
                <!-- Photo Upload -->
                <div class="col-md-3 mb-3 text-center">
                    <label class="form-label d-block">Photo Upload</label>
                    <div class="photo-preview mx-auto" onclick="document.getElementById('photo').click()">
                        <img id="photoPreview" style="display:none;" alt="Preview">
                        <span class="placeholder-text"><i class="bi bi-camera fs-3 d-block"></i>Click to upload</span>
                    </div>
                    <input type="file" class="form-control mt-2" id="photo" name="photo" accept="image/jpeg,image/png"
                           onchange="previewImage(this, 'photoPreview')" style="display:none;">
                    <small class="text-muted">JPG/PNG, Max 2MB</small>
                </div>
                
                <div class="col-md-9">
                    <div class="row">
                        <!-- Full Name -->
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo outputSafe($user['full_name'] ?? ''); ?>"
                                   onkeypress="return allowOnlyLetters(event)" maxlength="100" required>
                        </div>
                        
                        <!-- Father Name -->
                        <div class="col-md-6 mb-3">
                            <label for="father_name" class="form-label">Father's Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="father_name" name="father_name" 
                                   value="<?php echo outputSafe($user['father_name'] ?? ''); ?>"
                                   onkeypress="return allowOnlyLetters(event)" maxlength="100" required>
                        </div>
                        
                        <!-- Gender -->
                        <div class="col-md-4 mb-3">
                            <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select</option>
                                <option value="Male" <?php echo ($user['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($user['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($user['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <!-- DOB -->
                        <div class="col-md-4 mb-3">
                            <label for="dob" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="dob" name="dob" 
                                   value="<?php echo outputSafe($user['dob'] ?? ''); ?>"
                                   onchange="calculateAge(this.value, 'age')" required>
                        </div>
                        
                        <!-- Age -->
                        <div class="col-md-4 mb-3">
                            <label for="age" class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="age" name="age" 
                                   value="<?php echo outputSafe($user['age'] ?? ''); ?>"
                                   min="1" max="100" readonly required>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <!-- Contact Information -->
            <h6 class="text-primary mb-3"><i class="bi bi-telephone me-1"></i>Contact Information</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="mobile" name="mobile" 
                           value="<?php echo outputSafe($user['mobile'] ?? ''); ?>"
                           onkeypress="return allowOnlyDigits(event)" oninput="limitLength(this, 10)" maxlength="10" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo outputSafe($user['email'] ?? ''); ?>" required>
                </div>
            </div>
            
            <hr>
            
            <!-- Address Information -->
            <h6 class="text-primary mb-3"><i class="bi bi-geo-alt me-1"></i>Address Information</h6>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="address" name="address" rows="2" maxlength="500" required><?php echo outputSafe($user['address'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="city" name="city" 
                           value="<?php echo outputSafe($user['city'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="state" class="form-label">State <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="state" name="state" 
                           value="<?php echo outputSafe($user['state'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="pincode" class="form-label">Pincode <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="pincode" name="pincode" 
                           value="<?php echo outputSafe($user['pincode'] ?? ''); ?>"
                           onkeypress="return allowOnlyDigits(event)" oninput="limitLength(this, 6)" maxlength="6" required>
                </div>
            </div>
            
            <!-- Aadhar -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="aadhar_number" class="form-label">Aadhar Number</label>
                    <input type="text" class="form-control" id="aadhar_number" name="aadhar_number" 
                           value="<?php echo outputSafe($user['aadhar_number'] ?? ''); ?>"
                           onkeypress="return allowOnlyDigits(event)" oninput="limitLength(this, 12)" maxlength="12">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="student_id_doc" class="form-label">ID Proof Document (Aadhar/Student/Org ID)</label>
                    <input type="file" class="form-control" id="student_id_doc" name="student_id_doc" accept="image/jpeg,image/png,application/pdf">
                    <small class="text-muted">JPG, PNG, or PDF. Max 2MB</small>
                </div>
            </div>
            
            <hr>
            
            <!-- Route & Pass Details -->
            <h6 class="text-primary mb-3"><i class="bi bi-bus-front me-1"></i>Route & Pass Details</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="route_id" class="form-label">Select Route <span class="text-danger">*</span></label>
                    <select class="form-select" id="route_id" name="route_id" required>
                        <option value="">Choose a route</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo $route['id']; ?>" <?php echo (($user['route_id']??'') == $route['id']) ? 'selected' : ''; ?>>
                                <?php echo outputSafe($route['source'] . ' → ' . $route['destination'] . ' (' . $route['route_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="pass_duration" class="form-label">Pass Duration <span class="text-danger">*</span></label>
                    <select class="form-select" id="pass_duration" name="pass_duration" required>
                        <option value="">Select duration</option>
                        <option value="1-month" <?php echo (($user['pass_duration']??'') === '1-month') ? 'selected' : ''; ?>>1 Month</option>
                        <option value="3-month" <?php echo (($user['pass_duration']??'') === '3-month') ? 'selected' : ''; ?>>3 Months</option>
                        <option value="6-month" <?php echo (($user['pass_duration']??'') === '6-month') ? 'selected' : ''; ?>>6 Months</option>
                        <option value="1-year" <?php echo (($user['pass_duration']??'') === '1-year') ? 'selected' : ''; ?>>1 Year</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="boarding_point" class="form-label">Boarding Point <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="boarding_point" name="boarding_point" 
                           value="<?php echo outputSafe($user['boarding_point'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="destination" class="form-label">Destination <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="destination" name="destination" 
                           value="<?php echo outputSafe($user['destination'] ?? ''); ?>" required>
                </div>
            </div>
            
            <hr>
            
            <!-- Submit Buttons -->
            <div class="d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Cancel
                </a>
                <div>
                    <button type="button" class="btn btn-outline-primary me-2" onclick="previewApplication()">
                        <i class="bi bi-eye me-1"></i>Preview
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>Submit Application
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Application Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Filled by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('applicationForm').submit()">
                    <i class="bi bi-send me-1"></i>Confirm & Submit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Preview application before submitting
 */
function previewApplication() {
    const form = document.getElementById('applicationForm');
    const preview = document.getElementById('previewContent');
    
    // Build preview HTML
    let html = '<div class="preview-section">';
    html += '<div class="row">';
    
    // Personal Info
    html += '<div class="col-12"><h6 class="text-primary border-bottom pb-2">Personal Information</h6></div>';
    html += '<div class="col-md-6"><strong>Name:</strong> ' + (form.full_name.value || 'N/A') + '</div>';
    html += '<div class="col-md-6"><strong>Father\'s Name:</strong> ' + (form.father_name.value || 'N/A') + '</div>';
    html += '<div class="col-md-4"><strong>Gender:</strong> ' + (form.gender.value || 'N/A') + '</div>';
    html += '<div class="col-md-4"><strong>DOB:</strong> ' + (form.dob.value || 'N/A') + '</div>';
    html += '<div class="col-md-4"><strong>Age:</strong> ' + (form.age.value || 'N/A') + '</div>';
    
    // Contact Info
    html += '<div class="col-12 mt-3"><h6 class="text-primary border-bottom pb-2">Contact</h6></div>';
    html += '<div class="col-md-6"><strong>Mobile:</strong> ' + (form.mobile.value || 'N/A') + '</div>';
    html += '<div class="col-md-6"><strong>Email:</strong> ' + (form.email.value || 'N/A') + '</div>';
    
    // Address
    html += '<div class="col-12 mt-3"><h6 class="text-primary border-bottom pb-2">Address</h6></div>';
    html += '<div class="col-12"><strong>Address:</strong> ' + (form.address.value || 'N/A') + '</div>';
    html += '<div class="col-md-4"><strong>City:</strong> ' + (form.city.value || 'N/A') + '</div>';
    html += '<div class="col-md-4"><strong>State:</strong> ' + (form.state.value || 'N/A') + '</div>';
    html += '<div class="col-md-4"><strong>Pincode:</strong> ' + (form.pincode.value || 'N/A') + '</div>';
    html += '<div class="col-md-6"><strong>Aadhar:</strong> ' + (form.aadhar_number.value || 'N/A') + '</div>';
    
    // Route Info
    html += '<div class="col-12 mt-3"><h6 class="text-primary border-bottom pb-2">Route Details</h6></div>';
    const routeSelect = form.route_id;
    const routeText = routeSelect.options[routeSelect.selectedIndex]?.text || 'N/A';
    html += '<div class="col-md-6"><strong>Route:</strong> ' + routeText + '</div>';
    html += '<div class="col-md-6"><strong>Duration:</strong> ' + (form.pass_duration.value || 'N/A') + '</div>';
    html += '<div class="col-md-6"><strong>Boarding:</strong> ' + (form.boarding_point.value || 'N/A') + '</div>';
    html += '<div class="col-md-6"><strong>Destination:</strong> ' + (form.destination.value || 'N/A') + '</div>';
    
    html += '</div></div>';
    
    preview.innerHTML = html;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
