<?php
require_once __DIR__ . '/includes/catalog_store.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$active = catalog_is_admin_session_active();

echo json_encode(['admin' => $active], JSON_UNESCAPED_UNICODE);
