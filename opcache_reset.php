<?php
header('Content-Type: text/plain; charset=utf-8');
if (function_exists('opcache_reset')) {
    $ok = opcache_reset();
    echo 'opcache_reset: ' . ($ok ? 'ok' : 'failed') . "\n";
} else {
    echo 'opcache_reset: not available\n';
}
// show loaded script path for debugging
echo 'includes/catalog_store.php mtime: ' . (is_file(__DIR__ . '/includes/catalog_store.php') ? date('c', filemtime(__DIR__ . '/includes/catalog_store.php')) : '(missing)') . "\n";
?>