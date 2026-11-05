<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }
$page_title='Online Orders';
require_once 'config/database.php';

$message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $order_id=(int)($_POST['order_id']??0); $action=$_POST['action']??'';
    if($order_id>0 && in_array($action,['confirm','cancel','complete'],true)){
        if($action==='cancel'){
            $stmt=$conn->prepare("UPDATE orders SET status='Cancelled' WHERE order_id=? AND status='Pending'");$stmt->bind_param('i',$order_id);$stmt->execute();$message=$stmt->affected_rows?'Order cancelled.':'Only pending orders can be cancelled.';$stmt->close();
        } elseif($action==='complete'){
            $stmt=$conn->prepare("UPDATE orders SET status='Completed' WHERE order_id=? AND status='Confirmed'");$stmt->bind_param('i',$order_id);$stmt->execute();$message=$stmt->affected_rows?'Order marked as completed.':'Only confirmed orders can be completed.';$stmt->close();
        } else {
            $conn->begin_transaction();
            try{
                $check=$conn->prepare("SELECT order_id,customer_id,total_amount FROM orders WHERE order_id=? AND status='Pending' FOR UPDATE");$check->bind_param('i',$order_id);$check->execute();$order=$check->get_result()->fetch_assoc();$check->close();
                if(!$order) throw new Exception('Only pending orders can be confirmed.');
                $items=$conn->prepare("SELECT od.product_id,od.quantity,od.unit_price,p.product_name,p.stock FROM order_details od JOIN products p ON p.product_id=od.product_id WHERE od.order_id=? FOR UPDATE");$items->bind_param('i',$order_id);$items->execute();$items_result=$items->get_result();$rows=[];
                while($row=$items_result->fetch_assoc()){if((int)$row['stock']<(int)$row['quantity']) throw new Exception('Not enough stock for '.$row['product_name'].'.');$rows[]=$row;}
                $items->close();
                $sale=$conn->prepare('INSERT INTO sales(customer_id,user_id,sale_date,total_amount) VALUES(?,?,CURDATE(),?)');$sale->bind_param('iid',$order['customer_id'],$_SESSION['user_id'],$order['total_amount']);$sale->execute();$sale_id=$conn->insert_id;$sale->close();
                $detail=$conn->prepare('INSERT INTO sales_details(sale_id,product_id,quantity,subtotal) VALUES(?,?,?,?)');
                foreach($rows as $row){$subtotal=(float)$row['unit_price']*(int)$row['quantity'];$detail->bind_param('iiid',$sale_id,$row['product_id'],$row['quantity'],$subtotal);$detail->execute();
                    $up=$conn->prepare('UPDATE products SET stock=stock-? WHERE product_id=?');$up->bind_param('ii',$row['quantity'],$row['product_id']);$up->execute();$up->close();
                    $stock_after=(int)$row['stock']-(int)$row['quantity'];
                    $find=$conn->prepare('SELECT inventory_id FROM inventory WHERE product_id=? ORDER BY inventory_id DESC LIMIT 1');$find->bind_param('i',$row['product_id']);$find->execute();$inv=$find->get_result()->fetch_assoc();$find->close();
                    if($inv){$iu=$conn->prepare('UPDATE inventory SET stock_out=stock_out+?, current_stock=? WHERE inventory_id=?');$iu->bind_param('iii',$row['quantity'],$stock_after,$inv['inventory_id']);$iu->execute();$iu->close();}
                    else{$ii=$conn->prepare('INSERT INTO inventory(product_id,stock_in,stock_out,current_stock) VALUES(?,0,?,?)');$ii->bind_param('iii',$row['product_id'],$row['quantity'],$stock_after);$ii->execute();$ii->close();}
                }
                $detail->close();
                $uporder=$conn->prepare("UPDATE orders SET status='Confirmed' WHERE order_id=?");$uporder->bind_param('i',$order_id);$uporder->execute();$uporder->close();
                $conn->commit();$message='Order #'.$order_id.' confirmed and posted to Sales.';
            }catch(Throwable $e){$conn->rollback();$error=$e->getMessage();}
        }
    }
}
$filter=$_GET['status']??'All';
$where='';$params=[];$types='';if(in_array($filter,['Pending','Confirmed','Completed','Cancelled'],true)){$where=' WHERE o.status=?';$params[]=$filter;$types='s';}
$sql="SELECT o.order_id,o.order_date,o.status,o.total_amount,o.delivery_address,o.notes,c.customer_name,c.contact_number FROM orders o JOIN customers c ON c.customer_id=o.customer_id $where ORDER BY o.order_id DESC";$stmt=$conn->prepare($sql);if($types)$stmt->bind_param($types,...$params);$stmt->execute();$orders=$stmt->get_result();
include 'includes/header.php';
?>
<div class="page-header"><div><h2>Online Orders</h2><p>Review customer orders and convert confirmed orders into sales transactions.</p></div><a class="btn-primary" href="shop/index.php" target="_blank"><i class="fa-solid fa-store"></i> Open Customer Store</a></div>
<?php if($message): ?><div class="alert-message success-message"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if($error): ?><div class="alert-message error-message"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="report-filters"><a class="filter-pill <?php echo $filter==='All'?'active':''; ?>" href="orders.php">All</a><?php foreach(['Pending','Confirmed','Completed','Cancelled'] as $s): ?><a class="filter-pill <?php echo $filter===$s?'active':''; ?>" href="orders.php?status=<?php echo $s; ?>"><?php echo $s; ?></a><?php endforeach; ?></div>
<div class="table-container"><div class="table-header"><div><h3>Customer Orders</h3><p>Pending orders do not reduce inventory until they are confirmed.</p></div></div><div class="table-wrapper"><table><thead><tr><th>Order</th><th>Customer</th><th>Delivery Address</th><th>Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(!$orders->num_rows): ?><tr><td colspan="7" class="no-data">No orders found.</td></tr><?php else: while($o=$orders->fetch_assoc()): ?><tr><td><strong>#<?php echo $o['order_id']; ?></strong></td><td><strong><?php echo htmlspecialchars($o['customer_name']); ?></strong><br><small><?php echo htmlspecialchars($o['contact_number']); ?></small></td><td style="max-width:240px"><small><?php echo htmlspecialchars($o['delivery_address']); ?></small></td><td><?php echo date('M d, Y h:i A',strtotime($o['order_date'])); ?></td><td><strong>₱<?php echo number_format($o['total_amount'],2); ?></strong></td><td><span class="status-badge status-<?php echo strtolower($o['status']); ?>"><?php echo $o['status']; ?></span></td><td><div class="action-buttons"><?php if($o['status']==='Pending'): ?><form method="post"><input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>"><button class="btn-action btn-edit" name="action" value="confirm"><i class="fa-solid fa-check"></i> Confirm</button></form><form method="post"><input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>"><button class="btn-action btn-delete" name="action" value="cancel"><i class="fa-solid fa-xmark"></i> Cancel</button></form><?php elseif($o['status']==='Confirmed'): ?><form method="post"><input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>"><button class="btn-action btn-edit" name="action" value="complete"><i class="fa-solid fa-check-double"></i> Complete</button></form><?php else: ?><span style="color:#9a9089;font-size:12px">No action</span><?php endif; ?></div></td></tr><?php endwhile; endif; ?></tbody></table></div></div>
<?php include 'includes/footer.php'; ?>
