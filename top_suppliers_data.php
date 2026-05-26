<!--?php
require 'db.php';

$sql = "
  SELECT suppliers.name, SUM(sales_orders.total_price) AS total
  FROM sales_orders
  JOIN suppliers ON suppliers.id = sales_orders.supplier_id
  GROUP BY suppliers.id
  ORDER BY total DESC
";

$res = mysqli_query($conn, $sql);

$labels = [];
$values = [];

while ($row = mysqli_fetch_assoc($res)) {
    $labels[] = $row['name'];
    $values[] = (float)$row['total'];
}

echo json_encode([
    "labels" => $labels,
    "values" => $values
]);
