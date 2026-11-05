<?php

$page_title = "Customers";

require_once "config/database.php";


// =========================
// ADD CUSTOMER
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_customer"])) {

    $customer_name = trim($_POST["customer_name"]);
    $contact_number = trim($_POST["contact_number"]);

    if (!empty($customer_name)) {

        $stmt = $conn->prepare(
            "INSERT INTO customers (customer_name, contact_number)
             VALUES (?, ?)"
        );

        $stmt->bind_param(
            "ss",
            $customer_name,
            $contact_number
        );

        $stmt->execute();
        $stmt->close();

        header("Location: customers.php");
        exit();
    }
}


// =========================
// UPDATE CUSTOMER
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_customer"])) {

    $customer_id = $_POST["customer_id"];
    $customer_name = trim($_POST["customer_name"]);
    $contact_number = trim($_POST["contact_number"]);

    if (!empty($customer_name)) {

        $stmt = $conn->prepare(
            "UPDATE customers
             SET customer_name = ?, contact_number = ?
             WHERE customer_id = ?"
        );

        $stmt->bind_param(
            "ssi",
            $customer_name,
            $contact_number,
            $customer_id
        );

        $stmt->execute();
        $stmt->close();

        header("Location: customers.php");
        exit();
    }
}


// =========================
// DELETE CUSTOMER
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_customer"])) {

    $customer_id = $_POST["customer_id"];

    $stmt = $conn->prepare(
        "DELETE FROM customers
         WHERE customer_id = ?"
    );

    $stmt->bind_param(
        "i",
        $customer_id
    );

    $stmt->execute();
    $stmt->close();

    header("Location: customers.php");
    exit();
}


// =========================
// GET CUSTOMERS
// =========================
$query = "
    SELECT customer_id, customer_name, contact_number
    FROM customers
    ORDER BY customer_id DESC
";

$result = $conn->query($query);


include "includes/header.php";

?>

<!-- =========================
     PAGE HEADER
========================= -->

<div class="page-header">

    <div>
        <h2>Customer Management</h2>

        <p>
            Manage customer information and contact details.
        </p>
    </div>

    <button
        class="btn-primary"
        onclick="openAddModal()"
    >
        <span><i class="fa-solid fa-plus"></i></span>
        Add Customer
    </button>

</div>


<!-- =========================
     CUSTOMER TABLE
========================= -->

