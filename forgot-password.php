<?php
session_start();
require_once "includes/db.php";
require_once "includes/password_reset.php";

$error = "";
$success = "";
$devResetLink = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        try {
            if ($user) {
                $resetRequest = createPasswordResetRequest($conn, (int) $user['user_id']);
                $devResetLink = buildAppBaseUrl() . "/reset-password.php?token=" . urlencode($resetRequest['token']);
            }

            $success = "If an account with that email exists, a password reset link is ready.";
        } catch (RuntimeException $exception) {
            $error = "Unable to start the reset process right now.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
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
    margin: 20px 0 16px;
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

.success {
    color: #86efac;
}

.dev-link {
    margin-top: 16px;
    padding: 14px;
    border-radius: 10px;
    background: rgba(15,23,42,0.45);
}

.dev-link a,
.back-link {
    color: #93c5fd;
    word-break: break-all;
}
</style>
</head>
<body>
    <div class="auth-card">
        <h1>Reset your password</h1>
        <p>Enter your account email and we'll prepare a secure password reset link.</p>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <input type="email" name="email" required placeholder="Email Address">
            </div>

            <button type="submit" class="btn">Send Reset Link</button>
        </form>

        <?php if ($devResetLink && isLocalDevelopmentHost()): ?>
            <div class="dev-link">
                <strong>Local development link:</strong><br>
                <a href="<?= htmlspecialchars($devResetLink) ?>"><?= htmlspecialchars($devResetLink) ?></a>
            </div>
        <?php endif; ?>

        <p><a class="back-link" href="login.php">Back to login</a></p>
    </div>
</body>
</html>
