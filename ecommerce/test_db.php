<?php
// test_db.php - diagnostics for database connection issues
require __DIR__ . '/init.php';

echo '<h2>DB Connection Diagnostic</h2>';
echo '<p>PHP Version: ' . PHP_VERSION . '</p>';
echo '<p>Loaded ini file: ' . php_ini_loaded_file() . '</p>';
echo '<p>Script realpath: ' . htmlspecialchars(__FILE__) . '</p>';
echo '<p>db.php expected path: ' . htmlspecialchars(realpath(__DIR__ . '/db.php')) . '</p>';
echo '<p>db_connect.php expected path: ' . htmlspecialchars(realpath(__DIR__ . '/db_connect.php')) . '</p>';

if (!class_exists('PDO')) {
    echo '<div style="background:#f8d7da;padding:10px;border:1px solid #f5c2c7;">PDO class does not exist. Your PHP build is broken or extremely old.</div>';
} else {
    echo '<p>PDO class exists ✅</p>';
    echo '<p>Available PDO drivers: <strong>' . implode(', ', PDO::getAvailableDrivers()) . '</strong></p>';
}

// Try legacy db.php first
ob_start();
require __DIR__ . '/db.php';
$legacyOutput = ob_get_clean();
if ($legacyOutput) {
    echo '<div style="background:#fff3cd;padding:8px;border:1px solid #ffe58f;">Output from db.php:<br>' . nl2br(htmlspecialchars($legacyOutput)) . '</div>';
}

// Ensure PDO via new connector if needed
if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo '<p>Legacy include did not yield PDO. Attempting db_connect.php...</p>';
    try {
        require_once __DIR__ . '/db_connect.php';
        $pdo = get_pdo();
    } catch (Throwable $e) {
        echo '<div style="background:#f8d7da;padding:10px;border:1px solid #f5c2c7;">db_connect.php failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

if ($pdo instanceof PDO) {
    echo '<div style="background:#d4edda;padding:10px;border:1px solid #28a745;">$pdo is a valid PDO instance 🎉</div>';
    try {
        $count = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        echo '<p>products table row count: <strong>' . (int)$count . '</strong></p>';
    } catch (Throwable $e) {
        echo '<div style="background:#f8d7da;padding:10px;border:1px solid #f5c2c7;">Query failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    echo '<div style="background:#f8d7da;padding:10px;border:1px solid #f5c2c7;">No PDO instance available after both methods.</div>';
}

echo '<hr><p>Next: Visit <a href="index.php">index.php</a> again after fixing any above issues.</p>';
