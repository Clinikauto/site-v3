<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

$cachePath = __DIR__ . '/data/google_reviews_cache.json';
$cacheTtl = 6 * 3600;

function output_json($payload, $status = 200)
{
    http_response_code((int) $status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function load_cache($path)
{
    if (!is_file($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function save_cache($path, $payload)
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}

function sanitize_reviews($reviews)
{
    $clean = [];
    foreach ((array) $reviews as $review) {
        if (!is_array($review)) {
            continue;
        }
        $clean[] = [
            'author_name' => trim((string) ($review['author_name'] ?? 'Client')),
            'rating' => max(0, min(5, (int) ($review['rating'] ?? 0))),
            'text' => trim((string) ($review['text'] ?? '')),
            'relative_time_description' => trim((string) ($review['relative_time_description'] ?? '')),
            'time' => (int) ($review['time'] ?? 0),
            'author_url' => trim((string) ($review['author_url'] ?? '')),
            'profile_photo_url' => trim((string) ($review['profile_photo_url'] ?? ''))
        ];
    }

    usort($clean, function ($a, $b) {
        if ((int) $a['rating'] === (int) $b['rating']) {
            return (int) $b['time'] <=> (int) $a['time'];
        }
        return (int) $b['rating'] <=> (int) $a['rating'];
    });

    return array_slice($clean, 0, 3);
}

$apiKey = trim((string) (defined('GOOGLE_PLACES_API_KEY') ? GOOGLE_PLACES_API_KEY : ''));
$placeId = trim((string) (defined('GOOGLE_BUSINESS_PLACE_ID') ? GOOGLE_BUSINESS_PLACE_ID : ''));

if ($apiKey === '') {
    $apiKey = trim((string) getenv('GOOGLE_PLACES_API_KEY'));
}
if ($placeId === '') {
    $placeId = trim((string) getenv('GOOGLE_BUSINESS_PLACE_ID'));
}

$cache = load_cache($cachePath);
if (is_array($cache) && ((int) ($cache['fetched_at'] ?? 0) + $cacheTtl) > time()) {
    $cache['source'] = 'cache';
    output_json($cache);
}

if ($apiKey === '' || $placeId === '') {
    if (is_array($cache)) {
        $cache['source'] = 'cache';
        output_json($cache);
    }
    output_json([
        'ok' => false,
        'message' => 'Configuration Google Places manquante (GOOGLE_PLACES_API_KEY / GOOGLE_BUSINESS_PLACE_ID).',
        'reviews' => []
    ], 200);
}

$url = 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . rawurlencode($placeId)
    . '&fields=name,rating,user_ratings_total,reviews,url&reviews_sort=newest&language=fr&key=' . rawurlencode($apiKey);

$response = false;
if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    curl_close($ch);
} else {
    $response = @file_get_contents($url);
}

if ($response === false || trim((string) $response) === '') {
    if (is_array($cache)) {
        $cache['source'] = 'cache';
        output_json($cache);
    }
    output_json(['ok' => false, 'message' => 'Impossible de recuperer les avis Google.', 'reviews' => []], 200);
}

$decoded = json_decode($response, true);
$status = (string) ($decoded['status'] ?? '');
if ($status !== 'OK' || !isset($decoded['result']) || !is_array($decoded['result'])) {
    if (is_array($cache)) {
        $cache['source'] = 'cache';
        output_json($cache);
    }
    output_json([
        'ok' => false,
        'message' => 'Reponse Google invalide (' . $status . ').',
        'reviews' => []
    ], 200);
}

$result = $decoded['result'];
$payload = [
    'ok' => true,
    'fetched_at' => time(),
    'source' => 'live',
    'place_name' => trim((string) ($result['name'] ?? 'Clinik Auto')),
    'rating' => (float) ($result['rating'] ?? 0),
    'user_ratings_total' => (int) ($result['user_ratings_total'] ?? 0),
    'google_url' => trim((string) ($result['url'] ?? '')),
    'reviews' => sanitize_reviews($result['reviews'] ?? [])
];

save_cache($cachePath, $payload);
output_json($payload);
