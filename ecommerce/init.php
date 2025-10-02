<?php
// init.php - development helpers (remove or disable in production)
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Simple function to quickly dump and exit when needed (debugging)
if (!function_exists('dd')) {
    function dd(...$vars): void {
        echo '<pre style="background:#111;color:#0f0;padding:10px;">';
        foreach ($vars as $v) {
            var_dump($v);
        }
        echo '</pre>';
        exit;
    }
}
