<?php
include 'db.php';

$id       = $_POST['id'];
$supplier = $_POST['supplier_name'];
$contact  = $_POST['contact_person'];
$phone    = $_POST['phone'];
$email    = $_POST['email'];
$fb       = $_POST['facebook_link'];

$sql = "UPDATE suppliers 
        SET supplier_name=?, contact_person=?, phone=?, email=?, facebook_link=? 
        WHERE id=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssi", $supplier, $contact, $phone, $email, $fb, $id);
$stmt->execute();

header("Location: suppliers.php");
exit();
