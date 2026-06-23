<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

$user_id = $_SESSION['user_id'];

/* FETCH COMPARISONS */
$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.created_at,

        v1.id AS v1_id, v1.make AS v1_make, v1.model AS v1_model,
        vi1.image_path AS v1_image,

        v2.id AS v2_id, v2.make AS v2_make, v2.model AS v2_model,
        vi2.image_path AS v2_image,

        v3.id AS v3_id, v3.make AS v3_make, v3.model AS v3_model,
        vi3.image_path AS v3_image

    FROM comparisons c

    LEFT JOIN vehicle v1 ON c.vehicle1_id = v1.id
    LEFT JOIN vehicle_images vi1 ON vi1.vehicle_id = v1.id

    LEFT JOIN vehicle v2 ON c.vehicle2_id = v2.id
    LEFT JOIN vehicle_images vi2 ON vi2.vehicle_id = v2.id

    LEFT JOIN vehicle v3 ON c.vehicle3_id = v3.id
    LEFT JOIN vehicle_images vi3 ON vi3.vehicle_id = v3.id

    WHERE c.user_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$results = $stmt->get_result();
?>

<?php include "includes/sidebar.php"; ?>
<?php include "includes/topbar.php"; ?>

<style>
.main-content {
    margin-left: 240px;
    padding: 30px;
    background: #0b1220;
    min-height: 100vh;
    color: white;
}

.comp-card {
    background: #0f172a;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #1e293b;
}

.comp-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.vehicle-box {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.vehicle-box img {
    width: 80px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
}

.vs {
    background: #7c3aed;
    padding: 10px;
    border-radius: 50%;
    font-size: 12px;
}
</style>

<div class="main-content">

<h1>Comparison History</h1>

<?php if($results->num_rows > 0): ?>

<?php while($row = $results->fetch_assoc()): ?>

<div class="comp-card">

<p><?= date("M d, Y", strtotime($row['created_at'])) ?></p>

<div class="comp-row">

<!-- VEHICLE 1 -->
<div class="vehicle-box">
    <img src="<?= $row['v1_image'] ?? 'https://via.placeholder.com/80x60' ?>">
    <div>
        <b><?= $row['v1_make'] ?> <?= $row['v1_model'] ?></b>
    </div>
</div>

<div class="vs">VS</div>

<!-- VEHICLE 2 -->
<div class="vehicle-box">
    <img src="<?= $row['v2_image'] ?? 'https://via.placeholder.com/80x60' ?>">
    <div>
        <b><?= $row['v2_make'] ?> <?= $row['v2_model'] ?></b>
    </div>
</div>

<?php if($row['v3_id']): ?>
<div class="vs">VS</div>

<!-- VEHICLE 3 -->
<div class="vehicle-box">
    <img src="<?= $row['v3_image'] ?? 'https://via.placeholder.com/80x60' ?>">
    <div>
        <b><?= $row['v3_make'] ?> <?= $row['v3_model'] ?></b>
    </div>
</div>
<?php endif; ?>

</div>

<div style="margin-top:10px;">
    <a href="#" class="btn-view">View Full Comparison</a>
</div>

</div>

<?php endwhile; ?>

<?php else: ?>

<p>No comparisons yet</p>

<?php endif; ?>

</div>

<?php include "includes/footer.php"; ?>