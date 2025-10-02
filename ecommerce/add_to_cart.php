<?php
session_start();
require __DIR__ . '/db_connect.php';

header_remove('X-Powered-By');

function send_json($payload, $status=200){
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
$isAjax = in_array($requestedWith, ['xmlhttprequest','fetch']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) send_json(['ok'=>false,'error'=>'POST required'],405);
    header('Location: index.php'); exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$qty = isset($_POST['qty']) ? max(1,(int)$_POST['qty']) : 1;
if ($id <= 0) {
    if ($isAjax) send_json(['ok'=>false,'error'=>'Missing product id'],400);
    header('Location: index.php'); exit;
}

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id FROM products WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        if ($isAjax) send_json(['ok'=>false,'error'=>'Invalid product'],404);
        header('Location: index.php'); exit;
    }
} catch (Throwable $e) {
    if ($isAjax) send_json(['ok'=>false,'error'=>'DB error: '.$e->getMessage()],500);
    header('Location: index.php'); exit;
}

$_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
$count = array_sum($_SESSION['cart']);

if ($isAjax) send_json(['ok'=>true,'cartCount'=>$count]);

header('Location: cart.php');
