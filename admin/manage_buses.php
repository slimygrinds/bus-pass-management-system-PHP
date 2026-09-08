<?php
/**
 * Manage Buses
 * 
 * Admin can add, edit, delete, and manage buses assigned to routes.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Manage Buses';
$currentPage = 'manage_buses';

require_once '../includes/header.php';
requireAdmin();
require_once '../includes/admin_sidebar.php';

// Fetch routes for dropdown
$stmt = $pdo->query("SELECT * FROM bus_routes WHERE status = 'active' ORDER BY source");
$routes = $stmt->fetchAll();

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && validateCSRFToken($_POST['csrf_token'])) {
    $action = $_POST['form_action'] ?? '';
    
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO buses (route_id, bus_number, bus_name, bus_type, capacity, driver_name, pass_available) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            intval($_POST['route_id']), sanitize($_POST['bus_number']), sanitize($_POST['bus_name']),
            sanitize($_POST['bus_type']), intval($_POST['capacity']), sanitize($_POST['driver_name']),
            sanitize($_POST['pass_available'])
        ]);
        logActivity($pdo, 'admin', $_SESSION['admin_id'], "Added bus: " . sanitize($_POST['bus_number']));
        setFlashMessage('success', 'Bus added successfully!');
        redirect(BASE_URL . 'admin/manage_buses.php');
    }
    
    if ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE buses SET route_id=?, bus_number=?, bus_name=?, bus_type=?, capacity=?, driver_name=?, pass_available=?, status=? WHERE id=?");
        $stmt->execute([
            intval($_POST['route_id']), sanitize($_POST['bus_number']), sanitize($_POST['bus_name']),
            sanitize($_POST['bus_type']), intval($_POST['capacity']), sanitize($_POST['driver_name']),
            sanitize($_POST['pass_available']), sanitize($_POST['status']), intval($_POST['bus_id'])
        ]);
        setFlashMessage('success', 'Bus updated!');
        redirect(BASE_URL . 'admin/manage_buses.php');
    }
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM buses WHERE id = ?");
        $stmt->execute([intval($_POST['bus_id'])]);
        setFlashMessage('success', 'Bus deleted!');
        redirect(BASE_URL . 'admin/manage_buses.php');
    }
}

// Fetch buses with route info
$search = sanitize($_GET['search'] ?? '');
$where = "";
$params = [];
if (!empty($search)) {
    $where = "WHERE b.bus_number LIKE ? OR b.bus_name LIKE ? OR b.driver_name LIKE ?";
    $s = "%{$search}%";
    $params = [$s, $s, $s];
}

$stmt = $pdo->prepare("SELECT b.*, r.source, r.destination, r.route_code FROM buses b JOIN bus_routes r ON b.route_id = r.id {$where} ORDER BY b.bus_number");
$stmt->execute($params);
$buses = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
?>

<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Buses</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<div class="row">
    <!-- Add Bus Form -->
    <div class="col-lg-4 mb-4">
        <div class="card form-card">
            <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Add New Bus</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="form_action" value="add">
                    <div class="mb-3"><label class="form-label">Route <span class="text-danger">*</span></label>
                        <select class="form-select" name="route_id" required>
                            <option value="">Select Route</option>
                            <?php foreach ($routes as $r): ?>
                                <option value="<?php echo $r['id']; ?>"><?php echo outputSafe($r['source'] . ' → ' . $r['destination']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Bus Number <span class="text-danger">*</span></label><input type="text" class="form-control" name="bus_number" placeholder="e.g., GJ-01-AB-1234" required></div>
                    <div class="mb-3"><label class="form-label">Bus Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="bus_name" placeholder="e.g., City Express" required></div>
                    <div class="mb-3"><label class="form-label">Bus Type</label>
                        <select class="form-select" name="bus_type">
                            <option value="Non-AC">Non-AC</option><option value="AC">AC</option><option value="Sleeper">Sleeper</option><option value="Semi-Sleeper">Semi-Sleeper</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Capacity</label><input type="number" class="form-control" name="capacity" value="50"></div>
                    <div class="mb-3"><label class="form-label">Driver Name</label><input type="text" class="form-control" name="driver_name" placeholder="Driver name"></div>
                    <div class="mb-3"><label class="form-label">Pass Available</label>
                        <select class="form-select" name="pass_available"><option value="Yes">Yes</option><option value="No">No</option></select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Add Bus</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Buses List -->
    <div class="col-lg-8 mb-4">
        <div class="card table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bus-front me-2"></i>All Buses (<?php echo count($buses); ?>)</span>
                <form method="GET" class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" name="search" placeholder="Search..." value="<?php echo outputSafe($search); ?>">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($buses)): ?>
                    <div class="text-center py-4 text-muted"><p>No buses found.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead><tr><th>Bus No.</th><th>Name</th><th>Route</th><th>Type</th><th>Capacity</th><th>Driver</th><th>Pass</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($buses as $bus): ?>
                            <tr>
                                <td><strong><?php echo outputSafe($bus['bus_number']); ?></strong></td>
                                <td><?php echo outputSafe($bus['bus_name']); ?></td>
                                <td><small><?php echo outputSafe($bus['source'] . ' → ' . $bus['destination']); ?></small></td>
                                <td><span class="badge bg-info"><?php echo outputSafe($bus['bus_type']); ?></span></td>
                                <td><?php echo $bus['capacity']; ?></td>
                                <td><small><?php echo outputSafe($bus['driver_name'] ?? 'N/A'); ?></small></td>
                                <td><?php echo $bus['pass_available'] === 'Yes' ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                                <td><?php echo getStatusBadge($bus['status']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary btn-action" data-bs-toggle="modal" data-bs-target="#editBus<?php echo $bus['id']; ?>"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-action confirm-action" data-message="Delete this bus?"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <!-- Edit Bus Modal -->
                            <div class="modal fade" id="editBus<?php echo $bus['id']; ?>" tabindex="-1">
                                <div class="modal-dialog"><div class="modal-content"><form method="POST"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><input type="hidden" name="form_action" value="edit"><input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                    <div class="modal-header"><h5 class="modal-title">Edit Bus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <div class="mb-2"><label class="form-label">Route</label><select class="form-select" name="route_id"><?php foreach ($routes as $r): ?><option value="<?php echo $r['id']; ?>" <?php echo $bus['route_id'] == $r['id'] ? 'selected' : ''; ?>><?php echo outputSafe($r['source'] . ' → ' . $r['destination']); ?></option><?php endforeach; ?></select></div>
                                        <div class="mb-2"><label class="form-label">Bus Number</label><input type="text" class="form-control" name="bus_number" value="<?php echo outputSafe($bus['bus_number']); ?>" required></div>
                                        <div class="mb-2"><label class="form-label">Bus Name</label><input type="text" class="form-control" name="bus_name" value="<?php echo outputSafe($bus['bus_name']); ?>" required></div>
                                        <div class="mb-2"><label class="form-label">Type</label><select class="form-select" name="bus_type"><?php foreach (['AC','Non-AC','Sleeper','Semi-Sleeper'] as $t): ?><option value="<?php echo $t; ?>" <?php echo $bus['bus_type'] === $t ? 'selected' : ''; ?>><?php echo $t; ?></option><?php endforeach; ?></select></div>
                                        <div class="mb-2"><label class="form-label">Capacity</label><input type="number" class="form-control" name="capacity" value="<?php echo $bus['capacity']; ?>"></div>
                                        <div class="mb-2"><label class="form-label">Driver</label><input type="text" class="form-control" name="driver_name" value="<?php echo outputSafe($bus['driver_name'] ?? ''); ?>"></div>
                                        <div class="mb-2"><label class="form-label">Pass Available</label><select class="form-select" name="pass_available"><option value="Yes" <?php echo $bus['pass_available'] === 'Yes' ? 'selected' : ''; ?>>Yes</option><option value="No" <?php echo $bus['pass_available'] === 'No' ? 'selected' : ''; ?>>No</option></select></div>
                                        <div class="mb-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?php echo $bus['status'] === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $bus['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option><option value="maintenance" <?php echo $bus['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option></select></div>
                                    </div>
                                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
                                </form></div></div>
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
