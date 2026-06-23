<?php
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/header.php';

$type = $_GET['type'] ?? 'cars';
$id = (int)($_GET['id'] ?? 0);

/* =========================
   GET VEHICLE
========================= */

$existingImages = [];

if ($type === 'cars') {

$stmt = $conn->prepare("SELECT * FROM vehicle WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

$imgQuery = $conn->query("SELECT * FROM vehicle_images WHERE vehicle_id=$id");

while($img = $imgQuery->fetch_assoc()){
$existingImages[] = $img['image_path'];
}

} else {

$stmt = $conn->prepare("SELECT * FROM bikes WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

$imgQuery = $conn->query("
SELECT * 
FROM bike_images 
WHERE bike_id=$id 
ORDER BY image_type='main' DESC
");

while($img = $imgQuery->fetch_assoc()){
$existingImages[] = $img['image_url'];
}

}
/* =========================
   UPDATE VEHICLE
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$type = $_POST['type'];

if ($type === 'cars') {

$stmt = $conn->prepare("
UPDATE vehicle SET
make=?,
model=?,
body_type=?,
budget_range=?,
city_mpg=?,
highway_mpg=?,
seating_capacity=?,
drive_type=?,
acc_0_30=?,
acc_0_60=?,
quarter_mile=?,
braking_distance=?,
fuel_capacity=?,
length_mm=?,
width_mm=?,
height_mm=?,
wheelbase_mm=?,
u_turn_ft=?,
weight_kg=?,
size_class=?
WHERE id=?
");

$stmt->bind_param(
"ssssiiiiiiiiiiiiiiisi",
$_POST['make'],
$_POST['model'],
$_POST['body_type'],
$_POST['budget_range'],
$_POST['city_mpg'],
$_POST['highway_mpg'],
$_POST['seating_capacity'],
$_POST['drive_type'],
$_POST['acc_0_30'],
$_POST['acc_0_60'],
$_POST['quarter_mile'],
$_POST['braking_distance'],
$_POST['fuel_capacity'],
$_POST['length_mm'],
$_POST['width_mm'],
$_POST['height_mm'],
$_POST['wheelbase_mm'],
$_POST['u_turn_ft'],
$_POST['weight_kg'],
$_POST['size_class'],
$id
);

$stmt->execute();

/* update images */

$conn->query("DELETE FROM vehicle_images WHERE vehicle_id=$id");

$images = [
$_POST['image1'] ?? '',
$_POST['image2'] ?? '',
$_POST['image3'] ?? ''
];

foreach ($images as $img){

if(!empty($img)){

$img = $conn->real_escape_string($img);

$conn->query("
INSERT INTO vehicle_images (vehicle_id,image_path)
VALUES ($id,'$img')
");

}

}

} else {

$stmt = $conn->prepare("
UPDATE bikes SET
brand=?,
model=?,
category=?,
displacement_cc=?,
power_hp=?,
torque_nm=?,
weight_kg=?,
seat_height_mm=?,
year=?,
price_range=?,
extra_specs=?
WHERE id=?
");

$stmt->bind_param(
"sssiiiiisssi",
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
$_POST['extra_specs'],
$id
);

$stmt->execute();

/* update images */

$conn->query("DELETE FROM bike_images WHERE bike_id=$id");

$images = [$_POST['image1'],$_POST['image2'],$_POST['image3']];
$mainImage = $conn->real_escape_string($_POST['image1'] ?? '');

if(!empty($mainImage)){
$conn->query("
UPDATE bikes
SET image_url='$mainImage'
WHERE id=$id
");
}
$index = 0;

foreach ($images as $img){

if(!empty($img)){

$typeImg = ($index === 0) ? 'main' : 'gallery';

$img = $conn->real_escape_string($img);

$conn->query("
INSERT INTO bike_images
(bike_id,image_url,image_type,source)
VALUES
($id,'$img','$typeImg','admin_upload')
");

$index++;

}

}

}

header("Location: vehicles.php?type=$type");
exit;

}
?>

<div class="admin-wrapper">

<?php require 'includes/sidebar.php'; ?>

<div class="main-area">

<?php
$pageTitle = "Edit Vehicle";
require 'includes/topbar.php';
?>

<div class="content-area">

<div class="card" style="padding:30px;">

<h3>Edit <?php echo ucfirst($type); ?></h3>

<form method="POST">

<input type="hidden" name="type" value="<?php echo $type; ?>">

<!-- IMAGE PREVIEW -->

<h3>Current Images</h3>

<div style="display:flex;gap:15px;margin-bottom:20px;">

<?php foreach($existingImages as $img): ?>

<img src="<?php echo htmlspecialchars($img); ?>"
style="width:120px;height:80px;object-fit:cover;border-radius:6px;">

<?php endforeach; ?>

</div>


<h3>Image URLs</h3>

<div class="vehicle-admin-grid-3">

<input type="text" name="image1" 
value="<?php echo $existingImages[0] ?? ''; ?>" 
placeholder="Image 1 URL">

<input type="text" name="image2" 
value="<?php echo $existingImages[1] ?? ''; ?>" 
placeholder="Image 2 URL">

<input type="text" name="image3" 
value="<?php echo $existingImages[2] ?? ''; ?>" 
placeholder="Image 3 URL">

</div>

<br>

<?php if ($type === 'cars'): ?>

<!-- BASIC -->

<div class="vehicle-admin-card">

<h3>Basic</h3>
<div class="vehicle-admin-field">
<label>make</label>
<input type="text" name="make" value="<?php echo $vehicle['make']; ?>">
</div>
<div class="vehicle-admin-field">
<label>model</label>
<input type="text" name="model" value="<?php echo $vehicle['model']; ?>">
</div>
<div class="vehicle-admin-field">
<label>body type</label>
<input type="text" name="body_type" value="<?php echo $vehicle['body_type']; ?>">
</div>
</div>


<!-- PERFORMANCE -->

<div class="vehicle-admin-card">

<h3>Performance</h3>

<div class="vehicle-admin-field">
<label>0-30km/h</label>
<input type="number" name="acc_0_30" value="<?php echo $vehicle['acc_0_30']; ?>">
</div>
<div class="vehicle-admin-field">
<label>0-60km/h</label>
<input type="number" name="acc_0_60" value="<?php echo $vehicle['acc_0_60']; ?>">
</div>
<div class="vehicle-admin-field">
<label>quarter mile</label>
<input type="number" name="quarter_mile" value="<?php echo $vehicle['quarter_mile']; ?>">
</div>
<div class="vehicle-admin-field">
<label>braking distance</label>
<input type="number" name="braking_distance" value="<?php echo $vehicle['braking_distance']; ?>">
</div>
</div>

<!-- ECONOMY -->

<div class="vehicle-admin-card">

<h3>Fuel</h3>

<div class="vehicle-admin-field">
<label>city mpg</label>
<input type="number" name="city_mpg" value="<?php echo $vehicle['city_mpg']; ?>">
</div>
<div class="vehicle-admin-field">
<label>highway mpg</label>
<input type="number" name="highway_mpg" value="<?php echo $vehicle['highway_mpg']; ?>">
</div>
<div class="vehicle-admin-field">
<label>fuel capacity</label>
<input type="number" name="fuel_capacity" value="<?php echo $vehicle['fuel_capacity']; ?>">
</div>

</div>

<!-- DIMENSIONS -->

<div class="vehicle-admin-card">

<h3>Dimensions mm</h3>

<div class="vehicle-admin-field">
<label>length</label>
<input type="number" name="length_mm" value="<?php echo $vehicle['length_mm']; ?>">
</div>
<div class="vehicle-admin-field">
<label>width</label>
<input type="number" name="width_mm" value="<?php echo $vehicle['width_mm']; ?>">
</div>
<div class="vehicle-admin-field">
<label>height</label>
<input type="number" name="height_mm" value="<?php echo $vehicle['height_mm']; ?>">
</div>
<div class="vehicle-admin-field">
<label>wheelbase</label>
<input type="number" name="wheelbase_mm" value="<?php echo $vehicle['wheelbase_mm']; ?>">
</div>
</div>

<!-- OTHER -->

<div class="vehicle-admin-card">
    
<div class="vehicle-admin-field">
<label>weight</label>
<input type="number" name="weight_kg" value="<?php echo $vehicle['weight_kg']; ?>">
</div>
<div class="vehicle-admin-field">
<label>seating capacity</label>
<input type="number" name="seating_capacity" value="<?php echo $vehicle['seating_capacity']; ?>">
</div>
<div class="vehicle-admin-field">
<label>drive type</label>
<input type="text" name="drive_type" value="<?php echo $vehicle['drive_type']; ?>">
</div>
<div class="vehicle-admin-field">
<label>budget range</label>
<input type="text" name="budget_range" value="<?php echo $vehicle['budget_range']; ?>">
</div>
<div class="vehicle-admin-field">
<label>size class</label>
<input type="text" name="size_class" value="<?php echo $vehicle['size_class']; ?>">
</div>
</div>


<?php else: ?>

<!-- BIKE -->
 
<div class="vehicle-admin-field">
<label>brand</label>
<input type="text" name="brand" value="<?php echo $vehicle['brand']; ?>">
</div>
<div class="vehicle-admin-field">
<label>model</label>
<input type="text" name="bike_model" value="<?php echo $vehicle['model']; ?>">
</div>
<div class="vehicle-admin-field">
<label>category</label>
<input type="text" name="category" value="<?php echo $vehicle['category']; ?>">
</div>
<div class="vehicle-admin-field">
<label>cc</label>

<input type="number" name="displacement_cc" value="<?php echo $vehicle['displacement_cc']; ?>">
</div>
<div class="vehicle-admin-field">
<label>hp</label>
<input type="number" name="power_hp" value="<?php echo $vehicle['power_hp']; ?>">
</div>
<div class="vehicle-admin-field">
<label>torque</label>
<input type="number" name="torque_nm" value="<?php echo $vehicle['torque_nm']; ?>">
</div>
<div class="vehicle-admin-field">
<label>weight</label>

<input type="number" name="bike_weight_kg" value="<?php echo $vehicle['weight_kg']; ?>">
</div>
<div class="vehicle-admin-field">
<label>seat_height</label>
<input type="number" name="seat_height_mm" value="<?php echo $vehicle['seat_height_mm']; ?>">
</div>
<div class="vehicle-admin-field">
<label>year</label>
<input type="number" name="year" value="<?php echo $vehicle['year']; ?>">
</div>
<div class="vehicle-admin-field">
<label>price range</label>
<input type="text" name="price_range" value="<?php echo $vehicle['price_range']; ?>">
</div>
<div class="vehicle-admin-field">
<label>extra specs</label>
<textarea name="extra_specs"><?php echo $vehicle['extra_specs']; ?></textarea>

<?php endif; ?>

<br><br>

<button class="btn-primary">Update Vehicle</button>

<a href="vehicles.php?type=<?php echo $type; ?>" class="btn-primary" style="background:#6b7280;">Cancel</a>

</form>

</div>

</div>

</div>

</div>

<?php require 'includes/footer.php'; ?>