<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$type = strtolower(trim((string) ($_GET['type'] ?? 'car')));
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    exit('Invalid vehicle ID.');
}

function normalizeType(string $type): string
{
    return in_array($type, ['bike', 'bikes'], true) ? 'bike' : 'car';
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? 'vehicle';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'vehicle';
}

function imageExtension(string $source, ?string $mimeType = null): string
{
    $path = (string) parse_url($source, PHP_URL_PATH);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif'];

    if (in_array($ext, $allowed, true)) {
        return $ext === 'jpeg' ? 'jpg' : $ext;
    }

    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
        'image/avif' => 'avif',
    ];

    return $mimeMap[strtolower((string) $mimeType)] ?? 'jpg';
}

function detectMimeType(string $content): ?string
{
    if (!function_exists('finfo_open')) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    if (!$finfo) {
        return null;
    }

    $mime = finfo_buffer($finfo, $content) ?: null;
    finfo_close($finfo);

    return $mime;
}

function fetchRemoteImage(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT => 'VehiclePersonalityMatcher/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $data = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch);
        curl_close($ch);

        if ($error === 0 && $status >= 200 && $status < 300 && is_string($data) && $data !== '') {
            return $data;
        }
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 20,
            'user_agent' => 'VehiclePersonalityMatcher/1.0',
        ],
    ]);

    $data = @file_get_contents($url, false, $context);

    return is_string($data) && $data !== '' ? $data : null;
}

function fetchImageContent(string $path): ?string
{
    $path = trim($path);

    if ($path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path)) {
        return fetchRemoteImage($path);
    }

    $normalized = preg_replace('#^/vehicle-personality-matcher/#', '', str_replace('\\', '/', $path)) ?? $path;
    $fullPath = __DIR__ . '/' . ltrim($normalized, '/');

    if (!is_file($fullPath) || !is_readable($fullPath)) {
        return null;
    }

    $content = @file_get_contents($fullPath);

    return is_string($content) && $content !== '' ? $content : null;
}

function fetchBikeData(mysqli $conn, int $id): array
{
    $stmt = $conn->prepare("
        SELECT brand, model, image_url
        FROM bikes
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $bike = $stmt->get_result()->fetch_assoc();

    if (!$bike) {
        return [null, []];
    }

    $imgStmt = $conn->prepare("
        SELECT image_url
        FROM bike_images
        WHERE bike_id = ?
        ORDER BY image_type='main' DESC
        LIMIT 3
    ");
    $imgStmt->bind_param("i", $id);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result();

    $images = [];
    while ($row = $imgRes->fetch_assoc()) {
        $images[] = $row['image_url'];
    }

    if (empty($images) && !empty($bike['image_url'])) {
        $images[] = $bike['image_url'];
    }

    if (empty($images)) {
        require_once __DIR__ . '/includes/image_fetcher.php';
        $fetched = fetchBikeImagesSmart($bike['brand'], $bike['model']);
        $images = $fetched['images'] ?? [];
    }

    return [$bike['brand'] . ' ' . $bike['model'], array_values(array_unique(array_filter($images)))];
}

function fetchCarData(mysqli $conn, int $id): array
{
    $stmt = $conn->prepare("
        SELECT make, model
        FROM vehicle
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $vehicle = $stmt->get_result()->fetch_assoc();

    if (!$vehicle) {
        return [null, []];
    }

    $imgStmt = $conn->prepare("
        SELECT image_path
        FROM vehicle_images
        WHERE vehicle_id = ?
        ORDER BY created_at ASC
        LIMIT 3
    ");
    $imgStmt->bind_param("i", $id);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result();

    $images = [];
    while ($row = $imgRes->fetch_assoc()) {
        $images[] = $row['image_path'];
    }

    if (empty($images)) {
        require_once __DIR__ . '/includes/image_fetcher.php';
        $images = fetchCarImages($vehicle['make'], $vehicle['model']);
    }

    return [$vehicle['make'] . ' ' . $vehicle['model'], array_values(array_unique(array_filter($images)))];
}

$normalizedType = normalizeType($type);
[$vehicleName, $imagePaths] = $normalizedType === 'bike'
    ? fetchBikeData($conn, $id)
    : fetchCarData($conn, $id);

if ($vehicleName === null) {
    http_response_code(404);
    exit('Vehicle not found.');
}

if (empty($imagePaths)) {
    http_response_code(404);
    exit('No images available for this vehicle.');
}

$baseName = slugify($vehicleName);
$archiveEntries = [];

foreach ($imagePaths as $index => $imagePath) {
    $content = fetchImageContent($imagePath);

    if ($content === null) {
        continue;
    }

    $mimeType = detectMimeType($content);
    $extension = imageExtension($imagePath, $mimeType);
    $fileName = sprintf('%s-%02d.%s', $baseName, $index + 1, $extension);

    $archiveEntries[] = [
        'name' => $fileName,
        'content' => $content,
    ];
}

if (empty($archiveEntries)) {
    http_response_code(502);
    exit('The image sources could not be downloaded right now.');
}

$archivePath = '';
$downloadName = '';
$contentType = '';

if (class_exists('ZipArchive')) {
    $archivePath = tempnam(sys_get_temp_dir(), 'vehicle-images-');

    if ($archivePath === false) {
        http_response_code(500);
        exit('Could not create a temporary download file.');
    }

    $zip = new ZipArchive();

    if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        @unlink($archivePath);
        http_response_code(500);
        exit('Could not prepare the image download.');
    }

    foreach ($archiveEntries as $entry) {
        $zip->addFromString($entry['name'], $entry['content']);
    }

    $zip->close();
    $downloadName = $baseName . '-images.zip';
    $contentType = 'application/zip';
} elseif (class_exists('PharData')) {
    $archiveBase = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vehicle-images-' . bin2hex(random_bytes(8));
    $tarPath = $archiveBase . '.tar';
    $archivePath = $tarPath . '.gz';

    try {
        $tar = new PharData($tarPath);

        foreach ($archiveEntries as $entry) {
            $tar->addFromString($entry['name'], $entry['content']);
        }

        $tar->compress(Phar::GZ);
        unset($tar);
        @unlink($tarPath);
    } catch (Throwable $e) {
        @unlink($tarPath);
        @unlink($archivePath);
        http_response_code(500);
        exit('Could not prepare the image download.');
    }

    $downloadName = $baseName . '-images.tar.gz';
    $contentType = 'application/gzip';
} else {
    http_response_code(500);
    exit('Archive downloads are not available on this server.');
}

if (!is_file($archivePath)) {
    http_response_code(500);
    exit('Could not prepare the image download.');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($archivePath));
header('Cache-Control: private, no-store, no-cache, must-revalidate');

readfile($archivePath);
@unlink($archivePath);
exit;
