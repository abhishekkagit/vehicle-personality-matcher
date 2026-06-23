<?php

$GLOBALS['gemini_last_error'] = null;
$GLOBALS['groq_last_error'] = null;

function geminiSetLastError(?array $error): void
{
    $GLOBALS['gemini_last_error'] = $error;
}

function geminiGetLastError(): ?array
{
    $error = $GLOBALS['gemini_last_error'] ?? null;
    return is_array($error) ? $error : null;
}

function groqSetLastError(?array $error): void
{
    $GLOBALS['groq_last_error'] = $error;
}

function groqGetLastError(): ?array
{
    $error = $GLOBALS['groq_last_error'] ?? null;
    return is_array($error) ? $error : null;
}

function loadApiKeys(): array
{
    $apiKeysPath = __DIR__ . '/../config/api_keys.php';

    if (!file_exists($apiKeysPath)) {
        return [];
    }

    $apiKeys = require $apiKeysPath;
    return is_array($apiKeys) ? $apiKeys : [];
}

function geminiHttpPost(string $url, array $payload, string $apiKey): ?array
{
    geminiSetLastError(null);
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($jsonPayload === false) {
        geminiSetLastError([
            'type' => 'json_encode',
            'message' => 'Failed to encode Gemini request payload.',
        ]);
        return null;
    }

    $headers = [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            $decodedError = is_string($response) ? json_decode($response, true) : null;
            $message = $decodedError['error']['message'] ?? ($curlError !== '' ? $curlError : 'Gemini API request failed.');
            geminiSetLastError([
                'type' => 'http',
                'http_code' => $httpCode,
                'message' => $message,
                'response' => is_array($decodedError) ? $decodedError : null,
            ]);
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            geminiSetLastError([
                'type' => 'json_decode',
                'http_code' => $httpCode,
                'message' => 'Failed to decode Gemini API response.',
            ]);
            return null;
        }

        return $data;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $jsonPayload,
            'timeout' => 15,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        $lastError = error_get_last();
        geminiSetLastError([
            'type' => 'stream',
            'message' => $lastError['message'] ?? 'Gemini API request failed.',
        ]);
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        geminiSetLastError([
            'type' => 'json_decode',
            'message' => 'Failed to decode Gemini API response.',
        ]);
        return null;
    }

    if (!empty($data['error'])) {
        geminiSetLastError([
            'type' => 'http',
            'http_code' => $data['error']['code'] ?? 0,
            'message' => $data['error']['message'] ?? 'Gemini API request failed.',
            'response' => $data,
        ]);
        return null;
    }

    return $data;
}

function geminiExtractText(array $response): string
{
    $parts = $response['candidates'][0]['content']['parts'] ?? [];
    $text = '';

    foreach ($parts as $part) {
        if (!empty($part['text'])) {
            $text .= $part['text'];
        }
    }

    return trim($text);
}

function groqHttpPost(string $url, array $payload, string $apiKey): ?array
{
    groqSetLastError(null);
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($jsonPayload === false) {
        groqSetLastError([
            'type' => 'json_encode',
            'message' => 'Failed to encode Groq request payload.',
        ]);
        return null;
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            $decodedError = is_string($response) ? json_decode($response, true) : null;
            $message = $decodedError['error']['message'] ?? ($curlError !== '' ? $curlError : 'Groq API request failed.');
            groqSetLastError([
                'type' => 'http',
                'http_code' => $httpCode,
                'message' => $message,
                'response' => is_array($decodedError) ? $decodedError : null,
            ]);
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            groqSetLastError([
                'type' => 'json_decode',
                'http_code' => $httpCode,
                'message' => 'Failed to decode Groq API response.',
            ]);
            return null;
        }

        return $data;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $jsonPayload,
            'timeout' => 20,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        $lastError = error_get_last();
        groqSetLastError([
            'type' => 'stream',
            'message' => $lastError['message'] ?? 'Groq API request failed.',
        ]);
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        groqSetLastError([
            'type' => 'json_decode',
            'message' => 'Failed to decode Groq API response.',
        ]);
        return null;
    }

    if (!empty($data['error'])) {
        groqSetLastError([
            'type' => 'http',
            'http_code' => $data['error']['code'] ?? 0,
            'message' => $data['error']['message'] ?? 'Groq API request failed.',
            'response' => $data,
        ]);
        return null;
    }

    return $data;
}

function groqExtractText(array $response): string
{
    return trim((string) ($response['choices'][0]['message']['content'] ?? ''));
}

function groqChatCompletion(
    array $messages,
    string $model = 'llama-3.3-70b-versatile',
    float $temperature = 0.45,
    int $maxCompletionTokens = 360
): ?string {
    $apiKeys = loadApiKeys();
    $groqApiKey = (string) ($apiKeys['groq'] ?? '');

    if ($groqApiKey === '') {
        groqSetLastError([
            'type' => 'config',
            'message' => 'Groq API key is missing.',
        ]);
        return null;
    }

    $payload = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => $temperature,
        'max_completion_tokens' => $maxCompletionTokens,
    ];

    $response = groqHttpPost(
        'https://api.groq.com/openai/v1/chat/completions',
        $payload,
        $groqApiKey
    );

    if (!$response) {
        return null;
    }

    $text = groqExtractText($response);
    return $text !== '' ? $text : null;
}

function generateGeminiResultFeedback(array $context): ?string
{
    $apiKeys = loadApiKeys();
    $geminiApiKey = $apiKeys['gemini'] ?? '';

    if (!$geminiApiKey) {
        return null;
    }

    $matches = empty($context['matches']) ? 'none' : implode(', ', $context['matches']);
    $concerns = empty($context['concerns']) ? 'none' : implode(', ', $context['concerns']);

    $prompt = "Vehicle: {$context['vehicle_name']}\n"
        . "Type: {$context['vehicle_type']}\n"
        . "Compatibility score: {$context['compatibility']}\n"
        . "Regret prediction: {$context['regret']}\n"
        . "User preference scores: " . json_encode($context['user_scores']) . "\n"
        . "Vehicle scores: " . json_encode($context['vehicle_scores']) . "\n"
        . "Strong matches: {$matches}\n"
        . "Main concerns: {$concerns}\n\n"
        . "Write exactly 3 concise sentences for a student vehicle recommendation page. "
        . "Mention the strongest fit, mention one important concern if there is one, and give a practical final recommendation. "
        . "Do not use markdown, bullet points, headings, or mention that you are an AI.";

    $payload = [
        'system_instruction' => [
            'parts' => [
                [
                    'text' => 'You write concise, practical vehicle compatibility feedback for a web app.',
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
            'temperature' => 0.4,
            'topP' => 0.9,
            'maxOutputTokens' => 180,
        ],
    ];

    $response = geminiHttpPost(
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
        $payload,
        $geminiApiKey
    );

    if (!$response) {
        return groqChatCompletion(
            [
                [
                    'role' => 'system',
                    'content' => 'You write concise, practical vehicle compatibility feedback for a web app.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'llama-3.3-70b-versatile',
            0.4,
            180
        );
    }

    $text = geminiExtractText($response);
    return $text !== '' ? $text : null;
}
