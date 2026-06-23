<?php
session_start();
require_once __DIR__ . "/../../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("login");
}

$user_id = $_SESSION['user_id'];

$content = trim($_POST['content']);
$vehicle_id = (int)$_POST['vehicle_id'];
$type = $_POST['type'];
$parent_id = $_POST['parent_id'] ?? null;

if (!$content || !$vehicle_id || !$type) {
    die("error");
}

$stmt = $conn->prepare("
INSERT INTO reviews (user_id, vehicle_id, type, content, parent_id)
VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param("iissi", $user_id, $vehicle_id, $type, $content, $parent_id);
$stmt->execute();

echo "success";