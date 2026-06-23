<?php
session_start();
require_once __DIR__ . "/../../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../profile.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');

/* VALIDATION */
if (empty($name) || empty($username)) {
    $_SESSION['error'] = "All fields are required";
    header("Location: ../profile.php");
    exit;
}

/* USERNAME FORMAT (optional but good) */
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    $_SESSION['error'] = "Username must be 3–20 chars (letters, numbers, underscore)";
    header("Location: ../profile.php");
    exit;
}

/* CHECK USERNAME UNIQUE */
$check = $conn->prepare("SELECT user_id FROM users WHERE username=? AND user_id!=?");
$check->bind_param("si", $username, $user_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $_SESSION['error'] = "Username already taken";
    header("Location: ../profile.php");
    exit;
}

/* HANDLE PROFILE PIC */
$profilePicPath = null;

if (!empty($_FILES['profile_pic']['name'])) {

    $targetDir = "../../uploads/profile/";
    $fileName = time() . "_" . basename($_FILES["profile_pic"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {
        $profilePicPath = "uploads/profile/" . $fileName;
    }
}

/* UPDATE */
if ($profilePicPath) {

    $stmt = $conn->prepare("
        UPDATE users 
        SET name=?, username=?, profile_pic=? 
        WHERE user_id=?
    ");
    $stmt->bind_param("sssi", $name, $username, $profilePicPath, $user_id);

} else {

    $stmt = $conn->prepare("
        UPDATE users 
        SET name=?, username=? 
        WHERE user_id=?
    ");
    $stmt->bind_param("ssi", $name, $username, $user_id);
}

$stmt->execute();

/* UPDATE SESSION */
$_SESSION['name'] = $name;

/* SUCCESS MESSAGE */
$_SESSION['success'] = "Profile updated successfully";

/* REDIRECT */
header("Location: ../profile.php");
exit;