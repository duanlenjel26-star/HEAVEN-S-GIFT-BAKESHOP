<?php

$page_title = "Products";

require_once "config/database.php";


// =========================
// ADD PRODUCT
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_product"])) {

    $product_name = trim($_POST["product_name"]);
    $category = trim($_POST["category"]);
    $price = $_POST["price"];
    $stock = $_POST["stock"];

    if (
        !empty($product_name) &&
        !empty($category) &&
        $price !== "" &&
        $stock !== ""
    ) {

        $stmt = $conn->prepare(
            "INSERT INTO products
            (product_name, category, price, stock)
            VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssdi",
            $product_name,
            $category,
            $price,
            $stock
        );

        $stmt->execute();
        $stmt->close();

        header("Location: products.php");
        exit();
    }
}


// =========================
// UPDATE PRODUCT
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_product"])) {

    $product_id = $_POST["product_id"];
    $product_name = trim($_POST["product_name"]);
    $category = trim($_POST["category"]);
    $price = $_POST["price"];
    $stock = $_POST["stock"];

    if (
        !empty($product_name) &&
        !empty($category) &&
        $price !== "" &&
        $stock !== ""
    ) {

        $stmt = $conn->prepare(
            "UPDATE products
             SET product_name = ?,
                 category = ?,
                 price = ?,
                 stock = ?
             WHERE product_id = ?"
        );

        $stmt->bind_param(
            "ssdii",
            $product_name,
            $category,
            $price,
            $stock,
            $product_id
        );

        $stmt->execute();
        $stmt->close();

        header("Location: products.php");
        exit();
    }
}


// =========================
// DELETE PRODUCT
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_product"])) {

    $product_id = $_POST["product_id"];

    $stmt = $conn->prepare(
        "DELETE FROM products
         WHERE product_id = ?"
    );

    $stmt->bind_param(
        "i",
        $product_id
    );

    $stmt->execute();
    $stmt->close();

    header("Location: products.php");
    exit();
}


// =========================
// GET PRODUCTS
// =========================
$query = "
    SELECT
        product_id,
        product_name,
        category,
        price,
        stock
    FROM products
    ORDER BY product_id DESC
";

$result = $conn->query($query);


include "includes/header.php";

?>


<!-- =========================
     PAGE HEADER
========================= -->

<div class="page-header">

    <div>

        <h2>Product Management</h2>

        <p>
            Manage products, prices, categories, and stock levels.
        </p>

    </div>


    <button
        class="btn-primary"
        onclick="openAddModal()"
    >

        <span><i class="fa-solid fa-plus"></i></span>

        Add Product

    </button>

</div>



<!-- =========================
     PRODUCT TABLE
========================= -->

