<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

include 'includes/db.php';
include 'includes/header.php';
include 'includes/gemini_feedback.php';

function formatTraitLabel(string $trait): string
{
    return ucfirst(str_replace('_', ' ', $trait));
}

function buildFallbackFeedback(string $name, int $compatibility, array $matches, array $concerns): string
{
    if ($compatibility >= 80) {
        $tone = $name . " looks like a strong fit for your overall driving profile.";
    } elseif ($compatibility >= 60) {
        $tone = $name . " is a decent match, but it comes with a few trade-offs.";
    } else {
        $tone = $name . " may not be the most natural fit for your current preferences.";
    }

    if (!empty($matches)) {
        $tone .= " It aligns best with your preference for " . implode(', ', $matches) . ".";
    }

    if (!empty($concerns)) {
        $tone .= " The main areas to think about are " . implode(', ', $concerns) . ".";
    } else {
        $tone .= " No major mismatch stands out from the quiz.";
    }

    return $tone;
}

function getVehicleName(array $vehicle, string $type): string
{
    return $type === 'bike'
        ? trim(($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? ''))
        : trim(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? ''));
}

function getVehicleSubtitle(array $vehicle, string $type): string
{
    if ($type === 'bike') {
        return trim((string) ($vehicle['category'] ?? ''));
    }

    return trim((string) (($vehicle['body_type'] ?? '') ?: ($vehicle['size_class'] ?? '')));
}

function getVehiclePlaceholder(string $type): string
{
    return $type === 'bike'
        ? 'assets/images/bikes/placeholder.jpg'
        : 'assets/images/cars/placeholder.jpg';
}

function getRegretLabel(int $compatibility): string
{
    if($compatibility > 80){
    return "Low";
    }
    elseif($compatibility > 60){
    return "Moderate";
    }

    return "High";
}

function getTopTraitLabels(array $user, array $vehicleVector, int $limit = 2): array
{
    $differences = [];

    foreach ($user as $key => $value) {
        $differences[$key] = abs($value - $vehicleVector[$key]);
    }

    asort($differences);

    return array_map(
        'formatTraitLabel',
        array_slice(array_keys($differences), 0, $limit)
    );
}

function buildAlternativeReason(array $user, array $vehicleVector, int $compatibility): string
{
    $topTraits = getTopTraitLabels($user, $vehicleVector);

    if (empty($topTraits)) {
        return "Balanced backup option based on your current profile.";
    }

    $traitText = implode(' and ', $topTraits);

    if ($compatibility >= 80) {
        return "Strong backup option with the closest fit in " . $traitText . ".";
    }

    if ($compatibility >= 65) {
        return "Balanced alternative, especially if you value " . $traitText . ".";
    }

    return "Worth considering if your priority is " . $traitText . ".";
}

/* ===============================
   RECEIVE QUIZ DATA
================================ */

if(!isset($_GET['show'])){

$type = $_POST['vehicle_type'] ?? '';
$id   = (int)($_POST['vehicle_id'] ?? 0);

if(!$type || !$id){
    die("Invalid request");
}

/* ===============================
   USER PERSONALITY VECTOR
================================ */

/* ===============================
   ROAD TYPE NORMALIZATION
================================ */

$roadScore = match((int)($_POST['road_type'] ?? 3)){
    1 => 35,
    2 => 55,
    3 => 72,
    4 => 95,
    default => 70
};

/* ===============================
   USER PERSONALITY VECTOR (FIXED)
================================ */

$user = [

'performance' => (
    (int)$_POST['performance'] * 0.85 +
    (int)$_POST['usage'] * 0.15
),

'comfort' => (
    (int)$_POST['comfort'] * 0.60 +
    $roadScore * 0.40
),

'efficiency' => (
    (int)$_POST['mileage'] * 0.75 +
    (int)$_POST['usage'] * 0.25
),

'practicality' => (
    (int)$_POST['practicality'] * 0.45 +
    (int)$_POST['passengers'] * 0.35 +
    $roadScore * 0.20
),

'reliability' => (
    (int)$_POST['maintenance'] * 0.50 +
    (int)$_POST['ownership'] * 0.20 +
    (int)$_POST['cost_sensitivity'] * 0.30
)

];

$_SESSION['performance']  = $user['performance'];
$_SESSION['comfort']      = $user['comfort'];
$_SESSION['efficiency']   = $user['efficiency'];
$_SESSION['reliability']  = $user['reliability'];
$_SESSION['practicality'] = $user['practicality'];

$_SESSION['quiz_vehicle_type'] = $type;
$_SESSION['quiz_vehicle_id']   = $id;

/* redirect once */



}

