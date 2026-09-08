<?php
/**
 * Manage Users
 * 
 * Admin can view, search, filter, and manage all registered users.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Manage Users';
$currentPage = 'manage_users';

require_once '../includes/header.php';
requireAdmin();
require_once '../includes/admin_sidebar.php';

// Handle status change
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitize($_GET['action']);
    $userId = intval($_GET['id']);
    
    if (in_array($action, ['activate', 'block', 'deactivate'])) {
        $newStatus = ($action === 'activate') ? 'active' : (($action === 'block') ? 'blocked' : 'inactive');
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        logActivity($pdo, 'admin', $_SESSION['admin_id'], "Changed user #{$userId} status to {$newStatus}");
        setFlashMessage('success', "User status updated to {$newStatus}.");
        redirect(BASE_URL . 'admin/manage_users.php');
    }
}

// Pagination & Filters
$page = max(1, intval($_GET['page'] ?? 1));
$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');

$where = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (full_name LIKE ? OR username LIKE ? OR email LIKE ? OR mobile LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}

if (!empty($statusFilter)) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users {$where}");
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / RECORDS_PER_PAGE);
$offset = ($page - 1) * RECORDS_PER_PAGE;

$stmt = $pdo->prepare("SELECT * FROM users {$where} ORDER BY created_at DESC LIMIT " . RECORDS_PER_PAGE . " OFFSET {$offset}");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Users</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<div class="card table-card">
    <div class="card-header">
        <i class="bi bi-people me-2"></i>All Users
        <span class="badge bg-primary rounded-pill ms-2"><?php echo $totalRecords; ?></span>
    </div>
    <div class="card-body">
        <!-- Search & Filter -->
        <form method="GET" class="row mb-3 g-2">
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" name="search" placeholder="Search by name, username, email..." value="<?php echo outputSafe($search); ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="blocked" <?php echo $statusFilter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Search</button>
                <?php if (!empty($search) || !empty($statusFilter)): ?>
                    <a href="manage_users.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
        
        <?php if (empty($users)): ?>
            <div class="text-center py-4 text-muted"><i class="bi bi-people fs-1"></i><p class="mt-2">No users found.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Mobile</th>

                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?php echo $offset + $i + 1; ?></td>
                        <td><strong><?php echo outputSafe($u['full_name']); ?></strong></td>
                        <td><?php echo outputSafe($u['username']); ?></td>
                        <td><?php echo outputSafe($u['email']); ?></td>
                        <td><?php echo outputSafe($u['mobile'] ?? 'N/A'); ?></td>

                        <td><?php echo getStatusBadge($u['status']); ?></td>
                        <td><small><?php echo formatDate($u['created_at']); ?></small></td>
                        <td>
                            <?php if ($u['status'] === 'active'): ?>
                                <a href="?action=block&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger btn-action confirm-action" data-message="Block this user?"><i class="bi bi-slash-circle"></i></a>
                            <?php else: ?>
                                <a href="?action=activate&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-success btn-action"><i class="bi bi-check-circle"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php echo generatePagination($page, $totalPages, '?search=' . urlencode($search) . '&status=' . urlencode($statusFilter)); ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
