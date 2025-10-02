<?php
require 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $bio  = trim($_POST['bio'] ?? '');

    if ($name === '') {
        $error = 'Artist name is required.';
    } else {
        $stmt = $mysqli->prepare("INSERT INTO artists (name, bio) VALUES (?, ?)");
        $stmt->bind_param('ss', $name, $bio);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) {
            header('Location: artists.php');
            exit;
        } else {
            $error = 'Database error: could not add artist.';
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Artist · Alpha Gallery</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <?php $cssVer = @filemtime(__DIR__ . '/assets/style.css') ?: time(); ?>
  <link rel="stylesheet" href="assets/style.css?v=<?= $cssVer ?>">
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
      <h3 class="card-title">New Artist</h3>
      <?php if($error): ?><p style="color:red"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <form method="post" id="artistForm">
        <div class="form-grid">
          <label class="col-span-2">
            <span>Name</span>
            <input id="name" name="name" placeholder="e.g., Vincent van Gogh" required>
            <small class="field-help">Public display name for the artist.</small>
            <small class="field-error" data-for="name">Name is required.</small>
          </label>
          <label class="col-span-2">
            <span>Bio</span>
            <textarea id="bio" name="bio" placeholder="Short biography or details."></textarea>
          </label>
        </div>
        <div class="form-actions sticky">
          <input type="submit" class="btn" value="Add Artist">
        </div>
      </form>
    </div>
  </main>

  <footer class="site-footer">
    <div class="wrap"><p>© <?=date('Y')?> Alpha Gallery</p></div>
  </footer>
<script>
  (function(){
    const form = document.getElementById('artistForm');
    const showErr = (name, msg) => {
      const el = form.querySelector(`[data-for="${name}"]`);
      if (el) { el.textContent = msg; el.classList.add('show'); }
    };
    const clearErrs = () => form.querySelectorAll('.field-error').forEach(e=>e.classList.remove('show'));
    form.addEventListener('submit', function(e){
      clearErrs();
      let ok = true;
      const name = form.elements['name'];
      if (!name.value.trim()) { showErr('name','Name is required.'); ok = false; }
      if (!ok) e.preventDefault();
    });
  })();
  </script>
</body>
</html>
