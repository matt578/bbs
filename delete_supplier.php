<?php
include 'db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: suppliers.php");
exit();