$type = $_SESSION['quiz_vehicle_type'] ?? '';
$id   = $_SESSION['quiz_vehicle_id'] ?? 0;

$user = [
'performance'  => $_SESSION['performance'],
'comfort'      => $_SESSION['comfort'],
'efficiency'   => $_SESSION['efficiency'],
'reliability'  => $_SESSION['reliability'],
'practicality' => $_SESSION['practicality']
];
/* ===============================
   FETCH VEHICLE
================================ */

if($type === 'bike'){

$stmt = $conn->prepare("SELECT * FROM bikes WHERE id=?");
$stmt->bind_param("i",$id);

}else{

$stmt = $conn->prepare("SELECT * FROM vehicle WHERE id=?");
$stmt->bind_param("i",$id);

}

$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows === 0){
die("Vehicle not found");
}

$vehicle = $res->fetch_assoc();

/* ===============================
   REAL TIME MAINTENANCE SCORE
================================ */

function calculateMaintenance($vehicle,$type){

if($type === 'bike'){

$cc = $vehicle['displacement_cc'] ?? 150;
$hp = $vehicle['power_hp'] ?? 10;
$weight = $vehicle['weight_kg'] ?? 150;

$score = 100 - (
($cc * 0.03) +
($hp * 0.8) +
($weight * 0.05)
);

}else{

$mpg = $vehicle['city_mpg'] ?? 25;
$weight = $vehicle['weight_kg'] ?? 1500;

$score = ($mpg * 2) - ($weight * 0.01);

}

return max(20,min(100,round($score)));

}

$maintenanceScore = calculateMaintenance($vehicle,$type);

/* ===============================
   VEHICLE PERSONALITY VECTOR
================================ */

$vehicleVector = [

'performance'  => $vehicle['performance_score'] ?? 50,
'comfort'      => $vehicle['comfort_score'] ?? 50,
'efficiency'   => $vehicle['efficiency_score'] ?? 50,
'practicality' => $vehicle['practicality_score'] ?? 50,

'reliability'  => (
    ($vehicle['reliability_score'] ?? 50) +
    $maintenanceScore
)/2

];

/* ===============================
   DISTANCE SCORE
================================ */

$distance = 0;

foreach($user as $key=>$value){

    $diff = abs($value - $vehicleVector[$key]);

    if ($diff > 40) {
    $distance += $diff * 1.3;   // strong penalty
}
elseif ($diff > 20) {
    $distance += $diff * 1.15;  // 🔥 NEW: medium penalty
}
else {
    $distance += $diff;
}
}

// penalties
if (abs($user['performance'] - $vehicleVector['performance']) > 40) {
    $distance += 60;
}

if (abs($user['reliability'] - $vehicleVector['reliability']) > 40) {
    $distance += 50;
}

$maxDistance = count($user) * 180;
$maxDistance = count($user) * 180;

$distanceScore = 100 - (($distance / $maxDistance) * 100);

/* ===============================
   COSINE SIMILARITY
================================ */

function cosineSimilarity($A,$B){

$dot = 0;
$magA = 0;
$magB = 0;

foreach($A as $key=>$value){

$dot  += $A[$key] * $B[$key];
$magA += pow($A[$key],2);
$magB += pow($B[$key],2);

}

return $dot / (sqrt($magA) * sqrt($magB));

}

function calculateAlternativeCompatibilityDetails($user,$vehicle,$type){

$maintenanceScore = calculateMaintenance($vehicle,$type);

$vehicleVector = [

'performance'  => $vehicle['performance_score'] ?? 50,
'comfort'      => $vehicle['comfort_score'] ?? 50,
'efficiency'   => $vehicle['efficiency_score'] ?? 50,
'practicality' => $vehicle['practicality_score'] ?? 50,

'reliability'  => (
    ($vehicle['reliability_score'] ?? 50) +
    $maintenanceScore
)/2

];

$distance = 0;

foreach($user as $key=>$value){

    $diff = abs($value - $vehicleVector[$key]);

    if ($diff > 40) {
    $distance += $diff * 1.3;
}
elseif ($diff > 20) {
    $distance += $diff * 1.15;
}
else {
    $distance += $diff;
}
}

if (abs($user['performance'] - $vehicleVector['performance']) > 40) {
    $distance += 60;
}

if (abs($user['reliability'] - $vehicleVector['reliability']) > 40) {
    $distance += 50;
}

$maxDistance = count($user) * 180;
$distanceScore = 100 - (($distance / $maxDistance) * 100);
$compatibility = round($distanceScore);

if($user['performance'] < 50 && $vehicleVector['performance'] > 80){
    $compatibility -= 25;
}

if($user['practicality'] > 80 && $vehicleVector['practicality'] < 50){
    $compatibility -= 20;
}

if($user['efficiency'] > 80 && $vehicleVector['efficiency'] < 50){
    $compatibility -= 15;
}

$compatibility = max(0, min(100, $compatibility));

if ($distance > 400) {
    $compatibility -= 10;
}

return [
    'compatibility' => $compatibility,
    'vehicle_vector' => $vehicleVector
];

}

