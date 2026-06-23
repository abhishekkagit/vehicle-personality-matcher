<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once 'includes/compare_compatibility.php';
include 'includes/header1.php';
include 'includes/db.php';


/* ===============================
   USER PERSONALITY
================================ */

$userVector = [

'performance'  => $_SESSION['performance'] ?? 50,
'comfort'      => $_SESSION['comfort'] ?? 50,
'efficiency'   => $_SESSION['efficiency'] ?? 50,
'reliability'  => $_SESSION['reliability'] ?? 50,
'practicality' => $_SESSION['practicality'] ?? 50

];


/* ===============================
   GET BIKE IDS
================================ */

$v1 = $_GET['v1'] ?? null;
$v2 = $_GET['v2'] ?? null;
$v3 = $_GET['v3'] ?? null;

if(!$v1){
die("No bike selected for comparison.");
}


/* ===============================
   FETCH BIKE 1
================================ */

$stmt = $conn->prepare("SELECT * FROM bikes WHERE id=?");
$stmt->bind_param("i",$v1);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows === 0){
die("Bike not found.");
}

$vehicle1 = $res->fetch_assoc();


/* ===============================
   FETCH BIKE 2
================================ */

$vehicle2 = null;

if($v2){

$stmt = $conn->prepare("SELECT * FROM bikes WHERE id=?");
$stmt->bind_param("i",$v2);
$stmt->execute();

$res = $stmt->get_result();

if($res->num_rows > 0){
$vehicle2 = $res->fetch_assoc();
}

}


/* ===============================
   FETCH BIKE 3
================================ */

$vehicle3 = null;

if($v3){

$stmt = $conn->prepare("SELECT * FROM bikes WHERE id=?");
$stmt->bind_param("i",$v3);
$stmt->execute();

$res = $stmt->get_result();

if($res->num_rows > 0){
$vehicle3 = $res->fetch_assoc();
}

}


/* ===============================
   CALCULATE COMPATIBILITY
================================ */

$v1Score = calculateCompareCompatibility($userVector,$vehicle1,'bike');

$v2Score = null;

if($vehicle2){
$v2Score = calculateCompareCompatibility($userVector,$vehicle2,'bike');
}

$v3Score = null;

if($vehicle3){
$v3Score = calculateCompareCompatibility($userVector,$vehicle3,'bike');
}

$score1 = $v1Score;
$score2 = $v2Score ?? 0;
$score3 = $v3Score ?? 0;


/* ===============================
   SAVE BIKE COMPARISON
================================ */

?>

<div class="compare-page">

<h1 class="compare-title">Bike Comparison</h1>
<p class="compare-sub">
Compare bikes side-by-side and see which one matches your personality best
</p>

<div class="compare-cards">

<div class="compare-card">

<h3><?= $vehicle1['brand']." ".$vehicle1['model'] ?></h3>

<?php
$id1 = (int)$vehicle1['id'];

$img1 = $conn->query("SELECT image_url FROM bike_images WHERE bike_id=$id1 LIMIT 1")->fetch_assoc()['image_url'] ?? '';
?>

<img src="<?= $img1 ?>" class="compare-image">

<p>
₹<?= $vehicle1['price_range'] ?? '' ?>
</p>

<div class="compat-score">

<div class="circle">
<?= $score1 ?>%
</div>

<p>Personality Compatibility</p>

</div>

</div>


<?php if($vehicle2){ ?>

<div class="compare-card">

<h3><?= $vehicle2['brand']." ".$vehicle2['model'] ?></h3>

<?php
$img2 = $conn->query("SELECT image_url FROM bike_images WHERE bike_id={$vehicle2['id']} LIMIT 1")->fetch_assoc()['image_url'] ?? '';
?>

<img src="<?= $img2 ?>" class="compare-image">

<p>
₹<?= $vehicle2['price_range'] ?? '' ?>
</p>

<div class="compat-score">

<div class="circle">
<?= $score2 ?>%
</div>

<p>Personality Compatibility</p>

</div>

</div>

<?php if($vehicle3){ ?>

<div class="compare-card">

<h3><?= $vehicle3['brand']." ".$vehicle3['model'] ?></h3>

<?php
$img3 = $conn->query("SELECT image_url FROM bike_images WHERE bike_id={$vehicle3['id']} LIMIT 1")->fetch_assoc()['image_url'] ?? '';
?>

<img src="<?= $img3 ?>" class="compare-image">

<p>
₹<?= $vehicle3['price_range'] ?? '' ?>
</p>

<div class="compat-score">

<div class="circle">
<?= $score3 ?>%
</div>

<p>Personality Compatibility</p>

</div>

</div>

<?php } ?>
<?php } ?>

