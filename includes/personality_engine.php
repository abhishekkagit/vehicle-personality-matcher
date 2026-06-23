<?php

/* ============================================================
   NORMALIZATION
============================================================ */

if (!function_exists('normalizeHigher')) {
function normalizeHigher($value,$min,$max)
{
    if($max == $min) return 50;

    $score = (($value-$min)/($max-$min))*100;

    return max(0,min(100,$score));
}
}

if (!function_exists('normalizeLower')) {
function normalizeLower($value,$min,$max)
{
    if($max == $min) return 50;

    $score = 100 - (($value-$min)/($max-$min))*100;

    return max(0,min(100,$score));
}
}


/* ============================================================
   BODY TYPE NORMALIZATION
============================================================ */

function normalizeBodyType($type)
{

$type = strtolower(trim($type));

$map = [

"sporty"=>"sport",
"sports"=>"sport",

"suv"=>"suv",
"crossover"=>"suv",

"sedan"=>"sedan",

"pickup"=>"pickup",
"truck"=>"pickup",

"minivan"=>"minivan",

"wagon"=>"wagon",

"hatchback"=>"hatchback",

"7pass"=>"7pass"

];

return $map[$type] ?? "sedan";

}


/* ============================================================
   BODY TYPE ARCHETYPE
============================================================ */

function vehicleBodyArchetype($type)
{

switch($type)
{

case "sport":
return [95,50,45,70,40];

case "suv":
return [70,85,55,75,90];

case "sedan":
return [65,75,70,85,65];

case "pickup":
return [60,65,40,80,95];

case "minivan":
return [40,95,65,85,100];

case "wagon":
return [60,80,75,85,85];

case "hatchback":
return [55,65,85,80,75];

case "7pass":
return [50,85,60,80,95];

default:
return [65,75,70,80,70];

}

}


/* ============================================================
   BRAND RELIABILITY MAP
============================================================ */

function getCarBrandReliability($brand)
{

$brand = strtolower(trim($brand));

$map = [

"toyota"=>96,
"honda"=>95,
"lexus"=>97,

"mazda"=>94,
"subaru"=>90,

"hyundai"=>88,
"kia"=>88,

"ford"=>82,
"chevrolet"=>80,
"gmc"=>82,

"nissan"=>78,

"volkswagen"=>80,

"bmw"=>85,
"audi"=>84,
"mercedes"=>83,

"volvo"=>90,

"porsche"=>88,

"tesla"=>80,

"jeep"=>75,

"land rover"=>70,

"jaguar"=>72,

"ram"=>80,

];

return $map[$brand] ?? 80;

}


/* ============================================================
   BRAND COMFORT MAP
============================================================ */

function getBrandComfortScore($brand)
{

$brand = strtolower(trim($brand));

$map = [

"lexus"=>95,
"mercedes"=>92,
"bmw"=>88,
"audi"=>88,
"volvo"=>90,

"toyota"=>80,
"honda"=>80,
"mazda"=>78,
"subaru"=>78,

"hyundai"=>75,
"kia"=>75,

"ford"=>72,
"chevrolet"=>70,

"tesla"=>82,

"porsche"=>70,

"jeep"=>70,

];

return $map[$brand] ?? 75;

}


/* ============================================================
   PRICE COMFORT SCORE
============================================================ */

function getPriceComfortScore($priceMin,$priceMax)
{

$avg = ($priceMin + $priceMax) / 2;

if($avg > 80000) return 95;
if($avg > 50000) return 85;
if($avg > 30000) return 75;
if($avg > 20000) return 70;
if($avg > 15000) return 65;

return 60;

}


/* ============================================================
   MAIN SCORING ENGINE
============================================================ */

