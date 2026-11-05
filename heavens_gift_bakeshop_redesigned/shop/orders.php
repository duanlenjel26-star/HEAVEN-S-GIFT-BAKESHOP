<?php
require_once 'common.php'; require_customer();
$stmt=$conn->prepare('SELECT order_id,order_date,status,total_amount,delivery_address,notes FROM orders WHERE customer_id=? ORDER BY order_id DESC');$stmt->bind_param('i',$_SESSION['customer_id']);$stmt->execute();$orders=$stmt->get_result();
$shop_title='My Orders';include 'header.php';
?>
<div class="section-head"><div><h2>My Orders</h2><p>Track your submitted orders and their current status.</p></div><a class="btn btn-dark" href="products.php"><i class="fa-solid fa-plus"></i> New Order</a></div>
<?php if(isset($_GET['placed'])): ?><div class="alert success"><i class="fa-solid fa-circle-check"></i> Order #<?php echo (int)$_GET['placed']; ?> has been submitted successfully.</div><?php endif; ?>
<div class="card-panel">
<?php if(!$orders->num_rows): ?><div style="padding:55px;text-align:center;color:var(--muted)"><i class="fa-solid fa-receipt" style="font-size:38px;color:#c87932"></i><h3>No orders yet</h3><p>When you place an order, it will appear here.</p></div><?php else: while($o=$orders->fetch_assoc()): $d=$conn->prepare('SELECT od.quantity,od.unit_price,od.subtotal,p.product_name FROM order_details od JOIN products p ON p.product_id=od.product_id WHERE od.order_id=?');$d->bind_param('i',$o['order_id']);$d->execute();$lines=$d->get_result(); ?>
<div class="order-card"><div class="order-top"><span class="order-no">Order #<?php echo $o['order_id']; ?></span><span class="status <?php echo strtolower($o['status']); ?>"><?php echo shop_e($o['status']); ?></span></div><div class="order-meta"><?php echo date('M d, Y h:i A',strtotime($o['order_date'])); ?> · Total ₱<?php echo number_format($o['total_amount'],2); ?></div><div class="order-address"><i class="fa-solid fa-location-dot"></i> <strong>Delivery:</strong> <?php echo shop_e($o['delivery_address'] ?: 'Address not available for this order'); ?></div><div class="order-lines"><?php while($line=$lines->fetch_assoc()): ?><div class="order-line"><span><?php echo shop_e($line['product_name']); ?> × <?php echo $line['quantity']; ?></span><strong>₱<?php echo number_format($line['subtotal'],2); ?></strong></div><?php endwhile; ?></div><?php if($o['notes']): ?><div style="margin-top:12px;padding:10px 12px;background:#faf6f2;border-radius:9px;font-size:11px;color:var(--muted)"><strong>Note:</strong> <?php echo shop_e($o['notes']); ?></div><?php endif; ?></div>
<?php endwhile; endif; ?>
</div>
<?php include 'footer.php'; ?>
