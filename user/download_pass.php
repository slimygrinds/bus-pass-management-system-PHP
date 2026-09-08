<?php
/**
 * Download Bus Pass
 * 
 * Generates a printable bus pass card with professional layout.
 * Shows pass details, photo, QR placeholder, and signature area.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Download Pass';
$currentPage = 'download_pass';

require_once '../includes/header.php';
requireUser();
require_once '../includes/user_sidebar.php';

$userId = $_SESSION['user_id'];

// Check if specific pass ID is provided
$passId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($passId) {
    $stmt = $pdo->prepare("SELECT p.*, a.full_name, a.father_name, a.photo, a.address, a.city, a.state, a.pincode, a.aadhar_number,
                                   a.boarding_point, a.destination as app_dest, a.pass_duration, a.gender, a.dob,
                                   r.source, r.destination as route_dest, r.route_code
                            FROM passes p 
                            JOIN applications a ON p.application_id = a.id 
                            JOIN bus_routes r ON a.route_id = r.id 
                            WHERE p.id = ? AND p.user_id = ?");
    $stmt->execute([$passId, $userId]);
} else {
    // Fetch latest active pass
    $stmt = $pdo->prepare("SELECT p.*, a.full_name, a.father_name, a.photo, a.address, a.city, a.state, a.pincode, a.aadhar_number,
                                   a.boarding_point, a.destination as app_dest, a.pass_duration, a.gender, a.dob,
                                   r.source, r.destination as route_dest, r.route_code
                            FROM passes p 
                            JOIN applications a ON p.application_id = a.id 
                            JOIN bus_routes r ON a.route_id = r.id 
                            WHERE p.user_id = ? AND p.status = 'active'
                            ORDER BY p.created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
}
$pass = $stmt->fetch();
?>

<!-- Breadcrumb -->
<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Download Pass</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<?php if (!$pass): ?>
    <div class="card form-card">
        <div class="card-body text-center py-5">
            <i class="bi bi-credit-card-2-front text-muted fs-1"></i>
            <h5 class="mt-3 text-muted">No Active Pass Available</h5>
            <p class="text-muted">You need an approved and active bus pass to download.</p>
            <a href="apply_pass.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Apply for Pass
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Action Button Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <!-- Close button navigates back to dashboard -->
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-x-lg me-2"></i>Close
        </a>
        <!-- Print button -->
        <button class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>Print Pass
        </button>
    </div>

    <!-- Bus Pass Card -->
    <div class="d-flex justify-content-center">
        <div class="pass-card" id="busPassCard">
            <!-- Pass Header -->
            <div class="pass-card-header">
                <h5 class="mb-1"><i class="bi bi-bus-front me-1"></i> Bus Pass</h5>
                <small><?php echo SITE_NAME; ?></small>
            </div>
            
            <!-- Pass Body -->
            <div class="pass-card-body">
                <div class="d-flex justify-content-between mb-3">
                    <!-- Photo -->
                    <div>
                        <?php 
                        $photoSrc = BASE_URL . 'uploads/photos/' . ($pass['photo'] ?? 'default-avatar.png');
                        ?>
                        <img src="<?php echo $photoSrc; ?>" alt="Photo" class="pass-photo">
                    </div>
                    
                    <!-- QR Placeholder -->
                    <div class="qr-placeholder">
                        <div class="text-center">
                            <i class="bi bi-qr-code fs-3 d-block"></i>
                            <small>QR Code</small>
                        </div>
                    </div>
                </div>
                
                <!-- Pass Details -->
                <div class="pass-detail"><strong>Pass No:</strong> <?php echo outputSafe($pass['pass_number']); ?></div>
                <div class="pass-detail"><strong>Name:</strong> <?php echo outputSafe($pass['full_name']); ?></div>
                <div class="pass-detail"><strong>Aadhar No:</strong> <?php echo outputSafe($pass['aadhar_number']); ?></div>
                <div class="pass-detail"><strong>City:</strong> <?php echo outputSafe($pass['city']); ?></div>
                
                <hr class="my-2">
                
                <div class="pass-detail">
                    <strong>Route:</strong> 
                    <?php echo outputSafe($pass['source'] . ' → ' . $pass['route_dest']); ?>
                </div>
                <div class="pass-detail">
                    <strong>Boarding:</strong> <?php echo outputSafe($pass['boarding_point']); ?>
                </div>
                <div class="pass-detail">
                    <strong>Destination:</strong> <?php echo outputSafe($pass['app_dest']); ?>
                </div>
                
                <hr class="my-2">
                
                <div class="row">
                    <div class="col-6">
                        <div class="pass-detail"><strong>Issued:</strong> <?php echo formatDate($pass['issue_date']); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="pass-detail"><strong>Expires:</strong> <?php echo formatDate($pass['expiry_date']); ?></div>
                    </div>
                </div>
                <div class="pass-detail"><strong>Duration:</strong> <?php echo outputSafe($pass['pass_duration']); ?></div>
                
                <!-- Signature Area -->
                <div class="mt-3 text-end">
                    <div style="border-top: 1px solid #333; display: inline-block; width: 150px; padding-top: 5px;">
                        <small class="text-muted">Authorized Signature</small>
                    </div>
                </div>
            </div>
            
            <!-- Pass Footer -->
            <div class="pass-card-footer">
                <p class="mb-0">This pass is valid only for the route and period mentioned above.</p>
                <p class="mb-0">Always carry this pass while travelling.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
