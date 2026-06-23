<?php
session_start();
require_once __DIR__ . "/../../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../profile.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* CHECK FILE */
if (empty($_FILES['profile_pic']['name'])) {
    $_SESSION['error'] = "No file selected";
    header("Location: ../profile.php");
    exit;
}

/* VALIDATION */
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

if (!in_array($_FILES['profile_pic']['type'], $allowedTypes)) {
    $_SESSION['error'] = "Only JPG, PNG, WEBP allowed";
    header("Location: ../profile.php");
    exit;
}

if ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
    $_SESSION['error'] = "Max size is 2MB";
    header("Location: ../profile.php");
    exit;
}

/* UPLOAD */
$targetDir = "../../uploads/profile/";

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$fileName = "user_" . $user_id . "_" . time() . ".jpg";
$targetFile = $targetDir . $fileName;

if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
    $_SESSION['error'] = "Upload failed";
    header("Location: ../profile.php");
    exit;
}

/* SAVE PATH (IMPORTANT: RELATIVE PATH FOR WEBSITE) */
$dbPath = "uploads/profile/" . $fileName;

$stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE user_id=?");
$stmt->bind_param("si", $dbPath, $user_id);
$stmt->execute();

/* SUCCESS */
$_SESSION['success'] = "Profile picture updated";
header("Location: ../profile.php");
exit;