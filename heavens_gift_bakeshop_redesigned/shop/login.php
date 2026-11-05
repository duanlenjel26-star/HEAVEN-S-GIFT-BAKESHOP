<?php
require_once 'common.php';
if (isset($_SESSION['customer_id'])) { header('Location: products.php'); exit(); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $username=trim($_POST['username']??''); $password=$_POST['password']??'';
    $stmt=$conn->prepare('SELECT ca.account_id, ca.customer_id, ca.username, ca.password_hash, c.customer_name FROM customer_accounts ca JOIN customers c ON c.customer_id=ca.customer_id WHERE ca.username=?');
    $stmt->bind_param('s',$username); $stmt->execute(); $result=$stmt->get_result();
    if($result->num_rows===1){$user=$result->fetch_assoc(); if(password_verify($password,$user['password_hash'])){
                        // Keep the customer portal separate from the admin session.
                        unset($_SESSION['user_id'], $_SESSION['full_name'], $_SESSION['username'], $_SESSION['role']);
                        $_SESSION['customer_account_id']=$user['account_id'];$_SESSION['customer_id']=$user['customer_id'];$_SESSION['customer_name']=$user['customer_name'];$_SESSION['customer_username']=$user['username'];header('Location: products.php');exit();}}
    $error='Incorrect username or password.'; $stmt->close();
}
$shop_title='Customer Login'; include 'header.php';
?>
<div class="auth-wrap"><div class="auth-card">
<div class="auth-icon"><i class="fa-solid fa-right-to-bracket"></i></div><h1>Customer login</h1><p>Sign in to order products and view your order history.</p>
<?php if(isset($_GET['registered'])): ?><div class="alert success"><i class="fa-solid fa-circle-check"></i> Account created. You can now sign in.</div><?php endif; ?>
<?php if($error): ?><div class="alert error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo shop_e($error); ?></div><?php endif; ?>
<form method="post"><div class="field"><label>Username</label><input name="username" required autofocus></div><div class="field"><label>Password</label><input type="password" name="password" required></div><button class="btn btn-dark" style="width:100%" type="submit"><i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In</button></form>
<p style="margin-top:18px;text-align:center">No account yet? <a style="color:var(--brown);font-weight:700" href="register.php">Create one</a></p>
</div></div>
<?php include 'footer.php'; ?>
