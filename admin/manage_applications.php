<?php
/**
 * Manage Applications
 * 
 * Admin can view, approve, reject, search, filter, and print applications.
 * Approving an application automatically generates a bus pass.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Manage Applications';
$currentPage = 'manage_applications';

require_once '../includes/header.php';
requireAdmin();
require_once '../includes/admin_sidebar.php';

// ==========================================
// Handle Approve / Reject Actions
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && validateCSRFToken($_POST['csrf_token'])) {
        $appId = intval($_POST['application_id'] ?? 0);
        $action = sanitize($_POST['action'] ?? '');
        $remarks = sanitize($_POST['remarks'] ?? '');
        
        if ($appId > 0 && in_array($action, ['approve', 'reject'])) {
            // Get application details
            $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$appId]);
            $app = $stmt->fetch();
            
            if ($app) {
                if ($action === 'approve') {
                    // Update application status
                    $stmt = $pdo->prepare("UPDATE applications SET status = 'pending_payment', remarks = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$remarks ?: 'Application approved.', $appId]);
                    
                    // Notify user
                    createNotification($pdo, $app['user_id'], 'Payment Required', "Your application {$app['application_number']} has been approved. Please complete payment to generate your pass.");
                    
                    logActivity($pdo, 'admin', $_SESSION['admin_id'], "Approved application (pending payment): {$app['application_number']}");
                    setFlashMessage('success', "Application approved. Awaiting user payment.");
                    
                } elseif ($action === 'reject') {
                    $stmt = $pdo->prepare("UPDATE applications SET status = 'rejected', remarks = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$remarks ?: 'Application rejected.', $appId]);
                    
                    createNotification($pdo, $app['user_id'], 'Application Rejected', "Your application {$app['application_number']} has been rejected. Reason: " . ($remarks ?: 'Not specified.'));
                    
                    logActivity($pdo, 'admin', $_SESSION['admin_id'], "Rejected application: {$app['application_number']}");
                    setFlashMessage('warning', 'Application rejected.');
                }
                
                redirect(BASE_URL . 'admin/manage_applications.php?' . http_build_query($_GET));
            }
        }
    }
}

// Pagination & Filters
$page = max(1, intval($_GET['page'] ?? 1));
$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');

$where = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (a.application_number LIKE ? OR a.full_name LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s]);
}

if (!empty($statusFilter)) {
    $where .= " AND a.status = ?";
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a {$where}");
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / RECORDS_PER_PAGE);
$offset = ($page - 1) * RECORDS_PER_PAGE;

$stmt = $pdo->prepare("SELECT a.*, r.source, r.destination as route_dest, r.route_code, u.username 
                        FROM applications a 
                        JOIN bus_routes r ON a.route_id = r.id
                        JOIN users u ON a.user_id = u.id
                        {$where} 
                        ORDER BY a.applied_at DESC 
                        LIMIT " . RECORDS_PER_PAGE . " OFFSET {$offset}");
$stmt->execute($params);
$applications = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
?>

<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Applications</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<div class="card table-card">
    <div class="card-header">
        <i class="bi bi-file-earmark-check me-2"></i>All Applications
        <span class="badge bg-primary rounded-pill ms-2"><?php echo $totalRecords; ?></span>
    </div>
    <div class="card-body">
        <!-- Search & Filter Toolbar -->
        <form method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <!-- Search Input -->
            <div class="flex-grow-1" style="min-width: 200px; max-width: 340px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search"
                           placeholder="Search by App No, Name..."
                           value="<?php echo outputSafe($search); ?>">
                </div>
            </div>
            <!-- Status Filter -->
            <div style="min-width: 150px;">
                <select class="form-select form-select-sm" name="status">
                    <option value="">All Status</option>
                    <option value="pending"         <?php echo $statusFilter === 'pending'         ? 'selected' : ''; ?>>Pending</option>
                    <option value="pending_payment" <?php echo $statusFilter === 'pending_payment' ? 'selected' : ''; ?>>Pending Payment</option>
                    <option value="approved"        <?php echo $statusFilter === 'approved'        ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected"        <?php echo $statusFilter === 'rejected'        ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <!-- Action Buttons -->
            <div class="d-flex gap-2 ms-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i>Search
                </button>
                <?php if (!empty($search) || !empty($statusFilter)): ?>
                    <a href="manage_applications.php" class="btn btn-sm btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <?php if (empty($applications)): ?>
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-1"></i><p class="mt-2">No applications found.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>App No.</th>
                        <th>User / ID Proof</th>
                        <th>Route</th>
                        <th>Duration</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $i => $app): ?>
                    <tr>
                        <td><?php echo $offset + $i + 1; ?></td>
                        <td><strong><?php echo outputSafe($app['application_number']); ?></strong></td>
                        <td>
                            <strong><?php echo outputSafe($app['full_name']); ?></strong>
                            <br><small class="text-muted">@<?php echo outputSafe($app['username']); ?></small>
                        </td>
                        <td><small><?php echo outputSafe($app['source'] . ' → ' . $app['route_dest']); ?></small></td>
                        <td><?php echo outputSafe($app['pass_duration']); ?></td>
                        <td><small><?php echo formatDate($app['applied_at']); ?></small></td>
                        <td><?php echo getStatusBadge($app['status']); ?></td>
                        <td>
                            <!-- View Details -->
                            <button class="btn btn-sm btn-outline-info btn-action" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $app['id']; ?>" title="View">
                                <i class="bi bi-eye"></i>
                            </button>
                            
                            <?php if ($app['status'] === 'pending'): ?>
                            <!-- Approve -->
                            <button class="btn btn-sm btn-outline-success btn-action" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $app['id']; ?>" title="Approve">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <!-- Reject -->
                            <button class="btn btn-sm btn-outline-danger btn-action" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $app['id']; ?>" title="Reject">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- View Modal -->
                    <div class="modal fade" id="viewModal<?php echo $app['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-info text-white">
                                    <h5 class="modal-title">Application: <?php echo outputSafe($app['application_number']); ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-3 text-center">
                                            <?php $photo = $app['photo'] ? BASE_URL . 'uploads/photos/' . $app['photo'] : BASE_URL . 'assets/images/default-avatar.png'; ?>
                                            <img src="<?php echo $photo; ?>" alt="Photo" class="img-fluid rounded" width="120">
                                        </div>
                                        <div class="col-md-9">
                                            <table class="table table-sm table-borderless">
                                                <tr><td class="text-muted" width="35%">Name</td><td><?php echo outputSafe($app['full_name']); ?></td></tr>
                                                <tr><td class="text-muted">Father</td><td><?php echo outputSafe($app['father_name']); ?></td></tr>
                                                <tr><td class="text-muted">Gender / DOB</td><td><?php echo outputSafe($app['gender']); ?> | <?php echo formatDate($app['dob']); ?> (Age: <?php echo $app['age']; ?>)</td></tr>
                                                <tr><td class="text-muted">Mobile / Email</td><td><?php echo outputSafe($app['mobile']); ?> | <?php echo outputSafe($app['email']); ?></td></tr>

                                                <tr><td class="text-muted">Address</td><td><?php echo outputSafe($app['address']); ?>, <?php echo outputSafe($app['city']); ?>, <?php echo outputSafe($app['state']); ?> - <?php echo outputSafe($app['pincode']); ?></td></tr>
                                                <tr><td class="text-muted">Aadhar</td><td><?php echo outputSafe($app['aadhar_number']); ?></td></tr>
                                                <tr><td class="text-muted">Route</td><td><?php echo outputSafe($app['source'] . ' → ' . $app['route_dest']); ?></td></tr>
                                                <tr><td class="text-muted">Boarding → Dest</td><td><?php echo outputSafe($app['boarding_point'] . ' → ' . $app['destination']); ?></td></tr>
                                                <tr><td class="text-muted">Duration</td><td><?php echo outputSafe($app['pass_duration']); ?></td></tr>
                                                <tr><td class="text-muted">Status</td><td><?php echo getStatusBadge($app['status']); ?></td></tr>
                                                <?php if ($app['remarks']): ?>
                                                <tr><td class="text-muted">Remarks</td><td><?php echo outputSafe($app['remarks']); ?></td></tr>
                                                <?php endif; ?>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button class="btn btn-outline-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($app['status'] === 'pending'): ?>
                    <!-- Approve Modal -->
                    <div class="modal fade" id="approveModal<?php echo $app['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title">Approve Application</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Approve application <strong><?php echo outputSafe($app['application_number']); ?></strong> by <strong><?php echo outputSafe($app['full_name']); ?></strong>?</p>
                                        <div class="mb-3">
                                            <label class="form-label">Remarks (optional)</label>
                                            <textarea class="form-control" name="remarks" rows="2" placeholder="Any remarks..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Approve</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reject Modal -->
                    <div class="modal fade" id="rejectModal<?php echo $app['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title">Reject Application</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Reject application <strong><?php echo outputSafe($app['application_number']); ?></strong>?</p>
                                        <div class="mb-3">
                                            <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="remarks" rows="2" placeholder="Reason for rejection..." required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>Reject</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php echo generatePagination($page, $totalPages, '?search=' . urlencode($search) . '&status=' . urlencode($statusFilter)); ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
