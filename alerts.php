<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'acknowledge') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE alerts SET status = 'acknowledged' WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Alert acknowledged';
            $message_type = 'success';
            logAction('UPDATE', 'alerts', $id);
        }
    } elseif ($action === 'resolve') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE alerts SET status = 'resolved' WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Alert marked as resolved';
            $message_type = 'success';
            logAction('UPDATE', 'alerts', $id);
        }
    } elseif ($action === 'create') {
        $alert_type = $db->real_escape_string($_POST['alert_type']);
        $severity = $db->real_escape_string($_POST['severity']);
        $title = $db->real_escape_string($_POST['title']);
        $description = $db->real_escape_string($_POST['description']);
        $related_customer_id = !empty($_POST['related_customer_id']) ? (int)$_POST['related_customer_id'] : null;

        $stmt = $db->prepare("INSERT INTO alerts (alert_type, severity, title, description, related_customer_id, status) VALUES (?, ?, ?, ?, ?, ?)");
        $status = 'new';
        $stmt->bind_param("ssssss", $alert_type, $severity, $title, $description, $related_customer_id, $status);

        if ($stmt->execute()) {
            $message = 'Alert created successfully';
            $message_type = 'success';
            logAction('CREATE', 'alerts', $db->insert_id);
        }
    }
}

// Get alerts
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;
$status = $_GET['status'] ?? 'new';

$query = "SELECT a.*, c.full_name FROM alerts a
          LEFT JOIN customers c ON a.related_customer_id = c.id";

if (!empty($status)) {
    $query .= " WHERE a.status = '$status'";
}

$count_result = $db->query("SELECT COUNT(*) as total FROM alerts WHERE 1=1 " . (!empty($status) ? "AND status = '$status'" : ''));
$total = $count_result->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

$query .= " ORDER BY a.created_at DESC LIMIT $offset, $per_page";

$alerts = [];
$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
    $alerts[] = $row;
}

// Get stats
$newCount = $db->query("SELECT COUNT(*) as count FROM alerts WHERE status = 'new'")->fetch_assoc()['count'];
$criticalCount = $db->query("SELECT COUNT(*) as count FROM alerts WHERE severity = 'critical' AND status != 'resolved'")->fetch_assoc()['count'];
$totalCount = $db->query("SELECT COUNT(*) as count FROM alerts")->fetch_assoc()['count'];

// Get customers
$customers = [];
$result = $db->query("SELECT id, full_name FROM customers WHERE status = 'active' ORDER BY full_name");
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts - JEMA Water Management</title>
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
                    <h2>Alerts & Issues</h2>
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
                        <div class="stat-card danger">
                            <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">New Alerts</p>
                                <h3 class="stat-value"><?php echo $newCount; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="fas fa-triangle-exclamation"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Critical</p>
                                <h3 class="stat-value"><?php echo $criticalCount; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card primary">
                            <div class="stat-icon"><i class="fas fa-bell"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Total</p>
                                <h3 class="stat-value"><?php echo $totalCount; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addAlertModal" style="height: 100%; border: none; border-radius: 12px;">
                            <i class="fas fa-plus"></i> New Alert
                        </button>
                    </div>
                </div>

                <!-- Alerts Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Alerts List</h5>
                        <div>
                            <a href="?status=new" class="btn btn-sm btn-danger">New</a>
                            <a href="?status=acknowledged" class="btn btn-sm btn-warning">Acknowledged</a>
                            <a href="?status=resolved" class="btn btn-sm btn-success">Resolved</a>
                            <a href="alerts.php" class="btn btn-sm btn-secondary">All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Severity</th>
                                        <th>Title</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alerts as $alert): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo ucfirst(str_replace('_', ' ', $alert['alert_type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $alert['severity'] === 'critical' ? 'danger' : ($alert['severity'] === 'high' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo ucfirst($alert['severity']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo escape($alert['title']); ?></td>
                                            <td><?php echo $alert['full_name'] ? escape($alert['full_name']) : '-'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $alert['status'] === 'new' ? 'danger' : ($alert['status'] === 'acknowledged' ? 'warning' : 'success'); ?>">
                                                    <?php echo ucfirst($alert['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($alert['created_at'])); ?></td>
                                            <td>
                                                <?php if ($alert['status'] === 'new'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="acknowledge">
                                                        <input type="hidden" name="id" value="<?php echo $alert['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning">Acknowledge</button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($alert['status'] !== 'resolved'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="resolve">
                                                        <input type="hidden" name="id" value="<?php echo $alert['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">Resolve</button>
                                                    </form>
                                                <?php endif; ?>
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

    <!-- Add Alert Modal -->
    <div class="modal fade" id="addAlertModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Alert</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Alert Type *</label>
                            <select class="form-control" name="alert_type" required>
                                <option value="low_water">Low Water Level</option>
                                <option value="high_usage">High Usage</option>
                                <option value="unpaid_bill">Unpaid Bill</option>
                                <option value="leak_suspected">Leak Suspected</option>
                                <option value="system_alert">System Alert</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Severity *</label>
                            <select class="form-control" name="severity" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Related Customer (Optional)</label>
                            <select class="form-control" name="related_customer_id">
                                <option value="">None</option>
                                <?php foreach ($customers as $cust): ?>
                                    <option value="<?php echo $cust['id']; ?>">
                                        <?php echo escape($cust['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Alert</button>
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
