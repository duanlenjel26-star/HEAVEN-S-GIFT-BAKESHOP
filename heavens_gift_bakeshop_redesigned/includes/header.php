<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Heaven's Gift Bakeshop</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<div class="app">

    <?php include "sidebar.php"; ?>

    <main class="main-content">

        <header class="topbar">

            <div>
                <h1><?php echo isset($page_title) ? $page_title : "Dashboard"; ?></h1>
                <p>Bakeshop Management System</p>
            </div>

            <div class="user-info">

                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION["full_name"], 0, 1)); ?>
                </div>

                <div>
                    <strong>
                        <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
                    </strong>

                    <small>
                        <?php echo htmlspecialchars($_SESSION["role"]); ?>
                    </small>
                </div>

            </div>

        </header>

        <section class="page-content">