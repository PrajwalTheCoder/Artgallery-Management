<?php
require 'config.php';

$artists = $mysqli->query("SELECT id, name FROM artists ORDER BY name ASC");

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title'] ?? '');
    $artist_id  = intval($_POST['artist_id'] ?? 0);
    $price      = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : 0.0;
    $description= trim($_POST['description'] ?? '');
    $status     = 'available';
    $imageName  = null;

    if ($title === '' || $artist_id <= 0) {
        $error = 'Title and artist are required.';
    } else {
    // Handle image upload (optional)
    if (!empty($_FILES['image']['name'])) {
      $allowed = ['jpg', 'jpeg', 'png', 'gif'];
      $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
      $file = $_FILES['image'];
      $tmp  = $file['tmp_name'] ?? '';
      $size = isset($file['size']) ? (int)$file['size'] : 0;
      $maxSize = 5 * 1024 * 1024; // 5MB

      // Basic extension check
      if (!in_array($ext, $allowed)) {
        $error = 'Only JPG/PNG/GIF images are allowed.';
      }

      // Upload error codes
      if ($error === '' && isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
          case UPLOAD_ERR_INI_SIZE:
          case UPLOAD_ERR_FORM_SIZE:
            $error = 'Image is too large. Max 5MB.'; break;
          case UPLOAD_ERR_PARTIAL:
            $error = 'Upload was not completed. Please try again.'; break;
          case UPLOAD_ERR_NO_FILE:
            $error = 'No file uploaded.'; break;
          default:
            $error = 'Upload error (code ' . (int)$file['error'] . ').'; break;
        }
      }

      // Size check
      if ($error === '' && $size > $maxSize) {
        $error = 'Image is too large. Max 5MB.';
      }

      // MIME check via finfo
      if ($error === '' && is_uploaded_file($tmp) && function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi) {
          $mime = finfo_file($fi, $tmp);
          finfo_close($fi);
          $mimeAllowed = ['image/jpeg','image/png','image/gif'];
          if (!in_array($mime, $mimeAllowed)) {
            $error = 'Invalid image file type (detected ' . htmlspecialchars((string)$mime) . ').';
          }
        }
      }

      if ($error === '') {
        // Use filesystem path for moving the file; store only the filename in DB
        $webDir = 'assets/uploads';
        $fsDir  = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
        if (file_exists($fsDir) && !is_dir($fsDir)) {
          $error = 'Upload path exists as a file. Please rename or remove it: ' . $fsDir;
        } else if (!is_dir($fsDir)) {
          if (!mkdir($fsDir, 0777, true)) {
            $error = 'Failed to create upload directory: ' . $fsDir;
          }
        } else if (!is_writable($fsDir)) {
          $error = 'Upload directory is not writable: ' . $fsDir;
        }
        if ($error === '') {
          $imageName = time() . '_' . uniqid() . '.' . $ext;
          $targetFs  = $fsDir . DIRECTORY_SEPARATOR . $imageName;
          if (!is_uploaded_file($tmp) || !move_uploaded_file($tmp, $targetFs)) {
            $imageName = null;
            $error = 'Upload failed. Please ensure the folder is writable: ' . $fsDir;
          }
        }
      }
    }

    // Insert only if no errors
    if ($error === '') {
      $stmt = $mysqli->prepare("INSERT INTO artworks (title, artist_id, price, description, image, status) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param('sidsss', $title, $artist_id, $price, $description, $imageName, $status);
      $ok = $stmt->execute();
      $stmt->close();
      if ($ok) {
        header('Location: index.php');
        exit;
      } else {
        $error = 'Database error: could not add artwork.';
      }
    }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Artwork · Alpha Gallery</title>
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
      <h3 class="card-title">New Artwork</h3>
      <?php if($error): ?><p style="color:red"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <form method="post" enctype="multipart/form-data" id="artworkForm">
        <div class="form-grid">
          <label>
            <span>Title</span>
            <input name="title" placeholder="e.g., Starry Night" required>
            <small class="field-help">Give your artwork a clear, descriptive name.</small>
            <small class="field-error" data-for="title">Title is required.</small>
          </label>
          <label>
            <span>Artist</span>
            <select name="artist_id" required>
              <option value="">Select artist</option>
              <?php while($a = $artists->fetch_assoc()): ?>
                <option value="<?=$a['id']?>"><?=htmlspecialchars($a['name'])?></option>
              <?php endwhile; ?>
            </select>
            <small class="field-error" data-for="artist_id">Please select an artist.</small>
          </label>
          <label>
            <span>Price</span>
            <input name="price" type="number" step="0.01" placeholder="e.g., 1999.00">
            <small class="field-help">Leave empty if not for sale.</small>
          </label>
          <label class="col-span-2">
            <span>Description</span>
            <textarea name="description" placeholder="A short description, materials, dimensions, etc."></textarea>
          </label>
          <label class="col-span-2">
            <span>Image</span>
            <input name="image" type="file" accept="image/*">
            <small class="field-help">Supported: JPG, PNG, GIF. Max 5MB.</small>
            <small class="field-error" data-for="image">Please choose a valid image.</small>
          </label>
          <div id="imgPreviewWrap" class="col-span-2" style="display:none;">
            <img id="imgPreview" alt="Preview" class="img-preview" />
          </div>
        </div>
        <div class="form-actions sticky">
          <input type="submit" class="btn" value="Add Artwork">
        </div>
      </form>
    </div>
  </main>

  <footer class="site-footer">
    <div class="wrap"><p>© <?=date('Y')?> Alpha Gallery</p></div>
  </footer>
<script>
  (function(){
    const form = document.getElementById('artworkForm');
    const showErr = (name, msg) => {
      const el = form.querySelector(`[data-for="${name}"]`);
      if (el) { el.textContent = msg; el.classList.add('show'); }
    };
    const clearErrs = () => form.querySelectorAll('.field-error').forEach(e=>e.classList.remove('show'));

    form.addEventListener('submit', function(e){
      clearErrs();
      let ok = true;
      const title = form.elements['title'];
      const artist = form.elements['artist_id'];
      if (!title.value.trim()) { showErr('title','Title is required.'); ok = false; }
      if (!artist.value) { showErr('artist_id','Please select an artist.'); ok = false; }
      if (!ok) e.preventDefault();
    });

    const input = document.querySelector('input[name="image"]');
    if (!input) return;
    const wrap = document.getElementById('imgPreviewWrap');
    const img  = document.getElementById('imgPreview');
    const MAX = 5 * 1024 * 1024;
    input.addEventListener('change', function(){
      const f = this.files && this.files[0];
      if (!f) { if (wrap) wrap.style.display = 'none'; return; }
      if (f.size > MAX) { showErr('image','Image is too large. Max 5MB.'); this.value=''; if (wrap) wrap.style.display='none'; return; }
      const url = URL.createObjectURL(f);
      if (img) img.src = url;
      if (wrap) wrap.style.display = 'block';
    });
  })();
  </script>
</body>
</html>
