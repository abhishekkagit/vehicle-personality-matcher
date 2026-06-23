<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/gemini_feedback.php';

function vehicleChatJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function vehicleChatNormalizeType(string $type): string
{
    $type = strtolower(trim($type));
    return in_array($type, ['bike', 'bikes'], true) ? 'bike' : 'car';
}

function vehicleChatGetRequestPayload(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '', true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function vehicleChatGetVehicle(mysqli $conn, string $type, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    if ($type === 'bike') {
        $stmt = $conn->prepare('SELECT * FROM bikes WHERE id = ?');
    } else {
        $stmt = $conn->prepare('SELECT * FROM vehicle WHERE id = ?');
    }

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        return null;
    }

    return $result->fetch_assoc();
}

function vehicleChatGetVehicleName(array $vehicle, string $type): string
{
    if ($type === 'bike') {
        return trim((string) ($vehicle['brand'] ?? '') . ' ' . (string) ($vehicle['model'] ?? ''));
    }

    return trim((string) ($vehicle['make'] ?? '') . ' ' . (string) ($vehicle['model'] ?? ''));
}

function vehicleChatTextLength(string $text): int
{
    return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
}

function vehicleChatTextSlice(string $text, int $limit): string
{
    return function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
}

function vehicleChatFormatValue(string $field, $value, string $type): string
{
    if ($value === null || $value === '') {
        return 'Not available in the vehicle database.';
    }

    $value = is_scalar($value) ? trim((string) $value) : json_encode($value);

    $suffixes = [
        'displacement_cc' => ' cc',
        'power_hp' => ' hp',
        'torque_nm' => ' Nm',
        'weight_kg' => ' kg',
        'seat_height_mm' => ' mm',
        'city_mpg' => ' MPG',
        'highway_mpg' => ' MPG',
        'fuel_capacity' => ' L',
        'wheelbase_mm' => ' mm',
        'length_mm' => ' mm',
        'width_mm' => ' mm',
        'height_mm' => ' mm',
        'acc_0_60' => ' sec',
        'quarter_mile' => ' sec',
        'braking_distance' => ' ft',
        'price_min' => ' USD',
        'price_max' => ' USD',
    ];

    if (isset($suffixes[$field]) && is_numeric($value)) {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') . $suffixes[$field];
    }

    if (str_ends_with($field, '_score') && is_numeric($value)) {
        return (string) round((float) $value) . '/100';
    }

    return $value;
}

function vehicleChatBuildSpecCatalog(array $vehicle, string $type): array
{
    $fieldConfig = $type === 'bike'
        ? [
            'brand' => ['label' => 'Brand', 'aliases' => ['brand', 'maker', 'company']],
            'model' => ['label' => 'Model', 'aliases' => ['model', 'variant']],
            'category' => ['label' => 'Segment', 'aliases' => ['category', 'segment', 'type']],
            'year' => ['label' => 'Year', 'aliases' => ['year', 'model year']],
            'displacement_cc' => ['label' => 'Engine Displacement', 'aliases' => ['engine', 'cc', 'displacement', 'engine displacement', 'engine size']],
            'power_hp' => ['label' => 'Power', 'aliases' => ['power', 'horsepower', 'hp', 'bhp']],
            'torque_nm' => ['label' => 'Torque', 'aliases' => ['torque', 'nm']],
            'weight_kg' => ['label' => 'Weight', 'aliases' => ['weight', 'kerb weight', 'curb weight']],
            'seat_height_mm' => ['label' => 'Seat Height', 'aliases' => ['seat height', 'seat', 'height']],
            'price_range' => ['label' => 'Price Range', 'aliases' => ['price', 'pricing', 'price range', 'budget', 'cost']],
            'performance_score' => ['label' => 'Performance Score', 'aliases' => ['performance score', 'performance rating']],
            'comfort_score' => ['label' => 'Comfort Score', 'aliases' => ['comfort score', 'comfort rating']],
            'efficiency_score' => ['label' => 'Efficiency Score', 'aliases' => ['efficiency score', 'efficiency rating', 'efficiency', 'mileage', 'milage', 'fuel economy', 'good mileage', 'good milage']],
            'reliability_score' => ['label' => 'Reliability Score', 'aliases' => ['reliability score', 'reliability rating']],
            'practicality_score' => ['label' => 'Practicality Score', 'aliases' => ['practicality score', 'practicality rating']],
        ]
        : [
            'make' => ['label' => 'Brand', 'aliases' => ['brand', 'make', 'maker', 'company']],
            'model' => ['label' => 'Model', 'aliases' => ['model', 'variant']],
            'body_type' => ['label' => 'Body Type', 'aliases' => ['body type', 'segment', 'type']],
            'budget_range' => ['label' => 'Price Range', 'aliases' => ['price', 'pricing', 'price range', 'budget', 'cost']],
            'power_hp' => ['label' => 'Power', 'aliases' => ['power', 'horsepower', 'hp', 'bhp']],
            'torque_nm' => ['label' => 'Torque', 'aliases' => ['torque', 'nm']],
            'city_mpg' => ['label' => 'City Mileage', 'aliases' => ['city mileage', 'city mpg', 'mileage', 'fuel economy']],
            'highway_mpg' => ['label' => 'Highway Mileage', 'aliases' => ['highway mileage', 'highway mpg']],
            'seating_capacity' => ['label' => 'Seating Capacity', 'aliases' => ['seats', 'seating', 'seating capacity']],
            'drive_type' => ['label' => 'Drive Type', 'aliases' => ['drive type', 'drivetrain', 'fwd', 'rwd', 'awd']],
            'fuel_capacity' => ['label' => 'Fuel Tank', 'aliases' => ['fuel tank', 'fuel capacity', 'tank size']],
            'weight_kg' => ['label' => 'Weight', 'aliases' => ['weight', 'kerb weight', 'curb weight']],
            'acc_0_60' => ['label' => '0-60 Time', 'aliases' => ['0-60', '0 to 60', 'acceleration']],
            'performance_score' => ['label' => 'Performance Score', 'aliases' => ['performance score', 'performance rating']],
            'comfort_score' => ['label' => 'Comfort Score', 'aliases' => ['comfort score', 'comfort rating']],
            'efficiency_score' => ['label' => 'Efficiency Score', 'aliases' => ['efficiency score', 'efficiency rating']],
            'reliability_score' => ['label' => 'Reliability Score', 'aliases' => ['reliability score', 'reliability rating']],
            'practicality_score' => ['label' => 'Practicality Score', 'aliases' => ['practicality score', 'practicality rating']],
        ];

    $catalog = [];

    foreach ($fieldConfig as $field => $config) {
        if (!array_key_exists($field, $vehicle)) {
            continue;
        }

        $catalog[$field] = [
            'field' => $field,
            'label' => $config['label'],
            'aliases' => $config['aliases'],
            'value' => $vehicle[$field],
            'display' => vehicleChatFormatValue($field, $vehicle[$field], $type),
        ];
    }

    return $catalog;
}

