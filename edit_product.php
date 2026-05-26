<?php
ob_start();
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

$categoryOptions = [
    'Bike','Mountain Bike','Road Bike','Gravel Bike','BMX','Folding Bike','E-Bike','Kids Bike',
    'Bike Parts','Drivetrain','Brakes','Wheels','Tires and Tubes','Frame and Fork','Cockpit',
    'Pedals','Saddle and Seatpost','Gear','Helmet','Accessories','Tools','Apparel','Other'
];

$sizeOptions = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '26', '27.5', '29', '700C', 'Other'];

function tableExists(mysqli $conn, string $table): bool {
    $safe = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$safe}'");
    return $res && $res->num_rows > 0;
}

function columnExists(mysqli $conn, string $table, string $column): bool {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

if (!tableExists($conn, 'products')) {
    die('Products table is missing.');
}

$hasBrand     = columnExists($conn, 'products', 'brand');
$hasUpdatedAt = columnExists($conn, 'products', 'updated_at');
$hasImage     = columnExists($conn, 'products', 'image');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: inventory.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: inventory.php');
    exit();
}

$barcode     = trim($_POST['barcode'] ?? ($product['barcode'] ?? ''));
$name        = trim($_POST['name'] ?? ($product['name'] ?? ''));
$category    = trim($_POST['category'] ?? ($product['category'] ?? ''));
$size        = trim($_POST['size'] ?? ($product['size'] ?? ''));
$brand       = trim($_POST['brand'] ?? ($product['brand'] ?? ''));
$expiry_date = trim($_POST['expiry_date'] ?? ($product['expiry_date'] ?? ''));
$price       = $_POST['price'] ?? (string)($product['price'] ?? '0');
$quantity    = $_POST['quantity'] ?? (string)($product['quantity'] ?? '0');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode     = trim($_POST['barcode'] ?? '');
    $name        = trim($_POST['name'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $size        = trim($_POST['size'] ?? '');
    $brand       = trim($_POST['brand'] ?? '');
    $expiry_date = trim($_POST['expiry_date'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $quantity    = (int)($_POST['quantity'] ?? 0);
    $imageName   = $product['image'] ?? null;

    if ($barcode === '' || $name === '' || $category === '' || $size === '') {
        $error = 'Barcode, name, category, and size are required.';
    } elseif ($hasBrand && $brand === '') {
        $error = 'Brand is required.';
    } elseif ($price < 0 || $quantity < 0) {
        $error = 'Price and quantity must not be negative.';
    } elseif ($expiry_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_date)) {
        $error = 'Invalid expiry date.';
    } else {
        if ($hasBrand) {
            $dup = $conn->prepare("SELECT id FROM products WHERE barcode = ? AND brand = ? AND id <> ? LIMIT 1");
            $dup->bind_param("ssi", $barcode, $brand, $id);
        } else {
            $dup = $conn->prepare("SELECT id FROM products WHERE barcode = ? AND id <> ? LIMIT 1");
            $dup->bind_param("si", $barcode, $id);
        }

        $dup->execute();
        $exists = $dup->get_result()->fetch_assoc();
        $dup->close();

        if ($exists) {
            $error = $hasBrand
                ? 'This barcode already exists for the same brand.'
                : 'Barcode already exists.';
        }

        if ($error === '' && $hasImage && isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/assets/images/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $tmp  = $_FILES['image']['tmp_name'];
                $orig = $_FILES['image']['name'];
                $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (!in_array($ext, $allowed, true)) {
                    $error = 'Invalid image type.';
                } else {
                    $newName = uniqid('prod_', true) . '.' . $ext;
                    if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                        if (!empty($imageName) && file_exists($uploadDir . $imageName)) {
                            @unlink($uploadDir . $imageName);
                        }
                        $imageName = $newName;
                    } else {
                        $error = 'Failed to upload image.';
                    }
                }
            } else {
                $error = 'Image upload failed.';
            }
        }

        if ($error === '') {
            if ($hasBrand) {
                if ($hasUpdatedAt) {
                    $update = $conn->prepare("
                        UPDATE products
                        SET barcode = ?, name = ?, category = ?, size = ?, brand = ?, expiry_date = ?, price = ?, quantity = ?, image = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $update->bind_param("ssssssdisi", $barcode, $name, $category, $size, $brand, $expiry_date, $price, $quantity, $imageName, $id);
                } else {
                    $update = $conn->prepare("
                        UPDATE products
                        SET barcode = ?, name = ?, category = ?, size = ?, brand = ?, expiry_date = ?, price = ?, quantity = ?, image = ?
                        WHERE id = ?
                    ");
                    $update->bind_param("ssssssdisi", $barcode, $name, $category, $size, $brand, $expiry_date, $price, $quantity, $imageName, $id);
                }
            } else {
                if ($hasUpdatedAt) {
                    $update = $conn->prepare("
                        UPDATE products
                        SET barcode = ?, name = ?, category = ?, size = ?, expiry_date = ?, price = ?, quantity = ?, image = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $update->bind_param("sssssdisi", $barcode, $name, $category, $size, $expiry_date, $price, $quantity, $imageName, $id);
                } else {
                    $update = $conn->prepare("
                        UPDATE products
                        SET barcode = ?, name = ?, category = ?, size = ?, expiry_date = ?, price = ?, quantity = ?, image = ?
                        WHERE id = ?
                    ");
                    $update->bind_param("sssssdisi", $barcode, $name, $category, $size, $expiry_date, $price, $quantity, $imageName, $id);
                }
            }

            if ($update->execute()) {
                $update->close();
                header('Location: inventory.php?updated=1');
                exit();
            } else {
                $error = 'Failed to update product: ' . $update->error;
                $update->close();
            }
        }
    }
}

include 'header.php';
?>

<title>Edit Product — Bohol Bicycle Inventory</title>
<link rel="stylesheet" href="inventory.css">

<div class="inv-wrapper">
    <div class="inv-header">
        <div class="inv-header-left">
            <div class="inv-eyebrow">Products · Update</div>
            <h1 class="inv-title">Edit Product</h1>
            <small>Update inventory item details</small>
        </div>
        <div class="inv-header-right">
            <div class="inv-date-label">Today</div>
            <div class="inv-date-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <div class="inv-card" style="padding:24px;">
        <?php if ($error): ?>
            <div class="sup-alert sup-alert-error" style="margin-bottom:16px;">
                <span>✕</span> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= (int)$id ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="field-label">Barcode</label>
                    <input type="text" name="barcode" class="form-control" value="<?= htmlspecialchars($barcode) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="field-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="field-label">Category</label>
                    <select name="category" class="form-control" required>
                        <?php foreach ($categoryOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $category === $opt ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="field-label">Size</label>
                    <select name="size" class="form-control" required>
                        <?php foreach ($sizeOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $size === $opt ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="field-label">Brand</label>
                    <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($brand) ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="field-label">Expire Date</label>
                    <input type="date" name="expiry_date" class="form-control" value="<?= htmlspecialchars($expiry_date) ?>">
                </div>

                <div class="col-md-3">
                    <label class="field-label">Price</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= htmlspecialchars((string)$price) ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="field-label">Quantity</label>
                    <input type="number" min="0" name="quantity" class="form-control" value="<?= htmlspecialchars((string)$quantity) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="field-label">Image</label>
                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif">
                </div>
            </div>

            <div style="margin-top:20px; display:flex; gap:12px;">
                <button type="submit" class="btn-add-product" style="text-decoration:none; border:none;">Update Product</button>
                <a href="inventory.php" class="btn-modal-cancel" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; padding:0 18px;">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
<?php ob_end_flush(); ?>