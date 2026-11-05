<?php

$page_title = "Suppliers";

require_once "config/database.php";


// =========================
// ADD SUPPLIER
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_supplier"])) {

    $supplier_name = trim($_POST["supplier_name"]);
    $contact_number = trim($_POST["contact_number"]);
    $address = trim($_POST["address"]);

    if (!empty($supplier_name)) {

        $stmt = $conn->prepare(
            "INSERT INTO suppliers
            (supplier_name, contact_number, address)
            VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "sss",
            $supplier_name,
            $contact_number,
            $address
        );

        $stmt->execute();
        $stmt->close();

        header("Location: suppliers.php");
        exit();
    }
}


// =========================
// UPDATE SUPPLIER
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_supplier"])) {

    $supplier_id = $_POST["supplier_id"];
    $supplier_name = trim($_POST["supplier_name"]);
    $contact_number = trim($_POST["contact_number"]);
    $address = trim($_POST["address"]);

    if (!empty($supplier_name)) {

        $stmt = $conn->prepare(
            "UPDATE suppliers
             SET supplier_name = ?,
                 contact_number = ?,
                 address = ?
             WHERE supplier_id = ?"
        );

        $stmt->bind_param(
            "sssi",
            $supplier_name,
            $contact_number,
            $address,
            $supplier_id
        );

        $stmt->execute();
        $stmt->close();

        header("Location: suppliers.php");
        exit();
    }
}


// =========================
// DELETE SUPPLIER
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_supplier"])) {

    $supplier_id = $_POST["supplier_id"];

    $stmt = $conn->prepare(
        "DELETE FROM suppliers
         WHERE supplier_id = ?"
    );

    $stmt->bind_param(
        "i",
        $supplier_id
    );

    $stmt->execute();
    $stmt->close();

    header("Location: suppliers.php");
    exit();
}


// =========================
// GET SUPPLIERS
// =========================

$query = "
    SELECT
        supplier_id,
        supplier_name,
        contact_number,
        address
    FROM suppliers
    ORDER BY supplier_id DESC
";

$result = $conn->query($query);


include "includes/header.php";

?>


<!-- =========================
     PAGE HEADER
========================= -->

<div class="page-header">

    <div>

        <h2>Supplier Management</h2>

        <p>
            Manage supplier information and contact details.
        </p>

    </div>


    <button
        class="btn-primary"
        onclick="openAddModal()"
    >

        <span><i class="fa-solid fa-plus"></i></span>

        Add Supplier

    </button>

</div>



<!-- =========================
     SUPPLIER TABLE
========================= -->

