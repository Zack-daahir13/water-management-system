<?php
require_once 'config.php';
requireLogin();

$message = '';
$message_type = '';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name = $db->real_escape_string($_POST['full_name']);
        $email = $db->real_escape_string($_POST['email']);
        $phone = $db->real_escape_string($_POST['phone'] ?? '');

        $result = $auth->updateProfile($_SESSION['user_id'], $full_name, $email, $phone);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'danger';
    } elseif ($action === 'change_password') {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $message = 'New passwords do not match';
            $message_type = 'danger';
        } else if (strlen($new_password) < 6) {
            $message = 'Password must be at least 6 characters';
            $message_type = 'danger';
        } else {
            $result = $auth->changePassword($_SESSION['user_id'], $old_password, $new_password);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'danger';
        }
    }
}

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - JEMA Water Management</title>
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
                    <h2>My Profile</h2>
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

                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">

                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" value="<?php echo escape($user['username']); ?>" disabled>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" name="full_name" value="<?php echo escape($user['full_name']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo escape($user['email']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone" value="<?php echo escape($user['phone'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst(escape($user['role'])); ?>" disabled>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Member Since</label>
                                        <input type="text" class="form-control" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" disabled>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">

                                    <div class="mb-3">
                                        <label class="form-label">Current Password *</label>
                                        <input type="password" class="form-control" name="old_password" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">New Password *</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                        <small class="form-text text-muted">At least 6 characters</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password *</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>

                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-lock"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="col-lg-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Account Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <p class="text-muted">Account ID</p>
                                        <p><strong><?php echo $user['id']; ?></strong></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="text-muted">Status</p>
                                        <p>
                                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="text-muted">Account Created</p>
                                        <p><strong><?php echo date('F j, Y g:i A', strtotime($user['created_at'])); ?></strong></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="text-muted">Last Updated</p>
                                        <p><strong><?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
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
