<?php
/* ============================================================
   REPORTS.PHP — Bohol Bicycle Inventory
   PHP logic only — design in reports.css, JS in reports.js
   ============================================================ */
include 'header.php';
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports — Bohol Bicycle Inventory</title>
    <link rel="stylesheet" href="reports.css">
</head>
<body>

<div class="rpt-wrapper">

    <!-- PAGE HEADER -->
    <div class="rpt-header">
        <div class="rpt-header-left">
            <div class="rpt-eyebrow">Sales · Inventory · Suppliers</div>
            <h1 class="rpt-title">Reports</h1>
            <small>Sales • Inventory • Suppliers</small>
        </div>
        <div class="rpt-header-right">
            <div class="rpt-date-label">Today</div>
            <div class="rpt-date-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <!-- REPORT CARDS GRID -->
    <div class="rpt-grid">

        <!-- SALES REPORT -->
        <a href="report_sales.php" class="rpt-card rpt-sales">
            <div class="rpt-card-icon">📊</div>
            <div class="rpt-card-body">
                <div class="rpt-card-title">Sales Report</div>
                <div class="rpt-card-desc">View sales transactions, totals, and filter by date.</div>
            </div>
            <div class="rpt-card-footer">
                <span class="rpt-btn rpt-btn-sales">View Sales Report →</span>
            </div>
        </a>

        <!-- INVENTORY REPORT -->
        <a href="report_inventory.php" class="rpt-card rpt-inventory">
            <div class="rpt-card-icon">📦</div>
            <div class="rpt-card-body">
                <div class="rpt-card-title">Inventory Report</div>
                <div class="rpt-card-desc">View current stock and highlight low inventory items.</div>
            </div>
            <div class="rpt-card-footer">
                <span class="rpt-btn rpt-btn-inventory">View Inventory Report →</span>
            </div>
        </a>

        <!-- SUPPLIER REPORT -->
        <a href="report_suppliers.php" class="rpt-card rpt-supplier">
            <div class="rpt-card-icon">🏭</div>
            <div class="rpt-card-body">
                <div class="rpt-card-title">Supplier Report</div>
                <div class="rpt-card-desc">View supplier records and contact details.</div>
            </div>
            <div class="rpt-card-footer">
                <span class="rpt-btn rpt-btn-supplier">View Supplier Report →</span>
            </div>
        </a>

    </div>

</div>

<script src="reports.js"></script>
</body>
</html>