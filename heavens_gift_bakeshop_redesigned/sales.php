<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$page_title = "Sales";
require_once "config/database.php";

// ======================================================
// PROCESS SALE
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["complete_sale"])) {

    $customer_id = (int) $_POST["customer_id"];
    $sale_items = $_POST["items"] ?? [];

    if ($customer_id <= 0 || empty($sale_items)) {
        header("Location: sales.php?error=invalid");
        exit();
    }


    // --------------------------------------------------
    // START TRANSACTION
    // --------------------------------------------------

    $conn->begin_transaction();

    try {

        $total_amount = 0;
        $validated_items = [];


        // --------------------------------------------------
        // VALIDATE ALL PRODUCTS FIRST
        // --------------------------------------------------

        foreach ($sale_items as $item) {

            $product_id = (int) ($item["product_id"] ?? 0);
            $quantity = (int) ($item["quantity"] ?? 0);

            if ($product_id <= 0 || $quantity <= 0) {
                throw new Exception("Invalid product or quantity.");
            }


            // Get product information

            $stmt = $conn->prepare(
                "SELECT product_id,
                        product_name,
                        price,
                        stock
                 FROM products
                 WHERE product_id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param(
                "i",
                $product_id
            );

            $stmt->execute();

            $product_result =
                $stmt->get_result();


            if ($product_result->num_rows !== 1) {

                $stmt->close();

                throw new Exception(
                    "Product not found."
                );
            }


            $product =
                $product_result->fetch_assoc();


            $stmt->close();


            // Check stock

            if ($quantity > (int) $product["stock"]) {

                throw new Exception(
                    "Not enough stock for " .
                    $product["product_name"] .
                    "."
                );
            }


            // Calculate subtotal

            $price = (float) $product["price"];

            $subtotal =
                $price * $quantity;


            $total_amount += $subtotal;


            $validated_items[] = [
                "product_id" => $product_id,
                "product_name" => $product["product_name"],
                "price" => $price,
                "quantity" => $quantity,
                "subtotal" => $subtotal
            ];
        }



        // --------------------------------------------------
        // INSERT SALE
        // --------------------------------------------------

        $user_id =
            (int) $_SESSION["user_id"];


        $sale_date =
            date("Y-m-d");


        $stmt = $conn->prepare(
            "INSERT INTO sales
            (customer_id, user_id, sale_date, total_amount)
            VALUES (?, ?, ?, ?)"
        );


        $stmt->bind_param(
            "iisd",
            $customer_id,
            $user_id,
            $sale_date,
            $total_amount
        );


        $stmt->execute();


        $sale_id =
            $conn->insert_id;


        $stmt->close();



        // --------------------------------------------------
        // INSERT SALE DETAILS
        // --------------------------------------------------

        foreach ($validated_items as $item) {

            $product_id =
                $item["product_id"];

            $quantity =
                $item["quantity"];

            $subtotal =
                $item["subtotal"];


            // Insert sales detail

            $detail_stmt = $conn->prepare(
                "INSERT INTO sales_details
                (sale_id, product_id, quantity, subtotal)
                VALUES (?, ?, ?, ?)"
            );


            $detail_stmt->bind_param(
                "iiid",
                $sale_id,
                $product_id,
                $quantity,
                $subtotal
            );


            $detail_stmt->execute();

            $detail_stmt->close();



            // --------------------------------------------------
            // UPDATE PRODUCTS STOCK
            // --------------------------------------------------

            $product_update = $conn->prepare(
                "UPDATE products
                 SET stock = stock - ?
                 WHERE product_id = ?"
            );


            $product_update->bind_param(
                "ii",
                $quantity,
                $product_id
            );


            $product_update->execute();

            $product_update->close();



            // --------------------------------------------------
            // UPDATE INVENTORY
            // --------------------------------------------------

            $inventory_stmt = $conn->prepare(
                "SELECT inventory_id,
                        stock_out,
                        current_stock
                 FROM inventory
                 WHERE product_id = ?
                 FOR UPDATE"
            );


            $inventory_stmt->bind_param(
                "i",
                $product_id
            );


            $inventory_stmt->execute();


            $inventory_result =
                $inventory_stmt->get_result();


            if ($inventory_result->num_rows === 1) {

                $inventory =
                    $inventory_result->fetch_assoc();


                $new_stock_out =
                    (int) $inventory["stock_out"]
                    + $quantity;


                $new_current_stock =
                    (int) $inventory["current_stock"]
                    - $quantity;


                if ($new_current_stock < 0) {

                    throw new Exception(
                        "Inventory stock cannot be negative."
                    );
                }


                $inventory_update =
                    $conn->prepare(
                        "UPDATE inventory
                         SET stock_out = ?,
                             current_stock = ?
                         WHERE product_id = ?"
                    );


                $inventory_update->bind_param(
                    "iii",
                    $new_stock_out,
                    $new_current_stock,
                    $product_id
                );


                $inventory_update->execute();

                $inventory_update->close();


            } else {

                /*
                 * If an inventory record does not exist,
                 * create one based on the remaining product stock.
                 */

                $remaining_stock =
                    0;


                $product_stock_stmt =
                    $conn->prepare(
                        "SELECT stock
                         FROM products
                         WHERE product_id = ?"
                    );


                $product_stock_stmt->bind_param(
                    "i",
                    $product_id
                );


                $product_stock_stmt->execute();


                $stock_result =
                    $product_stock_stmt->get_result();


                if ($stock_result->num_rows === 1) {

                    $stock_data =
                        $stock_result->fetch_assoc();


                    $remaining_stock =
                        (int) $stock_data["stock"];
                }


                $product_stock_stmt->close();


                $inventory_insert =
                    $conn->prepare(
                        "INSERT INTO inventory
                        (product_id, stock_in, stock_out, current_stock)
                        VALUES (?, 0, ?, ?)"
                    );


                $inventory_insert->bind_param(
                    "iii",
                    $product_id,
                    $quantity,
                    $remaining_stock
                );


                $inventory_insert->execute();

                $inventory_insert->close();
            }


            $inventory_stmt->close();
        }



        // --------------------------------------------------
        // COMMIT TRANSACTION
        // --------------------------------------------------

        $conn->commit();


        header(
            "Location: sales.php?success=1"
        );

        exit();


    } catch (Exception $e) {

        // Cancel all changes if something goes wrong

        $conn->rollback();


        header(
            "Location: sales.php?error=" .
            urlencode($e->getMessage())
        );

        exit();
    }
}



// ======================================================
// GET CUSTOMERS
// ======================================================

$customer_query = "
    SELECT
        customer_id,
        customer_name,
        contact_number
    FROM customers
    ORDER BY customer_name ASC
";

$customer_result =
    $conn->query($customer_query);



// ======================================================
// GET PRODUCTS
// ======================================================

$product_query = "
    SELECT
        product_id,
        product_name,
        category,
        price,
        stock
    FROM products
    ORDER BY product_name ASC
";

$product_result =
    $conn->query($product_query);


$products = [];

while ($product = $product_result->fetch_assoc()) {

    $products[] = $product;
}



// ======================================================
// GET RECENT SALES
// ======================================================

$sales_query = "
    SELECT
        sales.sale_id,
        sales.sale_date,
        sales.total_amount,
        customers.customer_name,
        users.full_name
    FROM sales
    LEFT JOIN customers
        ON sales.customer_id = customers.customer_id
    LEFT JOIN users
        ON sales.user_id = users.user_id
    ORDER BY sales.sale_id DESC
";

$sales_result =
    $conn->query($sales_query);



// ======================================================
// INCLUDE HEADER
// ======================================================

include "includes/header.php";

?>



<!-- ======================================================
     PAGE HEADER
====================================================== -->

<div class="page-header">

    <div>

        <h2>Sales Management</h2>

        <p>
            Record customer purchases and manage sales transactions.
        </p>

    </div>


    <button
        class="btn-primary"
        onclick="openSaleModal()"
    >

        <span><i class="fa-solid fa-plus"></i></span>

        New Sale

    </button>

</div>



<!-- ======================================================
     SUCCESS MESSAGE
====================================================== -->

<?php if (isset($_GET["success"])): ?>

    <div class="sales-message success">

        <span><i class="fa-solid fa-check"></i></span>

        Sale completed successfully.

    </div>

<?php endif; ?>



<!-- ======================================================
     ERROR MESSAGE
====================================================== -->

<?php if (isset($_GET["error"])): ?>

    <div class="sales-message error">

        <span>!</span>

        <?php

        if ($_GET["error"] === "invalid") {

            echo "Please select a customer and at least one product.";

        } else {

            echo htmlspecialchars(
                $_GET["error"]
            );

        }

        ?>

    </div>

<?php endif; ?>



<!-- ======================================================
     SALES TABLE
====================================================== -->

<div class="table-container">


    <div class="table-header">

        <div>

            <h3>Sales Transactions</h3>

            <p>
                View recorded sales transactions.
            </p>

        </div>


        <!-- SEARCH -->

        <div class="search-box">

            <span><i class="fa-solid fa-magnifying-glass"></i></span>

            <input
                type="text"
                id="salesSearch"
                placeholder="Search sales..."
                onkeyup="searchSales()"
            >

        </div>

    </div>



    <div class="table-wrapper">

        <table id="salesTable">

            <thead>

                <tr>

                    <th>
                        Sale ID
                    </th>

                    <th>
                        Customer
                    </th>

                    <th>
                        Sale Date
                    </th>

                    <th>
                        Processed By
                    </th>

                    <th>
                        Total Amount
                    </th>

                </tr>

            </thead>


            <tbody>


                <?php if ($sales_result->num_rows > 0): ?>


                    <?php while ($sale = $sales_result->fetch_assoc()): ?>


                        <tr>


                            <!-- SALE ID -->

                            <td>

                                <span class="customer-id">

                                    #

                                    <?php
                                    echo $sale["sale_id"];
                                    ?>

                                </span>

                            </td>



                            <!-- CUSTOMER -->

                            <td>

                                <div class="customer-name">

                                    <div class="customer-avatar">

                                        <?php

                                        echo strtoupper(
                                            substr(
                                                $sale["customer_name"] ?? "W",
                                                0,
                                                1
                                            )
                                        );

                                        ?>

                                    </div>


                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $sale["customer_name"] ?? "Walk-in Customer"
                                        );

                                        ?>

                                    </strong>

                                </div>

                            </td>



                            <!-- DATE -->

                            <td>

                                <?php

                                echo date(
                                    "M d, Y",
                                    strtotime($sale["sale_date"])
                                );

                                ?>

                            </td>



                            <!-- USER -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $sale["full_name"] ?? "Unknown"
                                );

                                ?>

                            </td>



                            <!-- TOTAL -->

                            <td>

                                <strong class="sale-total">

                                    ₱

                                    <?php

                                    echo number_format(
                                        $sale["total_amount"],
                                        2
                                    );

                                    ?>

                                </strong>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="5"
                            class="no-data"
                        >

                            No sales transactions found.

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>



