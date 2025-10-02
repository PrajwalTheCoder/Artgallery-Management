<?php
require 'config.php';
$id = intval($_GET['id'] ?? 0);
if ($id) {
  // optionally remove image file
  $r = $mysqli->query("SELECT image FROM artworks WHERE id=$id");
  if ($r && $row = $r->fetch_assoc()) {
    if ($row['image']) @unlink(__DIR__.'/assets/uploads/'.$row['image']);
  }
  $stmt = $mysqli->prepare("DELETE FROM artworks WHERE id=?");
  $stmt->bind_param('i',$id);
  $stmt->execute();
}
header('Location: index.php');
exit;
