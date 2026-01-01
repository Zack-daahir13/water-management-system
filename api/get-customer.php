<?php
require_once '../config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($customer);
