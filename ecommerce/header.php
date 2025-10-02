<?php
// shared header
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Alpha.Shop'; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css?v=2">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
  <div class="brand"><a href="index.php">Alpha.Shop</a></div>
    <nav class="main-nav" id="mainNav">
      <a href="index.php">Home</a>
      <a href="#new">New Arrivals</a>
      <a href="#top">Top Selling</a>
      <a href="cart.php" class="nav-cart">Cart (<span id="cartCount"><?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>)</a>
    </nav>
    <div class="nav-actions">
      <button class="hamburger" id="navToggle" aria-label="Menu">☰</button>
    </div>
  </div>
</header>
<main class="page-main">