function fetchTopAlternatives($conn, $user, $type, $selectedId, $limit = 3){

if($type === 'bike'){

$stmt = $conn->prepare("
SELECT
    b.*,
    COALESCE(
        (
            SELECT bi.image_url
            FROM bike_images bi
            WHERE bi.bike_id = b.id
            ORDER BY bi.image_type='main' DESC
            LIMIT 1
        ),
        b.image_url
    ) AS thumb_image
FROM bikes b
WHERE b.id <> ?
");

}else{

$stmt = $conn->prepare("
SELECT
    v.*,
    (
        SELECT vi.image_path
        FROM vehicle_images vi
        WHERE vi.vehicle_id = v.id
        ORDER BY vi.created_at ASC
        LIMIT 1
    ) AS thumb_image
FROM vehicle v
WHERE v.id <> ?
");

}

$stmt->bind_param("i",$selectedId);
$stmt->execute();
$res = $stmt->get_result();
$alternatives = [];

while($candidate = $res->fetch_assoc()){

    $details = calculateAlternativeCompatibilityDetails($user,$candidate,$type);
    $traits = getTopTraitLabels($user, $details['vehicle_vector']);
    $image = $candidate['thumb_image'] ?? '';

    if(!$image){
        $image = getVehiclePlaceholder($type);
    }

    $alternatives[] = [
        'id' => (int)$candidate['id'],
        'name' => getVehicleName($candidate, $type),
        'subtitle' => getVehicleSubtitle($candidate, $type),
        'image' => $image,
        'compatibility' => $details['compatibility'],
        'regret' => getRegretLabel($details['compatibility']),
        'traits' => $traits,
        'reason' => buildAlternativeReason($user, $details['vehicle_vector'], $details['compatibility'])
    ];

}

usort($alternatives, function($left, $right){

    if ($left['compatibility'] === $right['compatibility']) {
        return strcmp($left['name'], $right['name']);
    }

    return $right['compatibility'] <=> $left['compatibility'];
});

return array_slice($alternatives, 0, $limit);

}


/* ===============================
   FINAL COMPATIBILITY
================================ */

$compatibility = round($distanceScore);

// 🔥 HARD MISMATCH PENALTIES

// performance mismatch (user wants low, bike is high)
if($user['performance'] < 50 && $vehicleVector['performance'] > 80){
    $compatibility -= 25;
}

// practicality mismatch
if($user['practicality'] > 80 && $vehicleVector['practicality'] < 50){
    $compatibility -= 20;
}

// efficiency mismatch
if($user['efficiency'] > 80 && $vehicleVector['efficiency'] < 50){
    $compatibility -= 15;
}

// 🔥 FINAL CLAMP (VERY IMPORTANT)
$compatibility = max(0, min(100, $compatibility));



// 🔥 extra clamp for unrealistic matches
if ($distance > 400) {
    $compatibility -= 10;
}
/* ===============================
   SAVE QUIZ RESULT
================================ */


/* VEHICLE NAME FIRST */
$name = ($type === 'bike')
? $vehicle['brand']." ".$vehicle['model']
: $vehicle['make']." ".$vehicle['model'];

/* SAVE RESULT */
if(!isset($_GET['show'])){

    // ... your calculations

    $user_id = $_SESSION['user_id'] ?? null;

    if($user_id){

        $name = ($type === 'bike')
        ? $vehicle['brand']." ".$vehicle['model']
        : $vehicle['make']." ".$vehicle['model'];

        $stmt = $conn->prepare("
            INSERT INTO quiz_results (user_id, personality, vehicle_name, match_score)
            VALUES (?, ?, ?, ?)
        ");

        $personality = json_encode($user);
        $stmt->bind_param("issi", $user_id, $personality, $name, $compatibility);
        $stmt->execute();
    }

    header("Location: result.php?show=1");
    exit;
}

/* ===============================
   REGRET PREDICTION
================================ */

if($compatibility > 80){
$regret = "Low";
}
elseif($compatibility > 60){
$regret = "Moderate";
}
else{
$regret = "High";
}

/* ===============================
   VEHICLE NAME
================================ */

$name = ($type === 'bike')
? $vehicle['brand']." ".$vehicle['model']
: $vehicle['make']." ".$vehicle['model'];

$matchedTraits = [];
$concernTraits = [];

foreach ($user as $key => $value) {
    $vehicleValue = $vehicleVector[$key];
    $label = strtolower(formatTraitLabel($key));

    if (abs($value - $vehicleValue) <= 20) {
        $matchedTraits[] = $label;
    }

    if (abs($value - $vehicleValue) > 30) {
        $concernTraits[] = $label;
    }
}

$feedbackSignature = sha1(json_encode([
    'vehicle' => $name,
    'type' => $type,
    'compatibility' => $compatibility,
    'user' => $user,
    'vehicle_vector' => $vehicleVector,
]));

$topAlternatives = fetchTopAlternatives($conn, $user, $type, (int)$id);
$comparePage = $type === 'bike' ? 'compare_bikes.php' : 'compare.php';




?>

<div class="quiz-wrapper">

<h2 class="quiz-title">Compatibility Results</h2>
<p class="quiz-sub">For: <?= htmlspecialchars($name) ?></p>

<div class="result-card">

<div class="result-icon">✔</div>

<div class="score">
<?= $compatibility ?>
</div>

<p class="score-label">Compatibility Score</p>

<div class="progress-bar">
<div class="progress-fill" style="width:<?= $compatibility ?>%"></div>
</div>

<p class="regret">
Regret Prediction:
<span class="tag"><?= $regret ?></span>
</p>

</div>


<div class="result-box success">

<h3>What Matches Your Profile</h3>

<ul>

<?php

if (empty($matchedTraits)) {
echo "<li>No perfect overlap was detected, but there are still some usable strengths in this match.</li>";
}

foreach($matchedTraits as $trait){
echo "<li>" . htmlspecialchars(formatTraitLabel($trait)) . " matches your preference</li>";
}

?>

</ul>

</div>


<div class="result-box warning">

<h3>Potential Concerns</h3>

<ul>


<?php
if(empty($concernTraits)){
echo "<li>No major concerns detected.</li>";
} else {
foreach($concernTraits as $trait){
echo "<li>" . htmlspecialchars(formatTraitLabel($trait)) . " may not fully match your expectation</li>";
}
}
?>
</ul>

</div>

<?php if(!empty($topAlternatives)): ?>

<div class="result-box alternatives-box">

<h3>Top 3 Alternatives</h3>
<p class="alternatives-intro">Other same-type options that also align well with your profile.</p>

<div class="alternatives-grid">

<?php foreach($topAlternatives as $index => $alternative): ?>

<article class="alternative-card">

<img
src="<?= htmlspecialchars($alternative['image']) ?>"
alt="<?= htmlspecialchars($alternative['name']) ?>"
class="alternative-image"
loading="lazy"
>

<div class="alternative-body">

<div class="alternative-meta">
<span class="alternative-rank">Alternative <?= $index + 1 ?></span>
<span class="alternative-score"><?= (int)$alternative['compatibility'] ?>%</span>
</div>

<h4><?= htmlspecialchars($alternative['name']) ?></h4>

<?php if(!empty($alternative['subtitle'])): ?>
<p class="alternative-subtitle"><?= htmlspecialchars($alternative['subtitle']) ?></p>
<?php endif; ?>

<p class="alternative-reason"><?= htmlspecialchars($alternative['reason']) ?></p>

<div class="alternative-traits">
<?php foreach($alternative['traits'] as $trait): ?>
<span class="trait-chip"><?= htmlspecialchars($trait) ?></span>
<?php endforeach; ?>
<span class="trait-chip regret-chip"><?= htmlspecialchars($alternative['regret']) ?> regret</span>
</div>

<div class="alternative-actions">
<a href="vehicle-details.php?type=<?= urlencode($type) ?>&id=<?= $alternative['id'] ?>" class="alt-link-primary">View Details</a>
<a href="<?= htmlspecialchars($comparePage) ?>?v1=<?= $id ?>&v2=<?= $alternative['id'] ?>" class="alt-link-secondary">Compare</a>
</div>

</div>

</article>

<?php endforeach; ?>

</div>

</div>

<?php endif; ?>


<div class="result-actions">

<a href="vehicle-details.php?type=<?= $type ?>&id=<?= $id ?>" class="btn blue">
View Vehicle Details
</a>

<a href="vehicles.php?type=<?= $type ?>&similar=<?= $id ?>" class="btn green">
View Similar Vehicles
</a>

<a href="quiz.php?type=<?= $type ?>&id=<?= $id ?>" class="btn grey">
Retake Quiz
</a>

</div>

</div>

<?php include 'includes/footer.php'; ?>
