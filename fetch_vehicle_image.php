<?php
header('Content-Type: application/json');

$make  = $_GET['make']  ?? '';
$model = $_GET['model'] ?? '';

if (!$make || !$model) {
  echo json_encode(['image' => null, 'images' => []]);
  exit;
}

$api = "https://en.wikipedia.org/w/api.php";
$ua  = "VehiclePersonalityMatcher/1.0";

$ctx = stream_context_create([
  'http' => [
    'method' => 'GET',
    'header' => "User-Agent: $ua\r\n"
  ]
]);

/*
  Build title attempts:
  1. Make Model
  2. Make Model car
  3. Make Model (2025 → 2015)
*/
$titlesToTry = [
  "$make $model",
  "$make $model car"
];

// Wikipedia-style year-in-brackets pages
for ($year = 2025; $year >= 2015; $year--) {
  $titlesToTry[] = "$make $model ($year)";
}

foreach ($titlesToTry as $title) {

  $params = http_build_query([
    'action' => 'query',
    'format' => 'json',
    'prop'   => 'pageimages',
    'piprop' => 'thumbnail',
    'pithumbsize' => 700,
    'titles' => $title
  ]);

  $res = @file_get_contents("$api?$params", false, $ctx);
  if (!$res) continue;

  $data = json_decode($res, true);
  if (!isset($data['query']['pages'])) continue;

  foreach ($data['query']['pages'] as $page) {
    if (isset($page['thumbnail']['source'])) {

      $url = $page['thumbnail']['source'];

      // Backward + forward compatible response
      echo json_encode([
        'image'  => $url,      // vehicles.php
        'images' => [$url]     // future / details page
      ]);
      exit;
    }
  }
}

// Nothing found → fallback
echo json_encode(['image' => null, 'images' => []]);
