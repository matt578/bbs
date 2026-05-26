<?php
require 'db.php';
include 'header.php';

function tableExists(mysqli $conn, string $table): bool {
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    return ($res && $res->num_rows > 0);
}

function columnExists(mysqli $conn, string $table, string $column): bool {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

function getColumns(mysqli $conn, string $table): array {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $cols[] = $r['Field'];
        }
    }
    return $cols;
}

function pickFirstExisting(array $columns, array $candidates): ?string {
    $lower = array_map('strtolower', $columns);
    foreach ($candidates as $c) {
        $idx = array_search(strtolower($c), $lower, true);
        if ($idx !== false) {
            return $columns[$idx];
        }
    }
    return null;
}

function fetchSumSafe(mysqli $conn, string $sql): float {
    $st = $conn->prepare($sql);
    if (!$st) return 0.0;
    if (!$st->execute()) {
        $st->close();
        return 0.0;
    }
    $res = $st->get_result();
    $sum = $res ? (float)(($res->fetch_assoc()['sum'] ?? 0)) : 0.0;
    $st->close();
    return $sum;
}

$products = [];
$addedSuccess = isset($_GET['added']) && $_GET['added'] === '1';
$restockedSuccess = isset($_GET['restocked']) && $_GET['restocked'] === '1';