</div>


<div class="compatibility-section">

<h2>Overall Compatibility Score</h2>


<div class="compat-row">

<span><?= $vehicle1['brand']." ".$vehicle1['model'] ?></span>

<div class="bar">

<div class="fill green" style="width:<?= $score1 ?>%"></div>

</div>

<span><?= $score1 ?>%</span>

</div>


<?php if($vehicle2){ ?>

<div class="compat-row">

<span><?= $vehicle2['brand']." ".$vehicle2['model'] ?></span>

<div class="bar">

<div class="fill blue" style="width:<?= $score2 ?>%"></div>

</div>

<span><?= $score2 ?>%</span>

</div>

<?php if($vehicle3){ ?>

<div class="compat-row">

<span><?= $vehicle3['brand']." ".$vehicle3['model'] ?></span>

<div class="bar">
<div class="fill purple" style="width:<?= $score3 ?>%"></div>
</div>

<span><?= $score3 ?>%</span>

</div>

<?php } ?>
<?php } ?>

</div>


<div class="radar-section">

<h2>Personality Attributes Comparison</h2>

<canvas id="compareRadar"></canvas>

</div>


<script src="assets/js/chart.js"></script>

<script>

const ctx = document.getElementById('compareRadar');

new Chart(ctx,{
type:'radar',
data:{
labels:[
'Performance',
'Comfort',
'Efficiency',
'Reliability',
'Practicality'
],
datasets:[
{
label:'<?= $vehicle1['brand']." ".$vehicle1['model'] ?>',
data:[
<?= $vehicle1['performance_score'] ?>,
<?= $vehicle1['comfort_score'] ?>,
<?= $vehicle1['efficiency_score'] ?>,
<?= $vehicle1['reliability_score'] ?>,
<?= $vehicle1['practicality_score'] ?>
],
borderColor:'#ef4444',
backgroundColor:'rgba(239,68,68,0.2)'
}

<?php if($vehicle2){ ?>,
{
label:'<?= $vehicle2['brand']." ".$vehicle2['model'] ?>',
data:[
<?= $vehicle2['performance_score'] ?>,
<?= $vehicle2['comfort_score'] ?>,
<?= $vehicle2['efficiency_score'] ?>,
<?= $vehicle2['reliability_score'] ?>,
<?= $vehicle2['practicality_score'] ?>
],
borderColor:'#3b82f6',
backgroundColor:'rgba(59,130,246,0.2)'
}
<?php } ?>

<?php if($vehicle3){ ?>,
{
label:'<?= $vehicle3['brand']." ".$vehicle3['model'] ?>',
data:[
<?= $vehicle3['performance_score'] ?>,
<?= $vehicle3['comfort_score'] ?>,
<?= $vehicle3['efficiency_score'] ?>,
<?= $vehicle3['reliability_score'] ?>,
<?= $vehicle3['practicality_score'] ?>
],
borderColor:'#8b5cf6',
backgroundColor:'rgba(139,92,246,0.2)'
}
<?php } ?>

]
}
});

</script>

<div class="attribute-section">

<h2>Detailed Attribute Comparison</h2>

<?php

$attributes = [
'Performance'  => 'performance_score',
'Comfort'      => 'comfort_score',
'Efficiency'   => 'efficiency_score',
'Reliability'  => 'reliability_score',
'Practicality' => 'practicality_score'
];

foreach($attributes as $label => $col){

$val1 = $vehicle1[$col] ?? 50;
$val2 = $vehicle2 ? ($vehicle2[$col] ?? 50) : 0;
$val3 = $vehicle3 ? ($vehicle3[$col] ?? 50) : 0;

?>

<div class="attribute-row">

<div class="attr-label"><?= $label ?></div>

<div class="attr-bars">

<div class="attr-item">
<span><?= $vehicle1['brand'] ?></span>
<div class="attr-bar-bg">
<div class="attr-bar red" style="width:<?= $val1 ?>%"></div>
</div>
<span><?= $val1 ?></span>
</div>

<?php if($vehicle2){ ?>
<div class="attr-item">
<span><?= $vehicle2['brand'] ?></span>
<div class="attr-bar-bg">
<div class="attr-bar blue" style="width:<?= $val2 ?>%"></div>
</div>
<span><?= $val2 ?></span>
</div>
<?php } ?>

<?php if($vehicle3){ ?>
<div class="attr-item">
<span><?= $vehicle3['brand'] ?></span>
<div class="attr-bar-bg">
<div class="attr-bar purple" style="width:<?= $val3 ?>%"></div>
</div>
<span><?= $val3 ?></span>
</div>
<?php } ?>

</div>

</div>

<?php } ?>

