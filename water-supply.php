<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_supply') {
        $supply_date = $_POST['supply_date'];
        $source_id = (int)$_POST['source_id'];
        $quantity_received = (float)$_POST['quantity_received'];
        $cost = (float)($_POST['cost'] ?? 0);
        $notes = $db->real_escape_string($_POST['notes'] ?? '');
        $recorded_by = $_SESSION['user_id'];

        $stmt = $db->prepare("INSERT INTO water_supply (supply_date, source_id, quantity_received, cost, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siddsii", $supply_date, $source_id, $quantity_received, $cost, $notes, $recorded_by);

        if ($stmt->execute()) {
            $message = 'Water supply recorded successfully';
            $message_type = 'success';
            logAction('CREATE', 'water_supply', $db->insert_id);

            // Check if water level is low and create alert
            $alertResult = $db->query("SELECT SUM(quantity_received) as total FROM water_supply WHERE supply_date = CURDATE()");
            $total = $alertResult->fetch_assoc()['total'];
            $low_water_level = (int)getSetting('low_water_alert_level', 20000);

            if ($total < $low_water_level) {
                $stmt = $db->prepare("INSERT INTO alerts (alert_type, severity, title, description, status) VALUES (?, ?, ?, ?, ?)");
                $title = 'Low Water Level Alert';
                $description = 'Current water supply is below threshold';
                $severity = 'high';
                $alert_type = 'low_water';
                $status = 'new';
                $stmt->bind_param("sssss", $alert_type, $severity, $title, $description, $status);
                $stmt->execute();
            }
        } else {
            $message = 'Error recording water supply';
            $message_type = 'danger';
        }
    } elseif ($action === 'add_source') {
        $source_name = $db->real_escape_string($_POST['source_name']);
        $source_type = $db->real_escape_string($_POST['source_type']);
        $location = $db->real_escape_string($_POST['location'] ?? '');
        $capacity = (float)($_POST['capacity'] ?? 0);

        $stmt = $db->prepare("INSERT INTO water_sources (source_name, source_type, location, capacity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssd", $source_name, $source_type, $location, $capacity);

        if ($stmt->execute()) {
            $message = 'Water source added successfully';
            $message_type = 'success';
            logAction('CREATE', 'water_sources', $db->insert_id);
        } else {
            $message = 'Error adding water source';
            $message_type = 'danger';
        }
    }
}

// Get water supply records
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

$count_result = $db->query("SELECT COUNT(*) as total FROM water_supply");
$total = $count_result->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

$supplies = [];
$result = $db->query("SELECT ws.*, src.source_name, u.full_name FROM water_supply ws
                     JOIN water_sources src ON ws.source_id = src.id
                     JOIN users u ON ws.recorded_by = u.id
                     ORDER BY ws.supply_date DESC, ws.created_at DESC LIMIT $offset, $per_page");
while ($row = $result->fetch_assoc()) {
    $supplies[] = $row;
}

// Get water sources
$sources = [];
$result = $db->query("SELECT * FROM water_sources WHERE status = 'active' ORDER BY source_name");
while ($row = $result->fetch_assoc()) {
    $sources[] = $row;
}

// Get today's total water
$todayResult = $db->query("SELECT SUM(quantity_received) as total FROM water_supply WHERE supply_date = CURDATE()");
$todayTotal = $todayResult->fetch_assoc()['total'] ?? 0;

// Get this month's total
$monthResult = $db->query("SELECT SUM(quantity_received) as total FROM water_supply WHERE MONTH(supply_date) = MONTH(CURDATE())");
$monthTotal = $monthResult->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Supply - JEMA Water Management</title>
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
                    <h2>Water Supply Management</h2>
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
                            <div class="stat-icon"><i class="fas fa-water"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Today's Supply</p>
                                <h3 class="stat-value"><?php echo number_format($todayTotal, 0); ?> L</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">This Month</p>
                                <h3 class="stat-value"><?php echo number_format($monthTotal, 0); ?> L</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="fas fa-list"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Total Records</p>
                                <h3 class="stat-value"><?php echo number_format($total); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card primary">
                            <div class="stat-icon"><i class="fas fa-faucet"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Active Sources</p>
                                <h3 class="stat-value"><?php echo count($sources); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Supply Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Record Water Supply</h5>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSourceModal">
                            <i class="fas fa-plus"></i> Add Source
                        </button>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="add_supply">
                            <div class="col-md-3">
                                <label class="form-label">Supply Date *</label>
                                <input type="date" class="form-control" name="supply_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Source *</label>
                                <select class="form-control" name="source_id" required>
                                    <option value="">Select Source</option>
                                    <?php foreach ($sources as $source): ?>
                                        <option value="<?php echo $source['id']; ?>">
                                            <?php echo escape($source['source_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Quantity (Liters) *</label>
                                <input type="number" class="form-control" name="quantity_received" step="0.01" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Cost (SOS)</label>
                                <input type="number" class="form-control" name="cost" step="0.01">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
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

                <!-- Supply Records -->
                <div class="card">
                    <div class="card-header">
                        <h5>Water Supply Records</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Source</th>
                                        <th>Quantity (L)</th>
                                        <th>Cost (SOS)</th>
                                        <th>Recorded By</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($supplies as $supply): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d', strtotime($supply['supply_date'])); ?></td>
                                            <td><?php echo escape($supply['source_name']); ?></td>
                                            <td><?php echo number_format($supply['quantity_received'], 0); ?></td>
                                            <td><?php echo number_format($supply['cost'], 0); ?></td>
                                            <td><?php echo escape($supply['full_name']); ?></td>
                                            <td><?php echo escape($supply['notes']); ?></td>
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

    <!-- Add Source Modal -->
    <div class="modal fade" id="addSourceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Water Source</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_source">
                        <div class="mb-3">
                            <label class="form-label">Source Name *</label>
                            <input type="text" class="form-control" name="source_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Source Type *</label>
                            <select class="form-control" name="source_type" required>
                                <option value="well">Well</option>
                                <option value="tank">Tank</option>
                                <option value="truck">Truck</option>
                                <option value="borehole">Borehole</option>
                                <option value="reservoir">Reservoir</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacity (Liters)</label>
                            <input type="number" class="form-control" name="capacity" step="0.01">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Source</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
