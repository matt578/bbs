<?php
$host = "localhost";
$user = "root";
$pass = ""; // default XAMPP password is empty
$db   = "bike_inventory"; // <-- your REAL database name

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
