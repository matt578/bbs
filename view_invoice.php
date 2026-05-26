<?php
include 'db.php';

$id = $_GET['id'];
$query = "SELECT * FROM sales_orders WHERE id = $id";
$order = mysqli_fetch_assoc(mysqli_query($conn, $query));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?= $order['order_code'] ?></title>
    <link rel="stylesheet" href="sales_order.css">
</head>
<body>

<div class="invoice-box">
    <h2>Invoice</h2>

    <p><strong>Order Code:</strong> <?= $order['order_code'] ?></p>
    <p><strong>Product:</strong> <?= $order['product_name'] ?></p>
    <p><strong>Category:</strong> <?= $order['category'] ?></p>
    <p><strong>Quantity:</strong> <?= $order['quantity'] ?></p>
    <p><strong>Total Price:</strong> $<?= number_format($order['total_price'], 2) ?></p>
    <p><strong>Date:</strong> <?= $order['created_at'] ?></p>

    <br>

    <button onclick="window.print()" class="btn-primary">Print Invoice</button>
</div>

</body>
</html>
