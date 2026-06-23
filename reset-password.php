<?php
session_start();
require_once "includes/db.php";
require_once "includes/password_reset.php";

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = "";
$resetRequest = null;

if ($token !== '') {
    $resetRequest = findPasswordResetRequest($conn, $token);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$resetRequest) {
        $error = "This reset link is invalid or has expired.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update->bind_param("si", $hashedPassword, $resetRequest['user_id']);

        if ($update->execute()) {
            deletePasswordResetRequestsForUser($conn, (int) $resetRequest['user_id']);
            header("Location: login.php?reset=1");
            exit;
        }

        $error = "Unable to update your password right now.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Set New Password</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #4c1d95, #1e1b4b);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
}

.auth-card {
    width: 100%;
    max-width: 460px;
    padding: 36px;
    border-radius: 16px;
    backdrop-filter: blur(20px);
    background: rgba(255,255,255,0.06);
    box-shadow: 0 20px 50px rgba(0,0,0,0.35);
    box-sizing: border-box;
}

h1 {
    margin-top: 0;
    margin-bottom: 8px;
}

p {
    color: #cbd5f5;
    line-height: 1.5;
}

.input-group {
    margin: 16px 0;
}

.input-group input {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: none;
    background: rgba(255,255,255,0.08);
    color: white;
    box-sizing: border-box;
}

.btn {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(90deg, #9333ea, #3b82f6);
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.error {
    color: #fca5a5;
}

.back-link {
    color: #93c5fd;
}
</style>
</head>
<body>
    <div class="auth-card">
        <h1>Create a new password</h1>

        <?php if (!$resetRequest): ?>
            <p class="error">This reset link is invalid or has expired.</p>
            <p><a class="back-link" href="forgot-password.php">Request a new reset link</a></p>
        <?php else: ?>
            <p>Choose a new password for <?= htmlspecialchars($resetRequest['email']) ?>.</p>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="input-group">
                    <input type="password" name="password" required placeholder="New Password">
                </div>

                <div class="input-group">
                    <input type="password" name="confirm_password" required placeholder="Confirm New Password">
                </div>

                <button type="submit" class="btn">Update Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
