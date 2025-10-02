<?php
require 'config.php';

$id = intval($_GET['id'] ?? 0);
$stmt = $mysqli->prepare("DELETE FROM artists WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

header('Location: artists.php');
exit;
