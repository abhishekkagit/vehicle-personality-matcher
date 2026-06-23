<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

$user_id = $_SESSION['user_id'];

/* FETCH SAVED VEHICLES */
$stmt = $conn->prepare("
    SELECT 
        sv.vehicle_id,
        sv.type,

        v.make, v.model, v.budget_range AS car_price,
        vi.image_path AS car_image,

        b.brand, b.model AS bike_model, b.price_range AS bike_price,
        bi.image_url AS bike_image

    FROM saved_vehicles sv

    LEFT JOIN vehicle v 
        ON sv.vehicle_id = v.id AND sv.type = 'car'

    LEFT JOIN vehicle_images vi 
        ON vi.vehicle_id = v.id

    LEFT JOIN bikes b 
        ON sv.vehicle_id = b.id AND sv.type = 'bike'

    LEFT JOIN bike_images bi 
        ON bi.bike_id = b.id AND bi.image_type = 'main'

    WHERE sv.user_id = ?
    GROUP BY sv.vehicle_id, sv.type
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

.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.card {
    background: #0f172a;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid #1e293b;
}

.card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

.card-body {
    padding: 15px;
}

.card h3 {
    margin: 0;
    font-size: 18px;
}

.price {
    color: #a78bfa;
    margin: 5px 0;
}

.actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.btn {
    flex: 1;
    padding: 8px;
    border-radius: 8px;
    text-align: center;
    font-size: 13px;
    text-decoration: none;
}

.btn-view {
    background: #1f2937;
    color: white;
}

.btn-saved {
    background: linear-gradient(90deg, #7c3aed, #9333ea);
    color: white;
}

.empty {
    text-align: center;
    margin-top: 50px;
    color: #9ca3af;
}
</style>

<div class="main-content">

<h1 class="page-title">Saved Vehicles</h1>
<p class="subtitle">Your saved collection</p>

<?php if($results->num_rows > 0): ?>

<div class="grid">

<?php while($row = $results->fetch_assoc()): ?>

<?php
$isCar = $row['type'] === 'car';

$name = $isCar
    ? $row['make'] . " " . $row['model']
    : $row['brand'] . " " . $row['bike_model'];

$price = $isCar
    ? $row['car_price']
    : $row['bike_price'];

$image = $isCar
    ? $row['car_image']
    : $row['bike_image'];

$type = $row['type'];
$id = $row['vehicle_id'];
?>

<div class="card">

<img src="<?= $image ?? 'https://via.placeholder.com/300x180' ?>">

<div class="card-body">

<h3><?= htmlspecialchars($name) ?></h3>

 <p class="price"><?= htmlspecialchars($price ?? 'N/A') ?></p>

<div class="actions">
    <a href="../vehicle-details.php?type=<?= $type ?>&id=<?= $id ?>" class="btn btn-view">
        View
    </a>

    <a href="#" class="btn btn-saved">
        ♥ Saved
    </a>
</div>

</div>

</div>

<?php endwhile; ?>

</div>

<?php else: ?>

<p class="empty">No saved vehicles yet</p>

<?php endif; ?>

</div>

<?php include "includes/footer.php"; ?>