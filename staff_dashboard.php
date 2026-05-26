<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: staff_login.php');
    exit();
}

include 'staff_header.php';

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

function getScalar(mysqli $conn, string $sql, $default = 0) {
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        return $row['total'] ?? $default;
    }
    return $default;
}

$hasSales = tableExists($conn, 'sales');
$hasSaleItems = tableExists($conn, 'sale_items');
$hasProducts = tableExists($conn, 'products');
$hasArchivedFlag = $hasProducts && columnExists($conn, 'products', 'is_archived');

$hasLossesTable = tableExists($conn, 'losses') && columnExists($conn, 'losses', 'amount');
$hasExpensesTable = tableExists($conn, 'expenses') && columnExists($conn, 'expenses', 'amount');

$todaySales   = 0.0;
$todayOrders  = 0;
$totalStock   = 0;
$lowStock     = 0;
$totalIncome  = 0.0;
$totalLoss    = 0.0;
$recentSales  = [];
$chartLabels  = [];
$chartData    = [];

if ($hasSales) {
    $todaySales = (float)getScalar(
        $conn,
        "SELECT COALESCE(SUM(total_amount),0) AS total FROM sales WHERE DATE(created_at)=CURDATE()",
        0
    );

    $todayOrders = (int)getScalar(
        $conn,
        "SELECT COUNT(*) AS total FROM sales WHERE DATE(created_at)=CURDATE()",
        0
    );

    $totalIncome = (float)getScalar(
        $conn,
        "SELECT COALESCE(SUM(total_amount),0) AS total FROM sales",
        0
    );
}

if ($hasLossesTable) {
    $totalLoss = (float)getScalar(
        $conn,
        "SELECT COALESCE(SUM(amount),0) AS total FROM losses",
        0
    );
} elseif ($hasExpensesTable) {
    $totalLoss = (float)getScalar(
        $conn,
        "SELECT COALESCE(SUM(amount),0) AS total FROM expenses",
        0
    );
}

if ($hasProducts) {
    $stockWhere = $hasArchivedFlag ? "WHERE is_archived = 0" : "";
    $lowStockWhere = $hasArchivedFlag ? "WHERE quantity <= 5 AND is_archived = 0" : "WHERE quantity <= 5";

    $totalStock = (int)getScalar(
        $conn,
        "SELECT COALESCE(SUM(quantity),0) AS total FROM products $stockWhere",
        0
    );

    $lowStock = (int)getScalar(
        $conn,
        "SELECT COUNT(*) AS total FROM products $lowStockWhere",
        0
    );
}

if ($hasSales && $hasSaleItems && $hasProducts) {
    $recentSql = "
        SELECT s.id, s.total_amount, s.created_at,
               GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') AS items
        FROM sales s
        LEFT JOIN sale_items si ON si.sale_id = s.id
        LEFT JOIN products p ON p.id = si.product_id
        GROUP BY s.id, s.total_amount, s.created_at
        ORDER BY s.id DESC
        LIMIT 5
    ";
    $res = $conn->query($recentSql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $recentSales[] = $row;
        }
    }
}

if ($hasSales) {
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chartLabels[] = date('M d', strtotime($date));
        $chartData[] = (float)getScalar(
            $conn,
            "SELECT COALESCE(SUM(total_amount),0) AS total FROM sales WHERE DATE(created_at)='$date'",
            0
        );
    }
} else {
    for ($i = 6; $i >= 0; $i--) {
        $chartLabels[] = date('M d', strtotime("-$i days"));
        $chartData[] = 0;
    }
}

$staffName = $_SESSION['user_name'] ?? $_SESSION['user'] ?? 'Staff';
?>

<title>Dashboard — Bohol Bicycle Inventory</title>
<link rel="stylesheet" href="staff_dashboard.css">

