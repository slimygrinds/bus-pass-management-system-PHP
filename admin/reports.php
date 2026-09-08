<?php
/**
 * Reports
 * 
 * Displays reports with charts and statistics.
 * Monthly applications, approval ratio, route-wise analysis.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Reports';
$currentPage = 'reports';
$loadChartJs = true;

require_once '../includes/header.php';
requireAdmin();
require_once '../includes/admin_sidebar.php';

// Monthly applications data
$monthlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE DATE_FORMAT(applied_at, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $monthlyData[] = ['month' => date('M', strtotime("-{$i} months")), 'count' => $stmt->fetchColumn()];
}

// Route-wise application count
$stmt = $pdo->query("SELECT r.source, r.destination, COUNT(a.id) as app_count 
                      FROM bus_routes r LEFT JOIN applications a ON a.route_id = r.id 
                      GROUP BY r.id ORDER BY app_count DESC LIMIT 10");
$routeStats = $stmt->fetchAll();

// Status counts
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status");
$statusCounts = [];
while ($row = $stmt->fetch()) $statusCounts[$row['status']] = $row['count'];

// Pass duration stats
$stmt = $pdo->query("SELECT pass_duration, COUNT(*) as count FROM applications GROUP BY pass_duration ORDER BY count DESC");
$durationStats = $stmt->fetchAll();

// Total stats
$totalApps = array_sum($statusCounts);
$approvalRate = $totalApps > 0 ? round(($statusCounts['approved'] ?? 0) / $totalApps * 100) : 0;
?>

<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Reports</li>
    </ol>
</nav>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3"><div class="card stat-card primary"><div class="card-body"><div class="stat-label">Total Applications</div><div class="stat-number"><?php echo $totalApps; ?></div></div></div></div>
    <div class="col-md-3 mb-3"><div class="card stat-card success"><div class="card-body"><div class="stat-label">Approval Rate</div><div class="stat-number"><?php echo $approvalRate; ?>%</div></div></div></div>
    <div class="col-md-3 mb-3"><div class="card stat-card warning"><div class="card-body"><div class="stat-label">Pending</div><div class="stat-number"><?php echo $statusCounts['pending'] ?? 0; ?></div></div></div></div>
    <div class="col-md-3 mb-3"><div class="card stat-card danger"><div class="card-body"><div class="stat-label">Rejected</div><div class="stat-number"><?php echo $statusCounts['rejected'] ?? 0; ?></div></div></div></div>
</div>

<!-- Charts -->
<div class="row mb-4">
    <div class="col-lg-8 mb-3">
        <div class="card chart-card h-100">
            <div class="card-header"><i class="bi bi-graph-up me-2"></i>Monthly Applications (12 Months)</div>
            <div class="card-body"><canvas id="yearlyChart" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card chart-card h-100">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Pass Duration Breakdown</div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="durationChart" height="200"></canvas></div>
        </div>
    </div>
</div>

<!-- Route-wise Stats -->
<div class="card table-card">
    <div class="card-header"><i class="bi bi-signpost-2 me-2"></i>Top Routes by Applications</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>#</th><th>Route</th><th>Applications</th><th>Chart</th></tr></thead>
                <tbody>
                    <?php foreach ($routeStats as $i => $rs): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo outputSafe($rs['source'] . ' → ' . $rs['destination']); ?></td>
                        <td><strong><?php echo $rs['app_count']; ?></strong></td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <?php $pct = $totalApps > 0 ? round($rs['app_count'] / $totalApps * 100) : 0; ?>
                                <div class="progress-bar" style="width: <?php echo max($pct, 5); ?>%"><?php echo $pct; ?>%</div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Yearly line chart
    new Chart(document.getElementById('yearlyChart'), {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', array_map(function($m) { return "'" . $m['month'] . "'"; }, $monthlyData)); ?>],
            datasets: [{
                label: 'Applications',
                data: [<?php echo implode(',', array_column($monthlyData, 'count')); ?>],
                borderColor: '#4e73df', backgroundColor: 'rgba(78,115,223,0.1)',
                tension: 0.3, fill: true
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
    
    // Duration doughnut
    new Chart(document.getElementById('durationChart'), {
        type: 'doughnut',
        data: {
            labels: [<?php echo implode(',', array_map(function($d) { return "'" . $d['pass_duration'] . "'"; }, $durationStats)); ?>],
            datasets: [{
                data: [<?php echo implode(',', array_column($durationStats, 'count')); ?>],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
