<?php require_once __DIR__ . '/common.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo shop_e($shop_title ?? "Heaven's Gift Bakeshop"); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="shop.css">
</head>
<body>
<header class="shop-nav">
    <a class="shop-brand" href="index.php">
        <span class="shop-brand-icon"><i class="fa-solid fa-store"></i></span>
        <span><strong>Heaven's Gift</strong><small>Bakeshop</small></span>
    </a>
    <nav>
        <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="products.php"><i class="fa-solid fa-bread-slice"></i> Products</a>
        <?php if (isset($_SESSION['customer_id'])): ?>
            <a href="orders.php"><i class="fa-solid fa-receipt"></i> My Orders</a>
            <a class="cart-link" href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart <b><?php echo cart_count(); ?></b></a>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <?php else: ?>
            <a href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
            <a class="nav-cta" href="register.php">Create Account</a>
        <?php endif; ?>
    </nav>
</header>
<main class="shop-main">
