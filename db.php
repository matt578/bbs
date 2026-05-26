<?php
// db.php

$host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST');
$user = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER');
$pass = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD');
$db   = $_ENV['MYSQLDATABASE'] ?? getenv('MYSQLDATABASE');
$port = $_ENV['MYSQLPORT'] ?? getenv('MYSQLPORT');

$conn = new mysqli(
    $host,
    $user,
    $pass,
    $db,
    (int)$port
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>