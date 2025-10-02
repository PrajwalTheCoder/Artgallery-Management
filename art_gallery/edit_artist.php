<?php
require 'config.php';

$id = intval($_GET['id'] ?? 0);
$artist = $mysqli->query("SELECT * FROM artists WHERE id = $id")->fetch_assoc();

if (!$artist) {
    die("Artist not found.");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $bio  = trim($_POST['bio'] ?? '');

    if ($name === '') {
        $error = 'Artist name is required.';
    } else {
        $stmt = $mysqli->prepare("UPDATE artists SET name = ?, bio = ? WHERE id = ?");
        $stmt->bind_param('ssi', $name, $bio, $id);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) {
            header('Location: artists.php');
            exit;
        } else {
            $error = 'Database error: could not update artist.';
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Artist · Alpha Gallery</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <?php $cssVer = @filemtime(__DIR__ . '/assets/style.css') ?: time(); ?>
  <link rel="stylesheet" href="assets/style.css?v=<?= $cssVer ?>">
</head>
<body>
  <header class="site-header"><div class="wrap"><div class="brand">ALPHA <span>GALLERY</span></div><nav class="main-nav"><a href="index.php">Artworks</a><a href="artists.php">Artists</a><a class="btn" href="add_artwork.php">+ Add Artwork</a><a class="btn ghost" href="add_artist.php">+ Add Artist</a></nav></div></header>
  <main class="wrap">
    <div class="card" style="margin:24px 0;">
      <?php if($error): ?><p style="color:red"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <form method="post">
        <label>Name
          <input name="name" value="<?=htmlspecialchars($artist['name'])?>" required>
        </label>
        <label>Bio
          <textarea name="bio"><?=htmlspecialchars($artist['bio'])?></textarea>
        </label>
        <input type="submit" class="btn" value="Update Artist">
      </form>
    </div>
  </main>
  <footer class="site-footer"><div class="wrap"><p>© <?=date('Y')?> Alpha Gallery</p></div></footer>
</body>
</html>