function vehicleChatNormalizeText(string $text): string
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? $text;
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;
    return trim($text);
}

function vehicleChatDetectSpecMatches(string $question, array $catalog): array
{
    $haystack = ' ' . vehicleChatNormalizeText($question) . ' ';
    $matches = [];

    foreach ($catalog as $field => $meta) {
        foreach ($meta['aliases'] as $alias) {
            $needle = ' ' . vehicleChatNormalizeText($alias) . ' ';
            if ($needle !== '  ' && str_contains($haystack, $needle)) {
                $matches[$field] = $meta;
                break;
            }
        }
    }

    return array_values($matches);
}

function vehicleChatQuestionNeedsWebContext(string $question): bool
{
    $terms = [
        'good', 'better', 'best', 'worth', 'buy', 'purchase', 'owner', 'owners',
        'review', 'reviews', 'problem', 'problems', 'issue', 'issues', 'reliable',
        'comfortable', 'comfort for', 'touring', 'long ride',
        'beginner', 'maintenance', 'service cost', 'resale',
        'common', 'experience', 'complaint', 'complaints', 'vs', 'compare',
        'suitable', 'enough', 'daily', 'commute', 'practical', 'why'
    ];

    $normalized = vehicleChatNormalizeText($question);

    foreach ($terms as $term) {
        if (str_contains($normalized, vehicleChatNormalizeText($term))) {
            return true;
        }
    }

    return false;
}

function vehicleChatDetermineMode(string $question, array $matches): string
{
    if (!$matches) {
        return 'web';
    }

    return vehicleChatQuestionNeedsWebContext($question) ? 'mixed' : 'spec';
}

function vehicleChatBuildSpecAnswer(string $vehicleName, array $matches): string
{
    if (count($matches) === 1) {
        $item = $matches[0];
        return "According to the vehicle database on this page, the {$item['label']} for {$vehicleName} is {$item['display']}.";
    }

    $parts = [];
    foreach (array_slice($matches, 0, 4) as $item) {
        $parts[] = "{$item['label']}: {$item['display']}";
    }

    return "According to the vehicle database on this page for {$vehicleName}, " . implode('; ', $parts) . '.';
}

function vehicleChatLooksLikeMileageQuestion(string $question): bool
{
    $normalized = vehicleChatNormalizeText($question);
    return str_contains($normalized, 'mileage')
        || str_contains($normalized, 'milage')
        || str_contains($normalized, 'fuel economy');
}