</div>
<div class="spec-section">

<h2>Detailed Specifications</h2>

<table class="spec-table">

<tr>
<th>Specification</th>
<th><?= $vehicle1['brand']." ".$vehicle1['model'] ?></th>

<?php if($vehicle2){ ?>
<th><?= $vehicle2['brand']." ".$vehicle2['model'] ?></th>
<?php } ?>

<?php if($vehicle3){ ?>
<th><?= $vehicle3['brand']." ".$vehicle3['model'] ?></th>
<?php } ?>

</tr>

<tr>
<td>Engine</td>
<td><?= $vehicle1['displacement_cc'] ?? 'N/A' ?> cc</td>

<?php if($vehicle2){ ?>
<td><?= $vehicle2['displacement_cc'] ?? 'N/A' ?> cc</td>
<?php } ?>

<?php if($vehicle3){ ?>
<td><?= $vehicle3['displacement_cc'] ?? 'N/A' ?> cc</td>
<?php } ?>

</tr>

<tr>
<td>Power</td>
<td><?= $vehicle1['power_hp'] ?? 'N/A' ?> hp</td>

<?php if($vehicle2){ ?>
<td><?= $vehicle2['power_hp'] ?? 'N/A' ?> hp</td>
<?php } ?>

<?php if($vehicle3){ ?>
<td><?= $vehicle3['power_hp'] ?? 'N/A' ?> hp</td>
<?php } ?>

</tr>

<tr>
<td>Torque</td>
<td><?= $vehicle1['torque_nm'] ?? 'N/A' ?> Nm</td>

<?php if($vehicle2){ ?>
<td><?= $vehicle2['torque_nm'] ?? 'N/A' ?> Nm</td>
<?php } ?>

<?php if($vehicle3){ ?>
<td><?= $vehicle3['torque_nm'] ?? 'N/A' ?> Nm</td>
<?php } ?>

</tr>

<tr>
<td>Weight</td>
<td><?= $vehicle1['weight_kg'] ?? 'N/A' ?> kg</td>

<?php if($vehicle2){ ?>
<td><?= $vehicle2['weight_kg'] ?? 'N/A' ?> kg</td>
<?php } ?>

<?php if($vehicle3){ ?>
<td><?= $vehicle3['weight_kg'] ?? 'N/A' ?> kg</td>
<?php } ?>

</tr>

<tr>
<td>Price Range</td>
<td><?= $vehicle1['price_range'] ?? 'N/A' ?></td>

<?php if($vehicle2){ ?>
<td><?= $vehicle2['price_range'] ?? 'N/A' ?></td>
<?php } ?>

<?php if($vehicle3){ ?>
<td><?= $vehicle3['price_range'] ?? 'N/A' ?></td>
<?php } ?>

</tr>

</table>

</div>
<?php if(!$vehicle3){ ?>

<div class="add-vehicle">

<div class="add-icon">+</div>

<h3>Add Another Bike</h3>
<p>Compare two bikes side by side</p>

<input type="text" id="vehicleSearch" placeholder="Search bike brand or model...">

<div id="searchResults"></div>

</div>
<script>

const searchBox = document.getElementById("vehicleSearch");
const results = document.getElementById("searchResults");

if(searchBox){

searchBox.addEventListener("keyup", function(){

let query = this.value;

if(query.length < 2){
results.innerHTML = "";
return;
}

fetch("search_bikes.php?q="+query)
.then(res => res.text())
.then(data => {

results.innerHTML = data;

document.querySelectorAll(".search-item").forEach(item=>{

item.onclick = function(){

let id = this.dataset.id;

let url = new URL(window.location.href);

if(id == url.searchParams.get("v1") || id == url.searchParams.get("v2")){
    return;
}

if(!url.searchParams.get("v2")){
    url.searchParams.set("v2",id);
}
else{
    url.searchParams.set("v3",id);
}

// 🔥 CRITICAL FIX
url.searchParams.set("type", "bike");

window.location.href = url.toString();

}

})

})

})

}

document.querySelector(".add-icon").onclick = function(){
document.getElementById("vehicleSearch").focus();
}

</script>

<?php } ?>


<?php include 'includes/footer.php'; ?>