<?php
/* ============================================================
   REPORT_SUPPLIERS.PHP — Bohol Bicycle Inventory
   Auto-detects column names to avoid undefined key errors
   ============================================================ */
include 'header.php';
include 'db.php';

/* ── AUTO-DETECT COLUMN NAMES ── */
function pickSupCol(mysqli $conn, array $candidates, string $fallback = ''): string {
    $res = $conn->query("SHOW COLUMNS FROM suppliers");
    $cols = [];
    if ($res) while ($r = $res->fetch_assoc()) $cols[] = strtolower($r['Field']);
    foreach ($candidates as $c) {
        if (in_array(strtolower($c), $cols, true)) return $c;
    }
    return $fallback;
}

$colName    = pickSupCol($conn, ['name','supplier_name','company','company_name']);
$colContact = pickSupCol($conn, ['contact_person','contact','person','contact_name']);
$colPhone   = pickSupCol($conn, ['phone','mobile','telephone','tel']);
$colEmail   = pickSupCol($conn, ['email','email_address','mail']);

$res = $conn->query("SELECT * FROM suppliers ORDER BY id DESC");
$suppliers = [];
while ($row = $res->fetch_assoc()) {
    $suppliers[] = [
        'id'      => (int)$row['id'],
        'name'    => $colName    ? ($row[$colName]    ?? '') : '',
        'contact' => $colContact ? ($row[$colContact] ?? '') : '',
        'phone'   => $colPhone   ? ($row[$colPhone]   ?? '') : '',
        'email'   => $colEmail   ? ($row[$colEmail]   ?? '') : '',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supplier Report — Bohol Bicycle Inventory</title>
    <link rel="stylesheet" href="report_suppliers.css">
</head>
<body>

<div class="rpt-wrapper">

    <!-- PAGE HEADER -->
    <div class="rpt-header">
        <div class="rpt-header-left">
            <div class="rpt-eyebrow">Suppliers · Contact Details</div>
            <h1 class="rpt-title">Supplier Report</h1>
            <small>Suppliers • Contact Details</small>
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
            <div class="rpt-card-title">
                Supplier Directory
                <span class="rpt-count"><?= count($suppliers) ?> supplier(s)</span>
            </div>
            <button class="btn-print" onclick="window.print()">🖨 Print</button>
        </div>

        <div class="rpt-divider no-print"></div>

        <!-- TABLE -->
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($suppliers) === 0): ?>
                    <tr>
                        <td colspan="5" class="td-empty">
                            <span class="empty-icon">🏭</span>
                            <span class="empty-text">No suppliers found.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td class="td-id"><?= $s['id'] ?></td>
                            <td class="td-name"><?= htmlspecialchars($s['name']) ?></td>
                            <td class="td-muted"><?= htmlspecialchars($s['contact']) ?></td>
                            <td class="td-mono"><?= htmlspecialchars($s['phone']) ?></td>
                            <td class="td-email"><?= htmlspecialchars($s['email']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="report_suppliers.js"></script>
</body>
</html>