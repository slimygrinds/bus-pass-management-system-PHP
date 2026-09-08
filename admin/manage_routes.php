<?php
/**
 * Manage Routes
 * 
 * Admin can add, edit, delete, and search bus routes.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Manage Routes';
$currentPage = 'manage_routes';

require_once '../includes/header.php';
requireAdmin();
require_once '../includes/admin_sidebar.php';

$errors = [];

// ==========================================
// Handle Add/Edit/Delete
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && validateCSRFToken($_POST['csrf_token'])) {
        $action = $_POST['form_action'] ?? '';
        
        // Add Route
        if ($action === 'add') {
            $routeCode   = sanitize($_POST['route_code'] ?? '');
            $source      = sanitize($_POST['source'] ?? '');
            $destination = sanitize($_POST['destination'] ?? '');
            $distance    = floatval($_POST['distance_km'] ?? 0);
            $fare        = floatval($_POST['fare'] ?? 0);
            
            if (empty($routeCode) || empty($source) || empty($destination)) {
                setFlashMessage('danger', 'All fields are required.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO bus_routes (route_code, source, destination, distance_km, fare) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$routeCode, $source, $destination, $distance, $fare]);
                logActivity($pdo, 'admin', $_SESSION['admin_id'], "Added route: {$routeCode}");
                setFlashMessage('success', 'Route added successfully!');
            }
            redirect(BASE_URL . 'admin/manage_routes.php');
        }
        
        // Edit Route
        if ($action === 'edit') {
            $id          = intval($_POST['route_id'] ?? 0);
            $routeCode   = sanitize($_POST['route_code'] ?? '');
            $source      = sanitize($_POST['source'] ?? '');
            $destination = sanitize($_POST['destination'] ?? '');
            $distance    = floatval($_POST['distance_km'] ?? 0);
            $fare        = floatval($_POST['fare'] ?? 0);
            $status      = sanitize($_POST['status'] ?? 'active');
            
            $stmt = $pdo->prepare("UPDATE bus_routes SET route_code=?, source=?, destination=?, distance_km=?, fare=?, status=? WHERE id=?");
            $stmt->execute([$routeCode, $source, $destination, $distance, $fare, $status, $id]);
            logActivity($pdo, 'admin', $_SESSION['admin_id'], "Updated route: {$routeCode}");
            setFlashMessage('success', 'Route updated successfully!');
            redirect(BASE_URL . 'admin/manage_routes.php');
        }
        
        // Delete Route
        if ($action === 'delete') {
            $id = intval($_POST['route_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM bus_routes WHERE id = ?");
            $stmt->execute([$id]);
            logActivity($pdo, 'admin', $_SESSION['admin_id'], "Deleted route #{$id}");
            setFlashMessage('success', 'Route deleted successfully!');
            redirect(BASE_URL . 'admin/manage_routes.php');
        }
    }
}

// Search
$search = sanitize($_GET['search'] ?? '');
$where = "";
$params = [];
if (!empty($search)) {
    $where = "WHERE source LIKE ? OR destination LIKE ? OR route_code LIKE ?";
    $s = "%{$search}%";
    $params = [$s, $s, $s];
}

$stmt = $pdo->prepare("SELECT * FROM bus_routes {$where} ORDER BY route_code");
$stmt->execute($params);
$routes = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
?>

<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Routes</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<div class="row">
    <!-- Add Route Form -->
    <div class="col-lg-4 mb-4">
        <div class="card form-card">
            <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Add New Route</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="form_action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Route Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="route_code" placeholder="e.g., RT019" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Source <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="source" placeholder="e.g., Ahmedabad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Destination <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="destination" placeholder="e.g., Surat" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Distance (km)</label>
                        <input type="number" class="form-control" name="distance_km" step="0.01" placeholder="e.g., 265">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fare (₹)</label>
                        <input type="number" class="form-control" name="fare" step="0.01" placeholder="e.g., 300">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i>Add Route
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Routes List -->
    <div class="col-lg-8 mb-4">
        <div class="card table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-signpost-2 me-2"></i>All Routes (<?php echo count($routes); ?>)</span>
                <form method="GET" class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" name="search" placeholder="Search routes..." value="<?php echo outputSafe($search); ?>">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($routes)): ?>
                    <div class="text-center py-4 text-muted"><p>No routes found.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr><th>Code</th><th>Source</th><th>Destination</th><th>Distance</th><th>Fare</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($routes as $route): ?>
                            <tr>
                                <td><strong><?php echo outputSafe($route['route_code']); ?></strong></td>
                                <td><?php echo outputSafe($route['source']); ?></td>
                                <td><?php echo outputSafe($route['destination']); ?></td>
                                <td><?php echo $route['distance_km']; ?> km</td>
                                <td>₹<?php echo $route['fare']; ?></td>
                                <td><?php echo getStatusBadge($route['status']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary btn-action" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $route['id']; ?>"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="form_action" value="delete">
                                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-action confirm-action" data-message="Delete this route? All associated buses will also be deleted."><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $route['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="form_action" value="edit">
                                            <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                            <div class="modal-header"><h5 class="modal-title">Edit Route</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <div class="modal-body">
                                                <div class="mb-3"><label class="form-label">Route Code</label><input type="text" class="form-control" name="route_code" value="<?php echo outputSafe($route['route_code']); ?>" required></div>
                                                <div class="mb-3"><label class="form-label">Source</label><input type="text" class="form-control" name="source" value="<?php echo outputSafe($route['source']); ?>" required></div>
                                                <div class="mb-3"><label class="form-label">Destination</label><input type="text" class="form-control" name="destination" value="<?php echo outputSafe($route['destination']); ?>" required></div>
                                                <div class="mb-3"><label class="form-label">Distance (km)</label><input type="number" class="form-control" name="distance_km" step="0.01" value="<?php echo $route['distance_km']; ?>"></div>
                                                <div class="mb-3"><label class="form-label">Fare (₹)</label><input type="number" class="form-control" name="fare" step="0.01" value="<?php echo $route['fare']; ?>"></div>
                                                <div class="mb-3"><label class="form-label">Status</label>
                                                    <select class="form-select" name="status">
                                                        <option value="active" <?php echo $route['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $route['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
