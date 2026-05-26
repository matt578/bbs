<!--?php
require 'db.php';

/*
Expected Output:
{
  "labels": ["09:00", "10:00", ... ],
  "Mon": [100,200,...],
  ...
}
*/

$sql = "
  SELECT 
      DAYOFWEEK(order_date) AS weekday,
      HOUR(order_date) AS hour,
      SUM(total_price) AS total
  FROM sales_orders
  GROUP BY weekday, hour
  ORDER BY hour ASC
";

$res = mysqli_query($conn, $sql);

$hours = [];
$data = [
  "Mon" => [], "Tue" => [], "Wed" => [],
  "Thu" => [], "Fri" => [], "Sat" => [], "Sun" => []
];

while ($row = mysqli_fetch_assoc($res)) {
    $hr = sprintf("%02d:00", $row['hour']);
    if (!in_array($hr, $hours)) $hours[] = $hr;

    $map = [
        2=>"Mon",3=>"Tue",4=>"Wed",5=>"Thu",
        6=>"Fri",7=>"Sat",1=>"Sun"
    ];

    $day = $map[$row['weekday']];
    $data[$day][$row['hour']] = (float)$row['total'];
}

// fill empty hours with 0
foreach ($data as $day => &$vals) {
    foreach ($hours as $h) {
        $hr = intval(substr($h,0,2));
        if (!isset($vals[$hr])) $vals[$hr] = 0;
    }
    ksort($vals);
}

echo json_encode([
    "labels" => $hours,
    "data" => $data
]);
