<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
?>
<?php
require_once 'includes/db.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$type = $_GET['type'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Vehicle Personality Matcher</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/home.css">
  <!-- AOS -->
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

<!-- GSAP -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

<!-- Typed.js -->
<script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>

<!-- Particles -->
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
</head>
<body>

<nav class="navbar">
  <div class="nav-container">
    
    <div class="nav-left">
      <span class="logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg"
             width="20" height="20"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="2"
             stroke-linecap="round"
             stroke-linejoin="round">
          <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
          <circle cx="7" cy="17" r="2"/>
          <path d="M9 17h6"/>
          <circle cx="17" cy="17" r="2"/>
        </svg>
      </span>

      <span class="logo-text">Vehicle Personality Matcher</span>
    </div>

    <div class="nav-right">
      <a href="index.php"
         class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
         Home
      </a>

      <a href="vehicles.php?type=car"
         class="nav-link <?= ($currentPage === 'vehicles.php' && $type === 'car') ? 'active' : '' ?>">
         Cars
      </a>

      <a href="vehicles.php?type=bike"
         class="nav-link <?= ($currentPage === 'vehicles.php' && $type === 'bike') ? 'active' : '' ?>">
         Bikes
      </a>

<?php if(isset($_SESSION['user_id'])): ?>

<?php
$uid = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT profile_pic, role FROM users WHERE user_id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$profilePic = $user['profile_pic'] ?? '/vehicle-personality-matcher/assets/images/default.jpg';

$dashboardLink = ($user['role'] === 'admin') 
    ? '/vehicle-personality-matcher/admin/dashboard.php' 
    : '/vehicle-personality-matcher/user/dashboard.php';
?>

<a href="<?= $dashboardLink ?>" class="profile-btn">
    <img src="/vehicle-personality-matcher/<?= $profilePic ?>" class="profile-avatar" alt="Profile">
</a>

<?php else: ?>

<a href="login.php"
   class="nav-link login-btn <?= $currentPage === 'login.php' ? 'active' : '' ?>">
   Login
</a>

<?php endif; ?>
    </div>

  </div>
</nav>
