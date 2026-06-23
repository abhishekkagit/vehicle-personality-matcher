<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">

    <div class="sidebar-logo">
        🚗 <span>Vehicle Matcher</span>
    </div>

    <ul class="sidebar-menu">

        <li class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php">
                <span>🏠</span> Dashboard
            </a>
        </li>

        <li class="<?= $current == 'profile.php' ? 'active' : '' ?>">
            <a href="profile.php">
                <span>👤</span> My Profile
            </a>
        </li>

        <li class="<?= $current == 'quiz-results.php' ? 'active' : '' ?>">
            <a href="quiz-results.php">
                <span>🧠</span> Quiz Results
            </a>
        </li>

        <li class="<?= $current == 'saved-vehicles.php' ? 'active' : '' ?>">
            <a href="saved-vehicles.php">
                <span>🚗</span> Saved Vehicles
            </a>
        </li>
<!-- 
        <li class="/*<?//= $current == 'settings.php' ? 'active' : '' ?>">*/
            <a href="settings.php">
                <span>⚙️</span> Settings
            </a>
        </li>
comment -->
    </ul>

    <div class="sidebar-bottom">
      <a href="/vehicle-personality-matcher/user/actions/logout.php" class="logout-btn">
    🚪 Logout
</a>
    </div>

</div>