<div class="table-container">


    <!-- TABLE HEADER -->

    <div class="table-header">

        <div>

            <h3>Product List</h3>

            <p>
                View and manage available bakeshop products.
            </p>

        </div>


        <!-- SEARCH -->

        <div class="search-box">

            <span><i class="fa-solid fa-magnifying-glass"></i></span>

            <input
                type="text"
                id="productSearch"
                placeholder="Search product..."
                onkeyup="searchProducts()"
            >

        </div>

    </div>



    <!-- TABLE -->

    <div class="table-wrapper">

        <table id="productTable">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>
                        Product Name
                    </th>

                    <th>
                        Category
                    </th>

                    <th>
                        Price
                    </th>

                    <th>
                        Stock
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody>


                <?php if ($result->num_rows > 0): ?>


                    <?php while ($product = $result->fetch_assoc()): ?>


                        <tr>


                            <!-- PRODUCT ID -->

                            <td>

                                <span class="customer-id">

                                    #
                                    <?php
                                    echo $product["product_id"];
                                    ?>

                                </span>

                            </td>



                            <!-- PRODUCT NAME -->

                            <td>

                                <div class="customer-name">


                                    <div class="customer-avatar product-avatar">

                                        <?php
                                        echo strtoupper(
                                            substr(
                                                $product["product_name"],
                                                0,
                                                1
                                            )
                                        );
                                        ?>

                                    </div>


                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $product["product_name"]
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
                                        $product["category"]
                                    );
                                    ?>

                                </span>

                            </td>



                            <!-- PRICE -->

                            <td>

                                <strong class="product-price">

                                    ₱<?php
                                    echo number_format(
                                        $product["price"],
                                        2
                                    );
                                    ?>

                                </strong>

                            </td>



                            <!-- STOCK -->

                            <td>

                                <strong class="stock-number">

                                    <?php
                                    echo $product["stock"];
                                    ?>

                                </strong>

                            </td>



                            <!-- STOCK STATUS -->

                            <td>

                                <?php

                                $stock = (int) $product["stock"];


                                if ($stock <= 0) {

                                    echo '
                                        <span class="stock-status out">
                                            Out of Stock
                                        </span>
                                    ';

                                } elseif ($stock <= 5) {

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



                            <!-- ACTIONS -->

                            <td>

                                <div class="action-buttons">


                                    <!-- EDIT -->

                                    <button
                                        class="btn-edit"
                                        onclick="openEditModal(
                                            <?php
                                            echo $product['product_id'];
                                            ?>,
                                            '<?php
                                            echo htmlspecialchars(
                                                $product['product_name'],
                                                ENT_QUOTES
                                            );
                                            ?>',
                                            '<?php
                                            echo htmlspecialchars(
                                                $product['category'],
                                                ENT_QUOTES
                                            );
                                            ?>',
                                            '<?php
                                            echo $product['price'];
                                            ?>',
                                            '<?php
                                            echo $product['stock'];
                                            ?>'
                                        )"
                                    >

                                        ✏ Edit

                                    </button>



                                    <!-- DELETE -->

                                    <button
                                        class="btn-delete"
                                        onclick="confirmDelete(
                                            <?php
                                            echo $product['product_id'];
                                            ?>,
                                            '<?php
                                            echo htmlspecialchars(
                                                $product['product_name'],
                                                ENT_QUOTES
                                            );
                                            ?>'
                                        )"
                                    >

                                        🗑 Delete

                                    </button>


                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="7"
                            class="no-data"
                        >

                            No products found.

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>



<!-- =========================
     ADD PRODUCT MODAL
========================= -->

<div
    id="addProductModal"
    class="modal"
>


    <div class="modal-content">


        <!-- CLOSE -->

        <button
            class="modal-close"
            onclick="closeAddModal()"
        >

            ×

        </button>



        <!-- ICON -->

        <div class="modal-icon">

            <i class="fa-solid fa-bread-slice"></i>

        </div>



        <h2>
            Add Product
        </h2>


        <p class="modal-subtitle">

            Enter the product information below.

        </p>



        <!-- FORM -->

        <form method="POST">


            <!-- PRODUCT NAME -->

            <div class="form-group">

                <label for="add_product_name">

                    Product Name

                </label>


                <input
                    type="text"
                    id="add_product_name"
                    name="product_name"
                    placeholder="Enter product name"
                    required
                >

            </div>



            <!-- CATEGORY -->

            <div class="form-group">

                <label for="add_category">

                    Category

                </label>


                <input
                    type="text"
                    id="add_category"
                    name="category"
                    placeholder="e.g. Bread, Cake, Pastry"
                    required
                >

            </div>



            <!-- PRICE -->

            <div class="form-group">

                <label for="add_price">

                    Price

                </label>


                <input
                    type="number"
                    id="add_price"
                    name="price"
                    placeholder="0.00"
                    min="0"
                    step="0.01"
                    required
                >

            </div>



            <!-- STOCK -->

            <div class="form-group">

                <label for="add_stock">

                    Stock

                </label>


                <input
                    type="number"
                    id="add_stock"
                    name="stock"
                    placeholder="Enter stock quantity"
                    min="0"
                    required
                >

            </div>



            <!-- BUTTONS -->

            <div class="modal-actions">


                <button
                    type="button"
                    class="btn-cancel"
                    onclick="closeAddModal()"
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    name="add_product"
                    class="btn-primary"
                >

                    Save Product

                </button>


            </div>


        </form>


    </div>

</div>



<!-- =========================
     EDIT PRODUCT MODAL
========================= -->

<div
    id="editProductModal"
    class="modal"
