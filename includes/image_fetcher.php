<?php

// ==================================================
// MODEL HELPERS
// ==================================================

function getBaseModel(string $model): string
{
    $parts = preg_split('/\s+/', trim($model));
    return count($parts) >= 2 ? ($parts[0] . ' ' . $parts[1]) : $model;
}

function getBikeSpecsSearchModel(string $model): string
{
    $parts = preg_split('/\s+/', trim($model));
    return implode(' ', array_slice($parts, 0, 3));
}

// ==================================================
// BIKE SPECS (UNCHANGED - DO NOT TOUCH)
// ==================================================

function fetchBikeSpecsImages(string $brand, string $model): array
{
    $searchModel = getBikeSpecsSearchModel($model);
    $query = urlencode("$brand $searchModel");

    $url = "https://www.bikespecs.org/api/v1/search?q={$query}";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0\r\n"
        ]
    ]);

    $json = @file_get_contents($url, false, $context);
    if (!$json) return [];

    $data = json_decode($json, true);
    if (empty($data['data'])) return [];

    $bestMatch = null;
    $latestYear = 0;

    foreach ($data['data'] as $item) {

        if (empty($item['images'])) continue;

        $year = isset($item['year']) ? (int)$item['year'] : 0;

        if ($year >= $latestYear) {
            $latestYear = $year;
            $bestMatch  = $item;
        }
    }

    if (!$bestMatch) return [];

    return array_values(array_unique($bestMatch['images']));
}

// ==================================================
// WIKIMEDIA COMMONS (GENERIC SEARCH)
// ==================================================

function fetchCommonsImages(string $searchTerm): array
{
    $url = "https://commons.wikimedia.org/w/api.php?" . http_build_query([
        'action'       => 'query',
        'format'       => 'json',
        'generator'    => 'search',
        'gsrsearch'    => $searchTerm,
        'gsrnamespace' => 6,
        'gsrlimit'     => 20,
        'prop'         => 'imageinfo',
        'iiprop'       => 'url|timestamp',
        'origin'       => '*'
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0\r\n"
        ]
    ]);

    $json = @file_get_contents($url, false, $context);
    if (!$json) return [];

    $data = json_decode($json, true);
    if (empty($data['query']['pages'])) return [];

    $images = [];

    foreach ($data['query']['pages'] as $page) {

        $info = $page['imageinfo'][0] ?? null;
        if (!$info) continue;

        $img = $info['url'] ?? null;
        $timestamp = $info['timestamp'] ?? null;

        if (!$img) continue;
        if (!preg_match('/\.(jpg|jpeg|png)$/i', $img)) continue;

        $images[] = [
            'url'       => $img,
            'timestamp' => strtotime($timestamp)
        ];
    }

    // Sort newest first
    usort($images, function ($a, $b) {
        return $b['timestamp'] <=> $a['timestamp'];
    });

    $urls = array_column($images, 'url');

    return array_slice(array_values(array_unique($urls)), 0, 3);
}

// ==================================================
// BIKE SMART FETCHER (UNCHANGED)
// ==================================================

function fetchBikeImagesSmart(string $brand, string $fullModel): array
{
    $images = fetchBikeSpecsImages($brand, $fullModel);
    if (!empty($images)) {
        return ['source' => 'bikespecs', 'images' => $images];
    }

    $images = fetchCommonsImages("$brand $fullModel");
    if (!empty($images)) {
        return ['source' => 'wikimedia_commons', 'images' => $images];
    }

    $baseModel = getBaseModel($fullModel);

    if ($baseModel !== $fullModel) {

        $images = fetchBikeSpecsImages($brand, $baseModel);
        if (!empty($images)) {
            return ['source' => 'bikespecs_fallback', 'images' => $images];
        }

        $images = fetchCommonsImages("$brand $baseModel");
        if (!empty($images)) {
            return ['source' => 'wikimedia_commons_fallback', 'images' => $images];
        }
    }

    return ['source' => null, 'images' => []];
}

// ==================================================
// CAR IMAGE FETCHER (WIKIMEDIA ONLY)
// ==================================================

function fetchCarImages(string $brand, string $model): array
{
    // 1️⃣ Try full model
    $images = fetchCommonsImages("$brand $model");
    if (!empty($images)) {
        return $images;
    }

    // 2️⃣ Try base model
    $baseModel = getBaseModel($model);
    if ($baseModel !== $model) {
        $images = fetchCommonsImages("$brand $baseModel");
        if (!empty($images)) {
            return $images;
        }
    }

    // 3️⃣ Try brand only
    $images = fetchCommonsImages($brand);
    if (!empty($images)) {
        return $images;
    }

    return [];
}
