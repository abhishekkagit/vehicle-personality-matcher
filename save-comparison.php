<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once 'includes/db.php';
function detectType($conn, $id){
    $stmt = $conn->prepare("SELECT id FROM bikes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if($stmt->get_result()->num_rows > 0){
        return 'bike';
    }

    return 'car';
}

// 🔥 override type from DB
$type = detectType($conn, $v1);
/* ===============================
   GET DATA
================================ */

$user_id = $_SESSION['user_id'] ?? null;

$v1 = isset($_GET['v1']) ? (int)$_GET['v1'] : null;
$v2 = isset($_GET['v2']) && $_GET['v2'] !== '' ? (int)$_GET['v2'] : null;
$v3 = isset($_GET['v3']) && $_GET['v3'] !== '' ? (int)$_GET['v3'] : null;

// 🔥 DETECT TYPE FROM DATABASE



// detect based on first vehicle
$type = detectType($conn, $v1);

/* ===============================
   BASIC VALIDATION
================================ */

if(!$user_id || !$v1 || !$type){
    die("Missing required data");
}

if($type !== 'car' && $type !== 'bike'){
    die("Invalid type");
}

if(!$v2 && !$v3){
    die("Need at least 2 vehicles");
}

/* ===============================
   VALIDATE VEHICLES
================================ */

function vehicleExists($conn, $id, $type){
    if($type === 'bike'){
        $stmt = $conn->prepare("SELECT id FROM bikes WHERE id=?");
    } else {
        $stmt = $conn->prepare("SELECT id FROM vehicle WHERE id=?");
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/* STRICT VALIDATION */

if(!vehicleExists($conn, $v1, $type)){
    die("Vehicle1 invalid");
}

if($v2 && !vehicleExists($conn, $v2, $type)){
    $v2 = null;
}

if($v3 && !vehicleExists($conn, $v3, $type)){
    $v3 = null;
}
// 🔥 PREVENT MIXING CAR & BIKE

$type = detectType($conn, $v1); // always detect from DB

if($v2 && detectType($conn, $v2) !== $type){
    die("Cannot mix car and bike");
}

if($v3 && detectType($conn, $v3) !== $type){
    die("Cannot mix car and bike");
}
/* ===============================
   INSERT (NO DUPLICATE CHECK FOR NOW)
================================ */

/* 🔥 TEMP: remove duplicate check to ensure insert works */

$stmt = $conn->prepare("
    INSERT INTO comparisons (user_id, vehicle1_id, vehicle2_id, vehicle3_id, type)
    VALUES (?, ?, ?, ?, ?)
");

if(!$stmt){
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("iiiis", $user_id, $v1, $v2, $v3, $type);

if(!$stmt->execute()){
    die("Insert failed: " . $stmt->error);
}

/* ===============================
   REDIRECT BACK
================================ */

if($type === 'bike'){
    $url = "/vehicle-personality-matcher/compare_bikes.php?v1=$v1&type=bike";
}else{
    $url = "/vehicle-personality-matcher/compare.php?v1=$v1&type=car";
}

if($v2) $url .= "&v2=$v2";
if($v3) $url .= "&v3=$v3";

$url .= "&saved=1";

header("Location: $url");
exit;