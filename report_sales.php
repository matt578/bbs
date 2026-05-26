<?php
include 'header.php';
include 'db.php';

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

if (!tableExists($conn, 'sales')) die("Sales table is missing.");
if (!tableExists($conn, 'sale_items')) die("Sale items table is missing.");
if (!tableExists($conn, 'products')) die("Products table is missing.");

$hasCategory   = columnExists($conn, 'products', 'category');
$hasSize       = columnExists($conn, 'products', 'size');
$hasExpiryDate = columnExists($conn, 'products', 'expiry_date');
$hasName       = columnExists($conn, 'products', 'name');

/* =========================
   CATEGORY FILTER
========================= */
$selectedCategory = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

$whereClause = "";
if (!empty($selectedCategory)) {
    $whereClause = "WHERE p.category = '{$selectedCategory}'";
}

/* =========================
   SAFE COLUMN HANDLING
========================= */
$productNameSql = $hasName ? "COALESCE(p.name, 'Unknown Product')" : "'Unknown Product'";
$categorySql    = $hasCategory ? "COALESCE(p.category, 'N/A')" : "'N/A'";
$sizeSql        = $hasSize ? "COALESCE(p.size, 'N/A')" : "'N/A'";
$expirySql      = $hasExpiryDate ? "COALESCE(DATE_FORMAT(p.expiry_date, '%Y-%m-%d'), 'N/A')" : "'N/A'";

/* =========================
   MAIN QUERY (WITH FILTER)
========================= */
$sql = "
SELECT
    s.id,
    s.total_amount,
    s.payment,
    s.change_amount,
    s.created_at,
    GROUP_CONCAT(
        CONCAT(
            {$productNameSql},
            ' (Qty: ', COALESCE(si.quantity, 0), ')',
            ' | Category: ', {$categorySql},
            ' | Size: ', {$sizeSql},
            ' | Expire: ', {$expirySql}
        )
        SEPARATOR '||'
    ) AS items
FROM sales s
LEFT JOIN sale_items si ON s.id = si.sale_id
LEFT JOIN products p ON si.product_id = p.id
{$whereClause}
GROUP BY s.id, s.total_amount, s.payment, s.change_amount, s.created_at
ORDER BY s.id DESC
";

$result = $conn->query($sql);
if (!$result) die("SQL Error: " . $conn->error);

$sales = [];
$totalSales = 0;

while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
    $totalSales += (float)$row['total_amount'];
}

/* =========================
   CATEGORY LIST
========================= */
$categories = [
'MTB','Gravel','Roadbike','Folding Bike','Fixie','Fatbike','BMX','Kidsbike',
'Ladies Bike','Vintagebike','Frame','Fork','Rim','Rimset','Tire','Cable',
'Hub','Brake','Lever','Darailleure','Sprocket','Brake Pads','Chain',
'Black Shie','Cleat','Accessories'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sales Report — Bohol Bicycle Inventory</title>
<link rel="stylesheet" href="report_sales.css">
</head>

<body>
<div class="rpt-wrapper">

    <div class="rpt-header">
        <div class="rpt-header-left">
            <div class="rpt-eyebrow">Transactions · Items · Totals</div>
            <h1 class="rpt-title">Sales Report</h1>
            <small>Transactions with Items</small>
        </div>
        <div class="rpt-header-right">
            <div class="rpt-total-label">Total Sales</div>
            <div class="rpt-total-value">₱<?= number_format($totalSales, 2) ?></div>
        </div>
    </div>

    <div class="rpt-card">

        <!-- =========================
             TOOLBAR (UPDATED)
        ========================== -->
        <div class="rpt-toolbar no-print">

            <div style="display:flex; gap:10px; align-items:center;">

                <!-- CATEGORY DROPDOWN -->
                <form method="GET">
                    <select name="category" onchange="this.form.submit()" class="rpt-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($selectedCategory == $cat) ? 'selected' : '' ?>>
                                <?= $cat ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <div class="rpt-card-title">
                    Sales Transactions
                    <span class="rpt-count"><?= count($sales) ?> record(s)</span>
                </div>
            </div>

            <button class="btn-print" onclick="window.print()">🖨 Print</button>
        </div>

        <div class="rpt-divider no-print"></div>

        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Payment</th>
                        <th class="text-right">Change</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($sales as $s): ?>
                    <tr>
                        <td><?= (int)$s['id'] ?></td>
                        <td><?= htmlspecialchars($s['created_at']) ?></td>
                        <td>
                            <?php if (!empty($s['items'])): ?>
                                <ul class="items-list">
                                    <?php foreach (explode('||', $s['items']) as $item): ?>
                                        <li><?= htmlspecialchars($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span>No items</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">₱<?= number_format($s['total_amount'], 2) ?></td>
                        <td class="text-right">₱<?= number_format($s['payment'], 2) ?></td>
                        <td class="text-right">₱<?= number_format($s['change_amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (count($sales) === 0): ?>
                    <tr>
                        <td colspan="6">No sales found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (count($sales) > 0): ?>
        <div class="rpt-total-strip no-print">
            <span>Grand Total</span>
            <span>₱<?= number_format($totalSales, 2) ?></span>
        </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
