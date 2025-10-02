<?php
session_start();
require __DIR__ . '/init.php';
require __DIR__ . '/db.php'; // legacy include (may set $pdo)
if (!isset($pdo) || !($pdo instanceof PDO)) {
  require_once __DIR__ . '/db_connect.php';
  try { $pdo = get_pdo(); } catch (Throwable $e) { die('DB unavailable'); }
}

$cart = $_SESSION['cart'] ?? [];
if (!$cart) { header('Location: cart.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    unset($_SESSION['cart']);
  $pageTitle = 'Order Placed - Alpha.Shop';
    include __DIR__ . '/header.php';
    echo '<div class="container"><div class="alert success">Order placed — thank you!</div><p><a class="btn" href="index.php">Back to shop</a></p></div>';
    include __DIR__ . '/footer.php';
    exit;
}

$ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$total = 0; $items = [];
foreach ($stmt->fetchAll() as $r) { $r['qty'] = $cart[$r['id']]; $r['subtotal'] = $r['qty'] * $r['price']; $total += $r['subtotal']; $items[] = $r; }

$pageTitle = 'Checkout - Alpha.Shop';
include __DIR__ . '/header.php';
?>
<div class="container checkout-page">
  <h1>Checkout</h1>
  <div class="checkout-layout">
    <div class="co-summary">
      <h2>Order Summary</h2>
      <ul class="summary-items">
        <?php foreach($items as $i): ?>
          <li>
            <span><?php echo htmlspecialchars($i['name']); ?> x <?php echo $i['qty']; ?></span>
            <strong>₹ <?php echo number_format($i['subtotal'],2); ?></strong>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="grand-total"><span>Total:</span> <strong>₹ <?php echo number_format($total,2); ?></strong></p>
    </div>
    <form method="post" class="co-form">
      <h2>Your Details</h2>
      <label>Name
        <input name="name" required>
      </label>
      <label>Address
        <textarea name="address" rows="4" required></textarea>
      </label>
      <button class="btn primary" type="submit">Place Order (Demo)</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
