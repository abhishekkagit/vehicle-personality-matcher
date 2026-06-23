<?php
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/header.php';


$type = $_GET['type'] ?? 'cars';

/* =========================
   FORM SUBMIT
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $type = $_POST['type'];
    require_once __DIR__ . '/../includes/image_fetcher.php';

    if ($type === 'cars') {

$make = $conn->real_escape_string($_POST['make']);
$model = $conn->real_escape_string($_POST['model']);
$body_type = $conn->real_escape_string($_POST['body_type']);

$price_min = $_POST['price_min'];
$price_max = $_POST['price_max'];
$budget_range = $_POST['budget_range'];

$city_mpg = $_POST['city_mpg'];
$highway_mpg = $_POST['highway_mpg'];
$seating_capacity = $_POST['seating_capacity'];
$drive_type = $conn->real_escape_string($_POST['drive_type']);

$acc_0_30 = $_POST['acc_0_30'];
$acc_0_60 = $_POST['acc_0_60'];
$quarter_mile = $_POST['quarter_mile'];
$braking_distance = $_POST['braking_distance'];

$fuel_capacity = $_POST['fuel_capacity'];

$length_mm = $_POST['length_mm'];
$width_mm = $_POST['width_mm'];
$height_mm = $_POST['height_mm'];
$wheelbase_mm = $_POST['wheelbase_mm'];
$u_turn_ft = $_POST['u_turn_ft'];

$weight_kg = $_POST['weight_kg'];
$size_class = $conn->real_escape_string($_POST['size_class']);


/* INSERT VEHICLE */

