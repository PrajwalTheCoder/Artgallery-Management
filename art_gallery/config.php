<?php
// config.php (Art Gallery) - uses env.php for credentials. Do not commit real secrets.
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'env.php';

$DB_HOST = env('ART_DB_HOST', env('DB_HOST', 'localhost'));
$DB_PORT = env('ART_DB_PORT', env('DB_PORT', '3306'));
$DB_USER = env('ART_DB_USER', env('DB_USER', 'root'));
$DB_PASS = env('ART_DB_PASS', env('DB_PASS', ''));
$DB_NAME = env('ART_DB_NAME', 'art_gallery_db');

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int)$DB_PORT);
if ($mysqli->connect_errno) {
    die('DB connect error: ' . htmlspecialchars($mysqli->connect_error));
}
$mysqli->set_charset('utf8mb4');
?>
