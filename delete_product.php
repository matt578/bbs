<?php
require 'db.php';

$log = $conn->prepare("INSERT INTO recent_activity (action, product_name) VALUES (?, ?)");
$log->execute(["Deleted Product", $product_name]);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
  // delete image if exists
  $q = $conn->prepare("SELECT image FROM products WHERE id=?");
  $q->bind_param('i', $id);
  $q->execute();
  $r = $q->get_result()->fetch_assoc();
  if ($r && !empty($r['image']) && file_exists(__DIR__.'/assets/images/'.$r['image'])) {
    @unlink(__DIR__.'/assets/images/'.$r['image']);
  }
  $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
}

header('Location: inventory.php');
exit;
