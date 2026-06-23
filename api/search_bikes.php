<?php
header('Content-Type: application/json');

include '../includes/db.php';
include '../includes/bike_cache.php';

$brand = trim($_GET['brand'] ?? '');
$model = trim($_GET['model'] ?? '');

if (!$brand || !$model) {
    echo json_encode(["success" => false, "error" => "Missing brand or model"]);
    exit;
}

// AUTO CACHE
cacheBikeIfNotExists($conn, $brand, $model);

// ALWAYS READ FROM DB
$stmt = $conn->prepare(
    "SELECT * FROM bikes WHERE brand = ? AND model = ? LIMIT 1"
);
$stmt->bind_param("ss", $brand, $model);
$stmt->execute();
$bike = $stmt->get_result()->fetch_assoc();

if (!$bike) {
    echo json_encode(["success" => false, "error" => "Bike not found"]);
    exit;
}

// IMAGES
$imgStmt = $conn->prepare(
    "SELECT image_url FROM bike_images WHERE bike_id = ?"
);
$imgStmt->bind_param("i", $bike["id"]);
$imgStmt->execute();

$images = [];
$res = $imgStmt->get_result();
while ($row = $res->fetch_assoc()) {
    $images[] = $row["image_url"];
}

$bike["images"] = $images;
$bike["extra_specs"] = json_decode($bike["extra_specs"], true);

echo json_encode([
    "success" => true,
    "data" => $bike
]);
