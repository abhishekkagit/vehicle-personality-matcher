<?php

function calculateMaintenance($vehicle, $type)
{
    if ($type === 'bike') {
        $cc = $vehicle['displacement_cc'] ?? 150;
        $hp = $vehicle['power_hp'] ?? 10;
        $weight = $vehicle['weight_kg'] ?? 150;

        $score = 100 - (
            ($cc * 0.03) +
            ($hp * 0.8) +
            ($weight * 0.05)
        );
    } else {
        $mpg = $vehicle['city_mpg'] ?? 25;
        $weight = $vehicle['weight_kg'] ?? 1500;

        $score = ($mpg * 2) - ($weight * 0.01);
    }

    return max(20, min(100, round($score)));
}

function buildCompatibilityVehicleVector($vehicle, $type)
{
    $maintenanceScore = calculateMaintenance($vehicle, $type);

    return [
        'performance' => $vehicle['performance_score'] ?? 50,
        'comfort' => $vehicle['comfort_score'] ?? 50,
        'efficiency' => $vehicle['efficiency_score'] ?? 50,
        'practicality' => $vehicle['practicality_score'] ?? 50,
        'reliability' => (
            ($vehicle['reliability_score'] ?? 50) +
            $maintenanceScore
        ) / 2
    ];
}

function calculateCompatibilityDetails($user, $vehicle, $type)
{
    $maintenanceScore = calculateMaintenance($vehicle, $type);
    $vehicleVector = buildCompatibilityVehicleVector($vehicle, $type);
    $distance = 0;

    foreach ($user as $key => $value) {
        $diff = abs($value - $vehicleVector[$key]);

        if ($diff > 40) {
            $distance += $diff * 1.3;
        } elseif ($diff > 20) {
            $distance += $diff * 1.15;
        } else {
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

    if ($user['performance'] < 50 && $vehicleVector['performance'] > 80) {
        $compatibility -= 25;
    }

    if ($user['practicality'] > 80 && $vehicleVector['practicality'] < 50) {
        $compatibility -= 20;
    }

    if ($user['efficiency'] > 80 && $vehicleVector['efficiency'] < 50) {
        $compatibility -= 15;
    }

    $compatibility = max(0, min(100, $compatibility));

    if ($distance > 400) {
        $compatibility -= 10;
    }

    return [
        'compatibility' => $compatibility,
        'vehicle_vector' => $vehicleVector,
        'maintenance_score' => $maintenanceScore,
        'distance' => $distance,
        'distance_score' => $distanceScore
    ];
}

function calculateCompareCompatibility($user, $vehicle, $type)
{
    $details = calculateCompatibilityDetails($user, $vehicle, $type);

    return $details['compatibility'];
}
