<?php
require_once 'config.php';
requireLogin();

$pricePerLiter = getSetting('price_per_liter', 0.5);

// Get dashboard statistics
$stats = [];

// Total Customers
$result = $db->query("SELECT COUNT(*) as count FROM customers WHERE status = 'active'");
$stats['total_customers'] = $result->fetch_assoc()['count'];

// Total Revenue Today
$result = $db->query("SELECT COALESCE(SUM(amount_paid), 0) as total FROM payments WHERE DATE(payment_date) = CURDATE()");
$stats['revenue_today'] = $result->fetch_assoc()['total'];

// Water Available
$result = $db->query("SELECT COALESCE(SUM(quantity_received), 0) as total FROM water_supply WHERE supply_date = CURDATE()");
$stats['water_today'] = $result->fetch_assoc()['total'];

// Unpaid Bills
$result = $db->query("SELECT COUNT(*) as count FROM bills WHERE payment_status = 'unpaid'");
$stats['unpaid_bills'] = $result->fetch_assoc()['count'];

// Recent Alerts
$result = $db->query("SELECT COUNT(*) as count FROM alerts WHERE status = 'new'");
$stats['new_alerts'] = $result->fetch_assoc()['count'];

// This Month Revenue
$result = $db->query("SELECT COALESCE(SUM(amount_paid), 0) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
$stats['monthly_revenue'] = $result->fetch_assoc()['total'];

// Get recent transactions
$recentTransactions = [];
$result = $db->query("SELECT p.*, c.full_name, b.bill_number FROM payments p
                     JOIN customers c ON p.customer_id = c.id
                     JOIN bills b ON p.bill_id = b.id
                     ORDER BY p.payment_date DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recentTransactions[] = $row;
}

// Get alerts
$alerts = [];
$result = $db->query("SELECT * FROM alerts WHERE status = 'new' ORDER BY created_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $alerts[] = $row;
}

// Get water supply data for chart (last 7 days)
$waterChartData = [];
$result = $db->query("SELECT DATE(supply_date) as date, SUM(quantity_received) as quantity FROM water_supply
                     WHERE supply_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                     GROUP BY DATE(supply_date) ORDER BY supply_date");
while ($row = $result->fetch_assoc()) {
    $waterChartData[] = $row;
}

// Get revenue data for chart (last 7 days)
$revenueChartData = [];
$result = $db->query("SELECT DATE(payment_date) as date, SUM(amount_paid) as revenue FROM payments
                     WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                     GROUP BY DATE(payment_date) ORDER BY payment_date");
while ($row = $result->fetch_assoc()) {
    $revenueChartData[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - JEMA Water Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-droplet"></i>
                    <span>JEMA</span>
                </div>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="customers.php">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="water-supply.php">
                        <i class="fas fa-faucet"></i>
                        <span>Water Supply</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="usage.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Water Usage</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="billing.php">
                        <i class="fas fa-receipt"></i>
                        <span>Billing</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payments.php">
                        <i class="fas fa-credit-card"></i>
                        <span>Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-file-pdf"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="alerts.php">
                        <i class="fas fa-bell"></i>
                        <span>Alerts</span>
                        <?php if ($stats['new_alerts'] > 0): ?>
                            <span class="badge bg-danger"><?php echo $stats['new_alerts']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="complaints.php">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Complaints</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-user-tie"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2>Dashboard</h2>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo escape($_SESSION['full_name']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                    </div>
                </div>
            </header>

            <div class="content">
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <p class="stat-label">Active Customers</p>
                                <h3 class="stat-value"><?php echo number_format($stats['total_customers']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="stat-info">
                                <p class="stat-label">Today Revenue</p>
                                <h3 class="stat-value"><?php echo number_format($stats['revenue_today'], 0); ?> SOS</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-water"></i>
                            </div>
                            <div class="stat-info">
                                <p class="stat-label">Water Today</p>
                                <h3 class="stat-value"><?php echo number_format($stats['water_today'], 0); ?> L</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card danger">
                            <div class="stat-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="stat-info">
                                <p class="stat-label">Unpaid Bills</p>
                                <h3 class="stat-value"><?php echo $stats['unpaid_bills']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Water Supply (Last 7 Days)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="waterChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Revenue (Last 7 Days)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Transactions</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Customer</th>
                                                <th>Bill</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentTransactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo escape($transaction['full_name']); ?></td>
                                                <td><?php echo escape($transaction['bill_number']); ?></td>
                                                <td><?php echo number_format($transaction['amount_paid'], 0); ?> SOS</td>
                                                <td><?php echo date('Y-m-d', strtotime($transaction['payment_date'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Alerts</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($alerts) > 0): ?>
                                    <div class="alerts-list">
                                        <?php foreach ($alerts as $alert): ?>
                                            <div class="alert-item alert-<?php echo $alert['severity']; ?>">
                                                <div class="alert-icon">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </div>
                                                <div class="alert-content">
                                                    <p class="alert-title"><?php echo escape($alert['title']); ?></p>
                                                    <p class="alert-time"><?php echo date('M d, h:i A', strtotime($alert['created_at'])); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No alerts at the moment</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Water Supply Chart
        const waterCtx = document.getElementById('waterChart').getContext('2d');
        new Chart(waterCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(fn($d) => date('M d', strtotime($d['date'])), $waterChartData)); ?>,
                datasets: [{
                    label: 'Water Supply (Liters)',
                    data: <?php echo json_encode(array_map(fn($d) => (int)$d['quantity'], $waterChartData)); ?>,
                    borderColor: '#1E88E5',
                    backgroundColor: 'rgba(30, 136, 229, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(fn($d) => date('M d', strtotime($d['date'])), $revenueChartData)); ?>,
                datasets: [{
                    label: 'Revenue (SOS)',
                    data: <?php echo json_encode(array_map(fn($d) => (int)$d['revenue'], $revenueChartData)); ?>,
                    backgroundColor: '#2E7D32'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Menu Toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
