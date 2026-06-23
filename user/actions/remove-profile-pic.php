<?php
session_start();
require_once __DIR__ . "/../../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../profile.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* GET CURRENT IMAGE */
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$currentPic = $user['profile_pic'];

/* DELETE FILE IF NOT DEFAULT */
if ($currentPic && $currentPic !== "assets/images/default.jpg") {

    $filePath = "../../" . $currentPic;

    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

/* RESET TO DEFAULT */
$default = "assets/images/default.jpg";

$update = $conn->prepare("UPDATE users SET profile_pic=? WHERE user_id=?");
$update->bind_param("si", $default, $user_id);
$update->execute();

/* SUCCESS */
$_SESSION['success'] = "Profile picture removed";
header("Location: ../profile.php");
exit;