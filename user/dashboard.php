<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];



$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

// 🔥 TOTAL QUIZZES
$q1 = $conn->prepare("SELECT COUNT(*) as total FROM quiz_results WHERE user_id=?");
$q1->bind_param("i", $user_id);
$q1->execute();
$total_quizzes = $q1->get_result()->fetch_assoc()['total'] ?? 0;

// 🔥 SAVED VEHICLES
$q2 = $conn->prepare("SELECT COUNT(*) as total FROM saved_vehicles WHERE user_id=?");
$q2->bind_param("i", $user_id);
$q2->execute();
$saved = $q2->get_result()->fetch_assoc()['total'] ?? 0;

// 🔥 COMPARISONS


// 🔥 AVG MATCH SCORE
$q4 = $conn->prepare("SELECT AVG(match_score) as avg_score FROM quiz_results WHERE user_id=?");
$q4->bind_param("i", $user_id);
$q4->execute();
$avg_score = round($q4->get_result()->fetch_assoc()['avg_score'] ?? 0);

// 🔥 RECENT RESULTS
$q5 = $conn->prepare("
    SELECT vehicle_name, match_score 
    FROM quiz_results 
    WHERE user_id=? 
    ORDER BY created_at DESC 
    LIMIT 3
");
$q5->bind_param("i", $user_id);
$q5->execute();
$recent = $q5->get_result();
?>

<?php include "includes/sidebar.php"; ?>
<?php include "includes/topbar.php"; ?>

<div class="main-content">

<h1>Welcome back, <?= htmlspecialchars($name) ?> 👋</h1>
<p class="subtitle">Here’s what’s happening with your vehicle matches</p>

<div class="stats-grid">

    <div class="card">
        <h3>Total Quizzes</h3>
        <h2><?= $total_quizzes ?></h2>
    </div>

    <div class="card">
        <h3>Saved Vehicles</h3>
        <h2><?= $saved ?></h2>
    </div>

  

</div>

<div class="dashboard-grid">

    <!-- RECENT RESULTS -->
    <div class="card large">
        <h3>Recent Quiz Results</h3>

        <?php while($row = $recent->fetch_assoc()): ?>
            <div class="list-item">
                <?= htmlspecialchars($row['vehicle_name']) ?> - <?= $row['match_score'] ?>%
            </div>
        <?php endwhile; ?>

        <?php if ($recent->num_rows == 0): ?>
            <p>No quiz results yet</p>
        <?php endif; ?>

    </div>

    <!-- QUICK ACTIONS -->
    <div class="card large">
        <h3>Quick Actions</h3>

      
        
       <a href="../vehicles.php?type=car" class="btn-secondary">Browse Cars</a>
       <a href="../vehicles.php?type=bike" class="btn-secondary">Browse bikes</a>

    </div>

</div>

</div>
<?php include "includes/footer.php"; ?>