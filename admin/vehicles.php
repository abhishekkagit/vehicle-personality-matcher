<?php 
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/header.php';


/* ============================================
   DELETE VEHICLE
============================================ */

if (isset($_GET['delete'])) {

$id = (int)$_GET['delete'];
$type = $_GET['type'] ?? 'cars';

if ($type === 'cars') {

$conn->query("DELETE FROM vehicle_images WHERE vehicle_id=$id");
$conn->query("DELETE FROM vehicle WHERE id=$id");

} else {

$conn->query("DELETE FROM bike_images WHERE bike_id=$id");
$conn->query("DELETE FROM bikes WHERE id=$id");

}

header("Location: vehicles.php?type=$type");
exit;

}
/* ============================================
   HANDLE ADD VEHICLE
============================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_vehicle') {

    $vehicleType = $_POST['vehicle_type'];

    if ($vehicleType === 'car') {

        $stmt = $conn->prepare("
            INSERT INTO vehicle (
                make, model, body_type,
                price_min, price_max,
                city_mpg, highway_mpg,
                seating_capacity, drive_type,
                fuel_capacity, weight_kg,
                size_class,
                acc_0_60, quarter_mile, braking_distance,
                performance_score, comfort_score,
                efficiency_score, reliability_score,
                practicality_score
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "sssiiiiisiiisssiiiii",
            $_POST['make'],
            $_POST['model'],
            $_POST['body_type'],
            $_POST['price_min'],
            $_POST['price_max'],
            $_POST['city_mpg'],
            $_POST['highway_mpg'],
            $_POST['seating_capacity'],
            $_POST['drive_type'],
            $_POST['fuel_capacity'],
            $_POST['weight_kg'],
            $_POST['size_class'],
            $_POST['acc_0_60'],
            $_POST['quarter_mile'],
            $_POST['braking_distance'],
            $_POST['performance_score'],
            $_POST['comfort_score'],
            $_POST['efficiency_score'],
            $_POST['reliability_score'],
            $_POST['practicality_score']
        );

        $stmt->execute();
        $vehicleId = $stmt->insert_id;

        $images = [$_POST['image_1'], $_POST['image_2'], $_POST['image_3']];

        foreach ($images as $img) {
            if (!empty($img)) {
                $img = $conn->real_escape_string($img);
                $conn->query("INSERT INTO vehicle_images (vehicle_id, image_path) VALUES ($vehicleId, '$img')");
            }
        }

    } else {

        $extraSpecs = $_POST['extra_specs'] ?? '';

        if (!empty($extraSpecs)) {
            json_decode($extraSpecs);
            if (json_last_error() !== JSON_ERROR_NONE) {
                die("Invalid JSON format in Extra Specs.");
            }
        }

        $stmt = $conn->prepare("
            INSERT INTO bikes (
                brand, model, category,
                displacement_cc, power_hp, torque_nm,
                weight_kg, seat_height_mm,
                year, price_range, extra_specs
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "sssiiiiisss",
            $_POST['brand'],
            $_POST['bike_model'],
            $_POST['category'],
            $_POST['displacement_cc'],
            $_POST['power_hp'],
            $_POST['torque_nm'],
            $_POST['bike_weight_kg'],
            $_POST['seat_height_mm'],
            $_POST['year'],
            $_POST['price_range'],
            $extraSpecs
        );

        $stmt->execute();
        $bikeId = $stmt->insert_id;

        $images = [$_POST['image_1'], $_POST['image_2'], $_POST['image_3']];
        $index = 0;

        foreach ($images as $img) {
            if (!empty($img)) {
                $type = ($index === 0) ? 'main' : 'gallery';
                $img = $conn->real_escape_string($img);

                $conn->query("
                    INSERT INTO bike_images
                    (bike_id, image_url, image_type, source)
                    VALUES ($bikeId, '$img', '$type', 'admin_upload')
                ");

                $index++;
            }
        }
    }

    header("Location: vehicles.php?type=cars");
    exit;
}


/* ============================================
   FILTER / PAGINATION
============================================ */

$type = $_GET['type'] ?? 'cars';
$limit = 5;
$page = max((int)($_GET['page'] ?? 1), 1);
$offset = ($page - 1) * $limit;

$brandSearch = $_GET['brand'] ?? '';
$modelSearch = $_GET['model'] ?? '';

$where = "WHERE 1=1";

