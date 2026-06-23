<?php
session_start();
require_once '../includes/db.php';
 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* FETCH USER */
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$profilePic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'assets/images/default.jpg';

/* STATS */
$quizCount = $conn->query("SELECT COUNT(*) as total FROM quiz_results WHERE user_id=$user_id")->fetch_assoc()['total'] ?? 0;
$savedCount = $conn->query("SELECT COUNT(*) as total FROM saved_vehicles WHERE user_id=$user_id")->fetch_assoc()['total'] ?? 0;
$compareCount = 0; // you can implement later
$matchScore = 94; // placeholder for now
?>
<?php include "includes/sidebar.php"; ?>
<?php include "includes/topbar.php"; ?>
<!DOCTYPE html>
<html>
<head>
<title>Profile</title>

<style>

/* ===== GLOBAL ===== */
body {
    margin:0;
    font-family: 'Inter', sans-serif;
    background: radial-gradient(circle at top, #0f172a, #020617);
    color: white;
}

/* ===== LAYOUT ===== */
.container {
    padding: 30px;
}

/* ===== PROFILE HEADER ===== */
.profile-card {
    background: linear-gradient(135deg, #1e293b, #020617);
    padding: 25px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
}

/* AVATAR */
.profile-avatar {
    width: 90px;
    height: 90px;
    border-radius: 16px;
    object-fit: cover;
    border: 2px solid #6366f1;
    cursor: pointer;
}

/* INFO */
.profile-info h2 {
    margin: 0;
}

.profile-info p {
    margin: 4px 0;
    color: #9ca3af;
}
.profile-info{
    margin-bottom: 45px;
    margin-right: 1000px;
}
/* STATS */
.stats {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 15px;
    margin-top: 20px;
}

.stat-box {
    background: #0f172a;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
}

.stat-box h3 {
    margin:0;
}

/* ===== FORMS ===== */
.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 30px;
}

.card {
    background: #0f172a;
    padding: 20px;
    border-radius: 15px;
}

input {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    background: #020617;
    border: 1px solid #334155;
    border-radius: 8px;
    color: white;
}

/* BUTTON */
.btn {
    margin-top: 15px;
    padding: 12px;
    width: 100%;
    border-radius: 10px;
    border: none;
    background: linear-gradient(90deg, #7c3aed, #6366f1);
    color: white;
    cursor: pointer;
}
.error-msg {
    background: #ffe5e5;
    color: #ff4d4d;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.success-msg {
    background: #e6ffed;
    color: #00c853;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.profile-avatar:hover {
    transform: scale(1.05);
    border-color: #7c3aed;
}
.btn-danger {
    background: #ef4444;
}

</style>
</head>

<body>

<div class="main-content">
<?php if (isset($_SESSION['error'])): ?>
    <div class="error-msg">
        <?= $_SESSION['error'] ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="success-msg">
        <?= $_SESSION['success'] ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<!-- ===== PROFILE HEADER ===== -->
<div class="profile-card">

<!-- UPLOAD FORM -->
 <div class="profile-left">
    <div class="profile-actions">
<form action="actions/upload-profile.php" method="POST" enctype="multipart/form-data">
    
    <label for="uploadPic">
<img src="/vehicle-personality-matcher/<?= htmlspecialchars($profilePic) ?>" class="profile-avatar">
    </label>

    <input type="file" name="profile_pic" id="uploadPic" hidden onchange="this.form.submit()">

</form>

<!-- REMOVE BUTTON (SEPARATE FORM) -->
<form action="/vehicle-personality-matcher/user/actions/remove-profile-pic.php" method="POST">
    <button class="btn btn-danger">Remove Pic</button>
</form>
 </div>
</div>
<div class="profile-info">
    <h2><?= htmlspecialchars($user['name'] ?? 'User') ?></h2>
    <p>@<?= htmlspecialchars($user['username'] ?? 'username') ?></p>
    <p><?= htmlspecialchars($user['email']) ?></p>
</div>

</div>

<!-- ===== STATS ===== -->
<div class="stats">

<div class="stat-box">
    <h3><?= $quizCount ?></h3>
    <p>Quizzes Taken</p>
</div>

<div class="stat-box">
    <h3><?= $savedCount ?></h3>
    <p>Saved Vehicles</p>
</div>


</div>

<!-- ===== FORMS ===== -->
<div class="grid">

<!-- EDIT PROFILE -->
<div class="card">
    <h3>Personal Information</h3>

   <form action="actions/update-profile.php" method="POST" enctype="multipart/form-data">

        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" placeholder="Full Name">

        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" placeholder="Username">

        <button class="btn">Save Changes</button>

    </form>
</div>

<!-- CHANGE PASSWORD -->
<div class="card">
    <h3>Change Password</h3>

    <form action="actions/change-password.php" method="POST">

<div class="password-field">
    <input type="password" name="current_password" placeholder="Current Password" required>
    <span class="toggle-eye">👁</span>
</div>

<div class="password-field">
    <input type="password" name="new_password" placeholder="New Password" required>
    <span class="toggle-eye">👁</span>
</div>

<div class="password-field">
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <span class="toggle-eye">👁</span>
</div>

        <button class="btn">Update Password</button>

    </form>
</div>

</div>

</div>
<script>
document.querySelectorAll(".toggle-eye").forEach(eye => {

    eye.addEventListener("click", () => {

        const input = eye.previousElementSibling;

        if (input.type === "password") {
            input.type = "text";
            eye.textContent = "🙈";
        } else {
            input.type = "password";
            eye.textContent = "👁";
        }

    });

});
</script>
</body>
</html>
