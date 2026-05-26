<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: settings.php");
  exit();
}

$system_name = trim($_POST['system_name'] ?? '');
$email_notifications = (int)($_POST['email_notifications'] ?? 0);
$auto_backup = (int)($_POST['auto_backup'] ?? 0);
$low_stock_level = (int)($_POST['low_stock_level'] ?? 5);
$theme_mode = (($_POST['theme_mode'] ?? 'light') === 'dark') ? 'dark' : 'light';

if ($system_name === '') {
  header("Location: settings.php?err=1");
  exit();
}

/* Ensure row exists */
$conn->query("INSERT INTO system_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id=id");

$stmt = $conn->prepare("
  UPDATE system_settings
  SET system_name=?, email_notifications=?, auto_backup=?, low_stock_level=?, theme_mode=?
  WHERE id=1
");
$stmt->bind_param("siiis", $system_name, $email_notifications, $auto_backup, $low_stock_level, $theme_mode);

if (!$stmt->execute()) {
  header("Location: settings.php?err=1");
  exit();
}

/* Reset all data (danger) */
if (isset($_POST['reset_all']) && $_POST['reset_all'] == '1') {
  $conn->begin_transaction();
  try {
    $conn->query("DELETE FROM sale_items");
    $conn->query("DELETE FROM sales");
    $conn->query("DELETE FROM suppliers");
    $conn->query("DELETE FROM products");
    $conn->commit();
  } catch (Exception $e) {
    $conn->rollback();
    header("Location: settings.php?err=1");
    exit();
  }
}

header("Location: settings.php?saved=1");
exit();