if ($type === 'cars') {

    if ($brandSearch) {
        $where .= " AND make LIKE '%".$conn->real_escape_string($brandSearch)."%'";
    }

    if ($modelSearch) {
        $where .= " AND model LIKE '%".$conn->real_escape_string($modelSearch)."%'";
    }

    $totalRows = $conn->query("SELECT COUNT(*) as total FROM vehicle $where")->fetch_assoc()['total'];
    $dataQuery = "SELECT * FROM vehicle $where ORDER BY id DESC LIMIT $limit OFFSET $offset";

} else {

    if ($brandSearch) {
        $where .= " AND brand LIKE '%".$conn->real_escape_string($brandSearch)."%'";
    }

    if ($modelSearch) {
        $where .= " AND model LIKE '%".$conn->real_escape_string($modelSearch)."%'";
    }

    $totalRows = $conn->query("SELECT COUNT(*) as total FROM bikes $where")->fetch_assoc()['total'];
    $dataQuery = "SELECT * FROM bikes $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
}

$totalPages = ceil($totalRows / $limit);
$dataResult = $conn->query($dataQuery);
?>

<div class="admin-wrapper">
<?php require 'includes/sidebar.php'; ?>
<div class="main-area">

<?php 
$pageTitle = "Vehicles";
require 'includes/topbar.php'; 
?>

<div class="content-area">
<div class="card">

<!-- TABS -->
<div class="tabs-header">
<a href="?type=cars" class="tab-btn <?php echo ($type === 'cars') ? 'active' : ''; ?>">Cars</a>
<a href="?type=bikes" class="tab-btn <?php echo ($type === 'bikes') ? 'active' : ''; ?>">Bikes</a>
</div>

<!-- FILTER -->
<div class="filter-bar">

<form method="GET" style="display:flex; gap:12px; flex:1;">
<input type="hidden" name="type" value="<?php echo $type; ?>">

<input type="text" name="brand" value="<?php echo htmlspecialchars($brandSearch); ?>" placeholder="Search by Brand" class="filter-input">

<input type="text" name="model" value="<?php echo htmlspecialchars($modelSearch); ?>" placeholder="Search by Model" class="filter-input">

<button type="submit" class="btn-primary">Filter</button>
</form>

<a href="add-vehicle.php?type=<?php echo $type; ?>" class="btn-primary">
+ Add Vehicle
</a>

</div>

<!-- TABLE -->
<div class="table-wrapper">
<table class="data-table">

<thead>
<tr>
<th>Image</th>
<th>Brand</th>
<th>Model</th>
<th><?php echo ($type === 'cars') ? 'price range' : 'price range'; ?></th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php if ($dataResult && $dataResult->num_rows > 0): ?>
<?php while ($row = $dataResult->fetch_assoc()): ?>

<?php
if ($type === 'cars') {
    $imgQuery = $conn->query("SELECT image_path FROM vehicle_images WHERE vehicle_id={$row['id']} ORDER BY id ASC LIMIT 1");
    $cover = ($imgQuery && $imgQuery->num_rows) ? $imgQuery->fetch_assoc()['image_path'] : 'https://via.placeholder.com/50';
} else {
    $imgQuery = $conn->query("SELECT image_url FROM bike_images WHERE bike_id={$row['id']} AND image_type='main' LIMIT 1");
    $cover = ($imgQuery && $imgQuery->num_rows) ? $imgQuery->fetch_assoc()['image_url'] : 'https://via.placeholder.com/50';
}
?>

<tr>
<td><img src="<?php echo htmlspecialchars($cover); ?>" class="vehicle-thumb"></td>
<td><?php echo htmlspecialchars($type === 'cars' ? $row['make'] : $row['brand']); ?></td>
<td><?php echo htmlspecialchars($row['model']); ?></td>

<td>
<?php 
if ($type === 'cars') {
    echo "$".$row['budget_range'];
} else {
    echo htmlspecialchars($row['price_range']);
}
?>
</td>

<td class="action-buttons">

<a href="edit-vehicle.php?id=<?php echo $row['id']; ?>&type=<?php echo $type; ?>" class="edit-btn">
✏️
</a>

<a href="vehicles.php?delete=<?php echo $row['id']; ?>&type=<?php echo $type; ?>" 
class="delete-btn"
onclick="return confirm('Delete this vehicle?');">
🗑️
</a>

</td>
</tr>

<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="7" style="text-align:center;">No results</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>

</div>
</div>
</div>
</div>

<?php require 'includes/footer.php'; ?>
