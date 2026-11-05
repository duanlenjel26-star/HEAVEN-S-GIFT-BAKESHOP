<?php
session_start();
unset($_SESSION['customer_id'],$_SESSION['customer_account_id'],$_SESSION['customer_name'],$_SESSION['customer_username'],$_SESSION['cart']);
header('Location: index.php');exit();
