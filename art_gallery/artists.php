<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $stmt = $mysqli->prepare("INSERT INTO artists (name,bio) VALUES (?,?)");
    $stmt->bind_param('ss', $name, $bio);
    $stmt->execute();
    header('Location: artists.php');
    exit;
}
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
  $stmt = $mysqli->prepare("SELECT * FROM artists WHERE name LIKE CONCAT('%', ?, '%') OR bio LIKE CONCAT('%', ?, '%') ORDER BY created_at DESC");
  $stmt->bind_param('ss', $q, $q);
  $stmt->execute();
  $res = $stmt->get_result();
  $stmt->close();
} else {
  $res = $mysqli->query("SELECT * FROM artists ORDER BY created_at DESC");
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Artists · Alpha Gallery</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet"><?php $cssVer = @filemtime(__DIR__ . '/assets/style.css') ?: time(); ?><link rel="stylesheet" href="assets/style.css?v=<?= $cssVer ?>"></head><body>
<header class="site-header"><div class="wrap"><div class="brand">ALPHA <span>GALLERY</span></div><nav class="main-nav"><a href="index.php">Artworks</a><a href="artists.php">Artists</a><a class="btn" href="add_artwork.php">+ Add Artwork</a><a class="btn ghost" href="add_artist.php">+ Add Artist</a></nav></div></header>
<main class="wrap">
  <div class="toolbar">
    <h2 class="section-title" style="margin:0">All Artists</h2>
    <form class="search" method="get" action="artists.php">
      <input type="search" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Search artists">
    </form>
    <a class="btn" href="add_artist.php">+ Add Artist</a>
  </div>

  <?php if ($res->num_rows === 0): ?>
    <div class="card" style="margin:12px 0;padding:22px">
      <p style="margin:0;color:var(--muted)">No artists yet. Start by adding your first artist.</p>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php while($row = $res->fetch_assoc()): ?>
        <?php $initials = strtoupper(substr($row['name'],0,1)); ?>
        <article class="card artist">
          <div class="content" style="padding:14px">
            <div class="head"><div class="avatar"><?=$initials?></div><h3 class="title" style="margin:0;font-size:1.05rem;"><?=htmlspecialchars($row['name'])?></h3></div>
            <p class="bio"><?= $row['bio'] ? htmlspecialchars($row['bio']) : '—' ?></p>
            <div class="actions" style="margin-top:10px; display:flex; gap:8px;">
              <a class="btn tiny ghost" href="edit_artist.php?id=<?=$row['id']?>">Edit</a>
              <a class="btn tiny danger" href="delete_artist.php?id=<?=$row['id']?>" onclick="return confirm('Delete this artist?')">Delete</a>
            </div>
          </div>
        </article>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>
</main>
<footer class="site-footer"><div class="wrap"><p>© <?=date('Y')?> Alpha Gallery</p></div></footer>
</body></html>