<div class="table-container">


    <!-- TABLE HEADER -->

    <div class="table-header">

        <div>

            <h3>Supplier List</h3>

            <p>
                View and manage registered suppliers.
            </p>

        </div>


        <!-- SEARCH -->

        <div class="search-box">

            <span><i class="fa-solid fa-magnifying-glass"></i></span>

            <input
                type="text"
                id="supplierSearch"
                placeholder="Search supplier..."
                onkeyup="searchSuppliers()"
            >

        </div>

    </div>



    <!-- TABLE -->

    <div class="table-wrapper">

        <table id="supplierTable">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>
                        Supplier Name
                    </th>

                    <th>
                        Contact Number
                    </th>

                    <th>
                        Address
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody>


                <?php if ($result->num_rows > 0): ?>


                    <?php while ($supplier = $result->fetch_assoc()): ?>


                        <tr>


                            <!-- SUPPLIER ID -->

                            <td>

                                <span class="customer-id">

                                    #
                                    <?php
                                    echo $supplier["supplier_id"];
                                    ?>

                                </span>

                            </td>



                            <!-- SUPPLIER NAME -->

                            <td>

                                <div class="customer-name">


                                    <div class="customer-avatar supplier-avatar">

                                        <?php

                                        echo strtoupper(
                                            substr(
                                                $supplier["supplier_name"],
                                                0,
                                                1
                                            )
                                        );

                                        ?>

                                    </div>


                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $supplier["supplier_name"]
                                        );

                                        ?>

                                    </strong>


                                </div>

                            </td>



                            <!-- CONTACT NUMBER -->

                            <td>

                                <?php

                                if (!empty($supplier["contact_number"])) {

                                    echo htmlspecialchars(
                                        $supplier["contact_number"]
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



                            <!-- ADDRESS -->

                            <td>

                                <?php

                                if (!empty($supplier["address"])) {

                                    echo htmlspecialchars(
                                        $supplier["address"]
                                    );

                                } else {

                                    echo '
                                        <span class="no-contact">
                                            No address
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
                                            echo $supplier['supplier_id'];
                                            ?>,
                                            '<?php
                                            echo htmlspecialchars(
                                                $supplier['supplier_name'],
                                                ENT_QUOTES
                                            );
                                            ?>',
                                            '<?php
                                            echo htmlspecialchars(
                                                $supplier['contact_number'] ?? '',
                                                ENT_QUOTES
                                            );
                                            ?>',
                                            '<?php
                                            echo htmlspecialchars(
                                                $supplier['address'] ?? '',
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
                                            echo $supplier['supplier_id'];
                                            ?>,
                                            '<?php
                                            echo htmlspecialchars(
                                                $supplier['supplier_name'],
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
                            colspan="5"
                            class="no-data"
                        >

                            No suppliers found.

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>



<!-- =========================
     ADD SUPPLIER MODAL
========================= -->

<div
    id="addSupplierModal"
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

            <i class="fa-solid fa-truck-fast"></i>

        </div>



        <h2>
            Add Supplier
        </h2>


        <p class="modal-subtitle">

            Enter the supplier's information below.

        </p>



        <!-- ADD FORM -->

        <form method="POST">


            <!-- SUPPLIER NAME -->

            <div class="form-group">

                <label for="add_supplier_name">

                    Supplier Name

                </label>


                <input
                    type="text"
                    id="add_supplier_name"
                    name="supplier_name"
                    placeholder="Enter supplier name"
                    required
                >

            </div>



            <!-- CONTACT NUMBER -->

            <div class="form-group">

                <label for="add_contact_number">

                    Contact Number

                </label>


                <input
                    type="text"
                    id="add_contact_number"
                    name="contact_number"
                    placeholder="Enter contact number"
                >

            </div>



            <!-- ADDRESS -->

            <div class="form-group">

                <label for="add_address">

                    Address

                </label>


                <input
                    type="text"
                    id="add_address"
                    name="address"
                    placeholder="Enter supplier address"
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
                    name="add_supplier"
                    class="btn-primary"
                >

                    Save Supplier

                </button>


            </div>


        </form>


    </div>

</div>



<!-- =========================
     EDIT SUPPLIER MODAL
========================= -->

<div
    id="editSupplierModal"
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
            Edit Supplier
        </h2>


        <p class="modal-subtitle">

            Update the supplier's information.

        </p>



        <!-- EDIT FORM -->

        <form method="POST">


            <!-- SUPPLIER ID -->

            <input
                type="hidden"
                id="edit_supplier_id"
                name="supplier_id"
            >



            <!-- SUPPLIER NAME -->

            <div class="form-group">

                <label for="edit_supplier_name">

                    Supplier Name

                </label>


                <input
                    type="text"
                    id="edit_supplier_name"
                    name="supplier_name"
                    required
                >

            </div>



            <!-- CONTACT NUMBER -->

            <div class="form-group">

                <label for="edit_contact_number">

                    Contact Number

                </label>


                <input
                    type="text"
                    id="edit_contact_number"
                    name="contact_number"
                >

            </div>



            <!-- ADDRESS -->

            <div class="form-group">

                <label for="edit_address">

                    Address

                </label>


                <input
                    type="text"
                    id="edit_address"
                    name="address"
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
                    name="update_supplier"
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
        "addSupplierModal"
    ).style.display = "flex";

}


// =========================
// CLOSE ADD MODAL
// =========================

function closeAddModal() {

    document.getElementById(
        "addSupplierModal"
    ).style.display = "none";

}


// =========================
// OPEN EDIT MODAL
// =========================

function openEditModal(
    id,
    name,
    contact,
    address
) {

    document.getElementById(
        "edit_supplier_id"
    ).value = id;


    document.getElementById(
        "edit_supplier_name"
    ).value = name;


    document.getElementById(
        "edit_contact_number"
    ).value = contact;


    document.getElementById(
        "edit_address"
    ).value = address;


    document.getElementById(
        "editSupplierModal"
    ).style.display = "flex";

}


// =========================
// CLOSE EDIT MODAL
// =========================

function closeEditModal() {

    document.getElementById(
        "editSupplierModal"
    ).style.display = "none";

}


// =========================
// SEARCH SUPPLIERS
// =========================

function searchSuppliers() {

    let input =
        document.getElementById(
            "supplierSearch"
        );


    let filter =
        input.value.toLowerCase();


    let table =
        document.getElementById(
            "supplierTable"
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

        form.action = "suppliers.php";


        // SUPPLIER ID

        let idInput =
            document.createElement("input");


        idInput.type = "hidden";

        idInput.name = "supplier_id";

        idInput.value = id;


        // DELETE ACTION

        let deleteInput =
            document.createElement("input");


        deleteInput.type = "hidden";

        deleteInput.name = "delete_supplier";

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
            "addSupplierModal"
        );


    let editModal =
        document.getElementById(
            "editSupplierModal"
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