function vehicleChatBuildDirectSpecFallback(
    string $question,
    string $vehicleName,
    string $type,
    array $vehicle,
    array $specMatches
): string {
    if ($type === 'bike' && vehicleChatLooksLikeMileageQuestion($question)) {
        $efficiencyScore = (float) ($vehicle['efficiency_score'] ?? 0);

        if ($efficiencyScore >= 75) {
            return "{$vehicleName} looks fairly good on mileage for its type. The exact mileage figure is not stored on this page, but its efficiency score is " . round($efficiencyScore) . "/100, which suggests it should be relatively reasonable rather than thirsty.";
        }

        if ($efficiencyScore >= 55) {
            return "{$vehicleName} looks average on mileage rather than outstanding. The page does not store an exact mileage number, but its efficiency score is " . round($efficiencyScore) . "/100, so I would expect a middle-ground result instead of class-leading efficiency.";
        }

        return "{$vehicleName} does not look especially strong on mileage. The page does not store an exact mileage number, but its efficiency score is " . round($efficiencyScore) . "/100, which points more toward performance than fuel saving.";
    }

    return vehicleChatBuildSpecAnswer($vehicleName, $specMatches);
}

function vehicleChatLoadApiKey(): string
{
    $apiKeysPath = __DIR__ . '/config/api_keys.php';
    if (!file_exists($apiKeysPath)) {
        return '';
    }

    $apiKeys = require $apiKeysPath;
    return (string) ($apiKeys['gemini'] ?? '');
}

function vehicleChatDecodeRedirectUrl(string $url): string
{
    $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if (str_starts_with($decoded, '//')) {
        $decoded = 'https:' . $decoded;
    }

    $parts = parse_url($decoded);
    if (!is_array($parts)) {
        return $decoded;
    }

    parse_str($parts['query'] ?? '', $query);
    if (!empty($query['uddg'])) {
        return urldecode((string) $query['uddg']);
    }

    return $decoded;
}

function vehicleChatSearchWeb(string $query, int $limit = 5): array
{
    $url = 'https://html.duckduckgo.com/html/?q=' . urlencode($query);
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36',
    ];

    $html = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (is_string($response) && $httpCode >= 200 && $httpCode < 400) {
            $html = $response;
        }
    }

    if ($html === '') {
        return [];
    }

    $results = [];

    if (class_exists('DOMDocument')) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();

        if ($loaded) {
            $xpath = new DOMXPath($dom);
            $links = $xpath->query("//a[contains(@class,'result__a')]");

            if ($links !== false) {
                foreach ($links as $link) {
                    $title = trim(html_entity_decode($link->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $href = vehicleChatDecodeRedirectUrl((string) $link->getAttribute('href'));
                    $snippet = '';

                    $snippetNode = $xpath->query(".//ancestor::div[contains(@class,'result')]//*[contains(@class,'result__snippet')][1]", $link);
                    if ($snippetNode !== false && $snippetNode->length > 0) {
                        $snippet = trim(html_entity_decode($snippetNode->item(0)->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    }

                    if ($title === '' || $href === '') {
                        continue;
                    }

                    $results[] = [
                        'title' => $title,
                        'url' => $href,
                        'snippet' => $snippet,
                    ];

                    if (count($results) >= $limit) {
                        break;
                    }
                }
            }
        }
    }

    if (!$results && preg_match_all('#<a[^>]+class="[^"]*result__a[^"]*"[^>]+href="([^"]+)"[^>]*>(.*?)</a>#is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $title = trim(strip_tags(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $href = vehicleChatDecodeRedirectUrl($match[1]);
            if ($title === '' || $href === '') {
                continue;
            }

            $results[] = [
                'title' => $title,
                'url' => $href,
                'snippet' => '',
            ];

            if (count($results) >= $limit) {
                break;
            }
        }
    }

    $deduped = [];
    foreach ($results as $result) {
        $key = strtolower($result['url']);
        if (!isset($deduped[$key])) {
            $deduped[$key] = $result;
        }
    }

    return array_values($deduped);
}

function vehicleChatSourceLabel(array $result): string
{
    $host = parse_url((string) ($result['url'] ?? ''), PHP_URL_HOST);
    if (is_string($host) && $host !== '') {
        $host = strtolower($host);
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        return $host;
    }

    $title = trim((string) ($result['title'] ?? ''));
    return $title !== '' ? $title : 'current web sources';
}

function vehicleChatSourceList(array $webResults, int $limit = 3): array
{
    $labels = [];

    foreach ($webResults as $result) {
        $label = vehicleChatSourceLabel($result);
        if ($label === '') {
            continue;
        }

        $key = strtolower($label);
        if (!isset($labels[$key])) {
            $labels[$key] = $label;
        }

        if (count($labels) >= $limit) {
            break;
        }
    }

    return array_values($labels);
}

function vehicleChatPrepareHistory($history): array
{
    if (!is_array($history)) {
        return [];
    }

    $messages = [];
    foreach (array_slice($history, -6) as $item) {
        if (!is_array($item)) {
            continue;
        }

        $role = (($item['role'] ?? '') === 'assistant') ? 'assistant' : 'user';
        $content = trim((string) ($item['content'] ?? ''));

        if ($content === '') {
            continue;
        }

        $messages[] = [
            'role' => $role,
            'content' => vehicleChatTextSlice($content, 500),
        ];
    }

    return $messages;
}

function vehicleChatGenerateGeminiOnlyAnswer(
    string $question,
    string $vehicleName,
    string $type,
    array $history
): ?string {
    $apiKey = vehicleChatLoadApiKey();
    if ($apiKey === '') {
        return null;
    }

    $historyLines = [];
    foreach ($history as $message) {
        $historyLines[] = strtoupper($message['role']) . ': ' . $message['content'];
    }

    $prompt = "Current vehicle: {$vehicleName}\n"
        . "Vehicle type: {$type}\n"
        . "Recent conversation: " . json_encode($historyLines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        . "User question: {$question}\n\n"
        . "Instructions:\n"
        . "1. Act like a full conversational AI assistant dedicated to this exact vehicle.\n"
        . "2. Answer naturally, directly, and helpfully like Gemini or ChatGPT.\n"
        . "3. Use your general knowledge about this vehicle instead of relying on database facts.\n"
        . "4. If there can be market, year, or variant differences, mention that briefly in a natural way.\n"
        . "5. Keep the tone conversational and useful, without markdown bullets unless necessary.\n"
        . "6. Continue the conversation naturally if the recent chat is relevant.";

    $payload = [
        'system_instruction' => [
            'parts' => [
                [
                    'text' => 'You are a smart, natural automotive assistant that answers like a premium general-purpose AI chatbot, with strong vehicle knowledge and conversational flow.',
                ],
            ],
        ],
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    [
                        'text' => $prompt,
                    ],
                ],
            ],
        ],
        'generationConfig' => [
            'temperature' => 0.55,
            'topP' => 0.95,
            'maxOutputTokens' => 360,
        ],
    ];

    $response = geminiHttpPost(
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
        $payload,
        $apiKey
    );

    if (!$response) {
        return null;
    }

    $text = geminiExtractText($response);
    return $text !== '' ? $text : null;
}

