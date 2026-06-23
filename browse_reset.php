<?php
include 'includes/db.php';

$type = $_GET['type'] ?? 'car';
$limit = 6;

if ($type === 'bike') {
  $stmt = $conn->prepare("
    SELECT id, brand, model, category, image_url
    FROM bikes
    ORDER BY id DESC
    LIMIT ?
  ");
} else {
  $stmt = $conn->prepare("
    SELECT id, make, model, body_type, price_min, price_max
    FROM vehicle
    ORDER BY id DESC
    LIMIT ?
  ");
}

$stmt->bind_param("i", $limit);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()):
?>
<div class="vehicle-card">
  <img src="<?= htmlspecialchars($row['image_url'] ?? 'assets/images/bikes/placeholder.jpg') ?>" class="vehicle-img">
  <div class="card-body">
    <h3><?= htmlspecialchars(($row['brand'] ?? $row['make']).' '.$row['model']) ?></h3>
  </div>
</div>
<?php endwhile; ?>
