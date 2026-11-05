<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$queries = [
    "CREATE TABLE IF NOT EXISTS customer_accounts (
        account_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_customer_account_customer FOREIGN KEY (customer_id)
            REFERENCES customers(customer_id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status ENUM('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        notes VARCHAR(255) NULL,
        CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id)
            REFERENCES customers(customer_id) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS order_details (
        order_detail_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        CONSTRAINT fk_order_details_order FOREIGN KEY (order_id)
            REFERENCES orders(order_id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_order_details_product FOREIGN KEY (product_id)
            REFERENCES products(product_id) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB"
];

$errors = [];
foreach ($queries as $sql) {
    if (!$conn->query($sql)) {
        $errors[] = $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Online Ordering Setup</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
*{box-sizing:border-box}body{margin:0;font-family:Inter,Arial,sans-serif;background:#f7f4f0;color:#2f211b;display:grid;place-items:center;min-height:100vh;padding:24px}.card{width:min(620px,100%);background:#fff;border:1px solid #eadfd4;border-radius:24px;padding:38px;box-shadow:0 24px 70px rgba(49,29,17,.1)}.icon{width:64px;height:64px;border-radius:18px;background:#fff1df;color:#b55b18;display:grid;place-items:center;font-size:25px;margin-bottom:18px}.ok{color:#147a52;background:#eaf8f1;border:1px solid #c9eadb}.err{color:#a52a20;background:#fff0ee;border:1px solid #f2d0cb}.msg{padding:14px 16px;border-radius:12px;margin:18px 0;font-size:14px}.btn{display:inline-flex;align-items:center;gap:9px;background:#9b4f22;color:#fff;padding:12px 18px;border-radius:11px;text-decoration:none;font-weight:700}.muted{color:#8a7c73;font-size:13px;line-height:1.7}
</style>
</head>
<body>
<div class="card">
    <div class="icon"><i class="fa-solid fa-database"></i></div>
    <h1>Online Ordering Setup</h1>
    <?php if (!$errors): ?>
        <div class="msg ok"><i class="fa-solid fa-circle-check"></i> The online ordering tables are ready.</div>
        <p class="muted">The setup added <strong>customer_accounts</strong>, <strong>orders</strong>, and <strong>order_details</strong>. Your existing tables were not recreated or deleted.</p>
        <a class="btn" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    <?php else: ?>
        <div class="msg err"><i class="fa-solid fa-triangle-exclamation"></i> Setup could not be completed.</div>
        <p class="muted"><?php echo htmlspecialchars(implode(' | ', $errors)); ?></p>
        <a class="btn" href="setup_online_orders.php"><i class="fa-solid fa-rotate"></i> Try Again</a>
    <?php endif; ?>
</div>
</body>
</html>
