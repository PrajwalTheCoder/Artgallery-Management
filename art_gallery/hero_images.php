<?php
// Simple admin for setting hero collage images
error_reporting(E_ALL); ini_set('display_errors', 1);
$msg = '';$err='';
$heroDirFs = __DIR__ . '/assets/hero';
$heroDirWeb = 'assets/hero';
if (!is_dir($heroDirFs)) { @mkdir($heroDirFs, 0777, true); }

function saveHero($field, $base){
  global $heroDirFs, $err;
  if (empty($_FILES[$field]['name'])) return null;
  $f = $_FILES[$field];
  if ($f['error'] !== UPLOAD_ERR_OK) { $err = 'Upload error for ' . $field; return null; }
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  $allowed = ['jpg','jpeg','png','gif','webp'];
  if (!in_array($ext, $allowed)) { $err = 'Invalid type for ' . $field; return null; }
  // Remove existing variants
  foreach(['jpg','jpeg','png','gif','webp'] as $e){ @unlink($heroDirFs . '/' . $base . '.' . $e); }
  $target = $heroDirFs . '/' . $base . '.' . $ext;
  if (!move_uploaded_file($f['tmp_name'], $target)) { $err = 'Failed to save ' . $field; return null; }
  return $target;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
  saveHero('img1','hero-1');
  saveHero('img2','hero-2');
  saveHero('img3','hero-3');
  if (!$err) $msg = 'Hero images updated.';
}

function heroUrl($base){
  global $heroDirFs, $heroDirWeb; foreach(['jpg','jpeg','png','gif','webp'] as $e){
    $p = $heroDirFs . '/' . $base . '.' . $e; if (is_file($p)) return $heroDirWeb . '/' . $base . '.' . $e;
  } return null;
}
$u1 = heroUrl('hero-1'); $u2 = heroUrl('hero-2'); $u3 = heroUrl('hero-3');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hero Images · Alpha Gallery</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css?v=<?= @filemtime(__DIR__.'/assets/style.css') ?: time() ?>">
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
  <main class="wrap">
    <div class="card" style="margin:24px 0;">
      <h3 class="card-title">Hero Images</h3>
      <?php if($msg): ?><div class="alert success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
      <?php if($err): ?><div class="alert error"><?=htmlspecialchars($err)?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <div class="form-grid">
          <label class="col-span-2">
            <span>Top Left (hero-1)</span>
            <input type="file" name="img1" accept="image/*">
            <?php if($u1): ?><small class="field-help">Current: <a href="<?=$u1?>" target="_blank">View</a></small><?php endif; ?>
          </label>
          <label class="col-span-2">
            <span>Top Right (hero-2)</span>
            <input type="file" name="img2" accept="image/*">
            <?php if($u2): ?><small class="field-help">Current: <a href="<?=$u2?>" target="_blank">View</a></small><?php endif; ?>
          </label>
          <label class="col-span-2">
            <span>Bottom Left (hero-3)</span>
            <input type="file" name="img3" accept="image/*">
            <?php if($u3): ?><small class="field-help">Current: <a href="<?=$u3?>" target="_blank">View</a></small><?php endif; ?>
          </label>
        </div>
        <div class="form-actions sticky">
          <button class="btn" type="submit">Save Images</button>
        </div>
      </form>
    </div>
  </main>
  <footer class="site-footer"><div class="wrap"><p>© <?=date('Y')?> Alpha Gallery</p></div></footer>
</body>
</html>