<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT id, username, email, full_name, phone, role, status FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($user);
