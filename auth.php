<?php
/**
 * Authentication Functions
 */

require_once 'config.php';

class Auth {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, username, email, password, full_name, role, status FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Username not found'];
        }

        $user = $result->fetch_assoc();

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is inactive'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid password'];
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        logAction('LOGIN', 'users', $user['id']);

        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
    }

    public function logout() {
        logAction('LOGOUT', 'users', $_SESSION['user_id']);
        session_destroy();
        return ['success' => true, 'message' => 'Logout successful'];
    }

    public function register($username, $email, $password, $full_name, $role = 'staff') {
        // Check if user already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $hashedPassword, $full_name, $role);

        if ($stmt->execute()) {
            logAction('CREATE', 'users', $this->db->insert_id, ['username' => $username, 'role' => $role]);
            return ['success' => true, 'message' => 'User registered successfully'];
        } else {
            return ['success' => false, 'message' => 'Registration failed: ' . $stmt->error];
        }
    }

    public function updateProfile($user_id, $full_name, $email, $phone) {
        $stmt = $this->db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);

        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            logAction('UPDATE', 'users', $user_id, ['full_name' => $full_name, 'email' => $email]);
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Update failed'];
        }
    }

    public function changePassword($user_id, $old_password, $new_password) {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!password_verify($old_password, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $user_id);

        if ($stmt->execute()) {
            logAction('UPDATE', 'users', $user_id, ['action' => 'password_changed']);
            return ['success' => true, 'message' => 'Password changed successfully'];
        } else {
            return ['success' => false, 'message' => 'Password change failed'];
        }
    }

    public function resetPassword($user_id, $new_password) {
        $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $user_id);

        if ($stmt->execute()) {
            logAction('UPDATE', 'users', $user_id, ['action' => 'password_reset']);
            return ['success' => true, 'message' => 'Password reset successfully'];
        } else {
            return ['success' => false, 'message' => 'Password reset failed'];
        }
    }

    public function verifySession() {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT id, status FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0 || $result->fetch_assoc()['status'] !== 'active') {
            session_destroy();
            return false;
        }

        return true;
    }
}

$auth = new Auth($db);
