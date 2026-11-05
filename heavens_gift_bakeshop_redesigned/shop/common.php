<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/database.php";

function shop_e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ensure_customer_tables($conn) {
    $sqls = [
        "CREATE TABLE IF NOT EXISTS customer_accounts (account_id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, username VARCHAR(50) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_customer_account_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS orders (order_id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, status ENUM('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending', total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, notes VARCHAR(255) NULL, CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE RESTRICT ON UPDATE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS order_details (order_detail_id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, unit_price DECIMAL(10,2) NOT NULL, subtotal DECIMAL(10,2) NOT NULL, CONSTRAINT fk_order_details_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE ON UPDATE CASCADE, CONSTRAINT fk_order_details_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT ON UPDATE CASCADE) ENGINE=InnoDB"
    ];
    foreach ($sqls as $sql) {
        if (!$conn->query($sql)) return false;
    }

    // Add the delivery address to online orders without recreating or deleting tables.
    $columnCheck = $conn->query("SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivery_address'");
    if ($columnCheck && (int)$columnCheck->fetch_assoc()['total'] === 0) {
        if (!$conn->query("ALTER TABLE orders ADD COLUMN delivery_address VARCHAR(255) NULL AFTER total_amount")) return false;
    }

    return true;
}

ensure_customer_tables($conn);

function require_customer() {
    if (!isset($_SESSION['customer_id'])) {
        header('Location: login.php');
        exit();
    }
}

function cart_count() {
    $count = 0;
    foreach (($_SESSION['cart'] ?? []) as $qty) $count += (int)$qty;
    return $count;
}
?>
