<?php
// Lightweight .env loader (no external deps). Include this early in config files.
// Usage: $value = env('KEY', 'default');

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        // Priority: $_ENV -> $_SERVER -> getenv() -> default
        if (array_key_exists($key, $_ENV)) return $_ENV[$key];
        if (array_key_exists($key, $_SERVER)) return $_SERVER[$key];
        $val = getenv($key);
        return $val === false ? $default : $val;
    }
}

if (!function_exists('load_env_file')) {
    function load_env_file(string $path): void {
        static $loaded = [];
        if (isset($loaded[$path])) return; // prevent repeat
        if (!is_file($path) || !is_readable($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '#')) continue;
            if (!str_contains($trim, '=')) continue;
            [$k, $v] = explode('=', $trim, 2);
            $k = trim($k);
            // Remove optional surrounding quotes
            $v = trim($v);
            if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
                $v = substr($v, 1, -1);
            }
            $_ENV[$k] = $v;
            // Avoid overwriting existing server vars
            if (!array_key_exists($k, $_SERVER)) {
                $_SERVER[$k] = $v;
            }
        }
        $loaded[$path] = true;
    }
}

// Auto-load root .env if present
$rootEnv = __DIR__ . DIRECTORY_SEPARATOR . '.env';
if (is_file($rootEnv)) {
    load_env_file($rootEnv);
}
