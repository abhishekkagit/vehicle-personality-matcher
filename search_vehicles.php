<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

include 'includes/db.php';
require_once 'includes/bike_cache.php';

/* ===============================
   INPUT
================================ */
$brand = trim($_POST['brand'] ?? '');
$model = trim($_POST['model'] ?? '');
$type  = $_POST['type'] ?? 'car';

if ($brand === '' && $model === '') {
  echo json_encode(['status' => 'empty']);
  exit;
}

/* ===============================
   BIKE SEARCH (STRICT + FIXED)
================================ */
if ($type === 'bike') {

  /* ---------- 1️⃣ EXACT DB MATCH (CASE-INSENSITIVE) ---------- */
  $stmt = $conn->prepare("
    SELECT id, brand, model, category, image_url
    FROM bikes
    WHERE LOWER(brand) = LOWER(?) 
      AND LOWER(model) = LOWER(?)
    LIMIT 1
  ");
  $stmt->bind_param("ss", $brand, $model);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res->num_rows === 1) {
    $b = $res->fetch_assoc();
    $img = $b['image_url'] ?: 'assets/images/bikes/placeholder.jpg';

    ob_start(); ?>
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
    <?php

    echo json_encode([
      'status' => 'found',
      'html'   => ob_get_clean()
    ]);
    exit;
  }

  /* ---------- 2️⃣ NOT FOUND → CALL API ---------- */
  $cacheResult = cacheBikeIfNotExists($conn, $brand, $model);

  if (!in_array($cacheResult['status'], ['cached', 'exists'])) {
    echo json_encode(['status' => 'not_found']);
    exit;
  }

  /* ---------- 3️⃣ FETCH INSERTED BIKE (CASE-INSENSITIVE) ---------- */
  $stmt = $conn->prepare("
    SELECT id, brand, model, category, image_url
    FROM bikes
    WHERE LOWER(brand) = LOWER(?) 
      AND LOWER(model) = LOWER(?)
    LIMIT 1
  ");
  $stmt->bind_param("ss", $brand, $model);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res->num_rows === 0) {
    echo json_encode(['status' => 'not_found']);
    exit;
  }

  $b = $res->fetch_assoc();
  $img = $b['image_url'] ?: 'assets/images/bikes/placeholder.jpg';

  ob_start(); ?>
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
  <?php

  echo json_encode([
    'status' => 'found',
    'html'   => ob_get_clean()
  ]);
  exit;
}

/* ===============================
   CAR SEARCH (UNCHANGED)
================================ */
$brandLike = '%'.$brand.'%';
$modelLike = '%'.$model.'%';

$stmt = $conn->prepare("
  SELECT 
    v.id, v.make, v.model, v.body_type, v.price_min, v.price_max,
    vi.image_path
  FROM vehicle v
  LEFT JOIN vehicle_images vi ON v.id = vi.vehicle_id
  WHERE v.make LIKE ? AND v.model LIKE ?
  GROUP BY v.id
  ORDER BY v.make, v.model
  LIMIT 12
");
$stmt->bind_param("ss", $brandLike, $modelLike);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  echo json_encode(['status' => 'not_found']);
  exit;
}

ob_start();
while ($c = $res->fetch_assoc()):
?>
  <div class="vehicle-card">
<?php
$img = !empty($c['image_path']) 
    ? $c['image_path'] 
    : 'assets/images/vehicles/default.jpg';
?>

<img src="<?= htmlspecialchars($img) ?>"
     data-make="<?= htmlspecialchars($c['make']) ?>"
     data-model="<?= htmlspecialchars($c['model']) ?>"
     class="vehicle-img"
     loading="lazy">

    <div class="card-body">
      <h3><?= htmlspecialchars($c['make'].' '.$c['model']) ?></h3>

      <?php if ($c['body_type']): ?>
        <span class="tag"><?= htmlspecialchars($c['body_type']) ?></span>
      <?php endif; ?>

      <p class="price">
        <?= ($c['price_min'] && $c['price_max'])
          ? '$'.number_format($c['price_min']).' – $'.number_format($c['price_max'])
          : 'Price not available' ?>
      </p>

      <a href="vehicle-details.php?id=<?= $c['id'] ?>"
         class="btn btn-primary full">
        View Details
      </a>
    </div>
  </div>
<?php endwhile;

echo json_encode([
  'status' => 'found',
  'html'   => ob_get_clean()
]);
exit;