<!-- ======================================================
     NEW SALE MODAL
====================================================== -->

<div
    id="saleModal"
    class="modal sale-modal"
>


    <div class="modal-content sale-modal-content">


        <!-- CLOSE -->

        <button
            class="modal-close"
            onclick="closeSaleModal()"
        >

            ×

        </button>



        <!-- ICON -->

        <div class="modal-icon">

            <i class="fa-solid fa-peso-sign"></i>

        </div>



        <h2>
            New Sale
        </h2>


        <p class="modal-subtitle">

            Select the customer and products for this transaction.

        </p>



        <!-- SALE FORM -->

        <form
            method="POST"
            id="saleForm"
            onsubmit="return validateSale()"
        >


            <!-- CUSTOMER -->

            <div class="form-group">

                <label for="customer_id">

                    Customer

                </label>


                <select
                    id="customer_id"
                    name="customer_id"
                    required
                >

                    <option value="">

                        Select Customer

                    </option>


                    <?php while ($customer = $customer_result->fetch_assoc()): ?>

                        <option
                            value="<?php
                            echo $customer["customer_id"];
                            ?>"
                        >

                            <?php

                            echo htmlspecialchars(
                                $customer["customer_name"]
                            );

                            ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>



            <!-- PRODUCTS -->

            <div class="sale-products-header">

                <h3>
                    Products
                </h3>


                <button
                    type="button"
                    class="btn-add-item"
                    onclick="addSaleItem()"
                >

                    <i class="fa-solid fa-plus"></i> Add Item

                </button>

            </div>



            <div
                id="saleItems"
                class="sale-items"
            >

                <!-- ITEMS WILL BE ADDED HERE -->

            </div>



            <!-- TOTAL -->

            <div class="sale-total-box">

                <span>
                    Total Amount
                </span>


                <strong id="saleTotal">

                    ₱0.00

                </strong>

            </div>



            <!-- BUTTONS -->

            <div class="modal-actions">


                <button
                    type="button"
                    class="btn-cancel"
                    onclick="closeSaleModal()"
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    name="complete_sale"
                    class="btn-primary"
                >

                    Complete Sale

                </button>


            </div>


        </form>

    </div>