>


    <div class="modal-content">


        <!-- CLOSE -->

        <button
            class="modal-close"
            onclick="closeEditModal()"
        >

            ×

        </button>



        <!-- ICON -->

        <div class="modal-icon">

            ✏

        </div>



        <h2>
            Edit Product
        </h2>


        <p class="modal-subtitle">

            Update the product information.

        </p>



        <!-- FORM -->

        <form method="POST">


            <!-- PRODUCT ID -->

            <input
                type="hidden"
                id="edit_product_id"
                name="product_id"
            >



            <!-- PRODUCT NAME -->

            <div class="form-group">

                <label for="edit_product_name">

                    Product Name

                </label>


                <input
                    type="text"
                    id="edit_product_name"
                    name="product_name"
                    required
                >

            </div>



            <!-- CATEGORY -->

            <div class="form-group">

                <label for="edit_category">

                    Category

                </label>


                <input
                    type="text"
                    id="edit_category"
                    name="category"
                    required
                >

            </div>



            <!-- PRICE -->

            <div class="form-group">

                <label for="edit_price">

                    Price

                </label>


                <input
                    type="number"
                    id="edit_price"
                    name="price"
                    min="0"
                    step="0.01"
                    required
                >

            </div>



            <!-- STOCK -->

            <div class="form-group">

                <label for="edit_stock">

                    Stock

                </label>


                <input
                    type="number"
                    id="edit_stock"
                    name="stock"
                    min="0"
                    required
                >

            </div>



            <!-- BUTTONS -->

            <div class="modal-actions">


                <button
                    type="button"
                    class="btn-cancel"
                    onclick="closeEditModal()"
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    name="update_product"
                    class="btn-primary"
                >

                    Save Changes

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
// OPEN ADD MODAL
// =========================

function openAddModal() {

    document.getElementById(
        "addProductModal"
    ).style.display = "flex";

}


// =========================
// CLOSE ADD MODAL
// =========================

function closeAddModal() {

    document.getElementById(
        "addProductModal"
    ).style.display = "none";

}


// =========================
// OPEN EDIT MODAL
// =========================

function openEditModal(
    id,
    name,
    category,
    price,
    stock
) {

    document.getElementById(
        "edit_product_id"
    ).value = id;


    document.getElementById(
        "edit_product_name"
    ).value = name;


    document.getElementById(
        "edit_category"
    ).value = category;


    document.getElementById(
        "edit_price"
    ).value = price;


    document.getElementById(
        "edit_stock"
    ).value = stock;


    document.getElementById(
        "editProductModal"
    ).style.display = "flex";

}


// =========================
// CLOSE EDIT MODAL
// =========================

function closeEditModal() {

    document.getElementById(
        "editProductModal"
    ).style.display = "none";

}


// =========================
// SEARCH PRODUCTS
// =========================

function searchProducts() {

    let input =
        document.getElementById(
            "productSearch"
        );


    let filter =
        input.value.toLowerCase();


    let table =
        document.getElementById(
            "productTable"
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
// DELETE CONFIRMATION
// =========================

function confirmDelete(
    id,
    name
) {

    let confirmed = confirm(
        "Are you sure you want to delete " +
        name +
        "?"
    );


    if (confirmed) {

        let form =
            document.createElement("form");


        form.method = "POST";

        form.action = "products.php";


        // PRODUCT ID

        let idInput =
            document.createElement("input");


        idInput.type = "hidden";

        idInput.name = "product_id";

        idInput.value = id;


        // DELETE ACTION

        let deleteInput =
            document.createElement("input");


        deleteInput.type = "hidden";

        deleteInput.name = "delete_product";

        deleteInput.value = "1";


        form.appendChild(
            idInput
        );


        form.appendChild(
            deleteInput
        );


        document.body.appendChild(
            form
        );


        form.submit();

    }

}


// =========================
// CLOSE MODAL WHEN CLICKING
// OUTSIDE THE MODAL
// =========================

window.onclick = function(event) {

    let addModal =
        document.getElementById(
            "addProductModal"
        );


    let editModal =
        document.getElementById(
            "editProductModal"
        );


    if (
        event.target === addModal
    ) {

        closeAddModal();

    }


    if (
        event.target === editModal
    ) {

        closeEditModal();

    }

};

</script>



<?php

include "includes/footer.php";

?>