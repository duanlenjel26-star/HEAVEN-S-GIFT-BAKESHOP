<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$page_title = "Users";

require_once "config/database.php";


// =========================
// ADD USER
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_user"])) {

    $full_name = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role = trim($_POST["role"]);

    if (
        !empty($full_name) &&
        !empty($username) &&
        !empty($password) &&
        !empty($role)
    ) {

        // Check if username already exists
        $check = $conn->prepare(
            "SELECT user_id FROM users WHERE username = ?"
        );

        $check->bind_param("s", $username);
        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {

            $error = "Username already exists.";

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO users
                (full_name, username, password, role)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $full_name,
                $username,
                $password,
                $role
            );

            if ($stmt->execute()) {
                header("Location: users.php");
                exit();
            }

            $stmt->close();
        }

        $check->close();

    } else {

        $error = "Please fill in all fields.";
    }
}


// =========================
// UPDATE USER
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_user"])) {

    $user_id = (int) $_POST["user_id"];
    $full_name = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role = trim($_POST["role"]);

    if (
        !empty($full_name) &&
        !empty($username) &&
        !empty($role)
    ) {

        // Check username for another user
        $check = $conn->prepare(
            "SELECT user_id
             FROM users
             WHERE username = ?
             AND user_id != ?"
        );

        $check->bind_param(
            "si",
            $username,
            $user_id
        );

        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {

            $error = "Username already exists.";

        } else {

            /*
             * If password is empty,
             * keep the existing password.
             */
            if (!empty($password)) {

                $stmt = $conn->prepare(
                    "UPDATE users
                     SET full_name = ?,
                         username = ?,
                         password = ?,
                         role = ?
                     WHERE user_id = ?"
                );

                $stmt->bind_param(
                    "ssssi",
                    $full_name,
                    $username,
                    $password,
                    $role,
                    $user_id
                );

            } else {

                $stmt = $conn->prepare(
                    "UPDATE users
                     SET full_name = ?,
                         username = ?,
                         role = ?
                     WHERE user_id = ?"
                );

                $stmt->bind_param(
                    "sssi",
                    $full_name,
                    $username,
                    $role,
                    $user_id
                );
            }

            if ($stmt->execute()) {

                /*
                 * If the currently logged-in user's
                 * information was changed, update
                 * the current session too.
                 */
                if ($user_id == $_SESSION["user_id"]) {

                    $_SESSION["full_name"] = $full_name;
                    $_SESSION["username"] = $username;
                    $_SESSION["role"] = $role;
                }

                $stmt->close();

                header("Location: users.php");
                exit();
            }

            $stmt->close();
        }

        $check->close();

    } else {

        $error = "Please fill in all required fields.";
    }
}


// =========================
// DELETE USER
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_user"])) {

    $user_id = (int) $_POST["user_id"];

    // Prevent deleting the currently logged-in account
    if ($user_id == $_SESSION["user_id"]) {

        $error = "You cannot delete the account you are currently using.";

    } else {

        // Check if this user has existing sales records
        $check_sales = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM sales
             WHERE user_id = ?"
        );

        $check_sales->bind_param("i", $user_id);
        $check_sales->execute();

        $sales_result = $check_sales->get_result();
        $sales_data = $sales_result->fetch_assoc();

        $check_sales->close();

        if ($sales_data["total"] > 0) {

            $error = "This user cannot be deleted because they have existing sales records.";

        } else {

            $stmt = $conn->prepare(
                "DELETE FROM users
                 WHERE user_id = ?"
            );

            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {

                $stmt->close();

                header("Location: users.php");
                exit();

            } else {

                $error = "Unable to delete the user.";
                $stmt->close();
            }
        }
    }
}

// =========================
// GET USERS
// =========================
$result = $conn->query(
    "SELECT user_id, full_name, username, role
     FROM users
     ORDER BY user_id ASC"
);

?>

