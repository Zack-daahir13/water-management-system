<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$message = '';
$message_type = '';
$pricePerLiter = getSetting('price_per_liter', 0.5);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'record_usage') {
        $customer_id = (int)$_POST['customer_id'];
        $reading_date = $_POST['reading_date'];
        $current_reading = (float)$_POST['current_reading'];
        $notes = $db->real_escape_string($_POST['notes'] ?? '');
        $recorded_by = $_SESSION['user_id'];

        // Get previous reading
        $prevResult = $db->query("SELECT current_reading FROM water_usage WHERE customer_id = $customer_id ORDER BY reading_date DESC LIMIT 1");
        $previous_reading = $prevResult->fetch_assoc()['current_reading'] ?? 0;
        $quantity_used = $current_reading - $previous_reading;

        $stmt = $db->prepare("INSERT INTO water_usage (customer_id, reading_date, previous_reading, current_reading, quantity_used, recorded_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdddis", $customer_id, $reading_date, $previous_reading, $current_reading, $quantity_used, $recorded_by, $notes);

        if ($stmt->execute()) {
            $message = 'Water usage recorded successfully';
            $message_type = 'success';
            logAction('CREATE', 'water_usage', $db->insert_id);
        } else {
            $message = 'Error recording usage';
            $message_type = 'danger';
        }
    }
}

// Get usage records with pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;

$count_result = $db->query("SELECT COUNT(*) as total FROM water_usage");
$total = $count_result->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

$usages = [];
$result = $db->query("SELECT wu.*, c.full_name, c.meter_number FROM water_usage wu
                     JOIN customers c ON wu.customer_id = c.id
                     ORDER BY wu.reading_date DESC LIMIT $offset, $per_page");
while ($row = $result->fetch_assoc()) {
    $usages[] = $row;
}

// Get customers for dropdown
$customers = [];
$result = $db->query("SELECT id, full_name, meter_number FROM customers WHERE status = 'active' ORDER BY full_name");
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

// Get stats
$todayResult = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(quantity_used), 0) as total FROM water_usage WHERE reading_date = CURDATE()");
$todayStats = $todayResult->fetch_assoc();

$weekResult = $db->query("SELECT COALESCE(SUM(quantity_used), 0) as total FROM water_usage WHERE reading_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$weekStats = $weekResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Usage - JEMA Water Management</title>
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
                    <h2>Water Usage Tracking</h2>
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
                            <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Today's Readings</p>
                                <h3 class="stat-value"><?php echo $todayStats['count']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="fas fa-water"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Today's Usage</p>
                                <h3 class="stat-value"><?php echo number_format($todayStats['total'], 0); ?> L</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">This Week</p>
                                <h3 class="stat-value"><?php echo number_format($weekStats['total'], 0); ?> L</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card primary">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Total Records</p>
                                <h3 class="stat-value"><?php echo number_format($total); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Record Usage -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Record Manual Meter Reading</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="record_usage">
                            <div class="col-md-3">
                                <label class="form-label">Customer *</label>
                                <select class="form-control" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $cust): ?>
                                        <option value="<?php echo $cust['id']; ?>">
                                            <?php echo escape($cust['full_name']); ?> (<?php echo escape($cust['meter_number']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Reading Date *</label>
                                <input type="date" class="form-control" name="reading_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Current Reading (Liters) *</label>
                                <input type="number" class="form-control" name="current_reading" step="0.01" required>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save"></i> Record
                                </button>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="Additional notes..."></textarea>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Usage Records -->
                <div class="card">
                    <div class="card-header">
                        <h5>Meter Readings History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Meter</th>
                                        <th>Date</th>
                                        <th>Previous (L)</th>
                                        <th>Current (L)</th>
                                        <th>Used (L)</th>
                                        <th>Estimated Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usages as $usage): ?>
                                        <tr>
                                            <td><?php echo escape($usage['full_name']); ?></td>
                                            <td><?php echo escape($usage['meter_number']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($usage['reading_date'])); ?></td>
                                            <td><?php echo number_format($usage['previous_reading'], 0); ?></td>
                                            <td><?php echo number_format($usage['current_reading'], 0); ?></td>
                                            <td><?php echo number_format($usage['quantity_used'], 0); ?></td>
                                            <td><?php echo number_format($usage['quantity_used'] * $pricePerLiter, 0); ?> SOS</td>
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
                                            <a class="page-link" href="?page=<?php echo $i; ?>">
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
