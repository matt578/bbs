<?php
require 'auth_helpers.php';
requireLogin();

require 'db.php';
include 'header.php';

function tableExists(mysqli $conn, string $table): bool {
    $safe = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$safe}'");
    return $res && $res->num_rows > 0;
}

function columnExists(mysqli $conn, string $table, string $column): bool {
    $safeTable  = $conn->real_escape_string($table);
    $safeColumn = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    return $res && $res->num_rows > 0;
}

function getScalar(mysqli $conn, string $sql, int|float $default = 0): int|float {
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        return $row['total'] ?? $default;
    }
    return $default;
}

function firstExistingTable(mysqli $conn, array $tables): ?string {
    foreach ($tables as $table) {
        if (tableExists($conn, $table)) {
            return $table;
        }
    }
    return null;
}

function firstExistingColumn(mysqli $conn, string $table, array $columns): ?string {
    foreach ($columns as $column) {
        if (columnExists($conn, $table, $column)) {
            return $column;
        }
    }
    return null;
}

$totalProducts = 0;
$totalStock    = 0;
$userCount     = 0;
$loginCount    = 0;
$todayLogin    = 0;
$totalIncome   = 0;
$totalLoss     = 0;

if (tableExists($conn, 'products')) {
    $totalProducts = (int)getScalar($conn, "SELECT COUNT(*) AS total FROM products");
    $totalStock    = (int)getScalar($conn, "SELECT COALESCE(SUM(quantity),0) AS total FROM products");
}

if (tableExists($conn, 'users')) {
    $userCount = (int)getScalar($conn, "SELECT COUNT(*) AS total FROM users");
}

if (tableExists($conn, 'login_history')) {
    $loginCount = (int)getScalar($conn, "SELECT COUNT(*) AS total FROM login_history");

    $dateColumn = null;
    $colsRes = $conn->query("SHOW COLUMNS FROM login_history");
    if ($colsRes) {
        while ($col = $colsRes->fetch_assoc()) {
            $field = strtolower($col['Field']);
            if (in_array($field, ['login_time', 'created_at', 'date', 'logged_at', 'time_in', 'timestamp'], true)) {
                $dateColumn = $col['Field'];
                break;
            }
        }
    }

    if ($dateColumn) {
        $todayLogin = (int)getScalar($conn, "SELECT COUNT(*) AS total FROM login_history WHERE DATE(`$dateColumn`) = CURDATE()");
    } else {
        $todayLogin = $loginCount;
    }
}

/* =========================
   TOTAL INCOME
   Priority:
   1. sales.total_amount / total / amount
   2. SUM(quantity * price)
   ========================= */
$salesTable = firstExistingTable($conn, ['sales', 'orders', 'transactions']);
if ($salesTable) {
    $incomeColumn = firstExistingColumn($conn, $salesTable, [
        'total_amount', 'total', 'amount', 'grand_total', 'sale_total'
    ]);

    if ($incomeColumn) {
        $totalIncome = (float)getScalar(
            $conn,
            "SELECT COALESCE(SUM(`$incomeColumn`),0) AS total FROM `$salesTable`"
        );
    } else {
        $qtyColumn   = firstExistingColumn($conn, $salesTable, ['quantity', 'qty']);
        $priceColumn = firstExistingColumn($conn, $salesTable, ['price', 'unit_price', 'selling_price']);

        if ($qtyColumn && $priceColumn) {
            $totalIncome = (float)getScalar(
                $conn,
                "SELECT COALESCE(SUM(`$qtyColumn` * `$priceColumn`),0) AS total FROM `$salesTable`"
            );
        }
    }
}

/* =========================
   TOTAL LOSS
   Priority:
   1. expenses.total_amount / total / amount
   2. SUM(quantity * cost)
   ========================= */
$lossTable = firstExistingTable($conn, ['expenses', 'losses', 'purchases']);
if ($lossTable) {
    $lossColumn = firstExistingColumn($conn, $lossTable, [
        'total_amount', 'total', 'amount', 'cost', 'expense_amount'
    ]);

    if ($lossColumn) {
        $totalLoss = (float)getScalar(
            $conn,
            "SELECT COALESCE(SUM(`$lossColumn`),0) AS total FROM `$lossTable`"
        );
    } else {
        $qtyColumn  = firstExistingColumn($conn, $lossTable, ['quantity', 'qty']);
        $costColumn = firstExistingColumn($conn, $lossTable, ['cost', 'unit_cost', 'purchase_price', 'price']);

        if ($qtyColumn && $costColumn) {
            $totalLoss = (float)getScalar(
                $conn,
                "SELECT COALESCE(SUM(`$qtyColumn` * `$costColumn`),0) AS total FROM `$lossTable`"
            );
        }
    }
}

