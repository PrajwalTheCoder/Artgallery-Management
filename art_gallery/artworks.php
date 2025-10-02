<?php
require 'config.php';
$artists = $mysqli->query("SELECT id,name FROM artists ORDER BY name");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $artist_id = intval($_POST['artist_id']);
    $year = $_POST['year'] ?? '';
    $price = $_POST['price'] ? floatval($_POST['price']) : null;
    $desc = $_POST['description'] ?? '';
    $imageName = null;

    // handle image upload
    if (!empty($_FILES['image']['name'])) {
        $uploaddir = __DIR__ . '/assets/uploads/';
        if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = time() . '_' . preg_replace('/[^a-z0-9._-]/i','',basename($_FILES['image']['name']));
        $target = $uploaddir . $imageName;
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    }

    $stmt = $mysqli->prepare("INSERT INTO artworks (title,artist_id,year,price,description,image) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('sisdss', $title, $artist_id, $year, $price, $desc, $imageName);
    $stmt->execute();
    header('Location: index.php');
    exit;
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Add Artwork</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="container">
  <div class="header"><h1>Add Artwork</h1><nav><a href="index.php">Back</a></nav></div>
  <div class="card">
    <form method="post" enctype="multipart/form-data">
      <label>Title<input name="title" required></label>
      <label>Artist
        <select name="artist_id" required>
          <option value="">Select artist</option>
          <?php while($a = $artists->fetch_assoc()): ?>
            <option value="<?=$a['id']?>"><?=htmlspecialchars($a['name'])?></option>
          <?php endwhile; ?>
        </select>
      </label>
      <div class="form-row">
        <input name="year" placeholder="Year (e.g. 2024)">
        <input name="price" placeholder="Price (e.g. 1200.00)">
      </div>
      <label>Image<input type="file" name="image" accept="image/*"></label>
      <label>Description<textarea name="description"></textarea></label>
      <input type="submit" value="Add Artwork">
    </form>
  </div>
</div>
</body></html>
