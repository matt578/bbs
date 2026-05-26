<?php
include 'db.php';
session_start();

$total = $_POST['total'];
$cash = $_POST['cash'];

if ($cash < $total) {
    echo "Not enough cash!";
    exit();
}

$change = $cash - $total;

// Save sale
$conn->query("INSERT INTO sales (total_amount, sale_date) VALUES ($total, NOW())");

unset($_SESSION['cart']);

echo "<h2>Payment Successful!</h2>";
echo "<p>Total: ₱$total</p>";
echo "<p>Cash: ₱$cash</p>";
echo "<p>Change: ₱$change</p>";
echo "<a href='pos.php'>Back to POS</a>";
