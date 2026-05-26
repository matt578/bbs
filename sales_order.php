<?php
/* ============================================================
   SALES_ORDER.PHP — Bohol Bicycle Inventory
   PHP logic only — design in sales_order.css, search in sales_order.js
   ============================================================ */
require 'db.php';
include 'header.php';

$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to'] ?? '');

$where = [];
$params = [];
$types = '';

if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[] = "DATE(s.created_at) >= ?";
    $params[] = $from;
    $types .= 's';
}

if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[] = "DATE(s.created_at) <= ?";
    $params[] = $to;
    $types .= 's';
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$sql = "
SELECT
  s.id            AS sale_id,
  s.total_amount,
  s.payment,
  s.change_amount,
  s.created_at,
  si.product_id,
  si.quantity     AS item_qty,
  p.name          AS product_name,
  p.category      AS product_category,
  p.expiry_date   AS product_expiry_date
FROM sales s
LEFT JOIN sale_items si ON si.sale_id = s.id
LEFT JOIN products   p  ON p.id = si.product_id
$whereSql
ORDER BY s.id DESC, si.id ASC
";

$sales = [];

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $sid = (int)$row['sale_id'];

            if (!isset($sales[$sid])) {
                $sales[$sid] = [
                    'sale_id'       => $sid,
                    'created_at'    => $row['created_at'],
                    'total_amount'  => (float)$row['total_amount'],
                    'payment'       => (float)$row['payment'],
                    'change_amount' => (float)$row['change_amount'],
                    'items'         => [],
                    'categories'    => [],
                    'total_qty'     => 0,
                    'expiry_dates'  => [],
                ];
            }

            if (!empty($row['product_name'])) {
                $qty = (int)$row['item_qty'];

                $sales[$sid]['items'][] = [
                    'name'        => $row['product_name'],
                    'qty'         => $qty,
                    'category'    => $row['product_category'] ?? '',
                    'expiry_date' => $row['product_expiry_date'] ?? ''
                ];

                $sales[$sid]['total_qty'] += $qty;

                if (!empty($row['product_category'])) {
                    $sales[$sid]['categories'][$row['product_category']] = true;
                }

                if (!empty($row['product_expiry_date'])) {
                    $sales[$sid]['expiry_dates'][$row['product_expiry_date']] = true;
                }
            }
        }
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Orders — Bohol Bicycle Inventory</title>
    <link rel="stylesheet" href="sales_order.css">
</head>
<body>

<div class="so-wrapper">

    <div class="so-header">
        <div class="so-header-left">
            <div class="so-eyebrow">Orders · Items · Invoice</div>
            <h1 class="so-title">Sales Orders</h1>
            <small>Orders • Items • Invoice</small>
        </div>
        <div class="so-header-right">
            <div class="so-date-label">Today</div>
            <div class="so-date-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <div class="so-card">

        <div class="so-toolbar">
            <div class="so-toolbar-left">
                <div class="so-card-title">Product Orders</div>
                <div class="so-card-sub">
                    Showing <span id="visibleCount"><?= count($sales) ?></span> order(s)
                </div>
            </div>
            <div class="so-toolbar-right">
                <form method="GET" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <input type="date" name="from" class="so-search" value="<?= htmlspecialchars($from) ?>" style="width:170px;">
                    <input type="date" name="to" class="so-search" value="<?= htmlspecialchars($to) ?>" style="width:170px;">
                    <button type="submit" class="btn-invoice" style="text-decoration:none; border:none;">Filter</button>
                    <a href="sales_order.php" class="btn-invoice" style="text-decoration:none;">Reset</a>
                </form>
            </div>
        </div>

        <div class="so-divider"></div>

        <div class="so-table-wrap">
            <table class="so-table" id="salesTable">
                <thead>
                    <tr>
                        <th>Order Code</th>
                        <th>Date</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Expire Date</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-right">Total Price</th>
                        <th class="text-right">Invoice</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($sales) > 0): ?>
                    <?php foreach ($sales as $s): ?>
                        <?php
                            $orderCode    = 'SO-' . str_pad($s['sale_id'], 6, '0', STR_PAD_LEFT);
                            $categories   = array_keys($s['categories']);
                            $categoryText = count($categories) === 1 ? $categories[0] : (count($categories) > 1 ? 'Mixed' : '—');

                            $expiryDates = array_keys($s['expiry_dates']);
                            if (count($expiryDates) === 1) {
                                $expiryText = date('M d, Y', strtotime($expiryDates[0]));
                            } elseif (count($expiryDates) > 1) {
                                $expiryText = 'Mixed';
                            } else {
                                $expiryText = '—';
                            }
                        ?>
                        <tr>
                            <td class="td-code"><?= htmlspecialchars($orderCode) ?></td>

                            <td class="td-date">
                                <?= !empty($s['created_at']) ? htmlspecialchars($s['created_at']) : '—' ?>
                            </td>

                            <td>
                                <?php if (count($s['items']) > 0): ?>
                                    <ul class="items-list">
                                        <?php foreach ($s['items'] as $it): ?>
                                            <li>
                                                <?= htmlspecialchars($it['name']) ?>
                                                <span class="item-qty">(<?= (int)$it['qty'] ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="td-empty-inline">No items</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="cat-pill"><?= htmlspecialchars($categoryText) ?></span>
                            </td>

                            <td>
                                <span class="cat-pill"><?= htmlspecialchars($expiryText) ?></span>
                            </td>

                            <td class="text-center">
                                <span class="qty-badge"><?= (int)$s['total_qty'] ?></span>
                            </td>

                            <td class="text-right td-price">
                                ₱<?= number_format((float)$s['total_amount'], 2) ?>
                            </td>

                            <td class="text-right">
                                <a href="invoice.php?sale_id=<?= (int)$s['sale_id'] ?>" class="btn-invoice">
                                    View Invoice
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="td-empty">
                            <span class="empty-icon">🧾</span>
                            <span class="empty-text">No sales orders found.</span>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="sales_order.js"></script>
</body>
</html>