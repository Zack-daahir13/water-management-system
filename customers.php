<?php
require_once 'config.php';
requireLogin();
requireAdmin();

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $customer_code = 'CUST-' . date('YmdHis') . rand(1000, 9999);
        $full_name = $db->real_escape_string($_POST['full_name']);
        $phone = $db->real_escape_string($_POST['phone'] ?? '');
        $email = $db->real_escape_string($_POST['email'] ?? '');
        $address = $db->real_escape_string($_POST['address'] ?? '');
        $district = $db->real_escape_string($_POST['district']);
        $area = $db->real_escape_string($_POST['area'] ?? '');
        $meter_number = $db->real_escape_string($_POST['meter_number']);
        $meter_type = $db->real_escape_string($_POST['meter_type'] ?? 'manual');
        $connection_date = $_POST['connection_date'] ?? date('Y-m-d');

        $stmt = $db->prepare("INSERT INTO customers (customer_code, full_name, phone, email, address, district, area, meter_number, meter_type, connection_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $customer_code, $full_name, $phone, $email, $address, $district, $area, $meter_number, $meter_type, $connection_date);

        if ($stmt->execute()) {
            $message = 'Customer added successfully';
            $message_type = 'success';
            logAction('CREATE', 'customers', $db->insert_id);
        } else {
            $message = 'Error adding customer: ' . $stmt->error;
            $message_type = 'danger';
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $full_name = $db->real_escape_string($_POST['full_name']);
        $phone = $db->real_escape_string($_POST['phone'] ?? '');
        $email = $db->real_escape_string($_POST['email'] ?? '');
        $address = $db->real_escape_string($_POST['address'] ?? '');
        $district = $db->real_escape_string($_POST['district']);
        $area = $db->real_escape_string($_POST['area'] ?? '');
        $status = $db->real_escape_string($_POST['status']);

        $stmt = $db->prepare("UPDATE customers SET full_name = ?, phone = ?, email = ?, address = ?, district = ?, area = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $full_name, $phone, $email, $address, $district, $area, $status, $id);

        if ($stmt->execute()) {
            $message = 'Customer updated successfully';
            $message_type = 'success';
            logAction('UPDATE', 'customers', $id);
        } else {
            $message = 'Error updating customer';
            $message_type = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE customers SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = 'Customer deleted successfully';
            $message_type = 'success';
            logAction('DELETE', 'customers', $id);
        }
    }
}

// Get customers
$search = $_GET['search'] ?? '';
$district = $_GET['district'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

$query = "SELECT * FROM customers WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM customers WHERE 1=1";

if (!empty($search)) {
    $search_escaped = $db->real_escape_string($search);
    $query .= " AND (full_name LIKE '%$search_escaped%' OR meter_number LIKE '%$search_escaped%' OR customer_code LIKE '%$search_escaped%')";
    $count_query .= " AND (full_name LIKE '%$search_escaped%' OR meter_number LIKE '%$search_escaped%' OR customer_code LIKE '%$search_escaped%')";
}

if (!empty($district)) {
    $district_escaped = $db->real_escape_string($district);
    $query .= " AND district = '$district_escaped'";
    $count_query .= " AND district = '$district_escaped'";
}

$count_result = $db->query($count_query);
$total = $count_result->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

$query .= " ORDER BY created_at DESC LIMIT $offset, $per_page";
$customers = [];
$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

// Get districts for filter
$districts = [];
$result = $db->query("SELECT DISTINCT district FROM customers WHERE district IS NOT NULL ORDER BY district");
while ($row = $result->fetch_assoc()) {
    $districts[] = $row['district'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - JEMA Water Management</title>
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
                    <h2>Customers Management</h2>
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

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Customers List</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                            <i class="fas fa-plus"></i> Add Customer
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <form method="GET" class="d-flex gap-2">
                                    <input type="text" class="form-control" name="search" placeholder="Search by name, meter, or code" value="<?php echo escape($search); ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="GET" class="d-flex gap-2">
                                    <select class="form-control" name="district" onchange="this.form.submit()">
                                        <option value="">All Districts</option>
                                        <?php foreach ($districts as $d): ?>
                                            <option value="<?php echo escape($d); ?>" <?php echo $district === $d ? 'selected' : ''; ?>>
                                                <?php echo escape($d); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Meter</th>
                                        <th>District</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($customers) > 0): ?>
                                        <?php foreach ($customers as $customer): ?>
                                            <tr>
                                                <td><?php echo escape($customer['customer_code']); ?></td>
                                                <td><?php echo escape($customer['full_name']); ?></td>
                                                <td><?php echo escape($customer['meter_number']); ?></td>
                                                <td><?php echo escape($customer['district']); ?></td>
                                                <td><?php echo escape($customer['phone']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $customer['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($customer['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="editCustomer(<?php echo $customer['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteCustomer(<?php echo $customer['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No customers found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&district=<?php echo urlencode($district); ?>">
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

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">District *</label>
                            <input type="text" class="form-control" name="district" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Area</label>
                            <input type="text" class="form-control" name="area">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meter Number *</label>
                            <input type="text" class="form-control" name="meter_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meter Type</label>
                            <select class="form-control" name="meter_type">
                                <option value="manual">Manual</option>
                                <option value="digital">Digital</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Connection Date</label>
                            <input type="date" class="form-control" name="connection_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="edit_phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" id="edit_address">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">District</label>
                            <input type="text" class="form-control" name="district" id="edit_district" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Area</label>
                            <input type="text" class="form-control" name="area" id="edit_area">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" id="edit_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCustomer(id) {
            fetch('api/get-customer.php?id=' + id)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_full_name').value = data.full_name;
                    document.getElementById('edit_phone').value = data.phone;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_address').value = data.address;
                    document.getElementById('edit_district').value = data.district;
                    document.getElementById('edit_area').value = data.area;
                    document.getElementById('edit_status').value = data.status;
                    new bootstrap.Modal(document.getElementById('editCustomerModal')).show();
                });
        }

        function deleteCustomer(id) {
            if (confirm('Are you sure you want to delete this customer?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
