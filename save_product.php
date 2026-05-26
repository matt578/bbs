<?php
require 'db.php';

//recent activity
$log = $conn->prepare("INSERT INTO recent_activity (action, product_name, quantity) VALUES (?, ?, ?)");
$log->execute(["Added Product", $name, $quantity]);


// helper to handle uploaded image
function handle_image_upload($file_input_name) {
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] == UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$file_input_name];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'], $allowed)) {
        return null;
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fname = uniqid('img_') . '.' . $ext;
    $dest = __DIR__ . '/assets/images/' . $fname;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    return $fname;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'add') {
    $name = $_POST['name'];
    $code = $_POST['code'];
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $img = handle_image_upload('image');

    $stmt = $conn->prepare("INSERT INTO products (name, code, category, price, quantity, image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssdis', $name, $code, $category, $price, $quantity, $img);
    $stmt->execute();
    header('Location: inventory.php');
    exit;
}

if ($action === 'edit') {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $code = $_POST['code'];
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $newimg = handle_image_upload('image');

    if ($newimg) {
        // get old image to delete
        $q = $conn->prepare("SELECT image FROM products WHERE id=?");
        $q->bind_param('i', $id);
        $q->execute();
        $r = $q->get_result()->fetch_assoc();
        if ($r && !empty($r['image']) && file_exists(__DIR__.'/assets/images/'.$r['image'])) {
            @unlink(__DIR__.'/assets/images/'.$r['image']);
        }
        $stmt = $conn->prepare("UPDATE products SET name=?, code=?, category=?, price=?, quantity=?, image=? WHERE id=?");
        $stmt->bind_param('sssdisi', $name, $code, $category, $price, $quantity, $newimg, $id);
    } else {
        $stmt = $conn->prepare("UPDATE products SET name=?, code=?, category=?, price=?, quantity=? WHERE id=?");
        $stmt->bind_param('sssdii', $name, $code, $category, $price, $quantity, $id);
    }
    $stmt->execute();
    header('Location: inventory.php');
    exit;
}

// fallback
header('Location: inventory.php');
exit;
