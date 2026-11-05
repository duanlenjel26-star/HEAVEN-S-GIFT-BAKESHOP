<?php

$page_title = "Dashboard";

require_once "config/database.php";


// ======================================================
// TODAY'S SALES
// ======================================================

$today_sales_query = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0) AS total
    FROM sales
    WHERE sale_date = CURDATE()
");

$today_sales =
    $today_sales_query->fetch_assoc()["total"];


// ======================================================
// TOTAL PRODUCTS
// ======================================================

$product_query = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");

$total_products =
    $product_query->fetch_assoc()["total"];


// ======================================================
// LOW STOCK PRODUCTS
// Low stock = 5 or less
// ======================================================

$low_stock_query = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
    WHERE stock > 0
    AND stock <= 5
");

$low_stock =
    $low_stock_query->fetch_assoc()["total"];


// ======================================================
// OUT OF STOCK PRODUCTS
// ======================================================

$out_stock_query = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
    WHERE stock <= 0
");

$out_stock =
    $out_stock_query->fetch_assoc()["total"];


// ======================================================
// TOTAL INVENTORY ITEMS
// ======================================================

$inventory_query = $conn->query("
    SELECT COUNT(*) AS total
    FROM inventory
");

$total_inventory =
    $inventory_query->fetch_assoc()["total"];


// ======================================================
// RECENT SALES
// ======================================================

$recent_sales_query = $conn->query("
    SELECT
        sales.sale_id,
        sales.sale_date,
        sales.total_amount,
        customers.customer_name
    FROM sales
    LEFT JOIN customers
        ON sales.customer_id = customers.customer_id
    ORDER BY sales.sale_id DESC
    LIMIT 5
");


// ======================================================
// STOCK ALERTS
// ======================================================

$stock_alert_query = $conn->query("
    SELECT
        product_id,
        product_name,
        category,
        stock
    FROM products
    WHERE stock <= 5
    ORDER BY stock ASC, product_name ASC
    LIMIT 8
");


include "includes/header.php";

?>


<!-- ======================================================
     WELCOME
====================================================== -->

<div class="welcome-box dashboard-welcome">

    <div>

        <h2>

            Welcome back,
            <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!

        </h2>

        <p>

            Here's what's happening in Heaven's Gift Bakeshop today.

        </p>

    </div>

    <div class="dashboard-date">

        <?php echo date("F d, Y"); ?>

    </div>

</div>



<!-- ======================================================
     SUMMARY CARDS
====================================================== -->

<div class="dashboard-grid dashboard-summary-grid">


    <!-- TODAY'S SALES -->

    <div class="dashboard-card dashboard-stat-card">

        <div class="card-icon">

            <i class="fa-solid fa-peso-sign"></i>

        </div>

        <div>

            <span>
                Today's Sales
            </span>

            <h2>
                ₱<?php echo number_format($today_sales, 2); ?>
            </h2>

        </div>

    </div>



    <!-- TOTAL PRODUCTS -->

    <div class="dashboard-card dashboard-stat-card">

        <div class="card-icon">

            <i class="fa-solid fa-bread-slice"></i>

        </div>

        <div>

            <span>
                Total Products
            </span>

            <h2>
                <?php echo $total_products; ?>
            </h2>

        </div>

    </div>



    <!-- LOW STOCK -->

    <div class="dashboard-card dashboard-stat-card">

        <div class="card-icon warning-icon">

            <i class="fa-solid fa-triangle-exclamation"></i>

        </div>

        <div>

            <span>
                Low Stock
            </span>

            <h2>
                <?php echo $low_stock; ?>
            </h2>

        </div>

    </div>



    <!-- INVENTORY -->

    <div class="dashboard-card dashboard-stat-card">

        <div class="card-icon">

            <i class="fa-solid fa-boxes-stacked"></i>

        </div>

        <div>

            <span>
                Inventory Items
            </span>

            <h2>
                <?php echo $total_inventory; ?>
            </h2>

        </div>

    </div>

</div>



<!-- ======================================================
     DASHBOARD LOWER SECTION
====================================================== -->

<div class="dashboard-lower-grid">


    <!-- ==================================================
         RECENT SALES
    ================================================== -->

    <div class="dashboard-panel">


        <div class="dashboard-panel-header">

            <div>

                <h3>
                    Recent Sales
                </h3>

                <p>
                    Latest sales transactions
                </p>

            </div>


            <a
                href="sales.php"
                class="view-all-link"
            >

                View All <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>



        <div class="dashboard-sales-list">


            <?php if ($recent_sales_query->num_rows > 0): ?>


                <?php while ($sale = $recent_sales_query->fetch_assoc()): ?>


                    <div class="recent-sale-item">


                        <div class="recent-sale-icon">

                            <i class="fa-solid fa-peso-sign"></i>

                        </div>


                        <div class="recent-sale-info">

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $sale["customer_name"]
                                    ?? "Walk-in Customer"
                                );

                                ?>

                            </strong>

                            <small>

                                Sale #<?php echo $sale["sale_id"]; ?>

                                ·

                                <?php

                                echo date(
                                    "M d, Y",
                                    strtotime($sale["sale_date"])
                                );

                                ?>

                            </small>

                        </div>


                        <div class="recent-sale-amount">

                            ₱<?php

                            echo number_format(
                                $sale["total_amount"],
                                2
                            );

                            ?>

                        </div>


                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="dashboard-empty">

                    <div class="dashboard-empty-icon"><i class="fa-solid fa-receipt"></i></div>

                    <p>
                        No sales transactions yet.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>



    <!-- ==================================================
         STOCK ALERTS
    ================================================== -->

    <div class="dashboard-panel">


        <div class="dashboard-panel-header">

            <div>

                <h3>
                    Stock Alerts
                </h3>

                <p>
                    Products that need attention
                </p>

            </div>


            <a
                href="inventory.php"
                class="view-all-link"
            >

                Inventory <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>



        <div class="stock-alert-list">


            <?php if ($stock_alert_query->num_rows > 0): ?>


                <?php while ($product = $stock_alert_query->fetch_assoc()): ?>


                    <?php

                    $stock =
                        (int) $product["stock"];

                    if ($stock <= 0) {

                        $alert_class =
                            "out";

                        $alert_text =
                            "Out of stock";

                    } else {

                        $alert_class =
                            "low";

                        $alert_text =
                            $stock . " left";

                    }

                    ?>


                    <div class="stock-alert-item">


                        <div
                            class="stock-alert-icon <?php echo $alert_class; ?>"
                        >

                            <?php

                            echo $stock <= 0
                                ? "!"
                                : "!";

                            ?>

                        </div>


                        <div class="stock-alert-info">

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $product["product_name"]
                                );

                                ?>

                            </strong>

                            <small>

                                <?php

                                echo htmlspecialchars(
                                    $product["category"]
                                );

                                ?>

                            </small>

                        </div>


                        <div
                            class="stock-alert-status <?php echo $alert_class; ?>"
                        >

                            <?php echo $alert_text; ?>

                        </div>


                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="dashboard-empty">

                    <div class="dashboard-empty-icon success"><i class="fa-solid fa-circle-check"></i></div>

                    <p>
                        All products have sufficient stock.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>

</div>



<!-- ======================================================
     INVENTORY STATUS
====================================================== -->

<div class="dashboard-panel inventory-status-panel">


    <div class="dashboard-panel-header">

        <div>

            <h3>
                Inventory Status
            </h3>

            <p>
                Quick overview of your product stock
            </p>

        </div>

        <a
            href="products.php"
            class="view-all-link"
        >

            Products <i class="fa-solid fa-arrow-right"></i>

        </a>

    </div>



    <div class="inventory-status-grid">


        <div class="inventory-status-card">

            <div class="inventory-status-number available">

                <?php

                $available_query = $conn->query("
                    SELECT COUNT(*) AS total
                    FROM products
                    WHERE stock > 5
                ");

                echo $available_query->fetch_assoc()["total"];

                ?>

            </div>

            <div>

                <strong>
                    Available
                </strong>

                <span>
                    More than 5 in stock
                </span>

            </div>

        </div>



        <div class="inventory-status-card">

            <div class="inventory-status-number low">

                <?php echo $low_stock; ?>

            </div>

            <div>

                <strong>
                    Low Stock
                </strong>

                <span>
                    1–5 items remaining
                </span>

            </div>

        </div>



        <div class="inventory-status-card">

            <div class="inventory-status-number out">

                <?php echo $out_stock; ?>

            </div>

            <div>

                <strong>
                    Out of Stock
                </strong>

                <span>
                    Needs restocking
                </span>

            </div>

        </div>


    </div>

</div>



<?php

include "includes/footer.php";

?>