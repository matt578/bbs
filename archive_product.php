<?php
require 'db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $hasArchiveFlag = false;
    $colRes = $conn->query("SHOW COLUMNS FROM products LIKE 'is_archived'");
    if ($colRes && $colRes->num_rows > 0) {
        $hasArchiveFlag = true;
    }

    if ($hasArchiveFlag) {
        $stmt = $conn->prepare("UPDATE products SET is_archived = 1, updated_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

header('Location: inventory.php');
exit();