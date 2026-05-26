<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: settings.php");
    exit();
}

$system_name = trim($_POST['system_name'] ?? 'Bohol Bicycle Inventory');
$email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
$auto_backup = isset($_POST['auto_backup']) ? 1 : 0;
$low_stock_alert = (int)($_POST['low_stock_alert'] ?? 10);
$theme_mode = ($_POST['theme_mode'] ?? 'light') === 'dark' ? 'dark' : 'light';

$sql = "UPDATE system_settings
        SET system_name=?,
            email_notifications=?,
            auto_backup=?,
            low_stock_alert=?,
            theme_mode=?
        WHERE id=1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("siiis", $system_name, $email_notifications, $auto_backup, $low_stock_alert, $theme_mode);
$stmt->execute();

header("Location: settings.php?success=1");
exit();