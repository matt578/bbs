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

/* =========================
   HELPERS
========================= */
function tableExists(mysqli $conn, string $table): bool {
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    return $res && $res->num_rows > 0;
}

function columnExists(mysqli $conn, string $table, string $column): bool {
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

if (!tableExists($conn, 'products')) {
    die('Products table missing.');
}

$hasArchive   = columnExists($conn, 'products', 'is_archived');
$hasSize      = columnExists($conn, 'products', 'size');
$hasBrand     = columnExists($conn, 'products', 'brand');
$hasExpiry    = columnExists($conn, 'products', 'expiry_date');
$hasImage     = columnExists($conn, 'products', 'image');
$hasUpdatedAt = columnExists($conn, 'products', 'updated_at');

/* =========================
   LOAD PRODUCTS
========================= */
$products = [];
$sql = $hasArchive
    ? "SELECT * FROM products WHERE is_archived = 0 ORDER BY name ASC"
    : "SELECT * FROM products ORDER BY name ASC";

$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}

/* =========================
   SELECT PRODUCT
========================= */
$selectedId = (int)($_GET['product_id'] ?? $_POST['source_product_id'] ?? 0);
$selectedProduct = null;

if ($selectedId > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $selectedId);
    $stmt->execute();
    $selectedProduct = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* =========================
   SAVE RESTOCK (FIXED)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_restock'])) {

    $sourceProductId = (int)$_POST['source_product_id'];
    $barcode         = trim($_POST['barcode']);
    $name            = trim($_POST['name']);
    $category        = trim($_POST['category']);
    $size            = trim($_POST['size'] ?? '');
    $brand           = trim($_POST['brand'] ?? '');
    $expiry_date     = $_POST['expiry_date'] ?? null;
    $price           = (float)$_POST['price'];
    $quantity        = (int)$_POST['quantity'];

    $oldImage  = $_POST['old_image'] ?? null;
    $imageName = $oldImage;

    if ($quantity <= 0) {
        $error = "Quantity must be greater than 0.";
    }

    /* IMAGE UPLOAD */
    if (!$error && $hasImage && isset($_FILES['image']) && !empty($_FILES['image']['name'])) {

        if (!is_dir('assets/images')) {
            mkdir('assets/images', 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $imageName = 'prod_' . time() . '.' . $ext;

        move_uploaded_file($_FILES['image']['tmp_name'], 'assets/images/' . $imageName);
    }

    if (!$error) {

        /* GET CURRENT QTY */
        $stmt = $conn->prepare("SELECT quantity FROM products WHERE id=?");
        $stmt->bind_param("i", $sourceProductId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $error = "Product not found.";
        } else {

            $newQty = $row['quantity'] + $quantity;

            /* UPDATE INSTEAD OF INSERT */
            $sql = "UPDATE products SET 
                        barcode=?, 
                        name=?, 
                        category=?, 
                        price=?, 
                        quantity=?";

            $types = "sssdi";
            $values = [$barcode, $name, $category, $price, $newQty];

            /* SIZE (FIXED) */
            if ($hasSize) {
                $sql .= ", size=?";
                $types .= "s";
                $values[] = $size;
            }

            if ($hasBrand) {
                $sql .= ", brand=?";
                $types .= "s";
                $values[] = $brand;
            }

            if ($hasExpiry) {
                $sql .= ", expiry_date=?";
                $types .= "s";
                $values[] = $expiry_date;
            }

            if ($hasImage) {
                $sql .= ", image=?";
                $types .= "s";
                $values[] = $imageName;
            }

            if ($hasUpdatedAt) {
                $sql .= ", updated_at=NOW()";
            }

            $sql .= " WHERE id=?";
            $types .= "i";
            $values[] = $sourceProductId;

            $stmt = $conn->prepare($sql);

            $bind = [];
            $bind[] = $types;

            foreach ($values as $k => $v) {
                $bind[] = &$values[$k];
            }

            call_user_func_array([$stmt, 'bind_param'], $bind);

            if ($stmt->execute()) {
                header("Location: inventory.php?restocked=1");
                exit();
            } else {
                $error = "Update failed: " . $stmt->error;
            }
        }
    }
}

include 'header.php';
?>

<title>Restock Product — Bohol Bicycle Inventory</title>
<link rel="stylesheet" href="inventory.css">

<div class="inv-wrapper">
    <div class="inv-header">
        <div class="inv-header-left">
            <div class="inv-eyebrow">Products · Stock Update</div>
            <h1 class="inv-title">Restock Product</h1>
            <small>Create a new stock entry from an existing product</small>
        </div>
        <div class="inv-header-right">
            <div class="inv-date-label">Today</div>
            <div class="inv-date-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <div class="inv-card" style="padding:24px;">

        <?php if ($error): ?>
            <div class="sup-alert sup-alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- SELECT PRODUCT -->
        <form method="GET" style="margin-bottom:20px;">
            <select name="product_id" class="form-control" onchange="this.form.submit()">
                <option value="">Choose product</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $selectedId == $p['id'] ? 'selected' : '' ?>>
                        <?= $p['name'] ?> | <?= $p['barcode'] ?> | ₱<?= number_format($p['price'],2) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($selectedProduct): ?>
        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="source_product_id" value="<?= $selectedProduct['id'] ?>">
            <input type="hidden" name="old_image" value="<?= $selectedProduct['image'] ?>">

            <div class="row g-3">

                <div class="col-md-4">
                    <label>Barcode</label>
                    <input type="text" name="barcode" class="form-control" value="<?= $selectedProduct['barcode'] ?>">
                </div>

                <div class="col-md-4">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="<?= $selectedProduct['name'] ?>">
                </div>

                <div class="col-md-4">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <?php foreach ($categoryOptions as $c): ?>
                            <option <?= $selectedProduct['category']==$c?'selected':'' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($hasSize): ?>
                <div class="col-md-3">
                    <label>Size</label>
                    <select name="size" class="form-control">
                        <?php foreach ($sizeOptions as $s): ?>
                            <option <?= $selectedProduct['size']==$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($hasBrand): ?>
                <div class="col-md-3">
                    <label>Brand</label>
                    <input type="text" name="brand" class="form-control" value="<?= $selectedProduct['brand'] ?>">
                </div>
                <?php endif; ?>

                <?php if ($hasExpiry): ?>
                <div class="col-md-3">
                    <label>Expire Date</label>
                    <input type="date" name="expiry_date" class="form-control" value="<?= $selectedProduct['expiry_date'] ?>">
                </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <label>Price</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?= $selectedProduct['price'] ?>">
                </div>

                <div class="col-md-4">
                    <label>Quantity (Add)</label>
                    <input type="number" name="quantity" class="form-control" required>
                </div>

                <?php if ($hasImage): ?>
                <div class="col-md-8">
                    <label>Image</label>
                    <input type="file" name="image" class="form-control">
                </div>
                <?php endif; ?>

            </div>

            <div style="margin-top:20px;">
                <button type="submit" name="save_restock" class="btn-add-product">Save Product</button>
            </div>

        </form>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
<?php ob_end_flush(); ?>