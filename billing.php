<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$message = '';
$message_type = '';
$pricePerLiter = getSetting('price_per_liter', 0.5);
$serviceFee = getSetting('service_fee', 5000);
$taxRate = getSetting('tax_rate', 10);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate_bills') {
        $billing_period_start = $_POST['period_start'];
        $billing_period_end = $_POST['period_end'];
        $unit_price = (float)$_POST['unit_price'];

        // Get customers with usage in this period
        $query = "SELECT DISTINCT c.id, c.full_name, COALESCE(SUM(wu.quantity_used), 0) as total_used
                  FROM customers c
                  LEFT JOIN water_usage wu ON c.id = wu.customer_id AND wu.reading_date BETWEEN '$billing_period_start' AND '$billing_period_end'
                  WHERE c.status = 'active'
                  GROUP BY c.id";

        $result = $db->query($query);
        $generated = 0;

        while ($row = $result->fetch_assoc()) {
            $customer_id = $row['id'];
            $quantity_used = $row['total_used'];
            $total_amount = ($quantity_used * $unit_price) + $serviceFee;
            $taxes = ($total_amount * $taxRate) / 100;
            $grand_total = $total_amount + $taxes;

            $bill_number = 'BILL-' . date('YmdHis') . '-' . $customer_id;
            $due_date = date('Y-m-d', strtotime($billing_period_end . ' +7 days'));

            $stmt = $db->prepare("INSERT INTO bills (bill_number, customer_id, billing_period_start, billing_period_end, quantity_used, unit_price, total_amount, service_fee, taxes, grand_total, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissdddddds", $bill_number, $customer_id, $billing_period_start, $billing_period_end, $quantity_used, $unit_price, $total_amount, $serviceFee, $taxes, $grand_total, $due_date);

            if ($stmt->execute()) {
                $generated++;
                logAction('CREATE', 'bills', $db->insert_id);
            }
        }

        $message = "Generated $generated bills successfully";
        $message_type = 'success';
    }
}

// Get bills
$page = (int)($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;
$status = $_GET['status'] ?? '';

$query = "SELECT b.*, c.full_name, c.meter_number FROM bills b
          JOIN customers c ON b.customer_id = c.id";

if (!empty($status)) {
    $query .= " WHERE b.payment_status = '$status'";
}

$count_result = $db->query("SELECT COUNT(*) as total FROM bills WHERE 1=1 " . (!empty($status) ? "AND payment_status = '$status'" : ''));
$total = $count_result->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

$query .= " ORDER BY b.issued_date DESC LIMIT $offset, $per_page";

$bills = [];
$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
    $bills[] = $row;
}

// Get stats
$unpaidResult = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total FROM bills WHERE payment_status = 'unpaid'");
$unpaidStats = $unpaidResult->fetch_assoc();

$paidResult = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total FROM bills WHERE payment_status = 'paid'");
$paidStats = $paidResult->fetch_assoc();

$totalResult = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total FROM bills");
$totalStats = $totalResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - JEMA Water Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-bar">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                    <h2>Billing Management</h2>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo escape($_SESSION['full_name']); ?></span>
                    </div>
                </div>
            </header>

            <div class="content">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo escape($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card primary">
                            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Total Bills</p>
                                <h3 class="stat-value"><?php echo $totalStats['count']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card danger">
                            <div class="stat-icon"><i class="fas fa-exclamation"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Unpaid</p>
                                <h3 class="stat-value"><?php echo $unpaidStats['count']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="fas fa-check"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Paid</p>
                                <h3 class="stat-value"><?php echo $paidStats['count']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="fas fa-coins"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Unpaid Amount</p>
                                <h3 class="stat-value"><?php echo number_format($unpaidStats['total'], 0); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generate Bills -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Generate Billing Period</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="generate_bills">
                            <div class="col-md-3">
                                <label class="form-label">Period Start *</label>
                                <input type="date" class="form-control" name="period_start" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Period End *</label>
                                <input type="date" class="form-control" name="period_end" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unit Price (per liter) *</label>
                                <input type="number" class="form-control" name="unit_price" value="<?php echo $pricePerLiter; ?>" step="0.01" required>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-cog"></i> Generate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bills Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Bills List</h5>
                        <div>
                            <a href="?status=unpaid" class="btn btn-sm btn-outline-danger">Unpaid</a>
                            <a href="?status=paid" class="btn btn-sm btn-outline-success">Paid</a>
                            <a href="billing.php" class="btn btn-sm btn-outline-primary">All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Bill #</th>
                                        <th>Customer</th>
                                        <th>Period</th>
                                        <th>Usage (L)</th>
                                        <th>Amount (SOS)</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bills as $bill): ?>
                                        <tr>
                                            <td><strong><?php echo escape($bill['bill_number']); ?></strong></td>
                                            <td><?php echo escape($bill['full_name']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($bill['billing_period_start'])); ?> to <?php echo date('Y-m-d', strtotime($bill['billing_period_end'])); ?></td>
                                            <td><?php echo number_format($bill['quantity_used'], 0); ?></td>
                                            <td><?php echo number_format($bill['grand_total'], 0); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $bill['payment_status'] === 'paid' ? 'success' : ($bill['payment_status'] === 'partially_paid' ? 'warning' : 'danger'); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $bill['payment_status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($bill['due_date'])); ?></td>
                                            <td>
                                                <a href="bill-detail.php?id=<?php echo $bill['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
