<?php

include 'includes/db.php';

$brand = trim($_POST['brand'] ?? '');
$model = trim($_POST['model'] ?? '');
$type = $_POST['type'] ?? 'car';
if (!$brand && !$model) exit;

$brandLike = "%$brand%";
$modelLike = "%$model%";

/* ===============================
   BIKE PREVIEW (FIXED ORDER)
================================ */
if ($type === 'bike') {

/* BIKE PREVIEW */
$stmt = $conn->prepare("
  SELECT id, brand, model, category, image_url
  FROM bikes
 WHERE LOWER(brand) LIKE LOWER(?) AND LOWER(model) LIKE LOWER(?)  
  ORDER BY id DESC
  LIMIT 8
");
$stmt->bind_param("ss", $brandLike, $modelLike);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  exit; // 🚫 NO FALLBACK
}

while ($b = $res->fetch_assoc()):
  $img = $b['image_url'] ?: 'assets/images/bikes/placeholder.jpg';
?>
<div class="vehicle-card">
  <img src="<?= htmlspecialchars($img) ?>" class="vehicle-img" loading="lazy">
  <div class="card-body">
    <h3><?= htmlspecialchars($b['brand'].' '.$b['model']) ?></h3>

    <?php if ($b['category']): ?>
      <span class="tag"><?= htmlspecialchars($b['category']) ?></span>
    <?php endif; ?>

    <a href="vehicle-details.php?type=bike&id=<?= $b['id'] ?>"
       class="btn btn-primary full">
      View Details
    </a>
  </div>
</div>
<?php endwhile;

exit;
}

/* ===============================
   CAR PREVIEW (FIXED ORDER)
================================ */
if ($type === 'car') {

/* CAR PREVIEW */
$stmt = $conn->prepare("
  SELECT 
    v.id, v.make, v.model, v.body_type,
    MIN(vi.image_path) as image_path
  FROM vehicle v
  LEFT JOIN vehicle_images vi ON v.id = vi.vehicle_id
  WHERE LOWER(v.make) LIKE LOWER(?) AND LOWER(v.model) LIKE LOWER(?)
  GROUP BY v.id
  ORDER BY v.id DESC
  LIMIT 8
");
$stmt->bind_param("ss", $brandLike, $modelLike);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  exit; // 🚫 NO FALLBACK
}

while ($c = $res->fetch_assoc()):
?>
<div class="vehicle-card">

<?php
$img = !empty($c['image_path']) 
    ? $c['image_path'] 
    : 'assets/images/vehicles/default.jpg';
?>

<img src="<?= htmlspecialchars($img) ?>" class="vehicle-img" loading="lazy">

<div class="card-body">
  <h3><?= htmlspecialchars($c['make'].' '.$c['model']) ?></h3>

  <?php if ($c['body_type']): ?>
    <span class="tag"><?= htmlspecialchars($c['body_type']) ?></span>
  <?php endif; ?>

  <a href="vehicle-details.php?id=<?= $c['id'] ?>"
     class="btn btn-primary full">
    View Details
  </a>
</div>
</div>
<?php endwhile;
}