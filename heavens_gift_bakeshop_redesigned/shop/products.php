<?php
require_once 'common.php';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['customer_id'])) { header('Location: login.php'); exit(); }
    $id=(int)$_POST['product_id']; $qty=max(1,(int)$_POST['quantity']);
    $stmt=$conn->prepare('SELECT stock FROM products WHERE product_id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $r=$stmt->get_result()->fetch_assoc();
    if($r && $r['stock']>0){$qty=min($qty,(int)$r['stock']);$_SESSION['cart'][$id]=min(((int)($_SESSION['cart'][$id]??0))+$qty,(int)$r['stock']);}
    header('Location: products.php?added=1'); exit();
}
$search=trim($_GET['q']??''); $category=trim($_GET['category']??'');
$cats=[];$cr=$conn->query('SELECT DISTINCT category FROM products ORDER BY category');while($c=$cr->fetch_assoc())$cats[]=$c['category'];
$sql='SELECT product_id,product_name,category,price,stock FROM products WHERE stock>0';$types='';$params=[];
if($search!==''){$sql.=' AND product_name LIKE ?';$types.='s';$params[]='%'.$search.'%';}if($category!==''){$sql.=' AND category=?';$types.='s';$params[]=$category;}$sql.=' ORDER BY product_name';
$stmt=$conn->prepare($sql);if($types)$stmt->bind_param($types,...$params);$stmt->execute();$result=$stmt->get_result();
$shop_title='Products';include 'header.php';
?>
<div class="section-head"><div><h2>Browse Products</h2><p>Select a product and add it to your cart.</p></div></div>
<?php if(isset($_GET['added'])): ?><div class="alert success"><i class="fa-solid fa-circle-check"></i> Product added to your cart.</div><?php endif; ?>
<form class="card-panel" style="padding:14px;margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap" method="get"><input style="flex:1;min-width:220px;border:1px solid var(--line);border-radius:10px;padding:12px" name="q" placeholder="Search products..." value="<?php echo shop_e($search); ?>"><select name="category" style="border:1px solid var(--line);border-radius:10px;padding:12px"><option value="">All categories</option><?php foreach($cats as $cat): ?><option value="<?php echo shop_e($cat); ?>" <?php echo $category===$cat?'selected':''; ?>><?php echo shop_e($cat); ?></option><?php endforeach; ?></select><button class="btn btn-dark"><i class="fa-solid fa-magnifying-glass"></i> Search</button></form>
<div class="product-grid">
<?php if($result->num_rows): while($p=$result->fetch_assoc()): ?><article class="product-card"><div class="product-art"><i class="fa-solid fa-bread-slice"></i></div><div class="category"><?php echo shop_e($p['category']); ?></div><h3><?php echo shop_e($p['product_name']); ?></h3><div class="price-row"><span class="price">₱<?php echo number_format($p['price'],2); ?></span><span class="stock"><?php echo (int)$p['stock']; ?> left</span></div><?php if(isset($_SESSION['customer_id'])): ?><form method="post" style="margin-top:14px;display:flex;gap:8px"><input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>"><input type="number" name="quantity" min="1" max="<?php echo $p['stock']; ?>" value="1" style="width:70px;border:1px solid var(--line);border-radius:9px;padding:9px;text-align:center"><button class="btn btn-dark" style="flex:1" name="add_to_cart"><i class="fa-solid fa-cart-plus"></i> Add</button></form><?php else: ?><a class="btn btn-dark" style="width:100%;margin-top:14px" href="login.php"><i class="fa-solid fa-lock"></i> Login to Order</a><?php endif; ?></article><?php endwhile; else: ?><div class="card-panel" style="padding:35px;grid-column:1/-1;text-align:center;color:var(--muted)"><i class="fa-solid fa-box-open" style="font-size:30px"></i><p>No available products matched your search.</p></div><?php endif; ?>
</div>
<?php include 'footer.php'; ?>
