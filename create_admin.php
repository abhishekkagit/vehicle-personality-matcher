<?php
include 'includes/db.php';

$email = "admin@example.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);

$stmt = $conn->prepare(
  "INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')"
);
$stmt->bind_param("ss", $email, $password);
$stmt->execute();

echo "Admin created";