$conn->query("
INSERT INTO vehicle
(
make,
model,
body_type,
budget_range,
city_mpg,
highway_mpg,
seating_capacity,
drive_type,
acc_0_30,
acc_0_60,
quarter_mile,
braking_distance,
fuel_capacity,
length_mm,
width_mm,
height_mm,
wheelbase_mm,
u_turn_ft,
weight_kg,
size_class
)
VALUES
(
'$make',
'$model',
'$body_type',
'$budget_range',
$city_mpg,
$highway_mpg,
$seating_capacity,
'$drive_type',
$acc_0_30,
$acc_0_60,
$quarter_mile,
$braking_distance,
$fuel_capacity,
$length_mm,
$width_mm,
$height_mm,
$wheelbase_mm,
$u_turn_ft,
$weight_kg,
'$size_class'
)
");

$vehicle_id = $conn->insert_id;


/* =========================
   INSERT IMAGES
========================= */

if (function_exists('fetchCarImages')) {

$images = fetchCarImages($make, $model);

if (!empty($images)) {

$imgStmt = $conn->prepare("
INSERT INTO vehicle_images (vehicle_id, image_path)
VALUES (?, ?)
");

foreach (array_slice($images, 0, 3) as $img) {
$imgStmt->bind_param("is", $vehicle_id, $img);
$imgStmt->execute();
}

}

}


    }
    
if ($type === 'bikes') {

$brand = $conn->real_escape_string($_POST['brand']);
$model = $conn->real_escape_string($_POST['bike_model']);
$category = $conn->real_escape_string($_POST['category']);

$displacement = $_POST['displacement_cc'];
$power = $_POST['power_hp'];
$torque = $_POST['torque_nm'];

$weight = $_POST['bike_weight_kg'];
$seat_height = $_POST['seat_height_mm'];

$year = $_POST['year'];
$price_range = $conn->real_escape_string($_POST['price_range']);

$extra_specs = $conn->real_escape_string($_POST['extra_specs'] ?? '{}');


$conn->query("
INSERT INTO bikes
(
brand,
model,
category,
displacement_cc,
power_hp,
torque_nm,
weight_kg,
seat_height_mm,
year,
price_range,
extra_specs
)
VALUES
(
'$brand',
'$model',
'$category',
$displacement,
$power,
$torque,
$weight,
$seat_height,
$year,
'$price_range',
'$extra_specs'
)
");

$bike_id = $conn->insert_id;

if (function_exists('fetchBikeImagesSmart')) {

$imageData = fetchBikeImagesSmart($brand, $model);
$images = $imageData['images'] ?? [];
$source = $imageData['source'] ?? 'auto_fetch';

if (!empty($images)) {

$imgStmt = $conn->prepare("
INSERT INTO bike_images (bike_id, image_url, image_type, source)
VALUES (?, ?, ?, ?)
");

foreach (array_slice($images, 0, 3) as $index => $img) {

$imageType = ($index === 0) ? 'main' : 'gallery';

$imgStmt->bind_param("isss", $bike_id, $img, $imageType, $source);
$imgStmt->execute();

if ($index === 0) {
$updateMain = $conn->prepare("
UPDATE bikes
SET image_url = ?
WHERE id = ?
");
$updateMain->bind_param("si", $img, $bike_id);
$updateMain->execute();
}

}

}

}

}
    $vehicle_id = $conn->insert_id;

    header("Location: vehicles.php?type=$type");
    exit;
}

?>

<div class="admin-wrapper">

<?php require 'includes/sidebar.php'; ?>

<div class="main-area">

<?php 
$pageTitle = "Add " . ucfirst($type);
require 'includes/topbar.php'; 
?>

<div class="content-area">

<div class="card" style="padding:30px;">

<h3>Add <?php echo ucfirst($type); ?></h3>

<form method="POST">

<input type="hidden" name="type" value="<?php echo $type; ?>">

<?php if ($type === 'cars'): ?>

<div class="vehicle-admin-container">




<!-- BASIC INFORMATION -->
<div class="vehicle-admin-card">
<h3>Basic Information</h3>

<div class="vehicle-admin-grid-2">

<div class="vehicle-admin-field">
<label>Make</label>
<input type="text" name="make" required>
</div>

<div class="vehicle-admin-field">
<label>Model</label>
<input type="text" name="model" required>
</div>

<div class="vehicle-admin-field vehicle-admin-full">
<label>Body Type</label>
<input type="text" name="body_type">
</div>

</div>
</div>


<!-- PRICING -->
<div class="vehicle-admin-card">
<h3>Pricing</h3>

<div class="vehicle-admin-grid-2">



<div class="vehicle-admin-field vehicle-admin-full">
<label>Budget Range</label>
<select name="budget_range">
<option value="">Select</option>
<option value="Budget">Budget</option>
<option value="Mid">Mid</option>
<option value="Premium">Premium</option>
</select>
</div>

</div>
</div>


<!-- FUEL ECONOMY -->
<div class="vehicle-admin-card">
<h3>Fuel Economy</h3>

<div class="vehicle-admin-grid-2">

<div class="vehicle-admin-field">
<label>City MPG</label>
<input type="number" step="0.1" name="city_mpg" required>
</div>

<div class="vehicle-admin-field">
<label>Highway MPG</label>
<input type="number" step="0.1" name="highway_mpg" required>
</div>

<div class="vehicle-admin-field vehicle-admin-full">
<label>Fuel Capacity</label>
<input type="number" step="0.1" name="fuel_capacity" required>
</div>

</div>
</div>


<!-- SEATING & DRIVE -->
<div class="vehicle-admin-card">
<h3>Seating & Drive</h3>

<div class="vehicle-admin-grid-2">

<div class="vehicle-admin-field">
<label>Seating Capacity</label>
<input type="number" name="seating_capacity" required>
</div>

<div class="vehicle-admin-field">
<label>Drive Type</label>
<input type="text" name="drive_type" required>
</div>

</div>
</div>


<!-- PERFORMANCE -->
<div class="vehicle-admin-card">
<h3>Performance</h3>

<div class="vehicle-admin-grid-2">

<div class="vehicle-admin-field">
<label>0-30 Acceleration</label>
<input type="number" step="0.1" name="acc_0_30" required>
</div>

<div class="vehicle-admin-field">
<label>0-60 Acceleration</label>
<input type="number" step="0.1" name="acc_0_60" required>
</div>

<div class="vehicle-admin-field">
<label>Quarter Mile</label>
<input type="number" step="0.1" name="quarter_mile" required>
</div>

<div class="vehicle-admin-field">
<label>Braking Distance</label>
<input type="number" step="0.1" name="braking_distance" required>
</div>

</div>
</div>


<!-- DIMENSIONS -->
<div class="vehicle-admin-card">
<h3>Dimensions</h3>

<div class="vehicle-admin-grid-2">

<div class="vehicle-admin-field">
<label>Length (mm)</label>
<input type="number" step="0.1" name="length_mm" required>
</div>

<div class="vehicle-admin-field">
<label>Width (mm)</label>
<input type="number" step="0.1" name="width_mm" required>
</div>

<div class="vehicle-admin-field">
<label>Height (mm)</label>
<input type="number" step="0.1" name="height_mm" required>
</div>

<div class="vehicle-admin-field">
<label>Wheelbase (mm)</label>
<input type="number" step="0.1" name="wheelbase_mm" required>
</div>

<div class="vehicle-admin-field vehicle-admin-full">
<label>U-Turn Radius (ft)</label>
<input type="number" step="0.1" name="u_turn_ft" required>
</div>

</div>
</div>


<!-- WEIGHT -->
<div class="vehicle-admin-card">
<h3>Weight & Size</h3>

<div class="vehicle-admin-grid-2">

<div class="vehicle-admin-field">
<label>Weight (kg)</label>
<input type="number" step="0.1" name="weight_kg" required>
</div>

<div class="vehicle-admin-field">
<label>Size Class</label>
<input type="text" name="size_class" required>
</div>

</div>
</div>

</div>


<?php else: ?>

<div class="vehicle-admin-container">




<!-- BASIC INFORMATION -->
<div class="vehicle-admin-card">
<h3>Basic Information</h3>

<div class="vehicle-admin-grid-2">

<div class="vehicle-admin-field">
<label>Brand</label>
<input type="text" name="brand" required>
</div>

<div class="vehicle-admin-field">
<label>Model</label>
<input type="text" name="bike_model" required>
</div>

<div class="vehicle-admin-field vehicle-admin-full">
<label>Category</label>
<select name="category">

<option value="sport">Sport</option>
<option value="commuter">Commuter</option>
<option value="cruiser">Cruiser</option>
<option value="touring">Touring</option>
<option value="adventure">Adventure</option>

</select>
</div>

</div>
</div>


<!-- ENGINE -->
<div class="vehicle-admin-card">
<h3>Engine</h3>

<div class="vehicle-admin-grid-3">

<div class="vehicle-admin-field">
<label>Displacement (cc)</label>
<input type="number" name="displacement_cc" required>
</div>

<div class="vehicle-admin-field">
<label>Power (HP)</label>
<input type="number" step="0.1" name="power_hp" required>
</div>

<div class="vehicle-admin-field">
<label>Torque (Nm)</label>
<input type="number" step="0.1" name="torque_nm" required>
</div>

</div>
</div>


<!-- DIMENSIONS -->
<div class="vehicle-admin-card">
<h3>Dimensions</h3>

<div class="vehicle-admin-grid-2">

<div class="vehicle-admin-field">
<label>Weight (kg)</label>
<input type="number" step="0.1" name="bike_weight_kg" required>
</div>

<div class="vehicle-admin-field">
<label>Seat Height (mm)</label>
<input type="number" step="0.1" name="seat_height_mm" required>
</div>

</div>
</div>


<!-- PRICING -->
<div class="vehicle-admin-card">
<h3>Pricing</h3>

<div class="vehicle-admin-grid-2">

<div class="vehicle-admin-field">
<label>Year</label>
<input type="number" name="year" required>
</div>

<div class="vehicle-admin-field">
<label>Price Range</label>
<input type="text" name="price_range" required>
</div>

</div>
</div>


<!-- EXTRA SPECS -->
<div class="vehicle-admin-card">
<h3>Extra Specs (JSON)</h3>

<div class="vehicle-admin-grid-1">

<div class="vehicle-admin-field vehicle-admin-full">
<label>Extra Specifications</label>
<textarea name="extra_specs" rows="5" placeholder='{"abs":true,"traction_control":true}'></textarea>
</div>

</div>
</div>

</div>

<?php endif; ?>

<br>

<button type="submit" class="btn-primary">
Save <?php echo ucfirst($type); ?>
</button>

<a href="vehicles.php?type=<?php echo $type; ?>" 
   class="btn-primary" 
   style="background:#6b7280;">
Cancel
</a>

</form>

</div>

</div>

</div>

</div>

<?php require 'includes/footer.php'; ?>
