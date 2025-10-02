<?php
session_start();
require __DIR__ . '/init.php';
require __DIR__ . '/db_connect.php';

// handle remove early
if (isset($_GET['remove'])) {
    $rid = (int)$_GET['remove'];
    unset($_SESSION['cart'][$rid]);
    header('Location: cart.php'); exit;
}

$cart = $_SESSION['cart'] ?? [];
$products = [];
$total = 0;
$dbError = null;
if ($cart) {
  try {
    $pdo = get_pdo();
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $r) {
      $r['qty'] = $cart[$r['id']];
      $r['subtotal'] = $r['qty'] * $r['price'];
      $total += $r['subtotal'];
      $products[] = $r;
    }
  } catch (Throwable $e) {
    $dbError = $e->getMessage();
  }
}

$pageTitle = 'Your Cart - Alpha.Shop';
include __DIR__ . '/header.php';
?>
<div class="container cart-page">
  <h1>Your Cart</h1>
  <?php if ($dbError): ?>
    <div class="alert error">Database error: <?php echo htmlspecialchars($dbError); ?></div>
  <?php elseif (empty($products)): ?>
    <div class="alert info">Cart is empty. <a href="index.php">Browse products</a>.</div>
  <?php else: ?>
    <div class="cart-layout">
      <div class="cart-items">
        <?php foreach($products as $p): ?>
          <div class="cart-item">
            <div class="ci-info">
              <h3><?php echo htmlspecialchars($p['name']); ?></h3>
              <div class="ci-price">₹ <?php echo number_format($p['price'],2); ?> x <?php echo $p['qty']; ?> = <strong>₹ <?php echo number_format($p['subtotal'],2); ?></strong></div>
            </div>
            <div class="ci-actions">
              <a class="link-danger" href="cart.php?remove=<?php echo $p['id']; ?>" onclick="return confirm('Remove item?');">Remove</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <aside class="cart-summary">
        <h2>Summary</h2>
        <p class="summary-line"><span>Items</span><span><?php echo array_sum(array_column($products,'qty')); ?></span></p>
        <p class="summary-total"><span>Total</span><span>₹ <?php echo number_format($total,2); ?></span></p>
        <a class="btn primary block" href="checkout.php">Checkout</a>
      </aside>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
