<?php
/**
 * Bus Timetable
 * 
 * Displays the complete bus timetable with route and bus details.
 * Supports filtering by route.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Bus Timetable';
$currentPage = 'bus_timetable';

require_once '../includes/header.php';
requireUser();
require_once '../includes/user_sidebar.php';

// Fetch filter
$routeFilter = intval($_GET['route'] ?? 0);

// Fetch routes for filter dropdown
$stmt = $pdo->prepare("SELECT * FROM bus_routes WHERE status = 'active' ORDER BY source");
$stmt->execute();
$routes = $stmt->fetchAll();

// Build timetable query
$where = "WHERE t.status = 'active'";
$params = [];

if ($routeFilter > 0) {
    $where .= " AND t.route_id = ?";
    $params[] = $routeFilter;
}

$stmt = $pdo->prepare("SELECT t.*, b.bus_number, b.bus_name, b.bus_type, b.pass_available,
                               r.source, r.destination, r.route_code
                        FROM timetable t
                        JOIN buses b ON t.bus_id = b.id
                        JOIN bus_routes r ON t.route_id = r.id
                        {$where}
                        ORDER BY r.source, t.departure_time");
$stmt->execute($params);
$timetable = $stmt->fetchAll();
?>

<!-- Breadcrumb -->
<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Bus Timetable</li>
    </ol>
</nav>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-clock me-2"></i>Bus Timetable</span>
        
        <!-- Route Filter -->
        <form method="GET" class="d-flex gap-2">
            <select class="form-select form-select-sm" name="route" style="width: auto;" onchange="this.form.submit()">
                <option value="0">All Routes</option>
                <?php foreach ($routes as $route): ?>
                    <option value="<?php echo $route['id']; ?>" <?php echo $routeFilter == $route['id'] ? 'selected' : ''; ?>>
                        <?php echo outputSafe($route['source'] . ' → ' . $route['destination']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="card-body">
        <?php if (empty($timetable)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-clock-history fs-1"></i>
                <p class="mt-2">No timetable entries found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="timetableTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Route</th>
                            <th>Bus No.</th>
                            <th>Bus Name</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Type</th>
                            <th>Days</th>
                            <th>Pass</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timetable as $index => $entry): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <strong><?php echo outputSafe($entry['source'] . ' → ' . $entry['destination']); ?></strong>
                                <br><small class="text-muted"><?php echo outputSafe($entry['route_code']); ?></small>
                            </td>
                            <td><?php echo outputSafe($entry['bus_number']); ?></td>
                            <td><?php echo outputSafe($entry['bus_name']); ?></td>
                            <td><span class="text-primary fw-bold"><?php echo date('h:i A', strtotime($entry['departure_time'])); ?></span></td>
                            <td><span class="text-success fw-bold"><?php echo date('h:i A', strtotime($entry['arrival_time'])); ?></span></td>
                            <td><span class="badge bg-info"><?php echo outputSafe($entry['bus_type']); ?></span></td>
                            <td><small><?php echo outputSafe($entry['days_of_operation']); ?></small></td>
                            <td>
                                <?php if ($entry['pass_available'] === 'Yes'): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Client-side table search
document.addEventListener('DOMContentLoaded', function() {
    filterTable('routeSearchInput', 'timetableTable');
});
</script>

<?php require_once '../includes/footer.php'; ?>
