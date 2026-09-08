<?php
/**
 * User Payment Gateway (Mock)
 * 
 * Allows users to pay for their approved applications
 * to generate their bus pass.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Complete Payment';
$currentPage = 'my_applications';

require_once '../includes/header.php';
requireUser();

$userId = $_SESSION['user_id'];
$appId = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;

if ($appId <= 0) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid application ID.'];
    header("Location: my_applications.php");
    exit();
}

// Fetch the application
$stmt = $pdo->prepare("SELECT a.*, r.source, r.destination as route_dest 
                        FROM applications a 
                        JOIN bus_routes r ON a.route_id = r.id 
                        WHERE a.id = ? AND a.user_id = ? AND a.status = 'pending_payment'");
$stmt->execute([$appId, $userId]);
$app = $stmt->fetch();

if (!$app) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Application not found or payment not required.'];
    header("Location: my_applications.php");
    exit();
}

$amount = 500.00; // Flat fee for the demo
$errors = [];

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = sanitize($_POST['payment_method'] ?? 'card');
    
    // We are skipping validation for this mock gateway so the user can enter any details
    // and the payment will still succeed.
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // 1. Generate Transaction ID
            $txnId = 'TXN' . strtoupper(uniqid());
            
            // 2. Insert into payments
            $stmt = $pdo->prepare("INSERT INTO payments (application_id, user_id, amount, transaction_id, status) VALUES (?, ?, ?, ?, 'completed')");
            $stmt->execute([$appId, $userId, $amount, $txnId]);
            
            // 3. Update application status to approved
            $stmt = $pdo->prepare("UPDATE applications SET status = 'approved', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$appId]);
            
            // 4. Generate Pass
            $passNumber = generatePassNumber($pdo);
            $issueDate = date('Y-m-d');
            $expiryDate = calculateExpiryDate($issueDate, $app['pass_duration']);
            
            $stmt = $pdo->prepare("INSERT INTO passes (application_id, user_id, pass_number, issue_date, expiry_date, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$appId, $userId, $passNumber, $issueDate, $expiryDate]);
            $passId = $pdo->lastInsertId();
            
            // 5. Notify user
            createNotification($pdo, $userId, 'Payment Successful!', "Payment of ₹{$amount} received for Application {$app['application_number']}. Pass No: {$passNumber} generated.");
            
            $pdo->commit();
            
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Payment successful! Your bus pass has been generated."];
            header("Location: download_pass.php?id=" . $passId);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "An error occurred during payment processing. Please try again.";
            error_log($e->getMessage());
        }
    }
}

require_once '../includes/user_sidebar.php';
?>

<!-- Breadcrumb -->
<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="my_applications.php">My Applications</a></li>
        <li class="breadcrumb-item active">Payment</li>
    </ol>
</nav>

<!-- Flash Messages -->
<?php echo displayFlashMessage(); ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo outputSafe($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 text-primary fw-bold"><i class="bi bi-credit-card me-2"></i>Complete Payment</h5>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6 border-end">
                        <h6 class="text-muted mb-3">Application Summary</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted">App No:</td>
                                <td class="fw-bold"><?php echo outputSafe($app['application_number']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Name:</td>
                                <td><?php echo outputSafe($app['full_name']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Route:</td>
                                <td><?php echo outputSafe($app['source'] . ' to ' . $app['route_dest']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Duration:</td>
                                <td><?php echo outputSafe($app['pass_duration']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center py-3 h-100 d-flex flex-column justify-content-center bg-light rounded">
                            <span class="text-muted mb-1">Total Amount Due</span>
                            <h2 class="text-primary mb-0 fw-bold">₹<?php echo number_format($amount, 2); ?></h2>
                        </div>
                    </div>
                </div>

                <hr>

                <h6 class="mb-3 text-muted">Mock Payment Gateway</h6>
                
                <ul class="nav nav-pills nav-fill mb-4" id="paymentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="card-tab" data-bs-toggle="pill" data-bs-target="#card" type="button" role="tab" onclick="document.getElementById('payment_method').value='card'">
                            <i class="bi bi-credit-card me-2"></i>Card
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="upi-tab" data-bs-toggle="pill" data-bs-target="#upi" type="button" role="tab" onclick="document.getElementById('payment_method').value='upi'">
                            <i class="bi bi-phone me-2"></i>UPI Apps
                        </button>
                    </li>
                </ul>

                <form action="payment.php?app_id=<?php echo $appId; ?>" method="POST" id="paymentForm">
                    <input type="hidden" name="payment_method" id="payment_method" value="card">
                    
                    <div class="alert alert-info py-2 mb-4">
                        <i class="bi bi-info-circle me-2"></i>This is a mock payment gateway for demo purposes. Enter any details to proceed.
                    </div>

                    <div class="tab-content" id="paymentTabsContent">
                        
                        <!-- CARD PAYMENT -->
                        <div class="tab-pane fade show active" id="card" role="tabpanel">
                            <div class="mb-3">
                                <label for="card_name" class="form-label">Name on Card</label>
                                <input type="text" class="form-control" id="card_name" name="card_name" placeholder="John Doe" value="<?php echo outputSafe($app['full_name']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="card_number" class="form-label">Card Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                    <input type="text" class="form-control" id="card_number" name="card_number" placeholder="XXXX XXXX XXXX XXXX">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="expiry" class="form-label">Expiry Date</label>
                                    <input type="text" class="form-control" id="expiry" name="expiry" placeholder="MM/YY">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="password" class="form-control" id="cvv" name="cvv" placeholder="XXX" maxlength="4">
                                </div>
                            </div>
                        </div>

                        <!-- UPI PAYMENT -->
                        <div class="tab-pane fade" id="upi" role="tabpanel">
                            <div class="mb-4">
                                <label class="form-label">Select UPI App</label>
                                <div class="row g-2">
                                    <div class="col-6 col-md-3">
                                        <input type="radio" class="btn-check upi-option" name="upi_app" id="gpay" value="gpay" autocomplete="off">
                                        <label class="btn btn-outline-primary w-100 py-3 upi-label" for="gpay">
                                            <i class="bi bi-google fs-3 d-block mb-1"></i>GPay
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" class="btn-check upi-option" name="upi_app" id="phonepe" value="bhim" autocomplete="off">
                                        <label class="btn btn-outline-primary w-100 py-3 upi-label" for="phonepe">
                                            <i class="bi bi-bank fs-3 d-block mb-1"></i>BHIM
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" class="btn-check upi-option" name="upi_app" id="razorpay" value="razorpay" autocomplete="off">
                                        <label class="btn btn-outline-primary w-100 py-3 upi-label" for="razorpay">
                                            <i class="bi bi-lightning fs-3 d-block mb-1"></i>RazorPay
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" class="btn-check upi-option" name="upi_app" id="paytm" value="paytm" autocomplete="off">
                                        <label class="btn btn-outline-primary w-100 py-3 upi-label" for="paytm">
                                            <i class="bi bi-wallet2 fs-3 d-block mb-1"></i>Paytm
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="upi_id" class="form-label">Or enter UPI ID</label>
                                <input type="text" class="form-control" id="upi_id" name="upi_id" placeholder="username@bank">
                            </div>
                        </div>

                    </div> <!-- End Tab Content -->

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm">
                            Pay ₹<?php echo number_format($amount, 2); ?> & Generate Pass
                        </button>
                        <a href="my_applications.php" class="btn btn-light rounded-pill">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Basic format for card number
document.getElementById('card_number').addEventListener('input', function (e) {
    var target = e.target, position = target.selectionEnd, length = target.value.length;
    target.value = target.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim();
    target.selectionEnd = position += ((target.value.charAt(position - 1) === ' ' && target.value.charAt(length - 1) === ' ' && length !== target.value.length) ? 1 : 0);
});

// Basic format for expiry
document.getElementById('expiry').addEventListener('input', function (e) {
    var target = e.target;
    target.value = target.value.replace(/[^\d]/g, '').replace(/(.{2})/, '$1/').trim();
});
</script>

<?php require_once '../includes/footer.php'; ?>
