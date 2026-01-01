<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'record_payment') {
        $bill_id = (int)$_POST['bill_id'];
        $customer_id = (int)$_POST['customer_id'];
        $amount_paid = (float)$_POST['amount_paid'];
        $payment_method = $db->real_escape_string($_POST['payment_method']);
        $payment_date = $_POST['payment_date'];
        $transaction_ref = $db->real_escape_string($_POST['transaction_ref'] ?? '');
        $notes = $db->real_escape_string($_POST['notes'] ?? '');
        $recorded_by = $_SESSION['user_id'];

        // Get bill details
        $billResult = $db->query("SELECT grand_total FROM bills WHERE id = $bill_id");
        $bill = $billResult->fetch_assoc();

        // Insert payment
        $stmt = $db->prepare("INSERT INTO payments (bill_id, customer_id, amount_paid, payment_method, payment_date, transaction_reference, recorded_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iddsisss", $bill_id, $customer_id, $amount_paid, $payment_method, $payment_date, $transaction_ref, $recorded_by, $notes);

        if ($stmt->execute()) {
            // Update bill status
            $totalPaid = $db->query("SELECT COALESCE(SUM(amount_paid), 0) as total FROM payments WHERE bill_id = $bill_id")->fetch_assoc()['total'];
            $billAmount = $bill['grand_total'];

            if ($totalPaid >= $billAmount) {
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'partially_paid';
            } else {
                $status = 'unpaid';
            }

            $db->query("UPDATE bills SET payment_status = '$status' WHERE id = $bill_id");

            $message = 'Payment recorded successfully';
            $message_type = 'success';
            logAction('CREATE', 'payments', $db->insert_id);
        } else {
            $message = 'Error recording payment';
            $message_type = 'danger';
        }
    }
}

// Get payments
$page = (int)($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;
$method = $_GET['method'] ?? '';

$query = "SELECT p.*, c.full_name, b.bill_number, b.grand_total FROM payments p
          JOIN customers c ON p.customer_id = c.id
          JOIN bills b ON p.bill_id = b.id";

if (!empty($method)) {
    $query .= " WHERE p.payment_method = '$method'";
}

$count_result = $db->query("SELECT COUNT(*) as total FROM payments WHERE 1=1 " . (!empty($method) ? "AND payment_method = '$method'" : ''));
$total = $count_result->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

$query .= " ORDER BY p.payment_date DESC, p.created_at DESC LIMIT $offset, $per_page";

$payments = [];
$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}

// Get stats
$todayResult = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(amount_paid), 0) as total FROM payments WHERE DATE(payment_date) = CURDATE()");
$todayStats = $todayResult->fetch_assoc();

$weekResult = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(amount_paid), 0) as total FROM payments WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$weekStats = $weekResult->fetch_assoc();

$totalResult = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(amount_paid), 0) as total FROM payments");
$totalStats = $totalResult->fetch_assoc();

// Get bills for payment
$unpaidBills = [];
$result = $db->query("SELECT b.id, b.bill_number, c.id as customer_id, c.full_name, b.grand_total, b.due_date FROM bills b
                     JOIN customers c ON b.customer_id = c.id
                     WHERE b.payment_status IN ('unpaid', 'partially_paid')
                     ORDER BY b.due_date ASC");
while ($row = $result->fetch_assoc()) {
    $unpaidBills[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - JEMA Water Management</title>
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
                    <h2>Payments</h2>
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
                            <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Today's Payments</p>
                                <h3 class="stat-value"><?php echo number_format($todayStats['total'], 0); ?> SOS</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">This Week</p>
                                <h3 class="stat-value"><?php echo number_format($weekStats['total'], 0); ?> SOS</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Total Transactions</p>
                                <h3 class="stat-value"><?php echo $totalStats['count']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card danger">
                            <div class="stat-icon"><i class="fas fa-coins"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Total Collected</p>
                                <h3 class="stat-value"><?php echo number_format($totalStats['total'], 0); ?> SOS</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Record Payment -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Record Payment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="record_payment">
                            <div class="col-md-4">
                                <label class="form-label">Bill *</label>
                                <select class="form-control" name="bill_id" id="billSelect" required onchange="updateBillInfo()">
                                    <option value="">Select Bill</option>
                                    <?php foreach ($unpaidBills as $bill): ?>
                                        <option value="<?php echo $bill['id']; ?>" data-customer="<?php echo $bill['customer_id']; ?>" data-amount="<?php echo $bill['grand_total']; ?>">
                                            <?php echo escape($bill['bill_number']); ?> - <?php echo escape($bill['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="customer_id" id="customerInput">
                            <div class="col-md-2">
                                <label class="form-label">Bill Amount</label>
                                <input type="text" class="form-control" id="billAmount" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Amount Paid *</label>
                                <input type="number" class="form-control" name="amount_paid" step="0.01" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Method *</label>
                                <select class="form-control" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-save"></i> Record
                                </button>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Transaction Reference</label>
                                <input type="text" class="form-control" name="transaction_ref" placeholder="e.g., Mobile money reference">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Date</label>
                                <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="1" placeholder="Notes..."></textarea>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Payment History</h5>
                        <div>
                            <a href="?method=cash" class="btn btn-sm btn-outline-primary">Cash</a>
                            <a href="?method=mobile_money" class="btn btn-sm btn-outline-info">Mobile Money</a>
                            <a href="payments.php" class="btn btn-sm btn-outline-secondary">All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Bill #</th>
                                        <th>Customer</th>
                                        <th>Amount Paid (SOS)</th>
                                        <th>Bill Amount (SOS)</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><strong><?php echo escape($payment['bill_number']); ?></strong></td>
                                            <td><?php echo escape($payment['full_name']); ?></td>
                                            <td><?php echo number_format($payment['amount_paid'], 0); ?></td>
                                            <td><?php echo number_format($payment['grand_total'], 0); ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo escape($payment['transaction_reference']); ?></td>
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
                                            <a class="page-link" href="?page=<?php echo $i; ?>&method=<?php echo $method; ?>">
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
        function updateBillInfo() {
            const select = document.getElementById('billSelect');
            const option = select.options[select.selectedIndex];
            document.getElementById('customerInput').value = option.dataset.customer;
            document.getElementById('billAmount').value = new Intl.NumberFormat('en-US').format(option.dataset.amount) + ' SOS';
        }

        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
