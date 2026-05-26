<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id       = (int)($_POST['id'] ?? 0);
    $supplier = trim($_POST['supplier_name'] ?? '');
    $contact  = trim($_POST['contact_person'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if ($id <= 0 || $supplier === '') {
        header("Location: suppliers.php");
        exit();
    }

    $stmt = $conn->prepare("UPDATE suppliers SET supplier_name=?, contact_person=?, phone=?, email=? WHERE id=?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssssi", $supplier, $contact, $phone, $email, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: suppliers.php");
    exit();
}

header("Location: suppliers.php");
exit();