</div>



<!-- ======================================================
     JAVASCRIPT
====================================================== -->

<script>


// ======================================================
// PRODUCT DATA FROM PHP
// ======================================================

const products = <?php echo json_encode($products); ?>;


// ======================================================
// ITEM COUNTER
// ======================================================

let itemCounter = 0;


// ======================================================
// OPEN SALE MODAL
// ======================================================

function openSaleModal() {

    document.getElementById(
        "saleModal"
    ).style.display = "flex";


    // Add first item automatically

    if (
        document.getElementById(
            "saleItems"
        ).children.length === 0
    ) {

        addSaleItem();

    }

}


// ======================================================
// CLOSE SALE MODAL
// ======================================================

function closeSaleModal() {

    document.getElementById(
        "saleModal"
    ).style.display = "none";

}


// ======================================================
// ADD SALE ITEM
// ======================================================

function addSaleItem() {

    const container =
        document.getElementById(
            "saleItems"
        );


    const itemIndex =
        itemCounter++;


    const row =
        document.createElement("div");


    row.className =
        "sale-item-row";


    row.setAttribute(
        "data-index",
        itemIndex
    );


    let productOptions = `
        <option value="">
            Select Product
        </option>
    `;


    products.forEach(function(product) {

        const disabled =
            parseInt(product.stock) <= 0
                ? "disabled"
                : "";


        productOptions += `
            <option
                value="${product.product_id}"
                ${disabled}
            >
                ${escapeHtml(product.product_name)}
                - ₱${parseFloat(product.price).toFixed(2)}
                - Stock: ${product.stock}
            </option>
        `;

    });


    row.innerHTML = `

        <div class="sale-product-field">

            <label>
                Product
            </label>

            <select
                name="items[${itemIndex}][product_id]"
                class="sale-product-select"
                onchange="updateSaleItem(this)"
                required
            >

                ${productOptions}

            </select>

        </div>


        <div class="sale-quantity-field">

            <label>
                Quantity
            </label>

            <input
                type="number"
                name="items[${itemIndex}][quantity]"
                class="sale-quantity-input"
                value="1"
                min="1"
                onchange="updateSaleItem(this)"
                oninput="updateSaleItem(this)"
                required
            >

        </div>


        <div class="sale-subtotal-field">

            <label>
                Subtotal
            </label>

            <strong class="item-subtotal">
                ₱0.00
            </strong>

        </div>


        <button
            type="button"
            class="btn-remove-item"
            onclick="removeSaleItem(this)"
            title="Remove item"
        >

            ×

        </button>

    `;


    container.appendChild(row);

}


