<?php

$page_title = "Inventory";

require_once "config/database.php";


// =========================
// RESTOCK PRODUCT
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["restock_product"])) {

    $product_id = $_POST["product_id"];
    $stock_in = (int) $_POST["stock_in"];

    if ($stock_in > 0) {

        // Get current product stock
        $stmt = $conn->prepare(
            "SELECT stock
             FROM products
             WHERE product_id = ?"
        );

        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $product_result = $stmt->get_result();

        if ($product_result->num_rows === 1) {

            $product = $product_result->fetch_assoc();

            $current_product_stock = (int) $product["stock"];

            // New stock
            $new_stock =
                $current_product_stock + $stock_in;


            // =========================
            // UPDATE PRODUCTS STOCK
            // =========================

            $update_product = $conn->prepare(
                "UPDATE products
                 SET stock = ?
                 WHERE product_id = ?"
            );

            $update_product->bind_param(
                "ii",
                $new_stock,
                $product_id
            );

            $update_product->execute();
            $update_product->close();


            // =========================
            // CHECK INVENTORY RECORD
            // =========================

            $inventory_check = $conn->prepare(
                "SELECT inventory_id,
                        stock_in,
                        stock_out,
                        current_stock
                 FROM inventory
                 WHERE product_id = ?"
            );

            $inventory_check->bind_param(
                "i",
                $product_id
            );

            $inventory_check->execute();

            $inventory_result =
                $inventory_check->get_result();


            if ($inventory_result->num_rows === 1) {

                $inventory =
                    $inventory_result->fetch_assoc();


                $new_stock_in =
                    (int) $inventory["stock_in"]
                    + $stock_in;


                $new_current_stock =
                    (int) $inventory["current_stock"]
                    + $stock_in;


                // =========================
                // UPDATE INVENTORY
                // =========================

                $update_inventory = $conn->prepare(
                    "UPDATE inventory
                     SET stock_in = ?,
                         current_stock = ?
                     WHERE product_id = ?"
                );

                $update_inventory->bind_param(
                    "iii",
                    $new_stock_in,
                    $new_current_stock,
                    $product_id
                );

                $update_inventory->execute();
                $update_inventory->close();


            } else {

                // =========================
                // CREATE INVENTORY RECORD
                // =========================

                $insert_inventory = $conn->prepare(
                    "INSERT INTO inventory
                    (product_id, stock_in, stock_out, current_stock)
                    VALUES (?, ?, 0, ?)"
                );

                $insert_inventory->bind_param(
                    "iii",
                    $product_id,
                    $stock_in,
                    $stock_in
                );

                $insert_inventory->execute();
                $insert_inventory->close();
            }

            $inventory_check->close();
        }

        $stmt->close();
    }

    header("Location: inventory.php");
    exit();
}


// =========================
// GET INVENTORY
// =========================

$query = "
    SELECT
        inventory.inventory_id,
        inventory.product_id,
        inventory.stock_in,
        inventory.stock_out,
        inventory.current_stock,
        products.product_name,
        products.category,
        products.price
    FROM inventory
    INNER JOIN products
        ON inventory.product_id = products.product_id
    ORDER BY inventory.inventory_id DESC
";

$result = $conn->query($query);


// =========================
// GET PRODUCTS WITHOUT
// INVENTORY RECORD
// =========================

$product_query = "
    SELECT
        product_id,
        product_name,
        category,
        stock
    FROM products
    ORDER BY product_name ASC
";

$product_result =
    $conn->query($product_query);


include "includes/header.php";

?>


<!-- =========================
     PAGE HEADER
========================= -->

<div class="page-header">

    <div>

        <h2>Inventory Management</h2>

        <p>
            Track product stock, stock-in, and stock-out quantities.
        </p>

    </div>


    <button
        class="btn-primary"
        onclick="openRestockModal()"
    >

        <span><i class="fa-solid fa-plus"></i></span>

        Add Stock

    </button>

</div>



<!-- =========================
     INVENTORY TABLE
========================= -->

