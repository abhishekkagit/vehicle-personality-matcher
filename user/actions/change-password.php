<?php
session_start();
require_once __DIR__ . "/../../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../profile.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

/* VALIDATION */
if (empty($current) || empty($new) || empty($confirm)) {
    $_SESSION['error'] = "All fields are required";
    header("Location: ../profile.php");
    exit;
}

/* MATCH CHECK */
if ($new !== $confirm) {
    $_SESSION['error'] = "Passwords do not match";
    header("Location: ../profile.php");
    exit;
}

/* STRONG PASSWORD */
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $new)) {
    $_SESSION['error'] = "Password must be 8+ chars with uppercase, lowercase, number, symbol";
    header("Location: ../profile.php");
    exit;
}

/* GET CURRENT PASSWORD */
$stmt = $conn->prepare("SELECT password FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* VERIFY */
if (!$user || !password_verify($current, $user['password'])) {
    $_SESSION['error'] = "Wrong current password";
    header("Location: ../profile.php");
    exit;
}

/* UPDATE */
$hashed = password_hash($new, PASSWORD_DEFAULT);

$update = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
$update->bind_param("si", $hashed, $user_id);
$update->execute();

/* SUCCESS */
$_SESSION['success'] = "Password updated successfully";
header("Location: ../profile.php");
exit;