function vehicleChatGenerateGroqOnlyAnswer(
    string $question,
    string $vehicleName,
    string $type,
    array $history
): ?string {
    $historyLines = [];
    foreach ($history as $message) {
        $historyLines[] = strtoupper($message['role']) . ': ' . $message['content'];
    }

    $prompt = "Current vehicle: {$vehicleName}\n"
        . "Vehicle type: {$type}\n"
        . "Recent conversation: " . json_encode($historyLines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        . "User question: {$question}\n\n"
        . "Instructions:\n"
        . "1. Act like a full conversational AI assistant dedicated to this exact vehicle.\n"
        . "2. Answer naturally, directly, and helpfully like Gemini or ChatGPT.\n"
        . "3. Use your general knowledge about this vehicle instead of relying on database facts.\n"
        . "4. If there can be market, year, or variant differences, mention that briefly in a natural way.\n"
        . "5. Keep the tone conversational and useful, without markdown bullets unless necessary.\n"
        . "6. Continue the conversation naturally if the recent chat is relevant.";

    return groqChatCompletion(
        [
            [
                'role' => 'system',
                'content' => 'You are a smart, natural automotive assistant that answers like a premium general-purpose AI chatbot, with strong vehicle knowledge and conversational flow.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ],
        'llama-3.3-70b-versatile',
        0.55,
        360
    );
}

function vehicleChatBuildGeminiFailureMessage(string $vehicleName): string
{
    $error = function_exists('geminiGetLastError') ? geminiGetLastError() : null;

    if (is_array($error)) {
        $httpCode = (int) ($error['http_code'] ?? 0);
        $message = trim((string) ($error['message'] ?? ''));

        if ($httpCode === 429) {
            return "Gemini is currently unavailable for {$vehicleName} because this API key has hit its quota limit. Please wait a bit or use a Gemini key/project with available quota.";
        }

        if ($httpCode === 401 || $httpCode === 403) {
            return "Gemini is currently unavailable for {$vehicleName} because the API key was rejected. Please check the Gemini API key and project permissions.";
        }

        if ($message !== '') {
            return "Gemini is currently unavailable for {$vehicleName}. API error: {$message}";
        }
    }

    return "Gemini is currently unavailable for {$vehicleName}. Please try again in a moment.";
}

function vehicleChatBuildProviderFailureMessage(string $vehicleName): string
{
    $geminiError = function_exists('geminiGetLastError') ? geminiGetLastError() : null;
    $groqError = function_exists('groqGetLastError') ? groqGetLastError() : null;

    if (is_array($geminiError) && (int) ($geminiError['http_code'] ?? 0) === 429) {
        if (is_array($groqError)) {
            $groqMessage = trim((string) ($groqError['message'] ?? ''));
            if ($groqMessage !== '') {
                return "Gemini hit its quota limit for {$vehicleName}, and Groq also failed. Groq error: {$groqMessage}";
            }
        }

        return "Gemini hit its quota limit for {$vehicleName}, and Groq fallback is also unavailable right now.";
    }

    if (is_array($groqError)) {
        $groqMessage = trim((string) ($groqError['message'] ?? ''));
        if ($groqMessage !== '') {
            return "The AI providers are currently unavailable for {$vehicleName}. Groq error: {$groqMessage}";
        }
    }

    return vehicleChatBuildGeminiFailureMessage($vehicleName);
}

function vehicleChatBuildVehicleContext(array $vehicle, string $type): array
{
    if ($type === 'bike') {
        return array_filter([
            'name' => trim((string) ($vehicle['brand'] ?? '') . ' ' . (string) ($vehicle['model'] ?? '')),
            'segment' => $vehicle['category'] ?? null,
            'year' => $vehicle['year'] ?? null,
            'engine' => vehicleChatFormatValue('displacement_cc', $vehicle['displacement_cc'] ?? null, $type),
            'power' => vehicleChatFormatValue('power_hp', $vehicle['power_hp'] ?? null, $type),
            'torque' => vehicleChatFormatValue('torque_nm', $vehicle['torque_nm'] ?? null, $type),
            'weight' => vehicleChatFormatValue('weight_kg', $vehicle['weight_kg'] ?? null, $type),
            'seat_height' => vehicleChatFormatValue('seat_height_mm', $vehicle['seat_height_mm'] ?? null, $type),
            'price_range' => $vehicle['price_range'] ?? null,
            'performance_score' => vehicleChatFormatValue('performance_score', $vehicle['performance_score'] ?? null, $type),
            'comfort_score' => vehicleChatFormatValue('comfort_score', $vehicle['comfort_score'] ?? null, $type),
            'reliability_score' => vehicleChatFormatValue('reliability_score', $vehicle['reliability_score'] ?? null, $type),
            'practicality_score' => vehicleChatFormatValue('practicality_score', $vehicle['practicality_score'] ?? null, $type),
            'efficiency_score' => vehicleChatFormatValue('efficiency_score', $vehicle['efficiency_score'] ?? null, $type),
        ], static fn($value) => $value !== null && $value !== '');
    }

    return array_filter([
        'name' => trim((string) ($vehicle['make'] ?? '') . ' ' . (string) ($vehicle['model'] ?? '')),
        'body_type' => $vehicle['body_type'] ?? null,
        'power' => vehicleChatFormatValue('power_hp', $vehicle['power_hp'] ?? null, $type),
        'torque' => vehicleChatFormatValue('torque_nm', $vehicle['torque_nm'] ?? null, $type),
        'city_mileage' => vehicleChatFormatValue('city_mpg', $vehicle['city_mpg'] ?? null, $type),
        'highway_mileage' => vehicleChatFormatValue('highway_mpg', $vehicle['highway_mpg'] ?? null, $type),
        'seating' => $vehicle['seating_capacity'] ?? null,
        'drive_type' => $vehicle['drive_type'] ?? null,
        'weight' => vehicleChatFormatValue('weight_kg', $vehicle['weight_kg'] ?? null, $type),
        'fuel_capacity' => vehicleChatFormatValue('fuel_capacity', $vehicle['fuel_capacity'] ?? null, $type),
        'price_range' => $vehicle['budget_range'] ?? null,
        'performance_score' => vehicleChatFormatValue('performance_score', $vehicle['performance_score'] ?? null, $type),
        'comfort_score' => vehicleChatFormatValue('comfort_score', $vehicle['comfort_score'] ?? null, $type),
        'reliability_score' => vehicleChatFormatValue('reliability_score', $vehicle['reliability_score'] ?? null, $type),
        'practicality_score' => vehicleChatFormatValue('practicality_score', $vehicle['practicality_score'] ?? null, $type),
        'efficiency_score' => vehicleChatFormatValue('efficiency_score', $vehicle['efficiency_score'] ?? null, $type),
    ], static fn($value) => $value !== null && $value !== '');
}

function vehicleChatGenerateGeneralAnswer(
    string $question,
    string $vehicleName,
    string $type,
    array $vehicle,
    array $specMatches,
    array $history,
    array $webResults = []
): ?string {
    $apiKey = vehicleChatLoadApiKey();
    if ($apiKey === '') {
        return null;
    }

    $historyLines = [];
    foreach ($history as $message) {
        $historyLines[] = strtoupper($message['role']) . ': ' . $message['content'];
    }

    $specSummary = [];
    foreach ($specMatches as $match) {
        $specSummary[] = [
            'label' => $match['label'],
            'value' => $match['display'],
        ];
    }

    $webSummary = [];
    foreach ($webResults as $index => $result) {
        $webSummary[] = [
            'index' => $index + 1,
            'source' => vehicleChatSourceLabel($result),
            'snippet' => $result['snippet'],
        ];
    }

    $prompt = "Vehicle: {$vehicleName}\n"
        . "Type: {$type}\n"
        . "User question: {$question}\n"
        . "Authoritative database specs: " . json_encode($specSummary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        . "Vehicle context from database: " . json_encode(vehicleChatBuildVehicleContext($vehicle, $type), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        . "Recent conversation: " . json_encode($historyLines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        . "Optional live web evidence: " . json_encode($webSummary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n"
        . "Instructions:\n"
        . "1. You are acting like a full conversational AI assistant dedicated to this specific vehicle.\n"
        . "2. Treat database specs as the source of truth and never contradict them.\n"
        . "3. If the user asks about known specs, mention the exact database values first, then you may explain what they mean in practical terms.\n"
        . "4. Use the vehicle context actively in your answer instead of sounding like a raw database lookup.\n"
        . "5. For broader questions, answer using your model knowledge and the vehicle context even when live web evidence is missing.\n"
        . "6. If live web evidence exists, blend it in naturally and mention source names only, never URLs.\n"
        . "7. If you are making an informed judgment rather than stating a verified fact, say so naturally with phrasing like 'based on this bike's setup' or 'generally speaking'.\n"
        . "8. Keep the answer conversational, helpful, and plain text without bullets or markdown.";

    $payload = [
        'system_instruction' => [
            'parts' => [
                [
                    'text' => 'You are a smart, natural vehicle assistant that chats like a premium AI product while staying grounded in provided bike and car data.',
                ],
            ],
        ],
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    [
                        'text' => $prompt,
                    ],
                ],
            ],
        ],
        'generationConfig' => [
            'temperature' => 0.45,
            'topP' => 0.95,
            'maxOutputTokens' => 320,
        ],
    ];

    $response = geminiHttpPost(
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
        $payload,
        $apiKey
    );

    if (!$response) {
        return null;
    }

    $text = geminiExtractText($response);
    return $text !== '' ? $text : null;
}

function vehicleChatGenerateWebAnswer(
    string $question,
    string $vehicleName,
    string $type,
    array $specMatches,
    array $webResults,
    array $history
): ?string {
    $apiKey = vehicleChatLoadApiKey();
    if ($apiKey === '') {
        return null;
    }

    $specSummary = [];
    foreach ($specMatches as $match) {
        $specSummary[] = [
            'label' => $match['label'],
            'value' => $match['display'],
        ];
    }

    $historyLines = [];
    foreach ($history as $message) {
        $historyLines[] = strtoupper($message['role']) . ': ' . $message['content'];
    }

    $webSummary = [];
    foreach ($webResults as $index => $result) {
        $webSummary[] = [
            'index' => $index + 1,
            'title' => $result['title'],
            'source' => vehicleChatSourceLabel($result),
            'snippet' => $result['snippet'],
        ];
    }

    $prompt = "Vehicle: {$vehicleName}\n"
        . "Type: {$type}\n"
        . "User question: {$question}\n"
        . "Authoritative database specs: " . json_encode($specSummary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        . "Recent conversation: " . json_encode($historyLines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        . "Web evidence: " . json_encode($webSummary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n"
        . "Instructions:\n"
        . "1. Treat database specs as the source of truth and never contradict them.\n"
        . "2. If the question touches a known spec, mention the database value first.\n"
        . "3. Answer conversationally, like a real assistant continuing the chat, and use the recent conversation when helpful.\n"
        . "4. Use only the web evidence for internet claims.\n"
        . "5. Mention sources naturally in the answer using source names only, for example 'Based on bikewale.com and team-bhp.com...', but never show URLs or bracket citations.\n"
        . "6. If the evidence is weak or missing, say you could not verify it from current web results.\n"
        . "7. Keep the answer concise, useful, and in plain text without markdown bullets.";

    $payload = [
        'system_instruction' => [
            'parts' => [
                [
                    'text' => 'You are a vehicle research assistant for a vehicle details page. Be accurate, grounded, and concise.',
                ],
            ],
        ],
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    [
                        'text' => $prompt,
                    ],
                ],
            ],
        ],
        'generationConfig' => [
            'temperature' => 0.3,
            'topP' => 0.9,
            'maxOutputTokens' => 280,
        ],
    ];

    $response = geminiHttpPost(
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
        $payload,
        $apiKey
    );

    if (!$response) {
        return null;
    }

    $text = geminiExtractText($response);
    return $text !== '' ? $text : null;
}

function vehicleChatBuildWebFallback(string $vehicleName, array $specMatches, array $webResults, array $vehicle, string $type): string
{
    $lines = [];

    if ($specMatches) {
        $lines[] = vehicleChatBuildSpecAnswer($vehicleName, $specMatches);
    }

    if ($webResults) {
        $first = $webResults[0];
        $sources = vehicleChatSourceList($webResults);
        $text = $first['snippet'] !== ''
            ? $first['snippet']
            : 'I found web sources related to this question, but the available snippet was limited.';
        if ($sources) {
            $text .= ' Based on ' . implode(', ', $sources) . '.';
        }
        $lines[] = $text;
    } else {
        $context = vehicleChatBuildVehicleContext($vehicle, $type);
        if ($type === 'bike') {
            $segment = $context['segment'] ?? 'bike';
            $power = $context['power'] ?? 'the available power figure';
            $weight = $context['weight'] ?? 'its weight';
            $lines[] = "I couldn't verify that from live web results right now, but based on this bike's setup as a {$segment} with {$power} and {$weight}, I can still help reason through it if you want a practical take.";
        } else {
            $bodyType = $context['body_type'] ?? 'vehicle';
            $power = $context['power'] ?? 'the available power figure';
            $lines[] = "I couldn't verify that from live web results right now, but based on this {$bodyType} vehicle and {$power}, I can still help reason through it if you want a practical take.";
        }
    }

    return implode(' ', $lines);
}

function vehicleChatBuildReasonedFallback(string $question, array $vehicle, string $type, string $vehicleName, array $specMatches = []): string
{
    $normalized = vehicleChatNormalizeText($question);

    if ($type === 'bike') {
        $category = strtolower((string) ($vehicle['category'] ?? ''));
        $power = (float) ($vehicle['power_hp'] ?? 0);
        $weight = (float) ($vehicle['weight_kg'] ?? 0);
        $seatHeight = (float) ($vehicle['seat_height_mm'] ?? 0);
        $comfortScore = (float) ($vehicle['comfort_score'] ?? 0);
        $performanceScore = (float) ($vehicle['performance_score'] ?? 0);
        $reliabilityScore = (float) ($vehicle['reliability_score'] ?? 0);
        $efficiencyScore = (float) ($vehicle['efficiency_score'] ?? 0);

        if (str_contains($normalized, 'track') || str_contains($normalized, 'weekend')) {
            $isTrackFocused = str_contains($category, 'sport') || $performanceScore >= 80 || $power >= 80;
            if ($isTrackFocused) {
                return "{$vehicleName} looks very strong for track riding and spirited weekend use. Based on its performance-focused setup" .
                    ($power > 0 ? ", {$power} hp," : '') .
                    " it should feel exciting and sharp rather than calm or forgiving.";
            }

            return "{$vehicleName} can still work for weekend fun, but based on its setup it doesn't look especially track-focused. It should feel more balanced than aggressive.";
        }

        if (str_contains($normalized, 'long ride') || str_contains($normalized, 'tour') || str_contains($normalized, 'highway')) {
            $touringFriendly = str_contains($category, 'adventure') || str_contains($category, 'tour') || str_contains($category, 'cruiser') || $comfortScore >= 75;
            $aggressive = str_contains($category, 'sport') || $power >= 100 || $seatHeight >= 830;

            if ($touringFriendly && !$aggressive) {
                return "{$vehicleName} should be a pretty good long-ride option. Based on this bike's comfort-oriented setup, it looks more suitable for longer hours in the saddle than a pure aggressive sport machine.";
            }

            if ($aggressive) {
                return "{$vehicleName} should have no trouble with highway performance, but it may not be the most relaxed long-ride bike. Based on its sport-focused setup, strong power, and likely aggressive ergonomics, it seems better for intensity than all-day comfort.";
            }

            return "{$vehicleName} seems usable for occasional long rides, but probably not as naturally comfortable as a dedicated tourer or cruiser. It looks more like a balanced compromise than a pure distance machine.";
        }

        if (str_contains($normalized, 'beginner') || str_contains($normalized, 'first bike')) {
            $beginnerFriendly = $power > 0 && $power <= 40 && $weight > 0 && $weight <= 180 && !str_contains($category, 'sport');
            if ($beginnerFriendly) {
                return "{$vehicleName} looks fairly beginner-friendly based on its manageable power and weight. It should be easier to learn on than a high-strung sport bike.";
            }

            return "{$vehicleName} does not look ideal as a first bike. Based on its power, category, and overall performance bias, it seems better suited to a rider who already has confidence and control.";
        }

        if (str_contains($normalized, 'daily') || str_contains($normalized, 'commute') || str_contains($normalized, 'city')) {
            $easyCommuter = $power > 0 && $power <= 45 && $weight > 0 && $weight <= 180 && $efficiencyScore >= 60 && !str_contains($category, 'sport');
            if ($easyCommuter) {
                return "{$vehicleName} looks like a sensible daily commuter. Based on its lighter, more manageable setup, it should be easier to live with in traffic and day-to-day riding.";
            }

            if (str_contains($category, 'sport') || $power >= 80) {
                return "{$vehicleName} can definitely be used daily, but it doesn't look like the easiest city commuter. Based on its sport-oriented character and high power, it feels more like an exciting bike to own than a stress-free everyday tool.";
            }

            return "{$vehicleName} seems decent for everyday use, though it may depend on what matters most to you. It looks more practical than extreme, but not especially commuter-focused either.";
        }

        if (str_contains($normalized, 'maintenance') || str_contains($normalized, 'service') || str_contains($normalized, 'reliable')) {
            $premium = in_array(strtolower((string) ($vehicle['brand'] ?? '')), ['ducati', 'bmw', 'triumph', 'mv agusta'], true);
            if ($premium || $power >= 100) {
                return "{$vehicleName} is likely to be a higher-commitment bike for maintenance and running costs. Based on its premium, high-performance setup, I would expect ownership to be less forgiving than a simpler everyday bike, even if it is rewarding.";
            }

            if ($reliabilityScore >= 70) {
                return "{$vehicleName} looks fairly reasonable from a reliability and ownership perspective based on the data on this page. It doesn't immediately suggest the kind of extreme performance setup that usually drives costs up fast.";
            }

            return "{$vehicleName} doesn't look especially low-stress from an ownership point of view, so I would treat maintenance and service expectations carefully. It seems more like a bike to buy for character than minimum hassle.";
        }

        if (vehicleChatLooksLikeMileageQuestion($question)) {
            if ($efficiencyScore >= 75) {
                return "{$vehicleName} looks fairly good on mileage for a bike in this class. There isn't an exact mileage number stored on this page, but its efficiency score of " . round($efficiencyScore) . "/100 suggests it should be relatively reasonable rather than overly thirsty.";
            }

            if ($efficiencyScore >= 55) {
                return "{$vehicleName} looks average on mileage rather than exceptional. There isn't an exact mileage number stored on this page, but its efficiency score of " . round($efficiencyScore) . "/100 suggests a middle-ground result.";
            }

            return "{$vehicleName} does not look especially mileage-focused. There isn't an exact mileage number stored on this page, but its efficiency score of " . round($efficiencyScore) . "/100 suggests this bike prioritizes performance more than fuel saving.";
        }
    }

    if ($specMatches) {
        return vehicleChatBuildSpecAnswer($vehicleName, $specMatches);
    }

    return "I couldn't reach live research right now, but I can still reason about {$vehicleName} from the specs and setup on this page if you ask something more specific like comfort, commuting, beginner-friendliness, maintenance, or highway use.";
}

$payload = vehicleChatGetRequestPayload();
$question = trim((string) ($payload['question'] ?? ''));
$type = vehicleChatNormalizeType((string) ($payload['type'] ?? 'car'));
$vehicleId = (int) ($payload['vehicle_id'] ?? 0);
$history = vehicleChatPrepareHistory($payload['history'] ?? []);

if ($question === '' || vehicleChatTextLength($question) > 700) {
    vehicleChatJson([
        'success' => false,
        'message' => 'Please ask a valid question.',
    ], 422);
}

$vehicle = vehicleChatGetVehicle($conn, $type, $vehicleId);
if (!$vehicle) {
    vehicleChatJson([
        'success' => false,
        'message' => 'Vehicle not found.',
    ], 404);
}

$vehicleName = vehicleChatGetVehicleName($vehicle, $type);
$answer = vehicleChatGenerateGeminiOnlyAnswer($question, $vehicleName, $type, $history);
$badge = 'Gemini vehicle assistant';

if ($answer === null) {
    $answer = vehicleChatGenerateGroqOnlyAnswer($question, $vehicleName, $type, $history);
    if ($answer !== null) {
        $badge = 'Groq fallback assistant';
    }
}

if ($answer === null) {
    $answer = vehicleChatBuildProviderFailureMessage($vehicleName);
    $badge = 'AI assistant unavailable';
}

vehicleChatJson([
    'success' => true,
    'mode' => 'gemini',
    'answer' => $answer,
    'badge' => $badge,
]);
