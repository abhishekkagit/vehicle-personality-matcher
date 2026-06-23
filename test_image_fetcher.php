<?php
require_once __DIR__ . '/includes/image_fetcher.php';

echo "<pre>";

echo "=== TEST 1: Variant-specific (Ninja H2) ===\n";
print_r(fetchBikeImagesSmart("Kawasaki", "Ninja H2"));

echo "\n=== TEST 2: Variant-specific (Ninja H2 SX SE) ===\n";
print_r(fetchBikeImagesSmart("Kawasaki", "Ninja H2 SX SE"));

echo "\n=== TEST 3: Base fallback only ===\n";
print_r(fetchBikeImagesSmart("Kawasaki", "Ninja H2 Unknown Variant"));

echo "</pre>";
