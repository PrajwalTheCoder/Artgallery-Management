<?php
session_start();
require __DIR__ . '/db_connect.php';
header_remove('X-Powered-By');

function json_out($data,$status=200){
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok'=>false,'error'=>'POST required'],405);

$pid = (int)($_POST['product_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
if ($pid<=0 || $name==='' || $rating<1 || $rating>5 || $comment==='') json_out(['ok'=>false,'error'=>'Invalid input'],422);

try {
  $pdo = get_pdo();
  $check = $pdo->prepare('SELECT id FROM products WHERE id=?');
  $check->execute([$pid]);
  if (!$check->fetch()) json_out(['ok'=>false,'error'=>'Product not found'],404);
  $ins = $pdo->prepare('INSERT INTO reviews (product_id,name,rating,comment) VALUES (?,?,?,?)');
  $ins->execute([$pid,$name,$rating,$comment]);
  $id = $pdo->lastInsertId();
  $avgRow = $pdo->prepare('SELECT AVG(rating) a, COUNT(*) c FROM reviews WHERE product_id=?');
  $avgRow->execute([$pid]);
  $ar = $avgRow->fetch();
  json_out([
    'ok'=>true,
    'review'=>[
      'id'=>$id,
      'name'=>$name,
      'rating'=>$rating,
      'comment'=>nl2br(htmlspecialchars($comment,ENT_QUOTES,'UTF-8')),
      'created_at'=>date('M j, Y')
    ],
    'avg'=>round($ar['a'],1),
    'count'=>(int)$ar['c']
  ]);
} catch(Throwable $e) {
  json_out(['ok'=>false,'error'=>'Server error'],500);
}