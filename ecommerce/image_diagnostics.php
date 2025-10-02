<?php
// image_diagnostics.php - helps you resolve missing product images
// Visit: http://localhost/ecommerce/image_diagnostics.php
// (Delete after fixing.)

declare(strict_types=1);
session_start();

require __DIR__ . '/db_connect.php';
$pdo = null; $err = null;
try { $pdo = get_pdo(); } catch(Throwable $e){ $err = $e->getMessage(); }

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$imageDir = __DIR__ . '/images';
$files = [];
if (is_dir($imageDir)) {
    foreach (scandir($imageDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        if (is_file($imageDir . '/' . $f)) $files[] = $f;
    }
}
$lowerFileMap = [];
foreach ($files as $f) { $lowerFileMap[strtolower($f)] = $f; }

$products = [];
if ($pdo) {
    $products = $pdo->query('SELECT id,name,image FROM products ORDER BY id')->fetchAll();
}

header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Image Diagnostics</title>
  <style>
    body{font:14px/1.4 system-ui, Arial, sans-serif; margin:24px; color:#111}
    h1{margin-top:0;font-size:1.7rem}
    table{border-collapse:collapse; width:100%; margin-top:1rem}
    th,td{border:1px solid #ddd;padding:6px 8px;font-size:.85rem;vertical-align:top}
    th{background:#f5f7fa;text-align:left}
    .ok{color:#057a36;font-weight:600}
    .miss{color:#b91c1c;font-weight:600}
    code{background:#f1f3f5;padding:2px 4px;border-radius:4px;font-size:.75rem}
    .files{columns:4;-webkit-columns:4;-moz-columns:4;font-size:.72rem;margin-top:.5rem}
    .hint{background:#fffbeb;border:1px solid #fcd34d;padding:8px 10px;border-radius:6px;font-size:.75rem;margin-top:1rem}
    .suggest{font-size:.7rem; color:#555}
  </style>
</head>
<body>
<h1>Product Image Diagnostics</h1>

<?php if ($err): ?>
  <p style="color:#b91c1c;">Database connection error: <?= h($err) ?></p>
<?php endif; ?>

<h2>Images in /images (<?= count($files) ?>)</h2>
<div class="files">
  <?php foreach ($files as $f): ?><?= h($f) ?><br><?php endforeach; ?>
</div>

<h2>Product → Image Mapping</h2>
<table>
  <thead><tr><th>ID</th><th>Name</th><th>DB Image</th><th>Status</th><th>Resolved Filename</th><th>Suggested SQL (if missing)</th></tr></thead>
  <tbody>
  <?php foreach ($products as $p):
        $raw = trim((string)$p['image']);
        $status = 'Missing'; $resolved = ''; $suggestSql = '';
        if ($raw !== '') {
            $lower = strtolower($raw);
            if (isset($lowerFileMap[$lower])) {
                $status = 'OK';
                $resolved = $lowerFileMap[$lower];
            } else {
                // suggest similar
                $candidates = [];
                foreach ($lowerFileMap as $lf=>$orig) {
                    if (strpos($lf, pathinfo($lower, PATHINFO_FILENAME)) !== false) $candidates[] = $orig;
                }
                if ($candidates) {
                    $resolved = 'Maybe: ' . implode(', ', $candidates);
                    $first = $candidates[0];
                    $suggestSql = "UPDATE products SET image='" . addslashes($first) . "' WHERE id=" . (int)$p['id'] . ";";
                }
            }
        }
  ?>
    <tr>
      <td><?= (int)$p['id'] ?></td>
      <td><?= h($p['name']) ?></td>
      <td><code><?= h($raw ?: '(empty)') ?></code></td>
      <td class="<?= $status==='OK' ? 'ok':'miss' ?>"><?= h($status) ?></td>
      <td><?= h($resolved) ?></td>
      <td class="suggest"><?= $suggestSql ? '<code>'.$suggestSql.'</code>' : '' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<div class="hint">
<strong>How to fix 404:</strong>
<ol style="margin:4px 0 0 18px;padding:0;font-size:.75rem;line-height:1.35">
  <li>Ensure the file is physically in <code>/images</code> (same folder as this script shows above).</li>
  <li>Copy the exact name shown in the list (respecting extension) into the product's <code>image</code> column.</li>
  <li>Run the suggested <code>UPDATE</code> SQL if provided.</li>
  <li>Hard refresh product list (Ctrl+F5).</li>
</ol>
</div>

</body>
</html>