<?php
// db_connect.php - unified PDO connection using environment variables.
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'env.php';

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = env('ECOM_DB_HOST', env('DB_HOST', 'localhost'));
    $db   = env('ECOM_DB_NAME', 'ecommerce_simple');
    $user = env('ECOM_DB_USER', env('DB_USER', 'root'));
    $pass = env('ECOM_DB_PASS', env('DB_PASS', ''));
    $charset = env('ECOM_DB_CHARSET', 'utf8mb4');
    $port = env('ECOM_DB_PORT', env('DB_PORT', '3306'));

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}
