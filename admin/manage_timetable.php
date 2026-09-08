<?php
/**
 * Manage Timetable
 * 
 * Admin can add, edit, and delete bus schedule entries.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Manage Timetable';
$currentPage = 'manage_timetable';

require_once '../includes/header.php';
requireAdmin();
require_once '../includes/admin_sidebar.php';

// Fetch routes and buses for dropdowns
$routes = $pdo->query("SELECT * FROM bus_routes WHERE status = 'active' ORDER BY source")->fetchAll();
$buses = $pdo->query("SELECT b.*, r.source, r.destination FROM buses b JOIN bus_routes r ON b.route_id = r.id WHERE b.status = 'active' ORDER BY b.bus_name")->fetchAll();

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && validateCSRFToken($_POST['csrf_token'])) {
    $action = $_POST['form_action'] ?? '';
    
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO timetable (route_id, bus_id, departure_time, arrival_time, days_of_operation) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([intval($_POST['route_id']), intval($_POST['bus_id']), $_POST['departure_time'], $_POST['arrival_time'], sanitize($_POST['days_of_operation'])]);
        setFlashMessage('success', 'Timetable entry added!');
        redirect(BASE_URL . 'admin/manage_timetable.php');
    }
    
    if ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE timetable SET route_id=?, bus_id=?, departure_time=?, arrival_time=?, days_of_operation=?, status=? WHERE id=?");
        $stmt->execute([intval($_POST['route_id']), intval($_POST['bus_id']), $_POST['departure_time'], $_POST['arrival_time'], sanitize($_POST['days_of_operation']), sanitize($_POST['status']), intval($_POST['entry_id'])]);
        setFlashMessage('success', 'Timetable updated!');
        redirect(BASE_URL . 'admin/manage_timetable.php');
    }
    
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM timetable WHERE id = ?")->execute([intval($_POST['entry_id'])]);
        setFlashMessage('success', 'Entry deleted!');
        redirect(BASE_URL . 'admin/manage_timetable.php');
    }
}

// Fetch timetable
$timetable = $pdo->query("SELECT t.*, b.bus_number, b.bus_name, r.source, r.destination, r.route_code FROM timetable t JOIN buses b ON t.bus_id = b.id JOIN bus_routes r ON t.route_id = r.id ORDER BY r.source, t.departure_time")->fetchAll();

$csrfToken = generateCSRFToken();
?>

<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Timetable</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<div class="row">
    <!-- Add Entry -->
    <div class="col-lg-4 mb-4">
        <div class="card form-card">
            <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Add Schedule</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="form_action" value="add">
                    <div class="mb-3"><label class="form-label">Route <span class="text-danger">*</span></label>
                        <select class="form-select" name="route_id" required>
                            <option value="">Select</option>
                            <?php foreach ($routes as $r): ?><option value="<?php echo $r['id']; ?>"><?php echo outputSafe($r['source'] . ' → ' . $r['destination']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Bus <span class="text-danger">*</span></label>
                        <select class="form-select" name="bus_id" required>
                            <option value="">Select</option>
                            <?php foreach ($buses as $b): ?><option value="<?php echo $b['id']; ?>"><?php echo outputSafe($b['bus_number'] . ' - ' . $b['bus_name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Departure Time <span class="text-danger">*</span></label><input type="time" class="form-control" name="departure_time" required></div>
                    <div class="mb-3"><label class="form-label">Arrival Time <span class="text-danger">*</span></label><input type="time" class="form-control" name="arrival_time" required></div>
                    <div class="mb-3"><label class="form-label">Days of Operation</label><input type="text" class="form-control" name="days_of_operation" value="Mon-Sat" placeholder="e.g., Mon-Sat, Daily"></div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Add</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Timetable List -->
    <div class="col-lg-8 mb-4">
        <div class="card table-card">
            <div class="card-header"><i class="bi bi-calendar3 me-2"></i>Timetable (<?php echo count($timetable); ?> entries)</div>
            <div class="card-body">
                <?php if (empty($timetable)): ?>
                    <div class="text-center py-4 text-muted"><p>No entries found.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead><tr><th>Route</th><th>Bus</th><th>Departure</th><th>Arrival</th><th>Days</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($timetable as $t): ?>
                            <tr>
                                <td><small><?php echo outputSafe($t['source'] . ' → ' . $t['destination']); ?></small></td>
                                <td><small><strong><?php echo outputSafe($t['bus_number']); ?></strong><br><?php echo outputSafe($t['bus_name']); ?></small></td>
                                <td><span class="text-primary fw-bold"><?php echo date('h:i A', strtotime($t['departure_time'])); ?></span></td>
                                <td><span class="text-success fw-bold"><?php echo date('h:i A', strtotime($t['arrival_time'])); ?></span></td>
                                <td><small><?php echo outputSafe($t['days_of_operation']); ?></small></td>
                                <td><?php echo getStatusBadge($t['status']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary btn-action" data-bs-toggle="modal" data-bs-target="#editTT<?php echo $t['id']; ?>"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="entry_id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-action confirm-action" data-message="Delete this entry?"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editTT<?php echo $t['id']; ?>" tabindex="-1">
                                <div class="modal-dialog"><div class="modal-content"><form method="POST"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><input type="hidden" name="form_action" value="edit"><input type="hidden" name="entry_id" value="<?php echo $t['id']; ?>">
                                    <div class="modal-header"><h5 class="modal-title">Edit Schedule</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <div class="mb-2"><label class="form-label">Route</label><select class="form-select" name="route_id"><?php foreach ($routes as $r): ?><option value="<?php echo $r['id']; ?>" <?php echo $t['route_id'] == $r['id'] ? 'selected' : ''; ?>><?php echo outputSafe($r['source'] . ' → ' . $r['destination']); ?></option><?php endforeach; ?></select></div>
                                        <div class="mb-2"><label class="form-label">Bus</label><select class="form-select" name="bus_id"><?php foreach ($buses as $b): ?><option value="<?php echo $b['id']; ?>" <?php echo $t['bus_id'] == $b['id'] ? 'selected' : ''; ?>><?php echo outputSafe($b['bus_number'] . ' - ' . $b['bus_name']); ?></option><?php endforeach; ?></select></div>
                                        <div class="mb-2"><label class="form-label">Departure</label><input type="time" class="form-control" name="departure_time" value="<?php echo $t['departure_time']; ?>" required></div>
                                        <div class="mb-2"><label class="form-label">Arrival</label><input type="time" class="form-control" name="arrival_time" value="<?php echo $t['arrival_time']; ?>" required></div>
                                        <div class="mb-2"><label class="form-label">Days</label><input type="text" class="form-control" name="days_of_operation" value="<?php echo outputSafe($t['days_of_operation']); ?>"></div>
                                        <div class="mb-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?php echo $t['status'] === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $t['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option></select></div>
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
