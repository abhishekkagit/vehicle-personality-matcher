<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'includes/db.php';

$limit  = 6;
$offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
$type   = $_POST['type'] ?? 'car';

/* =========================================================
   ======================= BIKES ===========================
   ========================================================= */
if ($type === 'bike') {

  $query = "
    SELECT
      id,
      brand,
      model,
      category,
      image_url
    FROM bikes
    ORDER BY id DESC
    LIMIT ? OFFSET ?
  ";

  $stmt = $conn->prepare($query);
  $stmt->bind_param("ii", $limit, $offset);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) exit;

  while ($row = $result->fetch_assoc()):

    $img = $row['image_url'] ?: 'assets/images/bikes/placeholder.jpg';
  ?>
    <div class="vehicle-card">
      <img
        src="<?= htmlspecialchars($img) ?>"
        class="vehicle-img"
        alt="Bike"
        loading="lazy"
      >

      <div class="card-body">
        <h3><?= htmlspecialchars($row['brand'] . ' ' . $row['model']) ?></h3>

        <?php if (!empty($row['category'])): ?>
          <span class="tag"><?= htmlspecialchars($row['category']) ?></span>
        <?php endif; ?>

        <a href="vehicle-details.php?type=bike&id=<?= $row['id'] ?>"
           class="btn btn-primary full">
          View Details
        </a>
      </div>
    </div>
  <?php endwhile;

  exit;
}

/* =========================================================
   ========================= CARS ==========================
   ========================================================= */

$query = "
  SELECT
    id,
    make,
    model,
    body_type,
    price_min,
    price_max
  FROM vehicle
  ORDER BY id DESC
  LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) exit;

while ($row = $result->fetch_assoc()):

  /* ONLY FETCH CACHED IMAGE (NO AUTO FETCH HERE) */
  $imgStmt = $conn->prepare("
      SELECT image_path
      FROM vehicle_images
      WHERE vehicle_id = ?
      ORDER BY created_at ASC
      LIMIT 1
  ");
  $imgStmt->bind_param("i", $row['id']);
  $imgStmt->execute();
  $imgRes = $imgStmt->get_result();

  $image = 'assets/images/vehicles/default.jpg';

  if ($imgRes->num_rows > 0) {
      $imgRow = $imgRes->fetch_assoc();
      $image = $imgRow['image_path'];
  }

?>
  <div class="vehicle-card">

    <img
      src="<?= htmlspecialchars($image) ?>"
      class="vehicle-img"
      alt="Car"
      loading="lazy"
    >

    <div class="card-body">
      <h3><?= htmlspecialchars($row['make'] . ' ' . $row['model']) ?></h3>

      <?php if (!empty($row['body_type'])): ?>
        <span class="tag"><?= htmlspecialchars($row['body_type']) ?></span>
      <?php endif; ?>

      <p class="price">
        <?php if ($row['price_min'] !== null && $row['price_max'] !== null): ?>
          ₹<?= number_format($row['price_min']) ?> –
          ₹<?= number_format($row['price_max']) ?>
        <?php else: ?>
          Price not available
        <?php endif; ?>
      </p>

      <a href="vehicle-details.php?type=car&id=<?= $row['id'] ?>"
         class="btn btn-primary full">
        View Details
      </a>
    </div>
  </div>

<?php endwhile; ?>
