<?php
require 'config.php';

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$error = '';
// load artists
$artists = $mysqli->query("SELECT id, name FROM artists ORDER BY name ASC");

// handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $artist_id   = intval($_POST['artist_id'] ?? 0);
    $year        = trim($_POST['year'] ?? '');
    $price       = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : 0.0;
    $description = trim($_POST['description'] ?? '');
    $status      = $_POST['status'] ?? 'available';
    $oldImage    = $_POST['old_image'] ?? null;
    $newImage    = $oldImage;

    if ($title === '' || $artist_id <= 0) {
        $error = 'Title and artist are required.';
    } else {
        // handle new image
    if (!empty($_FILES['image']['name'])) {
      $allowed = ['jpg', 'jpeg', 'png', 'gif'];
      $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
      $file = $_FILES['image'];
      $tmp  = $file['tmp_name'] ?? '';
      $size = isset($file['size']) ? (int)$file['size'] : 0;
      $maxSize = 5 * 1024 * 1024; // 5MB
      if (!in_array($ext, $allowed)) {
        $error = 'Only JPG/PNG/GIF images are allowed.';
      }
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
      if ($error === '' && $size > $maxSize) {
        $error = 'Image is too large. Max 5MB.';
      }
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
        $fsDir = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
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
          $newImage = time() . '_' . uniqid() . '.' . $ext;
          $target = $fsDir . DIRECTORY_SEPARATOR . $newImage;
          if (!is_uploaded_file($tmp) || !move_uploaded_file($tmp, $target)) {
            $error = 'Failed to move uploaded file.';
          } else {
            // remove old file if exists and different
            if ($oldImage && file_exists(__DIR__ . '/assets/uploads/' . $oldImage)) {
              @unlink(__DIR__ . '/assets/uploads/' . $oldImage);
            }
          }
        }
      }
    }

        if ($error === '') {
            $stmt = $mysqli->prepare("UPDATE artworks SET title=?, artist_id=?, year=?, price=?, description=?, status=?, image=? WHERE id=?");
            $stmt->bind_param('sisdsssi', $title, $artist_id, $year, $price, $description, $status, $newImage, $id);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                header('Location: index.php');
                exit;
            } else {
                $error = 'Database error: could not update artwork.';
            }
        }
    }
}

// load existing artwork (for GET or after failed POST)
$stmt = $mysqli->prepare("SELECT * FROM artworks WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$artwork = $res->fetch_assoc();
$stmt->close();
if (!$artwork) { header('Location: index.php'); exit; }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Artwork · Alpha Gallery</title>
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
      <h3 class="card-title">Edit Artwork</h3>
      <?php if($error): ?><p style="color:red"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <form method="post" enctype="multipart/form-data" id="editArtworkForm">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="old_image" value="<?=htmlspecialchars($artwork['image'])?>">
        <div class="form-grid">
          <label>
            <span>Title</span>
            <input name="title" value="<?=htmlspecialchars($artwork['title'])?>" placeholder="e.g., Starry Night" required>
            <small class="field-help">Update the artwork name.</small>
            <small class="field-error" data-for="title">Title is required.</small>
          </label>
          <label>
            <span>Artist</span>
            <select name="artist_id" required>
              <?php while($artist = $artists->fetch_assoc()): ?>
                <option value="<?= $artist['id'] ?>" <?= $artist['id'] == $artwork['artist_id'] ? 'selected' : '' ?>><?= htmlspecialchars($artist['name']) ?></option>
              <?php endwhile; ?>
            </select>
            <small class="field-error" data-for="artist_id">Please select an artist.</small>
          </label>
          <label>
            <span>Year</span>
            <input name="year" type="number" value="<?=htmlspecialchars($artwork['year'])?>" placeholder="e.g., 2021" required>
          </label>
          <label>
            <span>Price</span>
            <input name="price" type="number" step="0.01" value="<?=htmlspecialchars($artwork['price'])?>" placeholder="e.g., 1999.00">
            <small class="field-help">Leave empty if not for sale.</small>
          </label>
          <label class="col-span-2">
            <span>Description</span>
            <textarea name="description" placeholder="A short description, materials, dimensions, etc."><?=htmlspecialchars($artwork['description'])?></textarea>
          </label>
          <label>
            <span>Status</span>
            <input name="status" value="<?=htmlspecialchars($artwork['status'])?>" placeholder="available | sold | on hold" required>
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
          <input type="submit" class="btn" value="Update Artwork">
        </div>
      </form>
    </div>
  </main>
  <footer class="site-footer"><div class="wrap"><p>© <?=date('Y')?> Alpha Gallery</p></div></footer>
  <script>
    (function(){
      const form = document.getElementById('editArtworkForm');
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
        const year = form.elements['year'];
        if (!title.value.trim()) { showErr('title','Title is required.'); ok = false; }
        if (!artist.value) { showErr('artist_id','Please select an artist.'); ok = false; }
        if (!year.value) { showErr('year','Year is required.'); ok = false; }
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
