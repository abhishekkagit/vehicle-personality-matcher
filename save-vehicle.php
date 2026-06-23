<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    echo "login";
    exit;
}

$user_id = $_SESSION['user_id'];
$vehicle_id = $_POST['vehicle_id'] ?? 0;
$type = $_POST['type'] ?? 'car';

// check if exists
$check = $conn->prepare("
    SELECT id FROM saved_vehicles 
    WHERE user_id=? AND vehicle_id=? AND type=?
");
$check->bind_param("iis", $user_id, $vehicle_id, $type);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {

    $del = $conn->prepare("
        DELETE FROM saved_vehicles 
        WHERE user_id=? AND vehicle_id=? AND type=?
    ");
    $del->bind_param("iis", $user_id, $vehicle_id, $type);
    $del->execute();

    echo "removed";

} else {

    $ins = $conn->prepare("
        INSERT INTO saved_vehicles (user_id, vehicle_id, type)
        VALUES (?, ?, ?)
    ");
    $ins->bind_param("iis", $user_id, $vehicle_id, $type);
    $ins->execute();

    echo "added";
}