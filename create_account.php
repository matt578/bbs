<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $fullname, $email, $password);

    if ($stmt->execute()) {
        header("Location: login.php?success=1");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