if (tableExists($conn, 'products')) {
    $productColumns = getColumns($conn, 'products');
    $hasArchiveFlag = in_array('is_archived', $productColumns, true);

    $selectParts = ['*'];
    $sql = $hasArchiveFlag
        ? "SELECT * FROM products WHERE is_archived = 0 ORDER BY id DESC"
        : "SELECT * FROM products ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$salesTableCandidates = ['sales','sale','orders','order','transactions','transaction','invoice','invoices'];
$amountCandidates     = ['total','total_amount','grand_total','amount','paid','net_total','final_total'];
$dateCandidates       = ['created_at','date','sale_date','sold_at','datetime','timestamp','createdOn'];

$salesTable = null;
$amountCol  = null;
$dateCol    = null;

foreach ($salesTableCandidates as $t) {
    if (tableExists($conn, $t)) {
        $cols = getColumns($conn, $t);
        $a = pickFirstExisting($cols, $amountCandidates);
        $d = pickFirstExisting($cols, $dateCandidates);
        if ($a && $d) {
            $salesTable = $t;
            $amountCol = $a;
            $dateCol = $d;
            break;
        }
    }
}

$earnToday = 0.0;
$earnWeek  = 0.0;
$earnMonth = 0.0;
$earnYear  = 0.0;

if ($salesTable && $amountCol && $dateCol) {
    $tbl = "`" . str_replace("`", "", $salesTable) . "`";
    $amt = "`" . str_replace("`", "", $amountCol) . "`";
    $dt  = "`" . str_replace("`", "", $dateCol) . "`";

    $earnToday = fetchSumSafe($conn, "SELECT COALESCE(SUM($amt),0) AS sum FROM $tbl WHERE DATE($dt)=CURDATE()");
    $earnWeek  = fetchSumSafe($conn, "SELECT COALESCE(SUM($amt),0) AS sum FROM $tbl WHERE YEARWEEK($dt,1)=YEARWEEK(CURDATE(),1)");
    $earnMonth = fetchSumSafe($conn, "SELECT COALESCE(SUM($amt),0) AS sum FROM $tbl WHERE YEAR($dt)=YEAR(CURDATE()) AND MONTH($dt)=MONTH(CURDATE())");
    $earnYear  = fetchSumSafe($conn, "SELECT COALESCE(SUM($amt),0) AS sum FROM $tbl WHERE YEAR($dt)=YEAR(CURDATE())");
}
?>

<title>Inventory — Bohol Bicycle Inventory</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="inventory.css">

<div class="inv-wrapper">

    <div class="inv-header">
        <div class="inv-header-left">
            <div class="inv-eyebrow">Products · Categories · Stock</div>
            <h1 class="inv-title">Inventory</h1>
            <small>Products • Categories • Stock</small>
        </div>
        <div class="inv-header-right">
            <div class="inv-date-label">Today</div>
            <div class="inv-date-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <?php if ($addedSuccess): ?>
        <div class="sup-alert sup-alert-success" style="margin-bottom:16px;">
            <span>✓</span> Product added successfully.
        </div>
    <?php endif; ?>

    <?php if ($restockedSuccess): ?>
        <div class="sup-alert sup-alert-success" style="margin-bottom:16px;">
            <span>✓</span> Product restocked successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
        <div class="sup-alert sup-alert-success" style="margin-bottom:16px;">
            <span>✓</span> Product updated successfully.
        </div>
    <?php endif; ?>

    <div class="earn-grid">

        <div class="earn-card earn-s1">
            <div class="earn-top">
                <span class="earn-label">Earnings Today</span>
                <div class="earn-icon"><i class="bi bi-cash-coin"></i></div>
            </div>
            <div class="earn-value" id="earnTodayVal">₱<?= number_format($earnToday, 2) ?></div>
        </div>

        <div class="earn-card earn-s2">
            <div class="earn-top">
                <span class="earn-label">Earnings This Week</span>
                <div class="earn-icon"><i class="bi bi-calendar-week"></i></div>
            </div>
            <div class="earn-value" id="earnWeekVal">₱<?= number_format($earnWeek, 2) ?></div>
        </div>

        <div class="earn-card earn-s3">
            <div class="earn-top">
                <span class="earn-label">Earnings This Month</span>
                <div class="earn-icon"><i class="bi bi-calendar3"></i></div>
            </div>
            <div class="earn-value" id="earnMonthVal">₱<?= number_format($earnMonth, 2) ?></div>
        </div>

        <div class="earn-card earn-s4">
            <div class="earn-top">
                <span class="earn-label">Earnings This Year</span>
                <div class="earn-icon"><i class="bi bi-calendar2-event"></i></div>
            </div>
            <div class="earn-value" id="earnYearVal">₱<?= number_format($earnYear, 2) ?></div>
        </div>

    </div>

    <div class="inv-card">

        <div class="inv-toolbar">
            <div class="inv-toolbar-left">
                <div class="inv-card-title">Product List</div>
                <div class="inv-card-sub">
                    Showing <span id="visibleCount"><?= count($products) ?></span> product(s)
                </div>
            </div>
            <div class="inv-toolbar-right">
                <input type="text" class="inv-search" id="searchInput" placeholder="Search product...">
                <a href="add_product.php" class="btn-add-product">+ New Product</a>
                <a href="restock_product.php" class="btn-add-product">+ Restock</a>
            </div>
        </div>

        <div class="inv-divider"></div>

        <div class="inv-table-wrap">
            <table class="inv-table" id="productTable">
                <thead>
                    <tr>
                        <th>Barcode</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Size</th>
                        <th>Brand</th>
                        <th>Expire Date</th>
                        <th class="text-right">Price</th>
                        <th class="text-center">Count</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <?php $total = ((float)($p['price'] ?? 0) * (int)($p['quantity'] ?? 0)); ?>
                    <tr>
                        <td class="td-barcode"><?= htmlspecialchars($p['barcode'] ?? '') ?></td>

                        <td>
                            <?php if (!empty($p['image']) && file_exists('assets/images/' . $p['image'])): ?>
                                <img src="assets/images/<?= htmlspecialchars($p['image']) ?>" class="table-img" alt="">
                            <?php else: ?>
                                <div class="img-placeholder"><i class="bi bi-image"></i></div>
                            <?php endif; ?>
                        </td>

                        <td class="td-name"><?= htmlspecialchars($p['name'] ?? '') ?></td>
                        <td><span class="cat-pill"><?= htmlspecialchars($p['category'] ?? '') ?></span></td>
                        <td><?= htmlspecialchars($p['size'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['brand'] ?? '-') ?></td>
                        <td><?= !empty($p['expiry_date']) ? htmlspecialchars(date('M d, Y', strtotime($p['expiry_date']))) : '-' ?></td>
                        <td class="text-right td-price">₱<?= number_format((float)($p['price'] ?? 0), 2) ?></td>
                        <td class="text-center"><span class="qty-badge"><?= (int)($p['quantity'] ?? 0) ?></span></td>
                        <td class="text-right td-price">₱<?= number_format($total, 2) ?></td>

                        <td class="text-right">
                            <div class="action-btns">
                                <a href="edit_product.php?id=<?= (int)($p['id'] ?? 0) ?>" class="icon-btn icon-edit" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="archive_product.php?id=<?= (int)($p['id'] ?? 0) ?>"
                                   class="icon-btn icon-archive"
                                   onclick="return confirm('Archive this product?')"
                                   title="Archive">
                                    <i class="bi bi-archive"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (count($products) === 0): ?>
                    <tr>
                        <td colspan="11" class="td-empty">
                            <span class="empty-icon">📦</span>
                            <span class="empty-text">No products found.</span>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="inventory.js"></script>
</body>
</html>