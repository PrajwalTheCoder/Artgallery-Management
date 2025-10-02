<?php
require 'config.php';
$id = intval($_GET['id'] ?? 0);
$stmt = $mysqli->prepare("SELECT a.*, ar.name AS artist_name FROM artworks a JOIN artists ar ON a.artist_id = ar.id WHERE a.id = ?");
$stmt->bind_param('i',$id); $stmt->execute(); $res = $stmt->get_result(); $row = $res->fetch_assoc();
if (!$row) { echo "Not found"; exit; }
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=htmlspecialchars($row['title'])?> · Alpha Gallery</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet"><?php $cssVer = @filemtime(__DIR__ . '/assets/style.css') ?: time(); ?><link rel="stylesheet" href="assets/style.css?v=<?= $cssVer ?>"></head><body>
<header class="site-header"><div class="wrap"><div class="brand">ALPHA <span>GALLERY</span></div><nav class="main-nav"><a href="index.php">Artworks</a><a href="artists.php">Artists</a><a class="btn" href="add_artwork.php">+ Add Artwork</a><a class="btn ghost" href="add_artist.php">+ Add Artist</a></nav></div></header>
<main class="wrap">
  <a class="btn ghost" style="margin:18px 0;" href="index.php">← Back</a>
  <section class="detail">
    <div class="media">
      <?php if($row['image']): ?>
        <img src="assets/uploads/<?=rawurlencode($row['image'])?>" alt="<?=htmlspecialchars($row['title'])?>">
      <?php else: ?>
        <div class="no-image" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--muted)">No Image</div>
      <?php endif; ?>
    </div>
    <div class="info">
      <h2><?=htmlspecialchars($row['title'])?></h2>
      <p class="muted" style="margin-top:-6px">By <?=htmlspecialchars($row['artist_name'])?></p>

      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:12px 0 6px">
        <div class="card" style="padding:10px 12px">
          <div class="muted" style="font-size:.8rem">Price</div>
          <div style="font-weight:600;"> <?= $row['price'] ? ('$' . number_format($row['price'],2)) : '—' ?></div>
        </div>
        <div class="card" style="padding:10px 12px">
          <div class="muted" style="font-size:.8rem">Status</div>
          <div style="font-weight:600;"> <?=htmlspecialchars($row['status'] ?? 'Available')?></div>
        </div>
        <div class="card" style="padding:10px 12px">
          <div class="muted" style="font-size:.8rem">Year</div>
          <div style="font-weight:600;"> <?= isset($row['year']) ? htmlspecialchars($row['year']) : '—' ?></div>
        </div>
      </div>

      <?php if(!empty($row['description'])): ?>
        <h3 style="margin-top:16px;font-size:1rem">Description</h3>
        <p><?=nl2br(htmlspecialchars($row['description']))?></p>
      <?php endif; ?>

      <div class="actions" style="display:flex;gap:8px;margin-top:14px">
        <a class="btn" href="edit_artwork.php?id=<?=$row['id']?>">Edit</a>
        <a class="btn danger" href="delete_artwork.php?id=<?=$row['id']?>" onclick="return confirm('Delete this artwork?')">Delete</a>
      </div>
    </div>
  </section>
</main>
<footer class="site-footer"><div class="wrap"><p>© <?=date('Y')?> Alpha Gallery</p></div></footer>
</body></html>
