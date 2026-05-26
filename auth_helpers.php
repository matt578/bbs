<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$db   = "auth_db";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function completeLogin(array $row, mysqli $conn, string $method = 'email') {
    session_regenerate_id(true);

    $_SESSION['user_id'] = $row['id'];
    $_SESSION['name'] = $row['name'] ?? '';
    $_SESSION['email'] = $row['email'] ?? '';
    $_SESSION['login_method'] = $method;

    header('Location: Dashboard.php');
    exit();
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}
?>