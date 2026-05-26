<?php
ob_start();
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

$categoryOptions = [
    'MTB', 'Gravel', 'Roadbike', 'Folding Bike', 'Fixie', 'Fatbike', 'BMX', 'Kidsbike',
    'Ladies Bike', 'Vintagebike', 'Frame', 'Fork', 'Rim', 'Rimset', 'Tire', 'Cable',
    'Hub', 'Brake', 'Lever', 'Darailleure', 'Sprocket', 'Brake Pads', 'Chain',
    'Black Shie', 'Cleat', 'Accessories'
];

$sizeOptions = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '26', '27.5', '29', '700C', 'Other'];

function tableExists(mysqli $conn, string $table): bool {
    $safe = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$safe}'");
    return $res && $res->num_rows > 0;
}

function columnExists(mysqli $conn, string $table, string $column): bool {
    $table  = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

if (!tableExists($conn, 'products')) {
    die('Products table is missing.');
}

$hasArchive   = columnExists($conn, 'products', 'is_archived');
$hasSize      = columnExists($conn, 'products', 'size');
$hasBrand     = columnExists($conn, 'products', 'brand');
$hasExpiry    = columnExists($conn, 'products', 'expiry_date');
$hasImage     = columnExists($conn, 'products', 'image');
$hasCreatedAt = columnExists($conn, 'products', 'created_at');
$hasUpdatedAt = columnExists($conn, 'products', 'updated_at');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $barcode     = trim($_POST['barcode'] ?? '');
    $name        = trim($_POST['name'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $size        = trim($_POST['size'] ?? '');
    $brand       = trim($_POST['brand'] ?? '');
    $expiry_date = trim($_POST['expiry_date'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $quantity    = (int)($_POST['quantity'] ?? 0);

    $imageName = null;

    if ($barcode === '') {
        $error = 'Barcode is required.';
    } elseif ($name === '') {
        $error = 'Product name is required.';
    } elseif ($category === '') {
        $error = 'Category is required.';
    } elseif ($hasSize && $size === '') {
        $error = 'Size is required.';
    } elseif ($hasBrand && $brand === '') {
        $error = 'Brand is required.';
    } elseif ($price < 0) {
        $error = 'Price cannot be negative.';
    } elseif ($quantity < 0) {
        $error = 'Quantity cannot be negative.';
    } elseif ($expiry_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_date)) {
        $error = 'Invalid expiry date.';
    } else {
        if ($hasBrand) {
            $check = $conn->prepare("SELECT id FROM products WHERE barcode = ? AND brand = ? LIMIT 1");
            if (!$check) {
                $error = 'Failed to prepare duplicate check: ' . $conn->error;
            } else {
                $check->bind_param("ss", $barcode, $brand);
                $check->execute();
                $exists = $check->get_result()->fetch_assoc();
                $check->close();

                if ($exists) {
                    $error = 'This barcode already exists for the same brand.';
                }
            }
        } else {
            $check = $conn->prepare("SELECT id FROM products WHERE barcode = ? LIMIT 1");
            if (!$check) {
                $error = 'Failed to prepare barcode check: ' . $conn->error;
            } else {
                $check->bind_param("s", $barcode);
                $check->execute();
                $exists = $check->get_result()->fetch_assoc();
                $check->close();

                if ($exists) {
                    $error = 'Barcode already exists.';
                }
            }
        }
    }

    if ($error === '' && $hasImage && isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/assets/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tmpName   = $_FILES['image']['tmp_name'];
        $fileName  = $_FILES['image']['name'];
        $fileSize  = (int)($_FILES['image']['size'] ?? 0);
        $fileError = (int)($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE);

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if ($fileError !== UPLOAD_ERR_OK) {
            $error = 'Image upload failed.';
        } elseif (!in_array($ext, $allowed, true)) {
            $error = 'Only JPG, JPEG, PNG, WEBP, and GIF images are allowed.';
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $error = 'Image size must not exceed 5MB.';
        } else {
            $imageName = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            $dest = $uploadDir . $imageName;

            if (!move_uploaded_file($tmpName, $dest)) {
                $error = 'Failed to save uploaded image.';
            }
        }
    }

    if ($error === '') {
        $columns = ['barcode', 'name', 'category', 'price', 'quantity'];
        $values  = [$barcode, $name, $category, $price, $quantity];
        $types   = 'sssdi';

        if ($hasSize) {
            $columns[] = 'size';
            $values[]  = $size;
            $types    .= 's';
        }

        if ($hasBrand) {
            $columns[] = 'brand';
            $values[]  = $brand;
            $types    .= 's';
        }

        if ($hasExpiry) {
            $columns[] = 'expiry_date';
            $values[]  = ($expiry_date !== '' ? $expiry_date : null);
            $types    .= 's';
        }

        if ($hasImage) {
            $columns[] = 'image';
            $values[]  = $imageName;
            $types    .= 's';
        }

        if ($hasArchive) {
            $columns[] = 'is_archived';
            $values[]  = 0;
            $types    .= 'i';
        }

        if ($hasCreatedAt) {
            $columns[] = 'created_at';
        }

        if ($hasUpdatedAt) {
            $columns[] = 'updated_at';
        }

        $columnSql = [];
        $placeholders = [];

        foreach ($columns as $col) {
            $columnSql[] = "`$col`";
            if ($col === 'created_at' || $col === 'updated_at') {
                $placeholders[] = 'NOW()';
            } else {
                $placeholders[] = '?';
            }
        }

        $sql = "INSERT INTO products (" . implode(', ', $columnSql) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = 'Failed to prepare insert: ' . $conn->error;
        } else {
            $bindValues = array_values($values);
            $bindParams = [];
            $bindParams[] = $types;
            foreach ($bindValues as $k => $v) {
                $bindParams[] = &$bindValues[$k];
            }

            call_user_func_array([$stmt, 'bind_param'], $bindParams);

            if ($stmt->execute()) {
                $stmt->close();
                header('Location: inventory.php?added=1');
                exit();
            } else {
                $error = 'Failed to save product: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

include 'header.php';
?>

<title>Add Product — Bohol Bicycle Inventory</title>
<link rel="stylesheet" href="inventory.css">

<div class="inv-wrapper">
    <div class="inv-header">
        <div class="inv-header-left">
            <div class="inv-eyebrow">Products · New Entry</div>
            <h1 class="inv-title">Add Product</h1>
            <small>Create a new product record</small>
        </div>
        <div class="inv-header-right">
            <div class="inv-date-label">Today</div>
            <div class="inv-date-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <div class="inv-card" style="padding:24px;">
        <?php if ($error !== ''): ?>
            <div class="sup-alert sup-alert-error" style="margin-bottom:16px;">
                <span>✕</span> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="field-label">Barcode</label>
                    <input type="text" name="barcode" class="form-control" value="<?= htmlspecialchars($_POST['barcode'] ?? '') ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="field-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="field-label">Category</label>
                    <select name="category" class="form-control" required>
                        <option value="">Select category</option>
                        <?php foreach ($categoryOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= (($_POST['category'] ?? '') === $opt ? 'selected' : '') ?>>
                                <?= htmlspecialchars($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="field-label">Size</label>
                   <select name="size" class="form-control" <?= $hasSize ? 'required' : '' ?>>
                        <option value="">Select size</option>
                        <?php foreach ($sizeOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= (($_POST['size'] ?? '') === $opt ? 'selected' : '') ?>>
                                <?= htmlspecialchars($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="field-label">Brand</label>
                    <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="field-label">Expire Date</label>
                    <input type="date" name="expiry_date" class="form-control" value="<?= htmlspecialchars($_POST['expiry_date'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label class="field-label">Price</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="field-label">Quantity</label>
                    <input type="number" min="0" name="quantity" class="form-control" value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>" required>
                </div>

                <div class="col-md-8">
                    <label class="field-label">Image</label>
                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif">
                </div>

            </div>

            <div style="margin-top:20px; display:flex; gap:12px;">
                <button type="submit" name="save_product" class="btn-add-product" style="border:none;">Save Product</button>
                <a href="inventory.php" class="btn-modal-cancel" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; padding:0 18px;">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
<?php ob_end_flush(); ?>