<?php
session_start();
require __DIR__ . '/init.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/image_resolver.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// Obtain PDO via helper; suppress fatal if unavailable.
try {
  $pdo = get_pdo();
} catch (Throwable $e) {
  $pdo = null;
}

$pageTitle = 'Home - Alpha.Shop';
$products = [];
if ($pdo instanceof PDO) {
  try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
  } catch (Throwable $e) {
    $errorMsg = $e->getMessage();
  }
} else {
  $errorMsg = 'Database connection unavailable';
}

include __DIR__ . '/header.php';
?>

<section class="hero">
  <div class="container hero-inner">
    <div class="hero-text">
      <h1>Find clothes that match your style</h1>
      <p>Explore our curated collection of premium fashion items designed for comfort, durability, and style.</p>
      <a href="#new" class="btn primary">Shop Now</a>
      <div class="hero-stats">
        <div><strong>200+</strong><span>Brands</span></div>
        <div><strong>2,000+</strong><span>Products</span></div>
        <div><strong>30,000+</strong><span>Customers</span></div>
      </div>
    </div>
    <div class="hero-art">
      <?php
        // Hero image resolution logic:
        // Priority 1: config override ($HERO_IMAGE in config.php)
        // Priority 2: files named hero.* (hero.webp, hero.jpg, hero-bg.png, hero-models.*)
        // Priority 3: largest landscape image found in /images (width>=height heuristic by filename tokens)
        $heroWebp = $heroRaster = null; $chosen = null; $imgDir = __DIR__ . '/images';
        $candidates = [];
        if (is_dir($imgDir)) {
          foreach (glob($imgDir.'/*') as $f) {
            if (!is_file($f)) continue;
            $bn = basename($f);
            $ext = strtolower(pathinfo($bn, PATHINFO_EXTENSION));
            if (!in_array($ext,['webp','jpg','jpeg','png'])) continue;
            $candidates[] = $bn;
          }
        }
        // 1. Config override
        if ($HERO_IMAGE && in_array($HERO_IMAGE, $candidates)) {
           $ext = strtolower(pathinfo($HERO_IMAGE, PATHINFO_EXTENSION));
           if ($ext==='webp') $heroWebp = 'images/'.$HERO_IMAGE; else $heroRaster = 'images/'.$HERO_IMAGE;
        }
        // 2. Pattern search if not set
        if (!$heroWebp && !$heroRaster) {
          $priorityNames = [];
          foreach ($candidates as $bn) {
            $low = strtolower($bn);
            if (strpos($low,'hero') !== false) $priorityNames[] = $bn;
            elseif (strpos($low,'banner') !== false) $priorityNames[] = $bn;
            elseif (strpos($low,'model') !== false) $priorityNames[] = $bn;
          }
          // ensure stable order
          $priorityNames = array_unique($priorityNames);
          foreach ($priorityNames as $bn) {
            $ext = strtolower(pathinfo($bn, PATHINFO_EXTENSION));
            if ($ext==='webp' && !$heroWebp) $heroWebp = 'images/'.$bn;
            elseif(!$heroRaster && in_array($ext,['jpg','jpeg','png'])) $heroRaster = 'images/'.$bn;
          }
        }
        // 3. Fallback: pick the lexicographically largest file (often exported bigger names) for some visual instead of blank.
        if (!$heroWebp && !$heroRaster && $candidates) {
          sort($candidates);
          $fallbackName = end($candidates);
          $ext = strtolower(pathinfo($fallbackName, PATHINFO_EXTENSION));
          if ($ext==='webp') $heroWebp='images/'.$fallbackName; else $heroRaster='images/'.$fallbackName;
        }
      ?>
      <?php if ($heroWebp || $heroRaster): ?>
        <picture class="hero-media">
          <?php if($heroWebp): ?><source srcset="<?php echo $heroWebp; ?>" type="image/webp"><?php endif; ?>
          <img src="<?php echo htmlspecialchars($heroRaster ?: $heroWebp); ?>" alt="Season essentials" width="640" height="520" loading="eager" decoding="async">
        </picture>
      <?php else: ?>
        <div class="hero-media hero-fallback"></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section id="new" class="section">
  <div class="container section-header">
    <h2 class="section-title">New Arrivals</h2>
    <a href="#" class="view-all">View All →</a>
  </div>
  <div class="product-row">
    <?php foreach($products as $p): ?>
      <?php $displayImg = resolve_product_image($pdo instanceof PDO ? $pdo : null, $p); ?>
      <div class="product-tile">
        <a href="product.php?id=<?php echo $p['id']; ?>" class="tile-img-wrap">
          <img src="<?php echo htmlspecialchars($displayImg); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" width="400" height="500" loading="lazy">
          <span class="badge new">NEW</span>
        </a>
        <div class="tile-body">
          <h3 class="tile-title"><?php echo htmlspecialchars($p['name']); ?></h3>
          <div class="price-line">₹ <?php echo number_format($p['price'],2); ?></div>
          <form method="post" action="add_to_cart.php" class="tile-cart-form">
            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
            <input type="hidden" name="qty" value="1">
            <button class="btn add-cart" type="submit">Add to Cart</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($products)): ?>
      <p class="muted" style="grid-column:1/-1;">No products found.</p>
    <?php endif; ?>
  </div>
</section>

<section id="top" class="section alt">
  <div class="container section-header">
    <h2 class="section-title">Top Selling</h2>
  </div>
  <div class="product-row scroller-x">
    <?php foreach(array_slice($products,0,6) as $p): ?>
      <?php $displayImg = resolve_product_image($pdo instanceof PDO ? $pdo : null, $p); ?>
      <div class="product-tile small">
        <a href="product.php?id=<?php echo $p['id']; ?>" class="tile-img-wrap">
          <img src="<?php echo htmlspecialchars($displayImg); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" width="400" height="500" loading="lazy">
        </a>
        <div class="tile-body">
          <h3 class="tile-title"><?php echo htmlspecialchars($p['name']); ?></h3>
          <div class="price-line">₹ <?php echo number_format($p['price'],2); ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php if(isset($errorMsg)): ?>
  <div class="container"><div class="alert error">Error: <?php echo htmlspecialchars($errorMsg); ?></div></div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
