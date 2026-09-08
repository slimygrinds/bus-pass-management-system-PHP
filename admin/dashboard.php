<?php
/**
 * Admin Dashboard
 * 
 * Main admin landing page with statistics cards, charts,
 * recent activities, and latest registered users.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Admin Dashboard';
$currentPage = 'dashboard';
$loadChartJs = true; // Load Chart.js

require_once '../includes/header.php';
requireAdmin();
require_once '../includes/admin_sidebar.php';

// ==========================================
// Fetch Dashboard Statistics
// ==========================================

// Total Users
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();

// Pending Applications
$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
$pendingApps = $stmt->fetchColumn();

// Approved Passes
$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'approved'");
$approvedPasses = $stmt->fetchColumn();

// Rejected Passes
$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'rejected'");
$rejectedPasses = $stmt->fetchColumn();

// Active Passes (not expired)
$stmt = $pdo->query("SELECT COUNT(*) FROM passes WHERE status = 'active'");
$activePasses = $stmt->fetchColumn();

// Expired Passes
$stmt = $pdo->query("SELECT COUNT(*) FROM passes WHERE status = 'expired'");
$expiredPasses = $stmt->fetchColumn();

// Today's Applications
$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE DATE(applied_at) = CURDATE()");
$todayApps = $stmt->fetchColumn();

// Total Routes
$stmt = $pdo->query("SELECT COUNT(*) FROM bus_routes WHERE status = 'active'");
$totalRoutes = $stmt->fetchColumn();

// Total Buses
$stmt = $pdo->query("SELECT COUNT(*) FROM buses WHERE status = 'active'");
$totalBuses = $stmt->fetchColumn();

// Monthly Applications Data (last 6 months) for Chart
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE DATE_FORMAT(applied_at, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $monthlyData[] = [
        'month' => date('M Y', strtotime("-{$i} months")),
        'count' => $stmt->fetchColumn()
    ];
}

// Recent Activities
$stmt = $pdo->query("SELECT al.*, 
                             CASE WHEN al.user_type = 'admin' THEN ad.full_name 
                                  ELSE u.full_name END as user_name
                      FROM activity_logs al
                      LEFT JOIN users u ON al.user_type = 'user' AND al.user_id = u.id
                      LEFT JOIN admins ad ON al.user_type = 'admin' AND al.user_id = ad.id
                      ORDER BY al.created_at DESC LIMIT 8");
$recentActivities = $stmt->fetchAll();

// Latest Registered Users
$stmt = $pdo->query("SELECT id, full_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$latestUsers = $stmt->fetchAll();
?>

<!-- Breadcrumb -->
<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active">Admin Dashboard</li>
    </ol>
</nav>

<?php echo displayFlashMessage(); ?>

<!-- Welcome -->
<div class="welcome-card">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h4><i class="bi bi-shield-check me-2"></i>Admin Dashboard</h4>
            <p>Overview of the Bus Pass Management System. Manage users, applications, routes, and more.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="badge bg-light text-dark fs-6">
                <i class="bi bi-calendar me-1"></i><?php echo date('d M Y'); ?>
            </span>
        </div>
    </div>
</div>

<!-- Statistics Cards - Row 1 -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-number"><?php echo $totalUsers; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Pending Applications</div>
                        <div class="stat-number"><?php echo $pendingApps; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-clock"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Approved Passes</div>
                        <div class="stat-number"><?php echo $approvedPasses; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Rejected Passes</div>
                        <div class="stat-number"><?php echo $rejectedPasses; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards - Row 2 -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Active Passes</div>
                        <div class="stat-number"><?php echo $activePasses; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-credit-card"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card secondary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Expired Passes</div>
                        <div class="stat-number"><?php echo $expiredPasses; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-calendar-x"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Total Routes</div>
                        <div class="stat-number"><?php echo $totalRoutes; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-signpost-2"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stat-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Total Buses</div>
                        <div class="stat-number"><?php echo $totalBuses; ?></div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-bus-front"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Monthly Applications Chart -->
    <div class="col-lg-8 mb-3">
        <div class="card chart-card h-100">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2"></i>Monthly Applications (Last 6 Months)
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="120"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Approval Ratio Doughnut -->
    <div class="col-lg-4 mb-3">
        <div class="card chart-card h-100">
            <div class="card-header">
                <i class="bi bi-pie-chart me-2"></i>Application Status
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities & Latest Users -->
<div class="row">
    <!-- Recent Activities -->
    <div class="col-lg-7 mb-4">
        <div class="card table-card h-100">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>Recent Activities
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($recentActivities as $act): ?>
                    <div class="list-group-item py-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <span class="badge <?php echo $act['user_type'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?> me-1">
                                    <?php echo ucfirst($act['user_type']); ?>
                                </span>
                                <strong class="small"><?php echo outputSafe($act['user_name'] ?? 'Unknown'); ?></strong>
                                <span class="small text-muted"> - <?php echo outputSafe($act['action']); ?></span>
                            </div>
                            <small class="text-muted"><?php echo formatDate($act['created_at'], 'd M, h:i A'); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Latest Users -->
    <div class="col-lg-5 mb-4">
        <div class="card table-card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-person-plus me-2"></i>Latest Registered Users</span>
                <a href="manage_users.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>

                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestUsers as $u): ?>
                            <tr>
                                <td>
                                    <strong class="small"><?php echo outputSafe($u['full_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo outputSafe($u['email']); ?></small>
                                </td>

                                <td><small class="text-muted"><?php echo formatDate($u['created_at'], 'd M'); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Applications Bar Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($m) { return "'" . $m['month'] . "'"; }, $monthlyData)); ?>],
            datasets: [{
                label: 'Applications',
                data: [<?php echo implode(',', array_column($monthlyData, 'count')); ?>],
                backgroundColor: 'rgba(78, 115, 223, 0.7)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
    
    // Status Doughnut Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [{
                data: [<?php echo $pendingApps; ?>, <?php echo $approvedPasses; ?>, <?php echo $rejectedPasses; ?>],
                backgroundColor: ['#f6c23e', '#1cc88a', '#e74a3b'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
