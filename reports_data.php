<?php
include 'db.php';

$response = [];

// ---------- Weekly Sales ----------
$sales = [];
$getSales = mysqli_query($conn,
    "SELECT DAYNAME(date) AS day, HOUR(date) AS hour, total_price 
     FROM sales_orders"
);

while ($row = mysqli_fetch_assoc($getSales)) {
    $sales[] = $row;
}

$response['weekly_sales'] = $sales;


// ---------- Top Suppliers Pie ----------
$supplierData = [];
$getSupplier = mysqli_query($conn,
    "SELECT supplier, SUM(quantity) AS total_qty 
     FROM purchases
     GROUP BY supplier"
);

while ($row = mysqli_fetch_assoc($getSupplier)) {
    $supplierData[] = $row;
}

$response['top_suppliers'] = $supplierData;


// ---------- Supplier Performance (Bar Chart) ----------
$performance = [];
$getPerf = mysqli_query($conn,
    "SELECT supplier, early, on_time, late
     FROM supplier_performance
     LIMIT 5"
);

while ($row = mysqli_fetch_assoc($getPerf)) {
    $performance[] = $row;
}

$response['supplier_performance'] = $performance;


// Return JSON
header('Content-Type: application/json');
echo json_encode($response);
