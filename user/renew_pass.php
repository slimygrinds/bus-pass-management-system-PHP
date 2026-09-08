<?php
/**
 * Renew Bus Pass
 * 
 * Allows users to renew their expired or expiring passes
 * by submitting a renewal application.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Renew Pass';
$currentPage = 'renew_pass';

require_once '../includes/header.php';
requireUser();
require_once '../includes/user_sidebar.php';

$userId = $_SESSION['user_id'];

// Fetch passes that can be renewed (active/expired)
$stmt = $pdo->prepare("SELECT p.*, a.boarding_point, a.destination, a.pass_duration, a.route_id,
                               r.source, r.destination as route_dest, r.route_code
                        FROM passes p 
                        JOIN applications a ON p.application_id = a.id 
                        JOIN bus_routes r ON a.route_id = r.id 
                        WHERE p.user_id = ? AND p.status IN ('active', 'expired')
                        ORDER BY p.created_at DESC");
$stmt->execute([$userId]);
$passes = $stmt->fetchAll();

// Fetch active routes
$stmt = $pdo->prepare("SELECT * FROM bus_routes WHERE status = 'active' ORDER BY source, destination");
$stmt->execute();
$routes = $stmt->fetchAll();

// ==========================================
// Handle Renewal Submission
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Invalid request.');
    } else {
        $passId = intval($_POST['pass_id'] ?? 0);
        $newDuration = sanitize($_POST['new_duration'] ?? '');
        
        if ($passId <= 0 || empty($newDuration)) {
            setFlashMessage('danger', 'Please select a pass and duration.');
        } else {
            // Get the existing pass and application details
            $stmt = $pdo->prepare("SELECT p.*, a.* FROM passes p JOIN applications a ON p.application_id = a.id WHERE p.id = ? AND p.user_id = ?");
            $stmt->execute([$passId, $userId]);
            $existingPass = $stmt->fetch();
            
            if ($existingPass) {
                // Create a new application for renewal
                $applicationNumber = generateApplicationNumber($pdo);
                
                $stmt = $pdo->prepare("INSERT INTO applications (user_id, route_id, application_number, full_name, father_name, gender, dob, age, mobile, email, address, city, state, pincode, aadhar_number, boarding_point, destination, pass_duration, photo, student_id_doc, status) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                
                $stmt->execute([
                    $userId, $existingPass['route_id'], $applicationNumber,
                    $existingPass['full_name'], $existingPass['father_name'], $existingPass['gender'],
                    $existingPass['dob'], $existingPass['age'], $existingPass['mobile'], $existingPass['email'],
                    $existingPass['address'], $existingPass['city'], $existingPass['state'],
                    $existingPass['pincode'], $existingPass['aadhar_number'], $existingPass['boarding_point'],
                    $existingPass['destination'], $newDuration, $existingPass['photo'], $existingPass['student_id_doc']
                ]);
                
                // Mark old pass as renewed
                $stmt = $pdo->prepare("UPDATE passes SET status = 'renewed' WHERE id = ?");
                $stmt->execute([$passId]);
                
                createNotification($pdo, $userId, 'Renewal Submitted', "Your renewal application {$applicationNumber} has been submitted.");
                logActivity($pdo, 'user', $userId, "Submitted renewal application: {$applicationNumber}");
                
                setFlashMessage('success', "Renewal application submitted! Application No: <strong>{$applicationNumber}</strong>");
                redirect(BASE_URL . 'user/my_applications.php');
            } else {
                setFlashMessage('danger', 'Pass not found.');
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
        <li class="breadcrumb-item active">Renew Pass</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<div class="card form-card">
    <div class="card-header">
        <i class="bi bi-arrow-repeat me-2"></i>Renew Bus Pass
    </div>
    <div class="card-body">
        <?php if (empty($passes)): ?>
            <div class="text-center py-5">
                <i class="bi bi-credit-card-2-front text-muted fs-1"></i>
                <h5 class="mt-3 text-muted">No Passes to Renew</h5>
                <p class="text-muted">You don't have any active or expired passes.</p>
                <a href="apply_pass.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Apply for New Pass
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <!-- Select Pass to Renew -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Select Pass to Renew <span class="text-danger">*</span></label>
                    <div class="row g-3">
                        <?php foreach ($passes as $pass): ?>
                        <div class="col-md-6">
                            <div class="card border <?php echo ($pass['status'] === 'expired') ? 'border-danger' : 'border-primary'; ?>">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pass_id" 
                                               value="<?php echo $pass['id']; ?>" id="pass_<?php echo $pass['id']; ?>" required>
                                        <label class="form-check-label w-100" for="pass_<?php echo $pass['id']; ?>">
                                            <div class="d-flex justify-content-between">
                                                <strong><?php echo outputSafe($pass['pass_number']); ?></strong>
                                                <?php echo getStatusBadge($pass['status']); ?>
                                            </div>
                                            <p class="mb-1 small text-muted mt-1">
                                                <i class="bi bi-geo-alt"></i> 
                                                <?php echo outputSafe($pass['source'] . ' → ' . $pass['route_dest']); ?>
                                            </p>
                                            <p class="mb-0 small text-muted">
                                                <i class="bi bi-calendar"></i> 
                                                Expires: <?php echo formatDate($pass['expiry_date']); ?>
                                            </p>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- New Duration -->
                <div class="mb-4">
                    <label for="new_duration" class="form-label fw-bold">New Duration <span class="text-danger">*</span></label>
                    <select class="form-select" id="new_duration" name="new_duration" required>
                        <option value="">Select duration</option>
                        <option value="1-month">1 Month</option>
                        <option value="3-month">3 Months</option>
                        <option value="6-month">6 Months</option>
                        <option value="1-year">1 Year</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat me-1"></i>Submit Renewal
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
