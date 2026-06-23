<?php
include "includes/header.php";
include "includes/db.php"; // make sure this exists and $conn is defined

// HANDLE FORM SUBMIT
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  // BASIC VEHICLE DATA
  $vehicle_type = $_POST['vehicle_type'];
  $brand        = $_POST['brand'];
  $model        = $_POST['model'];
  $variant      = $_POST['variant'];
  $segment      = $_POST['segment'];
  $price_min    = $_POST['price_min'];
  $price_max    = $_POST['price_max'];
  $mileage_val  = $_POST['mileage_value'];

  // INSERT INTO vehicles
  $stmt = $conn->prepare(
    "INSERT INTO vehicles 
    (vehicle_type, brand, model, variant, segment, price_min, price_max, mileage_value)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
  );

  $stmt->bind_param(
    "sssssidd",
    $vehicle_type,
    $brand,
    $model,
    $variant,
    $segment,
    $price_min,
    $price_max,
    $mileage_val
  );

  $stmt->execute();
  $vehicle_id = $stmt->insert_id;

  // INSERT SPECS (DYNAMIC)
  if (!empty($_POST['spec_category'])) {
    foreach ($_POST['spec_category'] as $i => $cat) {
      $name  = $_POST['spec_name'][$i];
      $value = $_POST['spec_value'][$i];

      if ($cat && $name && $value) {
        $specStmt = $conn->prepare(
          "INSERT INTO vehicle_specs (vehicle_id, spec_category, spec_name, spec_value)
           VALUES (?, ?, ?, ?)"
        );
        $specStmt->bind_param("isss", $vehicle_id, $cat, $name, $value);
        $specStmt->execute();
      }
    }
  }

  // INSERT IMAGES
  if (!empty($_POST['img_urls'])) {
    foreach ($_POST['img_urls'] as $url) {
      if ($url) {
        $imgStmt = $conn->prepare(
          "INSERT INTO vehicle_images (vehicle_id, img_url)
           VALUES (?, ?)"
        );
        $imgStmt->bind_param("is", $vehicle_id, $url);
        $imgStmt->execute();
      }
    }
  }

  echo "<script>alert('Vehicle added successfully');</script>";
}
?>

<div class="admin-page">
  <div class="admin-card">

    <h2>Admin – Add Vehicle</h2>
    <p class="subtitle">Add vehicles and variants</p>

    <form method="POST">

      <!-- VEHICLE TYPE -->
      <div class="form-group">
        <label>Vehicle Type *</label>
        <div class="radio-group">
          <label>
            <input type="radio" name="vehicle_type" value="car" id="type-car" checked> Car
          </label>
          <label>
            <input type="radio" name="vehicle_type" value="bike" id="type-bike"> Bike
          </label>
        </div>
      </div>

      <!-- BRAND / MODEL / VARIANT -->
      <div class="form-row">
        <input name="brand" placeholder="Brand" required>
        <input name="model" placeholder="Model" required>
        <input name="variant" placeholder="Variant (VX / ZX / Standard)" required>
      </div>

      <!-- SEGMENT -->
      <div class="form-group">
        <label>Segment *</label>
        <select id="car-segment" name="segment">
          <option value="">Select car segment</option>
          <option>Sedan</option>
          <option>SUV</option>
          <option>Hatchback</option>
          <option>Coupe</option>
          <option>Luxury</option>
          <option>EV</option>
        </select>

        <select id="bike-segment" name="segment" style="display:none;" disabled>
          <option value="">Select bike segment</option>
          <option>Commuter</option>
          <option>Sport</option>
          <option>Cruiser</option>
          <option>Adventure</option>
          <option>Touring</option>
          <option>Superbike</option>
        </select>
      </div>

      <!-- PRICE + MILEAGE -->
      <div class="form-row">
        <input type="number" name="price_min" placeholder="Price Min (₹)" required>
        <input type="number" name="price_max" placeholder="Price Max (₹)" required>
        <input type="number" step="0.1" name="mileage_value" placeholder="Mileage" required>
      </div>

      <!-- SPECS -->
      <h4 class="section-title">Specifications</h4>

      <div id="specs-container">
        <div class="form-row">
          <input name="spec_category[]" placeholder="Category (Engine)">
          <input name="spec_name[]" placeholder="Spec name (Power)">
          <input name="spec_value[]" placeholder="Spec value (119 bhp)">
        </div>
      </div>

      <button type="button" class="btn-outline" onclick="addSpec()">+ Add Spec</button>

      <!-- IMAGES -->
      <h4 class="section-title">Images</h4>

      <div id="image-container">
        <input name="img_urls[]" placeholder="Image URL">
      </div>

      <button type="button" class="btn-outline" onclick="addImage()">+ Add Image</button>

      <!-- SUBMIT -->
      <div class="form-actions">
        <button type="submit" class="btn-primary">Save Vehicle</button>
      </div>

    </form>
  </div>
</div>

<script>
const carRadio = document.getElementById("type-car");
const bikeRadio = document.getElementById("type-bike");
const carSeg = document.getElementById("car-segment");
const bikeSeg = document.getElementById("bike-segment");

function toggleSegment() {
  if (carRadio.checked) {
    carSeg.style.display = "block";
    carSeg.disabled = false;
    bikeSeg.style.display = "none";
    bikeSeg.disabled = true;
  } else {
    bikeSeg.style.display = "block";
    bikeSeg.disabled = false;
    carSeg.style.display = "none";
    carSeg.disabled = true;
  }
}

carRadio.addEventListener("change", toggleSegment);
bikeRadio.addEventListener("change", toggleSegment);

function addSpec() {
  document.getElementById("specs-container").insertAdjacentHTML(
    "beforeend",
    `<div class="form-row">
      <input name="spec_category[]" placeholder="Category">
      <input name="spec_name[]" placeholder="Spec name">
      <input name="spec_value[]" placeholder="Spec value">
    </div>`
  );
}

function addImage() {
  document.getElementById("image-container").insertAdjacentHTML(
    "beforeend",
    `<input name="img_urls[]" placeholder="Image URL">`
  );
}
</script>

<?php include "includes/footer.php"; ?>
