<?php 
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/header.php';

/* =========================
   STAT COUNTS
========================= */

$carsCount = $conn->query("SELECT COUNT(*) as total FROM vehicle")
    ->fetch_assoc()['total'] ?? 0;

$bikesCount = $conn->query("SELECT COUNT(*) as total FROM bikes")
    ->fetch_assoc()['total'] ?? 0;

$usersCount = $conn->query("SELECT COUNT(*) as total FROM users")
    ->fetch_assoc()['total'] ?? 0;




/* =========================
   TOP MATCHED VEHICLES
========================= */

$topMatchesQuery = "
    SELECT 
        r.item_id,
        r.item_type,
        COUNT(*) as total_matches,
        AVG(r.compatibility_score) as avg_score
    FROM results r
    WHERE r.created_at >= NOW() - INTERVAL 30 DAY
    GROUP BY r.item_id, r.item_type
    ORDER BY total_matches DESC
    LIMIT 10
";

$topMatchesResult = $conn->query($topMatchesQuery);
?>

<div class="admin-wrapper">

    <?php require 'includes/sidebar.php'; ?>

    <div class="main-area">

        <?php 
        $pageTitle = "Dashboard";
        require 'includes/topbar.php'; 
        ?>

        <div class="content-area">

            <!-- =========================
                 STAT CARDS
            ========================== -->

            <div class="stats-grid">

                <div class="stat-card blue">
                    <div class="stat-info">
                        <p>Total Cars</p>
                        <h2><?php echo number_format($carsCount); ?></h2>
                    </div>
                    <div class="stat-icon">🚗</div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-info">
                        <p>Total Bikes</p>
                        <h2><?php echo number_format($bikesCount); ?></h2>
                    </div>
                    <div class="stat-icon">🏍️</div>
                </div>

                <div class="stat-card green">
                    <div class="stat-info">
                        <p>Total Users</p>
                        <h2><?php echo number_format($usersCount); ?></h2>
                    </div>
                    <div class="stat-icon">👤</div>
                </div>

              
                </div>

            </div>

<!-- QUICK ACTIONS -->
    <div class="card large">
        <h3>Quick Actions</h3>

      
        
       <a href="../index.php" class="btn-secondary">home</a>
       

    </div>


<?php require 'includes/footer.php'; ?>
