<?php

/* =========================
   NORMALIZATION FUNCTIONS
========================= */

function normalizeHigher($value,$min,$max)
{
    if($max == $min) return 50;
    return (($value-$min)/($max-$min))*100;
}

function normalizeLower($value,$min,$max)
{
    if($max == $min) return 50;
    return 100 - (($value-$min)/($max-$min))*100;
}


/* =========================
   CATEGORY NORMALIZATION
========================= */

function normalizeBikeCategory($category)
{
    $category = strtolower(trim($category));

    $map = [

        'sport' => 'sport',
        'naked bike' => 'sport',

        'allround' => 'commuter',
        'commuter' => 'commuter',

        'classic' => 'cruiser',
        'custom/cruiser' => 'cruiser',

        'touring' => 'touring',

        'enduro/offroad' => 'adventure',
        'Adventure' => 'adventure'

    ];

    return $map[$category] ?? 'commuter';
}


/* =========================
   CATEGORY ARCHETYPES
========================= */

function bikeCategoryArchetype($category)
{

switch($category)
{

case "sport":
return [95,30,45,70,25];

case "cruiser":
return [70,90,55,80,75];

case "touring":
return [75,95,60,82,90];

case "adventure":
return [80,85,65,85,92];

case "commuter":
return [55,80,95,90,95];

default:
return [65,75,70,80,80];

}

}


/* =========================
   BRAND RELIABILITY MAP
========================= */

function getBikeBrandReliability($brand)
{

$brand = strtolower(trim($brand));

$map = [

'honda'=>95,
'yamaha'=>92,
'suzuki'=>90,
'kawasaki'=>88,

'hero'=>93,
'bajaj'=>88,
'tvs'=>90,
'royal enfield'=>82,

'ktm'=>80,
'husqvarna'=>78,
'bmw'=>70,
'triumph'=>82,

'ducati'=>70,
'aprilia'=>74,
'moto guzzi'=>80,
'mv agusta'=>72,

'harley-davidson'=>78,
'indian'=>80,

'benelli'=>70,
'cfmoto'=>76,
'zontes'=>72,

'vespa'=>85,
'piaggio'=>82,

'kymco'=>86,
'sym'=>85,

'zero'=>88,
'energica'=>84,

'norton'=>70,
'bsa'=>78,

'gasgas'=>76,
'beta'=>74,

'sherco'=>72,
'rieju'=>70,

'derbi'=>75,

'um motorcycles'=>68,

'lifan'=>65,
'loncin'=>70,

'keeway'=>72,
'qjmotor'=>75,

'ather'=>92,
'ola electric'=>85,
'ultraviolette'=>86,

'tork motors'=>82,
'revolt'=>83,

'ampere'=>80,

'matter'=>84,

'simple energy'=>85,

'yezdi'=>80,
'jawa'=>82,

'mahindra'=>78,

'motron'=>70,

'horwin'=>75,

'vmoto'=>78,

'segway'=>80,

'talaria'=>82

];

return $map[$brand] ?? 75;

}


/* =========================
   MAIN SCORING ENGINE
========================= */

function calculateBikeScores($conn,$bike)
{

$extra = json_decode($bike['extra_specs'] ?? '{}',true);


/* =========================
   CATEGORY
========================= */

$category = normalizeBikeCategory($bike['category'] ?? '');
$archetype = bikeCategoryArchetype($category);


/* =========================
   PERFORMANCE
========================= */

$hp_score = normalizeHigher($bike['power_hp'] ?? 0,50,150);
$torque_score = normalizeHigher($bike['torque_nm'] ?? 0,50,200);
$weight_score = normalizeLower($bike['weight_kg'] ?? 200,150,400);

$performanceSpec =
(0.5 * $hp_score) +
(0.3 * $torque_score) +
(0.2 * $weight_score);


/* =========================
   COMFORT
========================= */

$weight_score = normalizeLower($bike['weight_kg'] ?? 200,100,300);
$seat_score = normalizeLower($bike['seat_height_mm'] ?? 800,700,900);

$comfortSpec =
($weight_score*0.4)+
($seat_score*0.6);

/* category dominates comfort */
$comfort =
($comfortSpec*0.7)+($archetype[1]*0.3);



/* =========================
   EFFICIENCY
========================= */

$eff_cc = normalizeLower($bike['displacement_cc'] ?? 500,100,1300);
$eff_weight = normalizeLower($bike['weight_kg'] ?? 200,100,300);

/* engine stress penalty */
$engineStress =
($bike['power_hp'] ?? 0) /
max(($bike['displacement_cc'] ?? 1),1);

$stressPenalty = min($engineStress*80,25);

$efficiencySpec =
($eff_cc*0.55)+
($eff_weight*0.25)+
((100-$stressPenalty)*0.20);


/* =========================
   RELIABILITY
========================= */

$brandReliability = getBikeBrandReliability($bike['brand'] ?? '');

$engineStress =
($bike['power_hp'] ?? 0) /
max(($bike['displacement_cc'] ?? 1),1);

$enginePenalty = min($engineStress*100,20);

$reliabilitySpec =
(0.5*$brandReliability)+
(0.3*(100-$enginePenalty))+
(0.2*60);
// 🔥 penalize extreme sport bikes
if(($bike['power_hp'] ?? 0) > 150){
    $reliabilitySpec -= 25;
}

/* ABS bonus */

if(!empty($extra['abs']))
$reliabilitySpec += 2;

/* =========================
   PRACTICALITY (FIXED)
========================= */

/* lighter = more practical in city */
$weightScore = normalizeLower($bike['weight_kg'] ?? 200,100,300);

/* efficiency already calculated above */
$effScore = $efficiencySpec;

/* comfort already calculated */
$comfortScore = $comfort;

/* category base */
switch($category)
{
case "commuter":   $base = 90; break;
case "adventure":  $base = 85; break;
case "touring":    $base = 80; break;
case "cruiser":    $base = 70; break;
case "sport":      $base = 55; break;
default:           $base = 75;
}

/* final practicality */
$practicalitySpec =
($base * 0.4) +
($effScore * 0.3) +
($comfortScore * 0.2) +
($weightScore * 0.1);

/* =========================
   BLEND WITH ARCHETYPE
========================= */

$performance =
($performanceSpec*0.80)+($archetype[0]*0.20);
// 🔥 minimum realistic performance floor for low-power bikes
if(($bike['power_hp'] ?? 0) < 15){
    $performance = max($performance, 12);
}
$efficiency =
($efficiencySpec*0.80)+($archetype[2]*0.20);

$reliability =
($reliabilitySpec*0.80)+($archetype[3]*0.20);

// 🔥 penalize impractical sport bikes (MOVE HERE FIRST)
if($category === "sport"){
    $practicalitySpec -= 25;
}

// THEN calculate final practicality
$practicality =
($practicalitySpec*0.85)+($archetype[4]*0.15);

// 🔥 FINAL SPORT REALISM FIX
if($category === "sport"){
    $practicality = min($practicality, 35);
}
/* =========================
   CLAMP VALUES
========================= */

$performance = ($performance > 100) ? 95 : max(0, $performance);
$comfort=min(100,max(0,$comfort));
$efficiency=min(100,max(0,$efficiency));
$reliability=min(100,max(0,$reliability));
$practicality=min(100,max(0,$practicality));


return [

'performance_score'=>round($performance),
'comfort_score'=>round($comfort),
'efficiency_score'=>round($efficiency),
'reliability_score'=>round($reliability),
'practicality_score'=>round($practicality)

];

}