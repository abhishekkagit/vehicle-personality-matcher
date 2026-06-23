<?php
require_once "includes/db.php";
session_start();

$error = "";

if (isset($_SESSION['google_oauth_error'])) {
    $error = $_SESSION['google_oauth_error'];
    unset($_SESSION['google_oauth_error']);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    // Gmail validation
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@gmail\.com$/", $email)) {
        $error = "Only Gmail addresses allowed.";
    }

    elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    }

    elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    }

    else {

        // Check duplicates
        $check = $conn->prepare("SELECT user_id FROM users WHERE email=? OR username=?");
        $check->bind_param("ss", $email, $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email or Username already taken.";
        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $defaultProfilePic = "assets/images/default.jpg";

            $stmt = $conn->prepare(
                "INSERT INTO users (name, username, email, password, role, is_verified, profile_pic) VALUES (?, ?, ?, ?, 'user', 0, ?)"
            );
            $stmt->bind_param("sssss", $name, $username, $email, $hashed, $defaultProfilePic);
            $stmt->execute();

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>

body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #4c1d95, #1e1b4b);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.auth-container {
    width: 100%;
    max-width: 900px;
}

.auth-card {
    display: flex;
    border-radius: 16px;
    overflow: hidden;
    backdrop-filter: blur(20px);
    background: rgba(255,255,255,0.05);
    box-shadow: 0 20px 50px rgba(0,0,0,0.4);
}

/* LEFT */
.auth-left {
    flex: 1;
    padding: 40px;
    color: white;
}

.auth-left h2 {
    margin-bottom: 5px;
}

.subtitle {
    font-size: 14px;
    color: #cbd5f5;
    margin-bottom: 25px;
}

.input-group {
    margin-bottom: 15px;
}

.input-group input {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: none;
    outline: none;
    background: rgba(255,255,255,0.08);
    color: white;
}

.btn-primary {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(90deg, #3b82f6, #9333ea);
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.btn-primary:hover {
    opacity: 0.9;
}

.divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 18px 0;
    color: #cbd5f5;
    font-size: 13px;
}

.divider::before,
.divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.15);
}

.google-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    background: #ffffff;
    color: #111827;
    font-weight: 600;
    text-decoration: none;
    box-sizing: border-box;
}

.google-icon {
    font-size: 18px;
    line-height: 1;
}

.bottom-text {
    margin-top: 20px;
    font-size: 14px;
}

.bottom-text a {
    color: #60a5fa;
    text-decoration: none;
}

.error {
    color: #f87171;
    margin-bottom: 10px;
}

/* RIGHT */
.auth-right {
    flex: 1;
    padding: 40px;
    background: linear-gradient(135deg, #6366f1, #9333ea);
    color: white;
}

.auth-right ul {
    padding-left: 20px;
}

.auth-right img {
    width: 100%;
    border-radius: 12px;
    margin-top: 15px;
}

</style>
</head>

<body>

<div class="auth-container">
  <div class="auth-card">

    <!-- LEFT -->
    <div class="auth-left">
      <h2>Create Account</h2>
      <p class="subtitle">Start your journey to finding the perfect vehicle</p>

      <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
      <?php endif; ?>

      <form method="POST">

        <div class="input-group">
          <input type="text" name="name" required placeholder="Full Name">
        </div>

        <div class="input-group">
          <input type="text" name="username" required placeholder="Username">
        </div>

        <div class="input-group">
          <input type="email" name="email" required placeholder="Email Address">
        </div>

        <div class="input-group">
          <input type="password" name="password" required placeholder="Password">
        </div>

        <div class="input-group">
          <input type="password" name="confirm_password" required placeholder="Confirm Password">
        </div>

        <button type="submit" class="btn-primary">
          Create Account
        </button>

      </form>

      <div class="divider">or</div>

      <a class="google-btn" href="auth/google/login.php">
        <span class="google-icon">G</span>
        Continue with Google
      </a>

      <p class="bottom-text">
        Already have an account?
        <a href="login.php">Login</a>
      </p>
    </div>

    <!-- RIGHT -->
    <div class="auth-right">
      <h3>🚗 Vehicle Personality</h3>
      <h2>Join Our Community</h2>

      <p>
        Create your account and start journey
      </p>

  
      <img src="assets\images\car_bike.png" alt="cars">
    </div>

  </div>
</div>

</body>
</html>