<?php include "includes/header.php"; ?>


<div class="page-header">

    <div>
        <h2>User Management</h2>
        <p>Manage system users and their account information.</p>
    </div>

    <button
        type="button"
        class="btn-primary"
        onclick="openAddModal()"
    >
        + Add User
    </button>

</div>


<?php if (isset($error) && !empty($error)): ?>

    <div class="user-error">
        <?php echo htmlspecialchars($error); ?>
    </div>

<?php endif; ?>


<div class="table-container">

    <div class="table-header">

        <div>
            <h3>System Users</h3>

            <p>
                List of registered users
            </p>
        </div>


        <div class="search-box">

            <span><i class="fa-solid fa-magnifying-glass"></i></span>

            <input
                type="text"
                id="userSearch"
                placeholder="Search users..."
                onkeyup="searchUsers()"
            >

        </div>

    </div>


    <div class="table-wrapper">

        <table id="usersTable">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>User</th>

                    <th>Username</th>

                    <th>Role</th>

                    <th>Actions</th>

                </tr>

            </thead>


            <tbody>

                <?php if ($result && $result->num_rows > 0): ?>

                    <?php while ($user = $result->fetch_assoc()): ?>

                        <?php
                        $initials = strtoupper(
                            substr($user["full_name"], 0, 1)
                        );
                        ?>

                        <tr>

                            <td>

                                <span class="customer-id">
                                    #<?php echo $user["user_id"]; ?>
                                </span>

                            </td>


                            <td>

                                <div class="customer-name">

                                    <div class="customer-avatar">
                                        <?php echo htmlspecialchars($initials); ?>
                                    </div>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $user["full_name"]
                                        );
                                        ?>
                                    </strong>

                                </div>

                            </td>


                            <td>

                                <span class="username-text">
                                    <?php
                                    echo htmlspecialchars(
                                        $user["username"]
                                    );
                                    ?>
                                </span>

                            </td>


                            <td>

                                <?php if (strtolower($user["role"]) === "owner"): ?>

                                    <span class="role-badge owner">
                                        Owner
                                    </span>

                                <?php else: ?>

                                    <span class="role-badge staff">
                                        <?php
                                        echo htmlspecialchars(
                                            $user["role"]
                                        );
                                        ?>
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <div class="action-buttons">

                                    <button
                                        type="button"
                                        class="btn-edit"
                                        onclick='openEditModal(
                                            <?php echo json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
                                        )'
                                    >
                                        Edit
                                    </button>


                                    <?php if ($user["user_id"] != $_SESSION["user_id"]): ?>

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirmDelete('<?php echo htmlspecialchars($user["full_name"], ENT_QUOTES); ?>');"
                                        >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?php echo $user["user_id"]; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_user"
                                                class="btn-delete"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    <?php endif; ?>

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
                            No users found.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>



<!-- =========================
     ADD USER MODAL
========================= -->

<div
    class="modal"
    id="addUserModal"
>

    <div class="modal-content">

        <button
            type="button"
            class="modal-close"
            onclick="closeAddModal()"
        >
            ×
        </button>


        <div class="modal-icon">
            <i class="fa-solid fa-user"></i>
        </div>


        <h2>Add New User</h2>

        <p class="modal-subtitle">
            Create a new system user account.
        </p>


        <form method="POST">

            <div class="form-group">

                <label for="add_full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="add_full_name"
                    name="full_name"
                    placeholder="Enter full name"
                    required
                >

            </div>


            <div class="form-group">

                <label for="add_username">
                    Username
                </label>

                <input
                    type="text"
                    id="add_username"
                    name="username"
                    placeholder="Enter username"
                    required
                >

            </div>


            <div class="form-group">

                <label for="add_password">
                    Password
                </label>

                <input
                    type="password"
                    id="add_password"
                    name="password"
                    placeholder="Enter password"
                    required
                >

            </div>


            <div class="form-group">

                <label for="add_role">
                    Role
                </label>

                <select
                    id="add_role"
                    name="role"
                    required
                >

                    <option value="">
                        Select role
                    </option>

                    <option value="Owner">
                        Owner
                    </option>

                    <option value="Staff">
                        Staff
                    </option>

                </select>

            </div>


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
                    name="add_user"
                    class="btn-primary"
                >
                    Add User
                </button>

            </div>

        </form>

    </div>

