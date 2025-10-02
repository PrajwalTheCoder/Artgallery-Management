<?php
session_start();
require __DIR__ . '/init.php';
require __DIR__ . '/db_connect.php';
require __DIR__ . '/image_resolver.php';

// Fallback if $pdo not created (mirrors index.php approach)
try { $pdo = get_pdo(); } catch(Throwable $e){ $pdo=null; $connError=$e->getMessage(); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$p = null;
if ($pdo instanceof PDO) {
  try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
  } catch(Throwable $e) { $queryError = $e->getMessage(); }
}

if (!$pdo) { $pageTitle='DB Error - Alpha.Shop'; include __DIR__.'/header.php'; echo '<div class="container"><div class="alert error">Database connection failed: '.htmlspecialchars($connError ?? 'Unknown').'</div></div>'; include __DIR__.'/footer.php'; exit; }
if (!$p) { http_response_code(404); $pageTitle = 'Not Found'; include __DIR__ . '/header.php'; echo '<div class="container"><p>Product not found.</p></div>'; include __DIR__ . '/footer.php'; exit; }
$pageTitle = htmlspecialchars($p['name']) . ' - Alpha.Shop';
include __DIR__ . '/header.php';
?>
<nav class="breadcrumb container"><a href="index.php">Home</a> &raquo; <span><?php echo htmlspecialchars($p['name']); ?></span></nav>
<section class="container product-view">
  <div class="pv-gallery">
    <div class="pv-main-img">
      <?php $displayImg = resolve_product_image($pdo instanceof PDO ? $pdo : null, $p); ?>
      <img src="<?php echo htmlspecialchars($displayImg); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" width="640" height="800" loading="lazy">
    </div>
  </div>
  <div class="pv-info">
    <h1><?php echo htmlspecialchars($p['name']); ?></h1>
    <?php
      // Load reviews + average
      $avgRating = null; $reviewCount = 0; $reviews = [];
      try {
        $rs = $pdo->prepare('SELECT id,name,rating,comment,created_at FROM reviews WHERE product_id=? ORDER BY created_at DESC LIMIT 12');
        $rs->execute([$p['id']]);
        $reviews = $rs->fetchAll();
        $reviewCount = count($reviews);
        if ($reviewCount) {
           $avgStmt = $pdo->prepare('SELECT AVG(rating) a, COUNT(*) c FROM reviews WHERE product_id=?');
           $avgStmt->execute([$p['id']]);
           $rowAvg = $avgStmt->fetch();
           $avgRating = round($rowAvg['a'],1);
           $reviewCount = (int)$rowAvg['c'];
        }
      } catch (Throwable $e) { /* ignore */ }
    ?>
    <div class="rating-summary">
      <?php if($avgRating): ?>
        <span class="stars" aria-label="<?php echo $avgRating; ?> out of 5">
          <?php
            $full = floor($avgRating); $half = ($avgRating*10 % 10)>=5; $i=0;
            for($i=1;$i<=5;$i++){ echo $i<=$full ? '★' : ($i==$full+1 && $half ? '☆' : '☆'); }
          ?>
        </span>
        <span class="rating-text"><?php echo $avgRating; ?>/5 (<?php echo $reviewCount; ?> reviews)</span>
      <?php else: ?>
        <span class="rating-text muted">No reviews yet</span>
      <?php endif; ?>
    </div>
  <div class="pv-price" id="unitPrice" data-unit="<?php echo htmlspecialchars($p['price'],ENT_QUOTES); ?>">₹ <?php echo number_format($p['price'],2); ?></div>
  <div class="pv-total" id="totalPrice" data-initial>Total: ₹ <?php echo number_format($p['price'],2); ?> <span class="qty-readout" id="qtyReadout" data-q="1">(Qty 1)</span></div>
    <p class="pv-desc"><?php echo nl2br(htmlspecialchars($p['description'])); ?></p>
    <form method="post" action="add_to_cart.php" class="pv-cart-form">
      <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
      <div class="qty-wrap">
        <label for="qtyInput">Qty</label>
  <div class="qty-control lg" data-qty>
          <button type="button" class="qty-btn" data-dec aria-label="Decrease quantity">−</button>
          <input id="qtyInput" type="number" name="qty" value="1" min="1" inputmode="numeric" pattern="[0-9]*">
          <button type="button" class="qty-btn" data-inc aria-label="Increase quantity">+</button>
        </div>
      </div>
      <button class="btn primary" type="submit">Add to Cart</button>
    </form>
  </div>
</section>
<section class="container reviews-section">
  <div class="reviews-tabs">
    <button class="rev-tab active" data-tab="details">Product Details</button>
    <button class="rev-tab" data-tab="reviews">Ratings & Reviews</button>
    <button class="rev-tab" data-tab="faq">FAQs</button>
  </div>
  <div class="rev-panels">
    <div data-tabpanel="details">
      <p class="muted">Additional structured details could go here (materials, sizing chart, care instructions).</p>
    </div>
    <div data-tabpanel="reviews" hidden>
      <div id="reviewList" class="review-list">
        <?php foreach($reviews as $rv): ?>
          <article class="review-card">
            <header>
              <strong><?php echo htmlspecialchars($rv['name']); ?></strong>
              <span class="stars" aria-label="<?php echo $rv['rating']; ?> out of 5"><?php echo str_repeat('★',(int)$rv['rating']) . str_repeat('☆',5-(int)$rv['rating']); ?></span>
            </header>
            <p><?php echo nl2br(htmlspecialchars($rv['comment'])); ?></p>
            <time datetime="<?php echo $rv['created_at']; ?>"><?php echo date('M j, Y', strtotime($rv['created_at'])); ?></time>
          </article>
        <?php endforeach; ?>
      </div>
      <form id="reviewForm" class="review-form" autocomplete="off">
        <h3>Add a Review</h3>
        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
        <label>Your Name <input name="name" required maxlength="120"></label>
        <label>Rating
          <select name="rating" required>
            <option value="">Select</option>
            <option value="5">5 - Excellent</option>
            <option value="4">4 - Good</option>
            <option value="3">3 - Average</option>
            <option value="2">2 - Poor</option>
            <option value="1">1 - Bad</option>
          </select>
        </label>
        <label>Comment
          <textarea name="comment" rows="3" required maxlength="1000"></textarea>
        </label>
        <button class="btn primary" type="submit">Submit Review</button>
        <div id="reviewMsg" class="muted" style="margin-top:6px;font-size:.7rem;"></div>
      </form>
    </div>
    <div data-tabpanel="faq" hidden>
      <p class="muted">FAQ content (shipping times, returns) can be added here.</p>
    </div>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
