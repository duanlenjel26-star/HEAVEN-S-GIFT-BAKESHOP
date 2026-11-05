<?php
require_once 'common.php';
$shop_title = "Customer Store";
$result = $conn->query("SELECT product_id, product_name, category, price, stock FROM products WHERE stock > 0 ORDER BY product_id DESC LIMIT 8");
include 'header.php';
?>
<section class="hero">
    <div class="hero-copy">
        <div class="eyebrow">Heaven's Gift Bakeshop</div>
        <h1>Fresh bakes,<br>simple ordering.</h1>
        <p>Browse available products, add your favorites to the cart, and submit your order online. Your order will appear in the bakeshop system for confirmation.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="products.php"><i class="fa-solid fa-bread-slice"></i> Browse Products</a>
            <?php if (!isset($_SESSION['customer_id'])): ?><a class="btn" style="background:rgba(255,255,255,.12);color:#fff" href="register.php"><i class="fa-solid fa-user-plus"></i> Create Account</a><?php endif; ?>
        </div>
    </div>
    <div class="hero-card">
        <div class="big-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <h3>Order from the store</h3>
        <p>Create a customer account, choose products, set quantities, and submit your order. You can track your order status from <strong>My Orders</strong>.</p>
        <a class="btn btn-dark" href="products.php">Start Shopping <i class="fa-solid fa-arrow-right"></i></a>
    </div>
</section>
<div class="section-head"><div><h2>Featured Products</h2><p>Currently available from Heaven's Gift Bakeshop.</p></div><a class="btn btn-outline" href="products.php">View All</a></div>
<div class="product-grid">
<?php while ($product = $result->fetch_assoc()): ?>
    <article class="product-card">
        <div class="product-art"><i class="fa-solid fa-bread-slice"></i></div>
        <div class="category"><?php echo shop_e($product['category']); ?></div>
        <h3><?php echo shop_e($product['product_name']); ?></h3>
        <div class="price-row"><span class="price">₱<?php echo number_format($product['price'],2); ?></span><span class="stock"><?php echo (int)$product['stock']; ?> available</span></div>
    </article>
<?php endwhile; ?>
</div>
<?php include 'footer.php'; ?>