<div class="table-container">


    <!-- TABLE HEADER -->

    <div class="table-header">

        <div>

            <h3>Inventory List</h3>

            <p>
                Monitor the current stock of your products.
            </p>

        </div>


        <!-- SEARCH -->

        <div class="search-box">

            <span><i class="fa-solid fa-magnifying-glass"></i></span>

            <input
                type="text"
                id="inventorySearch"
                placeholder="Search product..."
                onkeyup="searchInventory()"
            >

        </div>

    </div>



    <!-- TABLE -->

    <div class="table-wrapper">

        <table id="inventoryTable">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>
                        Product
                    </th>

                    <th>
                        Category
                    </th>

                    <th>
                        Stock In
                    </th>

                    <th>
                        Stock Out
                    </th>

                    <th>
                        Current Stock
                    </th>

                    <th>
                        Status
                    </th>

                </tr>

            </thead>


            <tbody>


                <?php if ($result->num_rows > 0): ?>


                    <?php while ($inventory = $result->fetch_assoc()): ?>


                        <tr>


                            <!-- INVENTORY ID -->

                            <td>

                                <span class="customer-id">

                                    #
                                    <?php
                                    echo $inventory["inventory_id"];
                                    ?>

                                </span>

                            </td>



                            <!-- PRODUCT -->

                            <td>

                                <div class="customer-name">


                                    <div class="customer-avatar product-avatar">

                                        <?php

                                        echo strtoupper(
                                            substr(
                                                $inventory["product_name"],
                                                0,
                                                1
                                            )
                                        );

                                        ?>

                                    </div>


                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $inventory["product_name"]
                                        );

                                        ?>

                                    </strong>


                                </div>

                            </td>



                            <!-- CATEGORY -->

                            <td>

                                <span class="category-badge">

                                    <?php

                                    echo htmlspecialchars(
                                        $inventory["category"]
                                    );

                                    ?>

                                </span>

                            </td>



                            <!-- STOCK IN -->

                            <td>

                                <span class="stock-in-value">

                                    +<?php
                                    echo $inventory["stock_in"];
                                    ?>

                                </span>

                            </td>



                            <!-- STOCK OUT -->

                            <td>

                                <span class="stock-out-value">

                                    -<?php
                                    echo $inventory["stock_out"];
                                    ?>

                                </span>

                            </td>



                            <!-- CURRENT STOCK -->

                            <td>

                                <strong class="current-stock-value">

                                    <?php
                                    echo $inventory["current_stock"];
                                    ?>

                                </strong>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <?php

                                $current_stock =
                                    (int) $inventory["current_stock"];


                                if ($current_stock <= 0) {

                                    echo '
                                        <span class="stock-status out">
                                            Out of Stock
                                        </span>
                                    ';

                                } elseif ($current_stock <= 5) {

                                    echo '
                                        <span class="stock-status low">
                                            Low Stock
                                        </span>
                                    ';

                                } else {

                                    echo '
                                        <span class="stock-status available">
                                            Available
                                        </span>
                                    ';

                                }

                                ?>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="7"
                            class="no-data"
                        >

                            No inventory records found.

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>



<!-- =========================
     RESTOCK MODAL
========================= -->

<div
    id="restockModal"
    class="modal"
>


    <div class="modal-content">


        <!-- CLOSE -->

        <button
            class="modal-close"
            onclick="closeRestockModal()"
        >

            ×

        </button>



        <!-- ICON -->

        <div class="modal-icon">

            <i class="fa-solid fa-boxes-stacked"></i>

        </div>



        <h2>
            Add Stock
        </h2>


        <p class="modal-subtitle">

            Add new stock to an existing product.

        </p>



        <!-- RESTOCK FORM -->

        <form method="POST">


            <!-- PRODUCT -->

            <div class="form-group">

                <label for="restock_product_id">

                    Product

                </label>


                <select
                    id="restock_product_id"
                    name="product_id"
                    required
                >

                    <option value="">
                        Select Product
                    </option>


                    <?php while ($product = $product_result->fetch_assoc()): ?>

                        <option
                            value="<?php
                            echo $product["product_id"];
                            ?>"
                        >

                            <?php

                            echo htmlspecialchars(
                                $product["product_name"]
                            );

                            ?>

                            -
                            Current Stock:
                            <?php
                            echo $product["stock"];
                            ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>



            <!-- STOCK IN -->

            <div class="form-group">

                <label for="stock_in">

                    Quantity to Add

                </label>


                <input
                    type="number"
                    id="stock_in"
                    name="stock_in"
                    placeholder="Enter quantity"
                    min="1"
                    required
                >

            </div>



            <!-- BUTTONS -->

            <div class="modal-actions">


                <button
                    type="button"
                    class="btn-cancel"
                    onclick="closeRestockModal()"
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    name="restock_product"
                    class="btn-primary"
                >

                    Add Stock

                </button>


            </div>


        </form>


    </div>

</div>



<!-- =========================
     JAVASCRIPT
========================= -->

<script>


// =========================
// OPEN RESTOCK MODAL
// =========================

function openRestockModal() {

    document.getElementById(
        "restockModal"
    ).style.display = "flex";

}


// =========================
// CLOSE RESTOCK MODAL
// =========================

function closeRestockModal() {

    document.getElementById(
        "restockModal"
    ).style.display = "none";

}


// =========================
// SEARCH INVENTORY
// =========================

function searchInventory() {

    let input =
        document.getElementById(
            "inventorySearch"
        );


    let filter =
        input.value.toLowerCase();


    let table =
        document.getElementById(
            "inventoryTable"
        );


    let rows =
        table.getElementsByTagName(
            "tr"
        );


    for (
        let i = 1;
        i < rows.length;
        i++
    ) {

        let text =
            rows[i].textContent.toLowerCase();


        if (
            text.includes(filter)
        ) {

            rows[i].style.display = "";

        } else {

            rows[i].style.display = "none";

        }

    }

}


// =========================
// CLOSE MODAL WHEN CLICKING
// OUTSIDE
// =========================

window.onclick = function(event) {

    let modal =
        document.getElementById(
            "restockModal"
        );


    if (
        event.target === modal
    ) {

        closeRestockModal();

    }

};

</script>



<?php

include "includes/footer.php";

?>