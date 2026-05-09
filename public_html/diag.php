<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<pre>";
echo "diag.php is at: " . __FILE__ . "\n";
echo "__DIR__         = " . __DIR__ . "\n";
echo "dirname(__DIR__) = " . dirname(__DIR__) . "\n\n";

echo "=== Searching for config.php ===\n";
$candidates = [
    __DIR__ . '/app/config/config.php',                       // public_html/app/...
    dirname(__DIR__) . '/app/config/config.php',              // /domains/lawmillion.us/app/...
    dirname(__DIR__, 2) . '/app/config/config.php',           // /domains/app/...
    dirname(__DIR__, 3) . '/app/config/config.php',           // /home/u.../app/...
    '/home/u647763412/domains/lawmillion.us/public_html/app/config/config.php',
    '/home/u647763412/domains/lawmillion.us/app/config/config.php',
    '/home/u647763412/app/config/config.php',
];
foreach ($candidates as $c) {
    echo (file_exists($c) ? "✓ FOUND: " : "✗ no:    ") . $c . "\n";
}

echo "\n=== Listing /home/u647763412/ contents ===\n";
foreach (glob('/home/u647763412/*') as $f) echo "  " . $f . "\n";

echo "\n=== Listing /home/u647763412/domains/lawmillion.us/ contents ===\n";
foreach (glob('/home/u647763412/domains/lawmillion.us/*') as $f) echo "  " . $f . "\n";

echo "</pre>";