<div class="table-container">

    <div class="table-header">

        <div>
            <h3>Customer List</h3>

            <p>
                View and manage registered customers.
            </p>
        </div>


        <!-- SEARCH -->

        <div class="search-box">

            <span><i class="fa-solid fa-magnifying-glass"></i></span>

            <input
                type="text"
                id="customerSearch"
                placeholder="Search customer..."
                onkeyup="searchCustomers()"
            >

        </div>

    </div>


    <div class="table-wrapper">

        <table id="customerTable">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>
                        Customer Name
                    </th>

                    <th>
                        Contact Number
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($customer = $result->fetch_assoc()): ?>

                        <tr>

                            <!-- CUSTOMER ID -->

                            <td>

                                <span class="customer-id">

                                    #
                                    <?php
                                    echo $customer["customer_id"];
                                    ?>

                                </span>

                            </td>


                            <!-- CUSTOMER NAME -->

                            <td>

                                <div class="customer-name">

                                    <div class="customer-avatar">

                                        <?php
                                        echo strtoupper(
                                            substr(
                                                $customer["customer_name"],
                                                0,
                                                1
                                            )
                                        );
                                        ?>

                                    </div>


                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $customer["customer_name"]
                                        );
                                        ?>

                                    </strong>

                                </div>

                            </td>


                            <!-- CONTACT NUMBER -->

                            <td>

                                <?php

                                if (!empty($customer["contact_number"])) {

                                    echo htmlspecialchars(
                                        $customer["contact_number"]
                                    );

                                } else {

                                    echo '
                                        <span class="no-contact">
                                            No contact number
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
                                            echo $customer['customer_id'];
                                            ?>,
                                            '<?php
                                            echo htmlspecialchars(
                                                $customer['customer_name'],
                                                ENT_QUOTES
                                            );
                                            ?>',
                                            '<?php
                                            echo htmlspecialchars(
                                                $customer['contact_number'] ?? '',
                                                ENT_QUOTES
                                            );
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
                                            echo $customer['customer_id'];
                                            ?>,
                                            '<?php
                                            echo htmlspecialchars(
                                                $customer['customer_name'],
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
                            colspan="4"
                            class="no-data"
                        >

                            No customers found.

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>



<!-- =========================
     ADD CUSTOMER MODAL
========================= -->

<div
    id="addCustomerModal"
    class="modal"
>

    <div class="modal-content">


        <!-- CLOSE BUTTON -->

        <button
            class="modal-close"
            onclick="closeAddModal()"
        >

            ×

        </button>


        <!-- ICON -->

        <div class="modal-icon">

            <i class="fa-solid fa-users"></i>

        </div>


        <h2>
            Add Customer
        </h2>


        <p class="modal-subtitle">

            Enter the customer's information below.

        </p>


        <!-- ADD FORM -->

        <form method="POST">


            <!-- CUSTOMER NAME -->

            <div class="form-group">

                <label
                    for="add_customer_name"
                >
                    Customer Name
                </label>


                <input
                    type="text"
                    id="add_customer_name"
                    name="customer_name"
                    placeholder="Enter customer name"
                    required
                >

            </div>


            <!-- CONTACT NUMBER -->

            <div class="form-group">

                <label
                    for="add_contact_number"
                >
                    Contact Number
                </label>


                <input
                    type="text"
                    id="add_contact_number"
                    name="contact_number"
                    placeholder="Enter contact number"
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
                    name="add_customer"
                    class="btn-primary"
                >

                    Save Customer

                </button>

            </div>

        </form>

    </div>

</div>



<!-- =========================
     EDIT CUSTOMER MODAL
========================= -->

<div
    id="editCustomerModal"
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
            Edit Customer
        </h2>


        <p class="modal-subtitle">

            Update the customer's information.

        </p>


        <!-- EDIT FORM -->

        <form method="POST">


            <!-- CUSTOMER ID -->

            <input
                type="hidden"
                id="edit_customer_id"
                name="customer_id"
            >


            <!-- CUSTOMER NAME -->

            <div class="form-group">

                <label
                    for="edit_customer_name"
                >
                    Customer Name
                </label>


                <input
                    type="text"
                    id="edit_customer_name"
                    name="customer_name"
                    required
                >

            </div>


            <!-- CONTACT NUMBER -->

            <div class="form-group">

                <label
                    for="edit_contact_number"
                >
                    Contact Number
                </label>


                <input
                    type="text"
                    id="edit_contact_number"
                    name="contact_number"
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
                    name="update_customer"
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
        "addCustomerModal"
    ).style.display = "flex";

}


// =========================
// CLOSE ADD MODAL
// =========================

function closeAddModal() {

    document.getElementById(
        "addCustomerModal"
    ).style.display = "none";

}


// =========================
// OPEN EDIT MODAL
// =========================

function openEditModal(
    id,
    name,
    contact
) {

    document.getElementById(
        "edit_customer_id"
    ).value = id;


    document.getElementById(
        "edit_customer_name"
    ).value = name;


    document.getElementById(
        "edit_contact_number"
    ).value = contact;


    document.getElementById(
        "editCustomerModal"
    ).style.display = "flex";

}


// =========================
// CLOSE EDIT MODAL
// =========================

function closeEditModal() {

    document.getElementById(
        "editCustomerModal"
    ).style.display = "none";

}


// =========================
// SEARCH CUSTOMERS
// =========================

function searchCustomers() {

    let input =
        document.getElementById(
            "customerSearch"
        );


    let filter =
        input.value.toLowerCase();


    let table =
        document.getElementById(
            "customerTable"
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

        form.action = "customers.php";


        // CUSTOMER ID

        let idInput =
            document.createElement("input");


        idInput.type = "hidden";

        idInput.name = "customer_id";

        idInput.value = id;


        // DELETE ACTION

        let deleteInput =
            document.createElement("input");


        deleteInput.type = "hidden";

        deleteInput.name = "delete_customer";

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
// CLOSE MODALS
// WHEN CLICKING OUTSIDE
// =========================

window.onclick = function(event) {

    let addModal =
        document.getElementById(
            "addCustomerModal"
        );


    let editModal =
        document.getElementById(
            "editCustomerModal"
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