<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

$user_id = $_SESSION['user_id'];

/* FETCH LAST 10 RESULTS */


$stmt = $conn->prepare("
    SELECT 
        qr.vehicle_name, 
        qr.match_score, 
        qr.created_at,

        v.id AS car_id,
        b.id AS bike_id,

        COALESCE(vi.image_path, bi.image_url) AS image_url

    FROM quiz_results qr

    LEFT JOIN vehicle v 
        ON CONCAT(v.make, ' ', v.model) = qr.vehicle_name

    LEFT JOIN vehicle_images vi 
        ON vi.vehicle_id = v.id

    LEFT JOIN bikes b 
        ON CONCAT(b.brand, ' ', b.model) = qr.vehicle_name

    LEFT JOIN bike_images bi 
        ON bi.bike_id = b.id AND bi.image_type = 'main'

    WHERE qr.user_id = ?
    GROUP BY qr.id
    ORDER BY qr.created_at DESC
    LIMIT 10
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

.page-title {
    font-size: 26px;
    font-weight: 600;
}

.subtitle {
    color: #9ca3af;
    margin-bottom: 25px;
}

.result-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #1e293b, #0f172a);
}

.result-left {
    display: flex;
    gap: 15px;
    align-items: center;
}

.result-img {
    width: 100px;
    height: 70px;
    border-radius: 10px;
    object-fit: cover;
    background: #111;
}

.result-info h3 {
    margin: 0;
    font-size: 18px;
}

.result-info p {
    margin: 4px 0;
    color: #9ca3af;
    font-size: 13px;
}

.score {
    font-size: 20px;
    font-weight: bold;
    color: #facc15;
}

.actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.btn {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.btn-view {
    background: #1f2937;
    color: white;
}

.btn-retake {
    background: #7c3aed;
    color: white;
}

.empty {
    text-align: center;
    color: #9ca3af;
    margin-top: 50px;
}
</style>

<div class="main-content">

<h1 class="page-title">Quiz Results History</h1>
<p class="subtitle">Your personality matches and vehicle recommendations</p>

<?php if($results->num_rows > 0): ?>

    <?php while($row = $results->fetch_assoc()): ?>

        <div class="result-card">

            <div class="result-left">

                <!-- Placeholder Image -->
                <img src="<?= $row['image_url'] ?? 'https://via.placeholder.com/100x70' ?>" class="result-img">

                <div class="result-info">
                    <h3><?= htmlspecialchars($row['vehicle_name']) ?></h3>
                    <p><?= date("M d, Y", strtotime($row['created_at'])) ?></p>

                    <div class="actions">
                        <a href="
<?php
if($row['car_id']){
    echo "../vehicle-details.php?type=car&id=".$row['car_id'];
}else{
    echo "../vehicle-details.php?type=bike&id=".$row['bike_id'];
}
?>
" class="btn btn-view">View Details</a>
                        <a href="
<?php
if($row['car_id']){
    echo "../quiz.php?type=car&id=".$row['car_id'];
}else{
    echo "../quiz.php?type=bike&id=".$row['bike_id'];
}
?>
" class="btn btn-retake">Retake Quiz</a>
                    </div>
                </div>

            </div>

            <div class="score">
                ⭐ <?= $row['match_score'] ?>%
            </div>

        </div>

    <?php endwhile; ?>

<?php else: ?>

    <p class="empty">No quiz results yet</p>

<?php endif; ?>

</div>

<?php include "includes/footer.php"; ?>