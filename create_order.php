<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product = $_POST['product_name'];
    $order_code = $_POST['order_code'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $total_price = $_POST['total_price'];

    $query = "INSERT INTO sales_orders (product_name, order_code, category, quantity, total_price)
              VALUES ('$product', '$order_code', '$category', '$quantity', '$total_price')";

    mysqli_query($conn, $query);

    header("Location: sales_orders.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Order</title>
    <link rel="stylesheet" href="sales_order.css">
</head>
<body>

<div class="wrapper">

    <!-- LEFT SIDEBAR -->
    <div class="sidebar">
        <h3>Menu</h3>
        <a href="sales_orders.php">Sales Orders</a>
        <a class="active" href="create_order.php">Create Order</a>
        <a href="inventory.php">Inventory</a>
        <a href="suppliers.php">Suppliers</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">

        <div class="page-header">
            <h2>Create Sales Order</h2>
        </div>

        <div class="card form-card">

            <form method="POST">

                <label>Product Name</label>
                <input type="text" name="product_name" required>

                <label>Order Code</label>
                <input type="text" name="order_code" required>

                <label>Category</label>
                <input type="text" name="category" required>

                <label>Quantity</label>
                <input type="number" name="quantity" required>

                <label>Total Price</label>
                <input type="number" step="0.01" name="total_price" required>

                <button type="submit" class="btn">Submit Order</button>
            </form>

        </div>

    </div>

</div>

</body>
</html>