function calculateVehicleScores($conn,$vehicle)
{

/* ========================
   BODY TYPE
======================== */

$body = normalizeBodyType($vehicle['body_type'] ?? "");
$archetype = vehicleBodyArchetype($body);


/* ========================
   PERFORMANCE
======================== */

$accScore = normalizeLower($vehicle['acc_0_60'] ?? 8,2.5,10);
$hpScore  = normalizeHigher($vehicle['power_hp'] ?? 150,70,500);

$performance =
($performanceSpec*0.8)+($archetype[0]*0.2);


/* ========================
   COMFORT
======================== */

$brandComfort = getBrandComfortScore($vehicle['make'] ?? "");

$priceComfort = getPriceComfortScore(
$vehicle['price_min'] ?? 0,
$vehicle['price_max'] ?? 0
);

$wheelbaseScore = normalizeHigher($vehicle['wheelbase_mm'] ?? 2600,2000,3300);

$weightScore = normalizeLower($vehicle['weight_kg'] ?? 1600,900,3500);

$physicalComfort =
(0.5*$wheelbaseScore)+
(0.5*$weightScore);

$comfortSpec =
(0.70*$archetype[1])+
(0.15*$priceComfort)+
(0.10*$brandComfort)+
(0.05*$physicalComfort);


/* ========================
   EFFICIENCY
======================== */

$avgMPG =
(
($vehicle['city_mpg'] ?? 0)+
($vehicle['highway_mpg'] ?? 0)
)/2;

$effScore = normalizeHigher($avgMPG,10,60);

$efficiencySpec = $effScore;


/* ========================
   RELIABILITY
======================== */

$brandReliability = getCarBrandReliability($vehicle['make'] ?? "");

$hp = $vehicle['power_hp'] ?? 150;
$weight = $vehicle['weight_kg'] ?? 1500;

$stress = ($hp / max($weight,1)) * 100;
$stressPenalty = min($stress,20);

$reliabilitySpec =
(0.7 * $brandReliability) +
(0.3 * (100 - $stressPenalty));


/* ========================
   PRACTICALITY
======================== */

$seatScore = normalizeHigher($vehicle['seating_capacity'] ?? 5,2,8);

switch($body)
{

case "minivan":
$bodyPracticality = 100;
break;

case "suv":
$bodyPracticality = 90;
break;

case "wagon":
$bodyPracticality = 85;
break;

case "pickup":
$bodyPracticality = 95;
break;

case "sedan":
$bodyPracticality = 65;
break;

case "sport":
$bodyPracticality = 40;
break;

case "hatchback":
$bodyPracticality = 75;
break;

default:
$bodyPracticality = 70;

}

$weightScore = normalizeLower($vehicle['weight_kg'] ?? 1600,900,3500);

$practicalitySpec =
($seatScore * 0.35) +
($bodyPracticality * 0.35) +
($efficiencySpec * 0.2) +
($weightScore * 0.1);


/* ========================
   FINAL SCORES
======================== */

$performance =
($performanceSpec*0.6)+($archetype[0]*0.4);

$comfort = $comfortSpec;

$efficiency =
($efficiencySpec*0.6)+($archetype[2]*0.4);

$reliability =
($reliabilitySpec*0.6)+($archetype[3]*0.4);

$practicality =
($practicalitySpec*0.6)+($archetype[4]*0.4);


/* ========================
   CLAMP
======================== */

$performance=min(100,max(0,$performance));
$comfort=min(100,max(0,$comfort));
$efficiency=min(100,max(0,$efficiency));
$reliability=min(100,max(0,$reliability));
$practicality=min(100,max(0,$practicality));


return [

"performance_score"=>round($performance),
"comfort_score"=>round($comfort),
"efficiency_score"=>round($efficiency),
"reliability_score"=>round($reliability),
"practicality_score"=>round($practicality)

];

}


/* ============================================================
   UPDATE VEHICLE SCORES
============================================================ */

function updateVehiclePersonality($conn,$vehicleId)
{

$stmt = $conn->prepare("SELECT * FROM vehicle WHERE id=?");
$stmt->bind_param("i",$vehicleId);
$stmt->execute();

$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if(!$vehicle) return;

$scores = calculateVehicleScores($conn,$vehicle);

$stmt = $conn->prepare("
UPDATE vehicle
SET
performance_score=?,
comfort_score=?,
efficiency_score=?,
reliability_score=?,
practicality_score=?
WHERE id=?
");

$stmt->bind_param(
"iiiiii",
$scores['performance_score'],
$scores['comfort_score'],
$scores['efficiency_score'],
$scores['reliability_score'],
$scores['practicality_score'],
$vehicleId
);

$stmt->execute();

}