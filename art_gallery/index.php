<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config.php';
$res = $mysqli->query("SELECT a.*, ar.name AS artist_name FROM artworks a JOIN artists ar ON a.artist_id = ar.id ORDER BY a.created_at DESC");
if (!$res) {
    die("Query failed: " . $mysqli->error);
}
$cssVer = @filemtime(__DIR__ . '/assets/style.css') ?: time();
// Resolve hero collage images if present
function heroImage($base){
  $dir = __DIR__ . '/assets/hero/';
  $web = 'assets/hero/';
  $exts = ['jpg','jpeg','png','webp','gif'];
  foreach($exts as $ext){
    $p = $dir . $base . '.' . $ext;
    if (is_file($p)) {
      $mtime = @filemtime($p) ?: time();
      return $web . rawurlencode($base . '.' . $ext) . '?v=' . $mtime;
    }
  }
  return null;
}
$h1 = heroImage('hero-1');
$h2 = heroImage('hero-2');
$h3 = heroImage('hero-3');
// Fallback: if named files not found, pick any three images from assets/hero
if (!$h1 || !$h2 || !$h3) {
  $dir = __DIR__ . '/assets/hero';
  if (is_dir($dir)) {
    $allowed = ['jpg','jpeg','png','webp','gif'];
    $files = [];
    foreach (scandir($dir) as $f) {
      if ($f === '.' || $f === '..') continue;
      $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed)) continue;
  $mt = @filemtime($dir . '/' . $f) ?: time();
  $files[] = [ 'path' => 'assets/hero/' . rawurlencode($f) . '?v=' . $mt, 'mtime' => $mt ];
    }
    if ($files) {
      usort($files, function($a,$b){ return $b['mtime'] <=> $a['mtime']; });
      $pick = array_slice(array_column($files,'path'), 0, 3);
      if (!$h1 && isset($pick[0])) $h1 = $pick[0];
      if (!$h2 && isset($pick[1])) $h2 = $pick[1];
      if (!$h3 && isset($pick[2])) $h3 = $pick[2];
    }
  }
}
$styleVars = [];
if ($h1) $styleVars[] = "--hero-a: url('" . $h1 . "')";
if ($h2) $styleVars[] = "--hero-b: url('" . $h2 . "')";
if ($h3) $styleVars[] = "--hero-c: url('" . $h3 . "')";
$heroStyle = implode('; ', $styleVars);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Alpha Gallery</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css?v=<?=$cssVer?>">
</head>
<body>
  <header class="site-header">
    <div class="wrap">
  <div class="brand">ALPHA <span>GALLERY</span></div>
      <nav class="main-nav">
        <a href="index.php">Artworks</a>
        <a href="artists.php">Artists</a>
        <a class="btn" href="add_artwork.php">+ Add Artwork</a>
        <a class="btn ghost" href="add_artist.php">+ Add Artist</a>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="wrap">
      <div class="hero-copy">
        <h1>Discover, curate, and manage remarkable art.</h1>
        <p>Showcase artworks, highlight artists, and keep your collection organized in one place.</p>
        <div class="cta">
          <a class="btn large" href="#gallery">Browse Gallery</a>
          <a class="btn ghost large" href="add_artwork.php">Add New</a>
        </div>
      </div>
      <div class="hero-collage" <?= $heroStyle ? "style=\"$heroStyle\"" : '' ?>>
        <div class="shape a" <?= $h1 ? "style=\"background: url('$h1') center / cover no-repeat\"" : '' ?>></div>
        <div class="shape b" <?= $h2 ? "style=\"background: url('$h2') center / cover no-repeat\"" : '' ?>></div>
        <div class="shape c" <?= $h3 ? "style=\"background: url('$h3') center / cover no-repeat\"" : '' ?>></div>
      </div>
    </div>
  </section>

  <main id="gallery" class="wrap">
  <h2 class="section-title">Latest artworks</h2>
    <div class="grid">
      <?php while($row = $res->fetch_assoc()): ?>
        <?php
          $hasImg = !empty($row['image']);
          $bg = $hasImg ? "style=\"background-image:url('" . 'assets/uploads/' . rawurlencode($row['image']) . "')\"" : '';
        ?>
        <article class="card artwork">
          <a class="media <?= $hasImg ? '' : 'no-img' ?>" <?=$bg?> href="view_artwork.php?id=<?=$row['id']?>" aria-label="View artwork"></a>
          <div class="content">
            <h3 class="title"><?=htmlspecialchars($row['title'] ?? 'Untitled')?></h3>
            <p class="artist">By <?=htmlspecialchars($row['artist_name'] ?? 'Unknown')?></p>
            <div class="meta">
              <span class="price"><?= $row['price'] ? ('$' . number_format($row['price'],2)) : '—' ?></span>
              <span class="status <?= strtolower($row['status'] ?? 'available') ?>"><?=htmlspecialchars($row['status'] ?? 'Available')?></span>
            </div>
            <div class="actions">
              <a class="btn tiny" href="view_artwork.php?id=<?=$row['id']?>">View</a>
              <a class="btn tiny ghost" href="edit_artwork.php?id=<?=$row['id']?>">Edit</a>
              <a class="btn tiny danger" href="delete_artwork.php?id=<?=$row['id']?>" onclick="return confirm('Delete this artwork?')">Delete</a>
            </div>
          </div>
        </article>
      <?php endwhile; ?>
    </div>
  </main>

  <footer class="site-footer">
    <div class="wrap">
      <p>© <?=date('Y')?> Alpha Gallery · Manage your collection with style.</p>
    </div>
  </footer>
</body>
</html>
