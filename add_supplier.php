<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $supplier = trim($_POST['supplier_name'] ?? '');
    $contact  = trim($_POST['contact_person'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if ($supplier === '') {
        header("Location: suppliers.php");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, phone, email) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssss", $supplier, $contact, $phone, $email);
    $stmt->execute();
    $stmt->close();

    header("Location: suppliers.php");
    exit();
}

header("Location: suppliers.php");
exit();