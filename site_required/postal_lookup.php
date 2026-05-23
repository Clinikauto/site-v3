<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/catalog_store.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$postalCode = preg_replace('/\D+/', '', (string) ($_GET['postal_code'] ?? ''));

if ($postalCode === '') {
    echo json_encode([
        'ok' => true,
        'postal_code' => '',
        'cities' => []
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$cities = catalog_lookup_cities_by_postal_code($postalCode, 30);

echo json_encode([
    'ok' => true,
    'postal_code' => $postalCode,
    'cities' => array_values($cities)
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);