// ======================================================
// UPDATE SALE ITEM
// ======================================================

function updateSaleItem(element) {

    const row =
        element.closest(
            ".sale-item-row"
        );


    const select =
        row.querySelector(
            ".sale-product-select"
        );


    const quantityInput =
        row.querySelector(
            ".sale-quantity-input"
        );


    const subtotalElement =
        row.querySelector(
            ".item-subtotal"
        );


    const productId =
        parseInt(select.value);


    const quantity =
        parseInt(quantityInput.value) || 0;


    const product =
        products.find(
            p =>
                parseInt(p.product_id)
                === productId
        );


    if (!product) {

        subtotalElement.textContent =
            "₱0.00";

        updateTotal();

        return;
    }


    const stock =
        parseInt(product.stock);


    // Prevent quantity greater than stock

    if (quantity > stock) {

        quantityInput.value =
            stock;

    }


    const finalQuantity =
        parseInt(quantityInput.value)
        || 0;


    const subtotal =
        parseFloat(product.price)
        * finalQuantity;


    subtotalElement.textContent =
        "₱" + subtotal.toFixed(2);


    updateTotal();

}


// ======================================================
// REMOVE SALE ITEM
// ======================================================

function removeSaleItem(button) {

    const row =
        button.closest(
            ".sale-item-row"
        );


    row.remove();


    updateTotal();

}


