<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'auth.php';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        $result = $auth->login($username, $password);
        if ($result['success']) {
            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - JEMA Water Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-icon {
            font-size: 48px;
            color: #1E88E5;
            margin-bottom: 15px;
        }
        .login-container h1 {
            font-size: 28px;
            color: #263238;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .login-container p {
            color: #757575;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            color: #263238;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }
        .form-control {
            border: 2px solid #E0E0E0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #1E88E5;
            box-shadow: 0 0 0 0.2rem rgba(30, 136, 229, 0.25);
        }
        .form-control::placeholder {
            color: #9E9E9E;
        }
        .btn-login {
            background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 136, 229, 0.4);
            color: white;
        }
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #FFEBEE;
            color: #C62828;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #E0E0E0;
            font-size: 12px;
            color: #757575;
        }
        .demo-credentials {
            background: #E8F4F8;
            border: 1px solid #B3E5FC;
            border-radius: 8px;
            padding: 12px;
            margin-top: 20px;
            font-size: 12px;
        }
        .demo-credentials strong {
            color: #1565C0;
        }
        .input-group-text {
            background: transparent;
            border: 2px solid #E0E0E0;
            border-right: none;
        }
        .input-group .form-control {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo-icon">
                <i class="fas fa-droplet"></i>
            </div>
            <h1>JEMA</h1>
            <p>Water Management System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo escape($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="demo-credentials">
            <strong>Demo Credentials:</strong><br>
            <strong>Username:</strong> admin<br>
            <strong>Password:</strong> admin123
        </div>

        <div class="login-footer">
            <p>&copy; 2024 JEMA Water Management System. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
