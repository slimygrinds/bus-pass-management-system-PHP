<?php
/**
 * My Applications
 * 
 * Displays all bus pass applications submitted by the user.
 * Shows status with color badges, search, and pagination.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'My Applications';
$currentPage = 'my_applications';

require_once '../includes/header.php';
requireUser();
require_once '../includes/user_sidebar.php';

$userId = $_SESSION['user_id'];

// Pagination setup
$page = max(1, intval($_GET['page'] ?? 1));
$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');

// Build query with filters
$where = "WHERE a.user_id = ?";
$params = [$userId];

if (!empty($search)) {
    $where .= " AND (a.application_number LIKE ? OR a.boarding_point LIKE ? OR a.destination LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($statusFilter)) {
    $where .= " AND a.status = ?";
    $params[] = $statusFilter;
}

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a {$where}");
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / RECORDS_PER_PAGE);
$offset = ($page - 1) * RECORDS_PER_PAGE;

// Fetch applications
$stmt = $pdo->prepare("SELECT a.*, r.source, r.destination as route_dest, r.route_code, p.id as pass_id, p.status as pass_status 
                        FROM applications a 
                        JOIN bus_routes r ON a.route_id = r.id 
                        LEFT JOIN passes p ON p.application_id = a.id
                        {$where}
                        ORDER BY a.applied_at DESC 
                        LIMIT " . RECORDS_PER_PAGE . " OFFSET {$offset}");
$stmt->execute($params);
$applications = $stmt->fetchAll();
?>

<!-- Breadcrumb -->
<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">My Applications</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-file-earmark-text me-2"></i>My Applications</span>
        <a href="apply_pass.php" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle me-1"></i>New Application
        </a>
    </div>
    <div class="card-body">
        <!-- Search & Filter -->
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" name="search" 
                           placeholder="Search by application no, route..." 
                           value="<?php echo outputSafe($search); ?>">
                    <select class="form-select form-select-sm" name="status" style="width: auto;">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if (!empty($search) || !empty($statusFilter)): ?>
                        <a href="my_applications.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">Showing <?php echo count($applications); ?> of <?php echo $totalRecords; ?> applications</small>
            </div>
        </div>
        
        <!-- Applications Table -->
        <?php if (empty($applications)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2">No applications found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Application No.</th>
                            <th>Route</th>
                            <th>Duration</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $index => $app): ?>
                        <tr>
                            <td><?php echo $offset + $index + 1; ?></td>
                            <td><strong><?php echo outputSafe($app['application_number']); ?></strong></td>
                            <td>
                                <small>
                                    <?php echo outputSafe($app['source'] . ' → ' . $app['route_dest']); ?>
                                    <br><span class="text-muted"><?php echo outputSafe($app['boarding_point'] . ' → ' . $app['destination']); ?></span>
                                </small>
                            </td>
                            <td><?php echo outputSafe($app['pass_duration']); ?></td>
                            <td><?php echo formatDate($app['applied_at']); ?></td>
                            <td><?php echo getStatusBadge($app['status']); ?></td>
                            <td>
                                <small class="text-muted"><?php echo outputSafe($app['remarks'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <?php if ($app['status'] === 'pending_payment'): ?>
                                    <a href="payment.php?app_id=<?php echo $app['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="bi bi-credit-card me-1"></i>Pay Now
                                    </a>
                                <?php elseif ($app['status'] === 'approved' && !empty($app['pass_id'])): ?>
                                    <?php if ($app['pass_status'] === 'active'): ?>
                                        <a href="download_pass.php?id=<?php echo $app['pass_id']; ?>" class="btn btn-sm btn-primary mb-1">
                                            <i class="bi bi-download me-1"></i>Download Pass
                                        </a>
                                        <a href="cancel_pass.php?id=<?php echo $app['pass_id']; ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Are you sure you want to cancel this pass? This action cannot be undone.');">
                                            <i class="bi bi-x-circle me-1"></i>Cancel Pass
                                        </a>
                                    <?php elseif ($app['pass_status'] === 'cancelled'): ?>
                                        <span class="badge bg-danger">Pass Cancelled</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($app['pass_status']); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php echo generatePagination($page, $totalPages, '?search=' . urlencode($search) . '&status=' . urlencode($statusFilter)); ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
