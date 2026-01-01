<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        require_once 'auth.php';
        $username = $db->real_escape_string($_POST['username']);
        $email = $db->real_escape_string($_POST['email']);
        $password = $_POST['password'];
        $full_name = $db->real_escape_string($_POST['full_name']);
        $role = $db->real_escape_string($_POST['role']);
        $phone = $db->real_escape_string($_POST['phone'] ?? '');

        $result = $auth->register($username, $email, $password, $full_name, $role);
        if ($result['success']) {
            $message = $result['message'];
            $message_type = 'success';
        } else {
            $message = $result['message'];
            $message_type = 'danger';
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $full_name = $db->real_escape_string($_POST['full_name']);
        $email = $db->real_escape_string($_POST['email']);
        $phone = $db->real_escape_string($_POST['phone']);
        $role = $db->real_escape_string($_POST['role']);
        $status = $db->real_escape_string($_POST['status']);

        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $full_name, $email, $phone, $role, $status, $id);

        if ($stmt->execute()) {
            $message = 'User updated successfully';
            $message_type = 'success';
            logAction('UPDATE', 'users', $id);
        } else {
            $message = 'Error updating user';
            $message_type = 'danger';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = 'User deactivated';
            $message_type = 'success';
            logAction('UPDATE', 'users', $id, ['action' => 'deactivated']);
        }
    } elseif ($action === 'reset_password') {
        $id = (int)$_POST['id'];
        $new_password = password_hash('password123', PASSWORD_BCRYPT);

        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $id);

        if ($stmt->execute()) {
            $message = 'Password reset to "password123"';
            $message_type = 'success';
            logAction('UPDATE', 'users', $id, ['action' => 'password_reset']);
        }
    }
}

// Get users
$page = (int)($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? 'active';

$query = "SELECT * FROM users";
$where = [];

if (!empty($role_filter)) {
    $where[] = "role = '$role_filter'";
}

if (!empty($status_filter)) {
    $where[] = "status = '$status_filter'";
}

if (count($where) > 0) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$count_result = $db->query("SELECT COUNT(*) as total FROM users" . (count($where) > 0 ? " WHERE " . implode(" AND ", $where) : ''));
$total = $count_result->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

$query .= " ORDER BY created_at DESC LIMIT $offset, $per_page";

$users = [];
$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Get stats
$adminCount = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$staffCount = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff'")->fetch_assoc()['count'];
$activeCount = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$totalUserCount = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - JEMA Water Management</title>
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
                    <h2>Users Management</h2>
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
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Total Users</p>
                                <h3 class="stat-value"><?php echo $totalUserCount; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Admins</p>
                                <h3 class="stat-value"><?php echo $adminCount; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Staff</p>
                                <h3 class="stat-value"><?php echo $staffCount; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card info">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-info">
                                <p class="stat-label">Active</p>
                                <h3 class="stat-value"><?php echo $activeCount; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add User -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Users List</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <a href="?role=admin&status=active" class="btn btn-sm btn-outline-primary">Admin</a>
                                <a href="?role=staff&status=active" class="btn btn-sm btn-outline-primary">Staff</a>
                                <a href="users.php" class="btn btn-sm btn-outline-secondary">All</a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo escape($user['username']); ?></td>
                                            <td><?php echo escape($user['full_name']); ?></td>
                                            <td><?php echo escape($user['email']); ?></td>
                                            <td><?php echo escape($user['phone']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $user['id']; ?>)" data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="reset_password">
                                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-info" onclick="return confirm('Reset password to password123?')">
                                                            <i class="fas fa-refresh"></i>
                                                        </button>
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
                                            <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>">
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-control" name="role" required>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="edit_phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role" id="edit_role" required>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" id="edit_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(id) {
            fetch('api/get-user.php?id=' + id)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_full_name').value = data.full_name;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_phone').value = data.phone;
                    document.getElementById('edit_role').value = data.role;
                    document.getElementById('edit_status').value = data.status;
                });
        }

        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
