<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $customer_id = (int)$_POST['customer_id'];
        $complaint_type = $db->real_escape_string($_POST['complaint_type']);
        $description = $db->real_escape_string($_POST['description']);
        $location = $db->real_escape_string($_POST['location'] ?? '');
        $priority = $db->real_escape_string($_POST['priority']);
        $complaint_number = 'CMPL-' . date('YmdHis') . rand(1000, 9999);
        $status = 'open';

        $stmt = $db->prepare("INSERT INTO complaints (complaint_number, customer_id, complaint_type, description, location, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssss", $complaint_number, $customer_id, $complaint_type, $description, $location, $priority, $status);

        if ($stmt->execute()) {
            $message = 'Complaint registered successfully';
            $message_type = 'success';
            logAction('CREATE', 'complaints', $db->insert_id);
        }
    } elseif ($action === 'update_status') {
        $id = (int)$_POST['id'];
        $new_status = $db->real_escape_string($_POST['status']);
        $resolution_notes = $db->real_escape_string($_POST['resolution_notes'] ?? '');
        $resolved_date = $new_status === 'resolved' ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare("UPDATE complaints SET status = ?, resolution_notes = ?, resolved_date = ? WHERE id = ?");
        $stmt->bind_param("sssi", $new_status, $resolution_notes, $resolved_date, $id);

        if ($stmt->execute()) {
            $message = 'Complaint updated successfully';
            $message_type = 'success';
            logAction('UPDATE', 'complaints', $id);
        }
    }
}

// Get complaints
$page = (int)($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;
$status_filter = $_GET['status'] ?? '';

$query = "SELECT cmp.*, c.full_name FROM complaints cmp
          JOIN customers c ON cmp.customer_id = c.id";

if (!empty($status_filter)) {
    $query .= " WHERE cmp.status = '$status_filter'";
}

$count_result = $db->query("SELECT COUNT(*) as total FROM complaints WHERE 1=1 " . (!empty($status_filter) ? "AND status = '$status_filter'" : ''));
$total = $count_result->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

$query .= " ORDER BY cmp.created_at DESC LIMIT $offset, $per_page";

$complaints = [];
$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}

// Get stats
$openCount = $db->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'open'")->fetch_assoc()['count'];
$inProgressCount = $db->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'in_progress'")->fetch_assoc()['count'];
$resolvedCount = $db->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'resolved'")->fetch_assoc()['count'];
$totalCount = $db->query("SELECT COUNT(*) as count FROM complaints")->fetch_assoc()['count'];

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
    <title>Complaints - JEMA Water Management</title>
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
                    <h2>Complaints</h2>
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
                            <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Open</p>
                                <h3 class="stat-value"><?php echo $openCount; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">In Progress</p>
                                <h3 class="stat-value"><?php echo $inProgressCount; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="fas fa-check"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Resolved</p>
                                <h3 class="stat-value"><?php echo $resolvedCount; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card primary">
                            <div class="stat-icon"><i class="fas fa-list"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Total</p>
                                <h3 class="stat-value"><?php echo $totalCount; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Complaint -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Register New Complaint</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addComplaintModal">
                            <i class="fas fa-plus"></i> New Complaint
                        </button>
                    </div>
                </div>

                <!-- Complaints Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Complaints List</h5>
                        <div>
                            <a href="?status=open" class="btn btn-sm btn-danger">Open</a>
                            <a href="?status=in_progress" class="btn btn-sm btn-warning">In Progress</a>
                            <a href="?status=resolved" class="btn btn-sm btn-success">Resolved</a>
                            <a href="complaints.php" class="btn btn-sm btn-secondary">All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Complaint #</th>
                                        <th>Customer</th>
                                        <th>Type</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($complaints as $complaint): ?>
                                        <tr>
                                            <td><strong><?php echo escape($complaint['complaint_number']); ?></strong></td>
                                            <td><?php echo escape($complaint['full_name']); ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo ucfirst(str_replace('_', ' ', $complaint['complaint_type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $complaint['priority'] === 'high' ? 'danger' : ($complaint['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                                                    <?php echo ucfirst($complaint['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $complaint['status'] === 'open' ? 'danger' : ($complaint['status'] === 'in_progress' ? 'warning' : 'success'); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($complaint['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewComplaint(<?php echo $complaint['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>">
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

    <!-- Add Complaint Modal -->
    <div class="modal fade" id="addComplaintModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Register Complaint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Customer *</label>
                            <select class="form-control" name="customer_id" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $cust): ?>
                                    <option value="<?php echo $cust['id']; ?>">
                                        <?php echo escape($cust['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Complaint Type *</label>
                            <select class="form-control" name="complaint_type" required>
                                <option value="leak">Leak</option>
                                <option value="low_pressure">Low Pressure</option>
                                <option value="billing_issue">Billing Issue</option>
                                <option value="meter_issue">Meter Issue</option>
                                <option value="service_quality">Service Quality</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority *</label>
                            <select class="form-control" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewComplaint(id) {
            alert('Detail view functionality will be implemented');
        }

        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
