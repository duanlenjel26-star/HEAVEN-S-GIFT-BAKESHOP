<?php
$current_page = basename($_SERVER["PHP_SELF"]);
?>
<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon"><i class="fa-solid fa-store"></i></div>
        <div>
            <h2>Heaven's Gift</h2>
            <span>Bakeshop</span>
        </div>
    </div>

    <div class="nav-section-label">MAIN MENU</div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span><span>Dashboard</span>
        </a>
        <a href="customers.php" class="nav-item <?php echo ($current_page == 'customers.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-users"></i></span><span>Customers</span>
        </a>
        <a href="products.php" class="nav-item <?php echo ($current_page == 'products.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-bread-slice"></i></span><span>Products</span>
        </a>
        <a href="inventory.php" class="nav-item <?php echo ($current_page == 'inventory.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-boxes-stacked"></i></span><span>Inventory</span>
        </a>
        <a href="suppliers.php" class="nav-item <?php echo ($current_page == 'suppliers.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-truck-fast"></i></span><span>Suppliers</span>
        </a>
        <a href="sales.php" class="nav-item <?php echo ($current_page == 'sales.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-receipt"></i></span><span>Sales</span>
        </a>
        <a href="orders.php" class="nav-item <?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-cart-shopping"></i></span><span>Online Orders</span>
        </a>
        <a href="sales_report.php" class="nav-item <?php echo ($current_page == 'sales_report.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-chart-column"></i></span><span>Sales Reports</span>
        </a>
        <a href="users.php" class="nav-item <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-user-gear"></i></span><span>Users</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="shop/index.php" class="nav-item shop-link" target="_blank">
            <span class="nav-icon"><i class="fa-solid fa-arrow-up-right-from-square"></i></span><span>Customer Store</span>
        </a>
        <a href="logout.php" class="nav-item logout">
            <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span><span>Logout</span>
        </a>
    </div>
</aside>
