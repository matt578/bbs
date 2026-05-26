<?php
/* ============================================================
   REPORT_INVENTORY.PHP — Bohol Bicycle Inventory
   ============================================================ */
include 'header.php';
include 'db.php';

$low  = max(1, (int)($_GET['low'] ?? 5));

$stmt = $conn->prepare("SELECT id, barcode, name, category, price, quantity FROM products ORDER BY quantity ASC");
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* =========================
   LOW STOCK LOGIC FIXED
   ========================= */
$lowItems = array_values(array_filter($items, function ($p) use ($low) {
    return (int)$p['quantity'] <= $low;
}));

$lowCount = count($lowItems);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Report — Bohol Bicycle Inventory</title>
    <link rel="stylesheet" href="report_inventory.css">
</head>
<body>

<div class="rpt-wrapper">

    <!-- PAGE HEADER -->
    <div class="rpt-header">
        <div class="rpt-header-left">
            <div class="rpt-eyebrow">Stock · Low Items · Summary</div>
            <h1 class="rpt-title">Inventory Report</h1>
            <small>Stock • Low Items • Summary</small>
        </div>
        <div class="rpt-header-right">
            <div class="rpt-total-label">Generated</div>
            <div class="rpt-total-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <!-- MAIN CARD -->
    <div class="rpt-card">

        <!-- TOOLBAR -->
        <div class="rpt-toolbar no-print">
            <form class="filter-form" method="GET" action="report_inventory.php">
                <label class="filter-label">Low stock threshold</label>
                <input type="number" name="low" min="1" value="<?= $low ?>" class="filter-input">
                <button type="submit" class="btn-apply">Apply</button>
                <a href="report_inventory.php" class="btn-reset">Reset</a>
            </form>

            <div class="toolbar-right">
                <?php if ($lowCount > 0): ?>
                    <div class="low-badge">⚠ <?= $lowCount ?> low stock item(s)</div>
                <?php endif; ?>
                <button class="btn-print" onclick="window.print()">🖨 Print</button>
            </div>
        </div>

        <div class="rpt-divider no-print"></div>

        <!-- ✅ LOW STOCK NOTIFICATION (FIXED) -->
        <?php if ($lowCount > 0): ?>
            <div class="low-stock-notification">
                <div class="notif-title">⚠ Low Stock Alert</div>
                <div class="notif-desc">
                    The following items are low in stock:
                </div>
                <ul class="notif-list">
                    <?php foreach ($lowItems as $item): ?>
                        <li>
                            <strong><?= htmlspecialchars($item['name']) ?></strong>
                            (<?= htmlspecialchars($item['category']) ?>)
                            — Qty: <?= (int)$item['quantity'] ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- TABLE -->
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Barcode</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th class="text-right">Price</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($items) === 0): ?>
                    <tr>
                        <td colspan="6" class="td-empty">No products found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $p): ?>
                        <?php $isLow = ((int)$p['quantity'] <= $low); ?>
                        <tr class="<?= $isLow ? 'row-low' : '' ?>">
                            <td><?= htmlspecialchars($p['barcode']) ?></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><span class="cat-pill"><?= htmlspecialchars($p['category']) ?></span></td>
                            <td class="text-right">₱<?= number_format((float)$p['price'], 2) ?></td>
                            <td class="text-center">
                                <span class="<?= $isLow ? 'qty-low' : 'qty-ok' ?>">
                                    <?= (int)$p['quantity'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="status-badge <?= $isLow ? 'status-low' : 'status-ok' ?>">
                                    <?= $isLow ? 'LOW' : 'OK' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="report_inventory.js"></script>
</body>
</html>