<?php

// ==============================
// LOAD API KEYS
// ==============================
$API_KEYS = require __DIR__ . '/../config/api_keys.php';
$API_NINJAS_KEY = $API_KEYS['api_ninjas'] ?? null;

if (!$API_NINJAS_KEY) {
    throw new Exception("API Ninjas key missing");
}

// ==============================
// DEPENDENCIES
// ==============================
require_once __DIR__ . '/image_fetcher.php';

// ==============================
// HELPERS
// ==============================
function extractNumber($value)
{
    if ($value === null) return null;
    if (is_numeric($value)) return (float)$value;
    if (preg_match('/([\d]+(\.\d+)?)/', $value, $m)) {
        return (float)$m[1];
    }
    return null;
}

function normalizeSeatHeight($value)
{
    if (!$value) return null;

    if (preg_match('/([\d\.]+)\s*mm/i', $value, $m)) {
        return (float)$m[1];
    }

    if (preg_match('/([\d\.]+)\s*inch/i', $value, $m)) {
        return round((float)$m[1] * 25.4, 1);
    }

    return extractNumber($value);
}

function normalizeCategory($type)
{
    if (!$type) return null;
    return ucfirst(strtolower(trim($type)));
}

function normalizeNinjasBike(array $n): array
{
    return [
        'category'        => normalizeCategory($n['type'] ?? null),
        'displacement_cc' => extractNumber($n['displacement'] ?? null),
        'power_hp'        => extractNumber($n['power'] ?? null),
        'torque_nm'       => extractNumber($n['torque'] ?? null),
        'seat_height_mm'  => normalizeSeatHeight($n['seat_height'] ?? null),
        'weight_kg'       => extractNumber($n['dry_weight'] ?? ($n['total_weight'] ?? null)),
        'year'            => isset($n['year']) ? (int)$n['year'] : null,
    ];
}

// ==============================
// MAIN CACHE FUNCTION
// ==============================
function cacheBikeIfNotExists(mysqli $conn, string $brand, string $model): array
{
    global $API_NINJAS_KEY;

    $brand = trim($brand);
    $model = trim($model);

    $ninjasUrl = "https://api.api-ninjas.com/v1/motorcycles?" . http_build_query([
        'make'  => $brand,
        'model' => $model
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "X-Api-Key: {$API_NINJAS_KEY}"
        ]
    ]);

    $json = @file_get_contents($ninjasUrl, false, $context);
    if (!$json) {
        return ['status' => 'api_failed'];
    }

    $ninjas = json_decode($json, true);
    if (empty($ninjas)) {
        return ['status' => 'no_results'];
    }

    // ==============================
    // FIND & MERGE EXACT MODEL MATCHES
    // ==============================
    $exactMatches = [];

    foreach ($ninjas as $raw) {
        if (!isset($raw['model'])) continue;

        if (strtolower(trim($raw['model'])) === strtolower($model)) {
            $exactMatches[] = $raw;
        }
    }

    if (empty($exactMatches)) {
        return ['status' => 'no_exact_match'];
    }

    // Merge duplicates intelligently
    $merged = $exactMatches[0];

    foreach ($exactMatches as $candidate) {

        foreach ([
            'power',
            'torque',
            'displacement',
            'seat_height',
            'dry_weight',
            'total_weight',
            'type',
            'year'
        ] as $field) {

            if (
                (empty($merged[$field]) || $merged[$field] === null) &&
                !empty($candidate[$field])
            ) {
                $merged[$field] = $candidate[$field];
            }
        }
    }

    $fullModel = trim($merged['model']);

    // Already exists?
    $check = $conn->prepare(
        "SELECT id FROM bikes WHERE brand = ? AND model = ? LIMIT 1"
    );
    $check->bind_param("ss", $brand, $fullModel);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        return [
            'status' => 'exists',
            'id'     => $existing['id']
        ];
    }

    $norm = normalizeNinjasBike($merged);

    // ==============================
    // 🚨 MANDATORY FIELD CHECK
    // ==============================
    $mandatoryFields = [
        $norm['category'],
        $norm['displacement_cc'],
        $norm['power_hp'],
        $norm['torque_nm'],
        $norm['weight_kg']
    ];

    foreach ($mandatoryFields as $field) {
        if ($field === null) {
            return ['status' => 'missing_mandatory_fields'];
        }
    }

    // ==============================
    // INSERT BIKE
    // ==============================
    $insert = $conn->prepare(
        "INSERT INTO bikes (
            brand,
            model,
            category,
            displacement_cc,
            power_hp,
            torque_nm,
            weight_kg,
            seat_height_mm,
            price_range,
            image_url,
            extra_specs,
            data_source,
            api_cached_at,
            year
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Unknown', NULL, ?, 'api_ninjas', NOW(), ?)"
    );

    $extraSpecs = json_encode(
        ['api_ninjas' => $merged],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $insert->bind_param(
        "sssddddssi",
        $brand,
        $fullModel,
        $norm['category'],
        $norm['displacement_cc'],
        $norm['power_hp'],
        $norm['torque_nm'],
        $norm['weight_kg'],
        $norm['seat_height_mm'],
        $extraSpecs,
        $norm['year']
    );

    if (!$insert->execute()) {
        return ['status' => 'db_insert_failed'];
    }

    $bikeId = $conn->insert_id;

    // ==============================
    // IMAGE SECTION
    // ==============================
    $imageData = fetchBikeImagesSmart($brand, $fullModel);

    if (!empty($imageData['images'])) {

        $images = array_slice($imageData['images'], 0, 3);

        $imgStmt = $conn->prepare(
            "INSERT INTO bike_images (bike_id, image_url, image_type, source)
             VALUES (?, ?, ?, ?)"
        );

        foreach ($images as $i => $img) {

            $type = ($i === 0) ? 'main' : 'gallery';

            $imgStmt->bind_param("isss", $bikeId, $img, $type, $imageData['source']);
            $imgStmt->execute();

            if ($i === 0) {
                $upd = $conn->prepare(
                    "UPDATE bikes SET image_url = ? WHERE id = ?"
                );
                $upd->bind_param("si", $img, $bikeId);
                $upd->execute();
            }
        }
    }

    return [
        'status' => 'cached',
        'id'     => $bikeId,
        'model'  => $fullModel
    ];
}
