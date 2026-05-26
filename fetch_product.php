<?php
require "db.php";

$barcode = $_POST['barcode'];

$stmt = $conn->prepare("SELECT * FROM inventory WHERE barcode=?");
$stmt->bind_param("s", $barcode);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "status" => "success",
        "name" => $row['name'],
        "price" => $row['price']
    ]);
} else {
    echo json_encode(["status" => "error"]);
}