</div>



<!-- =========================
     EDIT USER MODAL
========================= -->

<div
    class="modal"
    id="editUserModal"
>

    <div class="modal-content">

        <button
            type="button"
            class="modal-close"
            onclick="closeEditModal()"
        >
            ×
        </button>


        <div class="modal-icon">
            ✏
        </div>


        <h2>Edit User</h2>

        <p class="modal-subtitle">
            Update the user's account information.
        </p>


        <form method="POST">

            <input
                type="hidden"
                name="user_id"
                id="edit_user_id"
            >


            <div class="form-group">

                <label for="edit_full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="edit_full_name"
                    name="full_name"
                    required
                >

            </div>


            <div class="form-group">

                <label for="edit_username">
                    Username
                </label>

                <input
                    type="text"
                    id="edit_username"
                    name="username"
                    required
                >

            </div>


            <div class="form-group">

                <label for="edit_password">
                    New Password
                </label>

                <input
                    type="password"
                    id="edit_password"
                    name="password"
                    placeholder="Leave blank to keep current password"
                    autocomplete="new-password"
                >

            </div>


            <div class="form-group">

                <label for="edit_role">
                    Role
                </label>

                <select
                    id="edit_role"
                    name="role"
                    required
                >

                    <option value="Owner">
                        Owner
                    </option>

                    <option value="Staff">
                        Staff
                    </option>

                </select>

            </div>


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
                    name="update_user"
                    class="btn-primary"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>



<script>

    // =========================
    // ADD MODAL
    // =========================

    function openAddModal() {

        document.getElementById("addUserModal").style.display = "flex";

    }


    function closeAddModal() {

        document.getElementById("addUserModal").style.display = "none";

    }



    // =========================
    // EDIT MODAL
    // =========================

    function openEditModal(user) {

        document.getElementById("edit_user_id").value =
            user.user_id;

        document.getElementById("edit_full_name").value =
            user.full_name;

        document.getElementById("edit_username").value =
            user.username;

        document.getElementById("edit_password").value = "";

        document.getElementById("edit_role").value =
            user.role;


        document.getElementById("editUserModal").style.display =
            "flex";

    }


    function closeEditModal() {

        document.getElementById("editUserModal").style.display =
            "none";

    }



    // =========================
    // CLOSE MODAL WHEN CLICKING OUTSIDE
    // =========================

    window.addEventListener("click", function(event) {

        const addModal =
            document.getElementById("addUserModal");

        const editModal =
            document.getElementById("editUserModal");


        if (event.target === addModal) {

            closeAddModal();

        }


        if (event.target === editModal) {

            closeEditModal();

        }

    });



    // =========================
    // SEARCH USERS
    // =========================

    function searchUsers() {

        const input =
            document.getElementById("userSearch");

        const filter =
            input.value.toLowerCase();

        const table =
            document.getElementById("usersTable");

        const rows =
            table.getElementsByTagName("tbody")[0]
                .getElementsByTagName("tr");


        for (let i = 0; i < rows.length; i++) {

            const text =
                rows[i].textContent.toLowerCase();

            if (text.includes(filter)) {

                rows[i].style.display = "";

            } else {

                rows[i].style.display = "none";

            }

        }

    }



    // =========================
    // DELETE CONFIRMATION
    // =========================

    function confirmDelete(name) {

        return confirm(
            "Are you sure you want to delete " +
            name +
            "?"
        );

    }

</script>


<?php include "includes/footer.php"; ?>