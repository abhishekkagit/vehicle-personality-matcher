<?php
require 'includes/db.php';
require 'includes/password_reset.php';
$user = $conn->query("SELECT user_id, email FROM users LIMIT 1")->fetch_assoc();
if (!$user) {
    echo "no-users\n";
    exit;
}
$result = createPasswordResetRequest($conn, (int) $user['user_id']);
$found = findPasswordResetRequest($conn, $result['token']);
echo $found ? "found\n" : "not-found\n";
if ($found) {
    echo $found['email'] . "\n";
    echo $found['expires_at'] . "\n";
}
deletePasswordResetRequestsForUser($conn, (int) $user['user_id']);
?>
