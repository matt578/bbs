<?php
include 'db.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="sales_report.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Order ID', 'Customer', 'Total', 'Date']);

$result = $conn->query("SELECT * FROM sales_order");

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
