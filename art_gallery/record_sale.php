<?php
require 'config.php';

$artwork_id = intval($_GET['id'] ?? $_POST['artwork_id'] ?? 0);
$error = '';
// If POST: save sale
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $artwork_id = intval($_POST['artwork_id'] ?? 0);
    $buyer      = trim($_POST['buyer_name'] ?? '');
    $sale_price = isset($_POST['sale_price']) ? floatval($_POST['sale_price']) : 0.0;
    $sale_date  = trim($_POST['sale_date'] ?? '');

    if ($artwork_id <= 0 || $sale_price <= 0) {
        $error = 'Artwork and sale price are required.';
    } else {
        $stmt = $mysqli->prepare("INSERT INTO sales (artwork_id, buyer_name, sale_price, sale_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isds', $artwork_id, $buyer, $sale_price, $sale_date);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) {
            // update artwork status
            $u = $mysqli->prepare("UPDATE artworks SET status = 'sold', price = ? WHERE id = ?");
            $u->bind_param('di', $sale_price, $artwork_id);
            $u->execute();
            $u->close();

            header("Location: view_artwork.php?id={$artwork_id}");
            exit;
        } else {
            $error = 'Database error: could not record sale.';
        }
    }
}

// Load artwork for form display
$stmt = $mysqli->prepare("SELECT id, title, price, status FROM artworks WHERE id = ?");
$stmt->bind_param('i', $artwork_id);
$stmt->execute();
$res = $stmt->get_result();
$art = $res->fetch_assoc();
$stmt->close();

if (!$art) {
    echo "Artwork not found.";
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Record Sale</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="container">
    <div class="header"><h1>Record Sale</h1><nav><a href="index.php">Artworks</a> | <a href="view_artwork.php?id=<?=$artwork_id?>">Back</a></nav></div>
    <div class="card">
      <?php if($error): ?><p style="color:red"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <h3><?=htmlspecialchars($art['title'])?> (status: <?=htmlspecialchars($art['status'])?>)</h3>
      <form method="post">
        <input type="hidden" name="artwork_id" value="<?=intval($art['id'])?>">
        <label>Buyer Name<input name="buyer_name"></label>
        <label>Sale Price<input name="sale_price" required value="<?=htmlspecialchars($art['price'])?>"></label>
        <label>Sale Date<input type="date" name="sale_date" value="<?=date('Y-m-d')?>"></label>
        <input type="submit" value="Save Sale">
      </form>
    </div>
  </div>
</body>
</html>
