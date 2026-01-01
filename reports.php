<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$report_type = $_GET['type'] ?? 'daily';
$report_date = $_GET['date'] ?? date('Y-m-d');
$report_month = $_GET['month'] ?? date('Y-m');

// Daily Report
$dailyData = [];
if ($report_type === 'daily') {
    // Water supply
    $waterSupply = $db->query("SELECT SUM(quantity_received) as total FROM water_supply WHERE supply_date = '$report_date'");
    $dailyData['water_supply'] = $waterSupply->fetch_assoc()['total'] ?? 0;

    // Water usage
    $waterUsage = $db->query("SELECT SUM(quantity_used) as total FROM water_usage WHERE reading_date = '$report_date'");
    $dailyData['water_usage'] = $waterUsage->fetch_assoc()['total'] ?? 0;

    // Payments today
    $payments = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(amount_paid), 0) as total FROM payments WHERE DATE(payment_date) = '$report_date'");
    $paymentData = $payments->fetch_assoc();
    $dailyData['payments_count'] = $paymentData['count'];
    $dailyData['payments_total'] = $paymentData['total'];
}

// Monthly Report
$monthlyData = [];
if ($report_type === 'monthly') {
    $year = substr($report_month, 0, 4);
    $month = substr($report_month, 5, 2);

    // Water supply
    $waterSupply = $db->query("SELECT SUM(quantity_received) as total FROM water_supply WHERE YEAR(supply_date) = '$year' AND MONTH(supply_date) = '$month'");
    $monthlyData['water_supply'] = $waterSupply->fetch_assoc()['total'] ?? 0;

    // Water usage
    $waterUsage = $db->query("SELECT SUM(quantity_used) as total FROM water_usage WHERE YEAR(reading_date) = '$year' AND MONTH(reading_date) = '$month'");
    $monthlyData['water_usage'] = $waterUsage->fetch_assoc()['total'] ?? 0;

    // Payments
    $payments = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(amount_paid), 0) as total FROM payments WHERE YEAR(payment_date) = '$year' AND MONTH(payment_date) = '$month'");
    $paymentData = $payments->fetch_assoc();
    $monthlyData['payments_count'] = $paymentData['count'];
    $monthlyData['payments_total'] = $paymentData['total'];

    // Bills generated
    $bills = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total FROM bills WHERE YEAR(issued_date) = '$year' AND MONTH(issued_date) = '$month'");
    $billData = $bills->fetch_assoc();
    $monthlyData['bills_count'] = $billData['count'];
    $monthlyData['bills_total'] = $billData['total'];
}

// Daily water chart data
$chartData = [];
if ($report_type === 'daily') {
    $result = $db->query("SELECT HOUR(created_at) as hour, SUM(amount_paid) as revenue FROM payments WHERE DATE(payment_date) = '$report_date' GROUP BY HOUR(created_at)");
    while ($row = $result->fetch_assoc()) {
        $chartData[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - JEMA Water Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-bar">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                    <h2>Reports</h2>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo escape($_SESSION['full_name']); ?></span>
                    </div>
                </div>
            </header>

            <div class="content">
                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Report Type</label>
                                <select class="form-control" id="reportType" onchange="changeReportType()">
                                    <option value="daily" <?php echo $report_type === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                    <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    <option value="yearly" <?php echo $report_type === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="dateFilter">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" id="reportDate" value="<?php echo $report_date; ?>" onchange="filterReport()">
                            </div>
                            <div class="col-md-3" id="monthFilter" style="display: none;">
                                <label class="form-label">Month</label>
                                <input type="month" class="form-control" id="reportMonth" value="<?php echo $report_month; ?>" onchange="filterReport()">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary w-100" onclick="exportReport()">
                                    <i class="fas fa-download"></i> Export PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($report_type === 'daily'): ?>
                    <!-- Daily Report -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card primary">
                                <div class="stat-icon"><i class="fas fa-water"></i></div>
                                <div class="stat-info">
                                    <p class="stat-label">Water Supply</p>
                                    <h3 class="stat-value"><?php echo number_format($dailyData['water_supply'], 0); ?> L</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card success">
                                <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                                <div class="stat-info">
                                    <p class="stat-label">Water Usage</p>
                                    <h3 class="stat-value"><?php echo number_format($dailyData['water_usage'], 0); ?> L</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card warning">
                                <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
                                <div class="stat-info">
                                    <p class="stat-label">Payments</p>
                                    <h3 class="stat-value"><?php echo $dailyData['payments_count']; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card danger">
                                <div class="stat-icon"><i class="fas fa-coins"></i></div>
                                <div class="stat-info">
                                    <p class="stat-label">Revenue</p>
                                    <h3 class="stat-value"><?php echo number_format($dailyData['payments_total'], 0); ?> SOS</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($report_type === 'monthly'): ?>
                    <!-- Monthly Report -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card primary">
                                <div class="stat-icon"><i class="fas fa-water"></i></div>
                                <div class="stat-info">
                                    <p class="stat-label">Total Supply</p>
                                    <h3 class="stat-value"><?php echo number_format($monthlyData['water_supply'], 0); ?> L</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card success">
                                <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                                <div class="stat-info">
                                    <p class="stat-label">Total Usage</p>
                                    <h3 class="stat-value"><?php echo number_format($monthlyData['water_usage'], 0); ?> L</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card warning">
                                <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                                <div class="stat-info">
                                    <p class="stat-label">Bills Generated</p>
                                    <h3 class="stat-value"><?php echo $monthlyData['bills_count']; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card danger">
                                <div class="stat-icon"><i class="fas fa-coins"></i></div>
                                <div class="stat-info">
                                    <p class="stat-label">Revenue</p>
                                    <h3 class="stat-value"><?php echo number_format($monthlyData['payments_total'], 0); ?> SOS</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Detailed Table -->
                <div class="card">
                    <div class="card-header">
                        <h5>Detailed Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Detailed analytics and data tables will be displayed here based on selected report type.
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeReportType() {
            const type = document.getElementById('reportType').value;
            document.getElementById('dateFilter').style.display = type === 'daily' ? 'block' : 'none';
            document.getElementById('monthFilter').style.display = type === 'monthly' ? 'block' : 'none';
            filterReport();
        }

        function filterReport() {
            const type = document.getElementById('reportType').value;
            let param = '';

            if (type === 'daily') {
                param = '&date=' + document.getElementById('reportDate').value;
            } else if (type === 'monthly') {
                param = '&month=' + document.getElementById('reportMonth').value;
            }

            window.location.href = 'reports.php?type=' + type + param;
        }

        function exportReport() {
            alert('Export functionality will be implemented');
        }

        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
