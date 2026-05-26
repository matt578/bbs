<?php
require 'db.php';

$sql = "
  SELECT 
      suppliers.name,
      SUM(CASE WHEN status = 'Early' THEN 1 ELSE 0 END) AS early,
      SUM(CASE WHEN status = 'On Time' THEN 1 ELSE 0 END) AS ontime,
      SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) AS late
  FROM deliveries
  JOIN suppliers ON suppliers.id = deliveries.supplier_id
  GROUP BY suppliers.id
  ORDER BY ontime DESC
  LIMIT 5
";

$res = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}

echo json_encode($data);
