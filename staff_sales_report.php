<?php
/* ============================================================
   STAFF_SALES_REPORT.PHP — Bohol Bicycle Inventory
   Staff-only sales report with date filter
   ============================================================ */
session_start();
require 'db.php';
include 'staff_header.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

/* ── DATE FILTER ── */
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

/* ── FETCH FILTERED SALES ── */
$stmt = $conn->prepare("
    SELECT
        s.id, s.total_amount, s.payment, s.change_amount, s.created_at,
        GROUP_CONCAT(CONCAT(p.name,' (',si.quantity,')') SEPARATOR '||') AS items
    FROM sales s
    LEFT JOIN sale_items si ON s.id = si.sale_id
    LEFT JOIN products p ON si.product_id = p.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY s.id
    ORDER BY s.id DESC
");
$stmt->bind_param('ss', $dateFrom, $dateTo);
$stmt->execute();
$result = $stmt->get_result();

$sales = [];
$totalSales  = 0;
$totalOrders = 0;

while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
    $totalSales += (float)$row['total_amount'];
    $totalOrders++;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Casher Sales Report — Bohol Bicycle Inventory</title>
    <link rel="stylesheet" href="staff_sales_report.css">
</head>
<body>

<div class="ssr-wrapper">

    <div class="ssr-header">
        <div class="ssr-header-left">
            <div class="ssr-eyebrow">Casher Portal · Sales Report</div>
            <h1 class="ssr-title">Sales Report</h1>
            <small>Transactions with Items · Filter by Date</small>
        </div>
        <div class="ssr-header-right">
            <div class="ssr-total-label">Total Sales</div>
            <div class="ssr-total-value">₱<?= number_format($totalSales, 2) ?></div>
        </div>
    </div>

    <div class="ssr-summary-grid">
        <div class="ssr-sum-card sum-s1">
            <div class="sum-label">Total Sales</div>
            <div class="sum-value">₱<?= number_format($totalSales, 2) ?></div>
        </div>
        <div class="ssr-sum-card sum-s2">
            <div class="sum-label">Total Orders</div>
            <div class="sum-value"><?= $totalOrders ?></div>
        </div>
        <div class="ssr-sum-card sum-s3">
            <div class="sum-label">Average Order</div>
            <div class="sum-value">₱<?= $totalOrders > 0 ? number_format($totalSales / $totalOrders, 2) : '0.00' ?></div>
        </div>
    </div>

    <div class="ssr-card">

        <div class="ssr-toolbar no-print">
            <form class="date-filter-form" method="GET" action="staff_sales_report.php">
                <div class="date-filter-group">
                    <label class="date-label">From</label>
                    <input type="date" name="date_from" class="date-input" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="date-filter-group">
                    <label class="date-label">To</label>
                    <input type="date" name="date_to" class="date-input" value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <button type="submit" class="btn-filter">Apply</button>
                <a href="staff_sales_report.php" class="btn-reset">Reset</a>
            </form>

            <div class="toolbar-right">
                <div class="ssr-count"><?= $totalOrders ?> record(s)</div>
                <button class="btn-print" onclick="window.print()">🖨 Print</button>
                <a href="staff_dashboard.php" class="btn-back">← Dashboard</a>
            </div>
        </div>

        <div class="ssr-divider no-print"></div>

        <div class="ssr-table-wrap">
            <table class="ssr-table" id="salesTable">
                <thead>
                    <tr>
                        <th style="width:80px;">#</th>
                        <th>Date & Time</th>
                        <th>Items Sold</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Payment</th>
                        <th class="text-right">Change</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($sales) > 0): ?>
                    <?php foreach ($sales as $s): ?>
                        <tr>
                            <td class="td-id">SO-<?= str_pad($s['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td class="td-date"><?= htmlspecialchars($s['created_at']) ?></td>
                            <td class="td-items">
                                <?php if (!empty($s['items'])): ?>
                                    <ul class="items-list">
                                        <?php foreach (explode('||', $s['items']) as $item): ?>
                                            <li><?= htmlspecialchars($item) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="td-muted">No items</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right td-price">₱<?= number_format((float)$s['total_amount'], 2) ?></td>
                            <td class="text-right td-payment">₱<?= number_format((float)$s['payment'], 2) ?></td>
                            <td class="text-right td-change">₱<?= number_format((float)$s['change_amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="td-empty">
                            <span class="empty-icon">🧾</span>
                            <span class="empty-text">No sales found for the selected date range.</span>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalOrders > 0): ?>
        <div class="ssr-total-strip no-print">
            <div class="strip-left">
                <span class="strip-label">Period:</span>
                <span class="strip-period"><?= date('M d, Y', strtotime($dateFrom)) ?> → <?= date('M d, Y', strtotime($dateTo)) ?></span>
            </div>
            <div class="strip-right">
                <span class="strip-label">Grand Total</span>
                <span class="strip-value">₱<?= number_format($totalSales, 2) ?></span>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="staff_sales_report.js"></script>
</body>
</html>