$chartLabels = [];
$chartData   = [];

if (tableExists($conn, 'products')) {
    $stockReport = $conn->query("SELECT name, quantity FROM products ORDER BY quantity DESC, id DESC LIMIT 12");
    if ($stockReport instanceof mysqli_result) {
        while ($s = $stockReport->fetch_assoc()) {
            $chartLabels[] = $s['name'];
            $chartData[]   = (int)$s['quantity'];
        }
    }
}
?>

<title>Dashboard — Bohol Bicycle Inventory</title>
<link rel="stylesheet" href="dashboard.css">

<div class="dash-wrapper">

    <div class="dash-header">
        <div class="dash-header-left">
            <div class="dash-eyebrow">Overview · Stocks · Users · Logins</div>
            <h1 class="dash-title">Dashboard</h1>
            <small>Overview • Stocks • Users • Logins</small>
        </div>
        <div class="dash-header-right">
            <div class="dash-date-label">Today</div>
            <div class="dash-date-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <div class="kpi-grid">

        <div class="kpi-card kpi-s1">
            <div class="kpi-top">
                <span class="kpi-label">Total Stock</span>
                <div class="kpi-icon">S</div>
            </div>
            <div class="kpi-value"><?= $totalStock ?></div>
            <div class="kpi-sub">units in inventory</div>
        </div>

        <div class="kpi-card kpi-s2">
            <div class="kpi-top">
                <span class="kpi-label">Total Products</span>
                <div class="kpi-icon">P</div>
            </div>
            <div class="kpi-value"><?= $totalProducts ?></div>
            <div class="kpi-sub">product types</div>
        </div>

        <div class="kpi-card kpi-s3">
            <div class="kpi-top">
                <span class="kpi-label">Total Users</span>
                <div class="kpi-icon">U</div>
            </div>
            <div class="kpi-value"><?= $userCount ?></div>
            <div class="kpi-sub">registered accounts</div>
        </div>

        <div class="kpi-card kpi-s4">
            <div class="kpi-top">
                <span class="kpi-label">Today's Logins</span>
                <div class="kpi-icon">L</div>
            </div>
            <div class="kpi-value"><?= $todayLogin ?></div>
            <div class="kpi-sub">session today</div>
        </div>

        <div class="kpi-card kpi-s5">
            <div class="kpi-top">
                <span class="kpi-label">Total Income</span>
                <div class="kpi-icon">I</div>
            </div>
            <div class="kpi-value">₱<?= number_format($totalIncome, 2) ?></div>
            <div class="kpi-sub">overall income</div>
        </div>

        <div class="kpi-card kpi-s6">
            <div class="kpi-top">
                <span class="kpi-label">Total Loss</span>
                <div class="kpi-icon">X</div>
            </div>
            <div class="kpi-value">₱<?= number_format($totalLoss, 2) ?></div>
            <div class="kpi-sub">overall loss</div>
        </div>

    </div>

    <div class="chart-section">

        <div class="chart-left">
            <div class="chart-card-title">Stock Overview</div>
            <div class="chart-canvas-wrap">
                <canvas id="barChart"></canvas>
            </div>
            <div class="chart-left-label">Active Stock</div>
            <div class="chart-left-sub">(+<?= $totalStock ?>) units in inventory</div>
        </div>

        <div class="chart-right">
            <div class="chart-right-header">
                <div>
                    <div class="chart-right-title">Stock Report</div>
                    <div class="chart-right-sub">
                        (+<?= $totalStock ?>) more in <?= date('Y') ?> — Top 12 products by quantity
                    </div>
                </div>
                <div class="chart-legend">
                    <span class="legend-dot dot-stock"></span> Stock &nbsp;
                    <span class="legend-dot dot-trend"></span> Trend
                </div>
            </div>
            <div class="chart-right-wrap">
                <canvas id="areaChart"></canvas>
            </div>
        </div>

    </div>

</div>

<script>
window.CHART_LABELS = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
window.CHART_DATA   = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="dashboard.js"></script>
</body>
</html>