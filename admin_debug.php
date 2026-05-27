<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/catalog_store.php';

echo "DEFINED=" . (defined('CATALOG_STORE_PATH') ? '1' : '0') . "\n";
echo "CATALOG_STORE_PATH=" . (defined('CATALOG_STORE_PATH') ? catalog_store_path() : '(none)') . "\n";
echo "catalog_store_path()=" . catalog_store_path() . "\n";

echo "Included files:\n";
foreach (get_included_files() as $f) {
    echo $f . "\n";
}

?>