// ======================================================
// UPDATE TOTAL
// ======================================================

function updateTotal() {

    let total = 0;


    const rows =
        document.querySelectorAll(
            ".sale-item-row"
        );


    rows.forEach(function(row) {

        const select =
            row.querySelector(
                ".sale-product-select"
            );


        const quantityInput =
            row.querySelector(
                ".sale-quantity-input"
            );


        const productId =
            parseInt(select.value);


        const quantity =
            parseInt(quantityInput.value)
            || 0;


        const product =
            products.find(
                p =>
                    parseInt(p.product_id)
                    === productId
            );


        if (product) {

            total +=
                parseFloat(product.price)
                * quantity;

        }

    });


    document.getElementById(
        "saleTotal"
    ).textContent =
        "₱" + total.toFixed(2);

}


// ======================================================
// VALIDATE SALE
// ======================================================

function validateSale() {

    const customer =
        document.getElementById(
            "customer_id"
        ).value;


    const rows =
        document.querySelectorAll(
            ".sale-item-row"
        );


    if (!customer) {

        alert(
            "Please select a customer."
        );

        return false;

    }


    if (rows.length === 0) {

        alert(
            "Please add at least one product."
        );

        return false;

    }


    const selectedProducts = [];


    for (
        let i = 0;
        i < rows.length;
        i++
    ) {

        const select =
            rows[i].querySelector(
                ".sale-product-select"
            );


        const quantityInput =
            rows[i].querySelector(
                ".sale-quantity-input"
            );


        const productId =
            parseInt(select.value);


        const quantity =
            parseInt(quantityInput.value);


        if (!productId) {

            alert(
                "Please select a product."
            );

            return false;

        }


        if (
            !quantity ||
            quantity <= 0
        ) {

            alert(
                "Quantity must be greater than zero."
            );

            return false;

        }


        const product =
            products.find(
                p =>
                    parseInt(p.product_id)
                    === productId
            );


        if (
            product &&
            quantity > parseInt(product.stock)
        ) {

            alert(
                "Not enough stock for " +
                product.product_name +
                "."
            );

            return false;

        }


        // Prevent duplicate product rows

        if (
            selectedProducts.includes(productId)
        ) {

            alert(
                "A product cannot be added twice. " +
                "Please combine its quantity into one item."
            );

            return false;

        }


        selectedProducts.push(
            productId
        );

    }


    return confirm(
        "Are you sure you want to complete this sale?"
    );

}


// ======================================================
// SEARCH SALES
// ======================================================

function searchSales() {

    const input =
        document.getElementById(
            "salesSearch"
        );


    const filter =
        input.value.toLowerCase();


    const table =
        document.getElementById(
            "salesTable"
        );


    const rows =
        table.getElementsByTagName(
            "tr"
        );


    for (
        let i = 1;
        i < rows.length;
        i++
    ) {

        const text =
            rows[i]
                .textContent
                .toLowerCase();


        if (
            text.includes(filter)
        ) {

            rows[i].style.display = "";

        } else {

            rows[i].style.display =
                "none";

        }

    }

}


// ======================================================
// ESCAPE HTML
// ======================================================

function escapeHtml(text) {

    const div =
        document.createElement(
            "div"
        );


    div.textContent = text;


    return div.innerHTML;

}


// ======================================================
// CLOSE MODAL WHEN CLICKING OUTSIDE
// ======================================================

window.onclick = function(event) {

    const modal =
        document.getElementById(
            "saleModal"
        );


    if (
        event.target === modal
    ) {

        closeSaleModal();

    }

};

</script>



<?php

include "includes/footer.php";

?>