<div class="sd-wrapper">

    <div class="sd-header">
        <div class="sd-header-left">
            <div class="sd-eyebrow">Casher Portal · Overview</div>
            <h1 class="sd-title">Welcome, <?= htmlspecialchars($staffName) ?> 👋</h1>
            <small>Here's what's happening today</small>
        </div>
        <div class="sd-header-right">
            <div class="sd-date-label">Today</div>
            <div class="sd-date-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <div class="sd-kpi-grid">

        <div class="sd-kpi-card kpi-s1">
            <div class="kpi-top">
                <span class="kpi-label">Today's Sales</span>
                <div class="kpi-icon">₱</div>
            </div>
            <div class="kpi-value">₱<?= number_format($todaySales, 2) ?></div>
            <div class="kpi-sub">revenue today</div>
        </div>

        <div class="sd-kpi-card kpi-s2">
            <div class="kpi-top">
                <span class="kpi-label">Today's Orders</span>
                <div class="kpi-icon">🧾</div>
            </div>
            <div class="kpi-value"><?= $todayOrders ?></div>
            <div class="kpi-sub">transactions today</div>
        </div>

        <div class="sd-kpi-card kpi-s3">
            <div class="kpi-top">
                <span class="kpi-label">Total Stock</span>
                <div class="kpi-icon">📦</div>
            </div>
            <div class="kpi-value"><?= $totalStock ?></div>
            <div class="kpi-sub">units available</div>
        </div>

        <div class="sd-kpi-card <?= $lowStock > 0 ? 'kpi-s4-warn' : 'kpi-s4' ?>">
            <div class="kpi-top">
                <span class="kpi-label">Low Stock</span>
                <div class="kpi-icon">⚠</div>
            </div>
            <div class="kpi-value"><?= $lowStock ?></div>
            <div class="kpi-sub"><?= $lowStock > 0 ? 'items need restocking' : 'all items stocked' ?></div>
        </div>

        <div class="sd-kpi-card kpi-s5">
            <div class="kpi-top">
                <span class="kpi-label">Total Income</span>
                <div class="kpi-icon">💰</div>
            </div>
            <div class="kpi-value">₱<?= number_format($totalIncome, 2) ?></div>
            <div class="kpi-sub">all-time sales income</div>
        </div>

        <div class="sd-kpi-card kpi-s6">
            <div class="kpi-top">
                <span class="kpi-label">Total Loss</span>
                <div class="kpi-icon">📉</div>
            </div>
            <div class="kpi-value">₱<?= number_format($totalLoss, 2) ?></div>
            <div class="kpi-sub">losses / expenses total</div>
        </div>

    </div>

    <div class="sd-bottom-grid">

        <div class="sd-card">
            <div class="sd-card-header">
                <div class="sd-card-title">Recent Sales</div>
                <a href="staff_sales_report.php" class="sd-view-all">View All →</a>
            </div>

            <div class="sd-table-wrap">
                <table class="sd-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Items</th>
                            <th>Date</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($recentSales)): ?>
                        <?php foreach ($recentSales as $s): ?>
                            <tr>
                                <td class="td-code">SO-<?= str_pad((string)$s['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td class="td-items"><?= htmlspecialchars(mb_strimwidth($s['items'] ?? 'No items', 0, 40, '…')) ?></td>
                                <td class="td-date"><?= date('M d, H:i', strtotime($s['created_at'])) ?></td>
                                <td class="text-right td-price">₱<?= number_format((float)$s['total_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="td-empty">
                                <span class="empty-icon">🧾</span>
                                <span class="empty-text">No sales yet today.</span>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sd-card">
            <div class="sd-card-header">
                <div class="sd-card-title">Sales — Last 7 Days</div>
                <span class="sd-chart-badge">
                    <span class="badge-dot"></span> Revenue
                </span>
            </div>
            <div class="sd-chart-wrap">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

    </div>

</div>

<script>
window.CHART_LABELS = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
window.CHART_DATA   = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="staff_dashboard.js"></script>
</body>
</html>