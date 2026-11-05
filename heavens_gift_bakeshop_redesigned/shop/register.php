<?php
require_once 'common.php';
if (isset($_SESSION['customer_id'])) { header('Location: products.php'); exit(); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name=trim($_POST['customer_name']??''); $contact=trim($_POST['contact_number']??''); $username=trim($_POST['username']??''); $password=$_POST['password']??''; $confirm=$_POST['confirm_password']??'';
    if ($name===''||$contact===''||$username===''||$password==='') $error='Please complete all required fields.';
    elseif (strlen($password)<6) $error='Password must be at least 6 characters.';
    elseif ($password!==$confirm) $error='Passwords do not match.';
    else {
        $check=$conn->prepare('SELECT account_id FROM customer_accounts WHERE username=?'); $check->bind_param('s',$username); $check->execute();
        if ($check->get_result()->num_rows) $error='Username is already registered.';
        else {
            $conn->begin_transaction();
            try {
                $stmt=$conn->prepare('INSERT INTO customers(customer_name,contact_number) VALUES(?,?)'); $stmt->bind_param('ss',$name,$contact); $stmt->execute(); $customer_id=$conn->insert_id; $stmt->close();
                $hash=password_hash($password,PASSWORD_DEFAULT); $stmt=$conn->prepare('INSERT INTO customer_accounts(customer_id,username,password_hash) VALUES(?,?,?)'); $stmt->bind_param('iss',$customer_id,$username,$hash); $stmt->execute(); $stmt->close();
                $conn->commit(); header('Location: login.php?registered=1'); exit();
            } catch(Throwable $e){ $conn->rollback(); $error='Unable to create the account. Please try again.'; }
        }
        $check->close();
    }
}
$shop_title='Create Customer Account'; include 'header.php';
?>
<div class="auth-wrap"><div class="auth-card">
<div class="auth-icon"><i class="fa-solid fa-user-plus"></i></div><h1>Create your account</h1><p>Create a customer account to order products online and track your orders.</p>
<?php if($error): ?><div class="alert error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo shop_e($error); ?></div><?php endif; ?>
<form method="post">
<div class="form-grid"><div class="field full"><label>Full Name</label><input name="customer_name" required value="<?php echo shop_e($_POST['customer_name']??''); ?>"></div>
<div class="field"><label>Contact Number</label><input name="contact_number" required value="<?php echo shop_e($_POST['contact_number']??''); ?>"></div>
<div class="field"><label>Username</label><input name="username" required value="<?php echo shop_e($_POST['username']??''); ?>"></div>
<div class="field"><label>Password</label><input type="password" name="password" required></div>
<div class="field"><label>Confirm Password</label><input type="password" name="confirm_password" required></div></div>
<button class="btn btn-dark" style="width:100%" type="submit"><i class="fa-solid fa-user-check"></i> Create Account</button></form>
<p style="margin-top:18px;text-align:center">Already have an account? <a style="color:var(--brown);font-weight:700" href="login.php">Sign in</a></p>
</div></div>
<?php include 'footer.php'; ?>
