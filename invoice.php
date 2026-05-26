<?php
require 'db.php';
include 'header.php';

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
if ($sale_id <= 0) {
  die("Invalid sale id.");
}

// sale header
$stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();

if (!$sale) {
  die("Sale not found.");
}

// items
$sql = "
SELECT
  si.quantity,
  si.price,
  si.subtotal,
  p.name,
  p.category
FROM sale_items si
LEFT JOIN products p ON p.id = si.product_id
WHERE si.sale_id = ?
ORDER BY si.id ASC
";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("i", $sale_id);
$stmt2->execute();
$items = $stmt2->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Invoice</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{ background:#f4f6fb; }
    .wrap{ padding:28px; }
    .cardx{ border:0; border-radius:18px; box-shadow:0 10px 30px rgba(16,24,40,.08); overflow:hidden; }
    .thead-dark th{ background:#111827 !important; color:#fff !important; border:0 !important; }
  </style>
</head>
<body>

<div class="wrap">
  <div class="card cardx">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h4 class="fw-bold mb-1">Invoice</h4>
          <div class="text-muted">Order: <span class="fw-semibold">SO-<?= str_pad((int)$sale_id, 6, "0", STR_PAD_LEFT) ?></span></div>
          <div class="text-muted">Date: <span class="fw-semibold"><?= htmlspecialchars($sale['created_at'] ?? '') ?></span></div>
        </div>

        <div class="text-end">
          <button class="btn btn-primary" onclick="window.print()">Print</button>
        </div>
      </div>

      <hr class="my-4">

      <div class="table-responsive">
        <table class="table align-middle">
          <thead class="thead-dark">
            <tr>
              <th>Item</th>
              <th>Category</th>
              <th class="text-center">Qty</th>
              <th class="text-end">Price</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php while($r = $items->fetch_assoc()): ?>
              <tr>
                <td class="fw-semibold"><?= htmlspecialchars($r['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['category'] ?? '—') ?></td>
                <td class="text-center"><?= (int)$r['quantity'] ?></td>
                <td class="text-end">₱<?= number_format((float)$r['price'], 2) ?></td>
                <td class="text-end fw-bold">₱<?= number_format((float)$r['subtotal'], 2) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <hr class="my-4">

      <div class="row g-3">
        <div class="col-md-6"></div>
        <div class="col-md-6">
          <div class="d-flex justify-content-between">
            <span class="text-muted">Total</span>
            <span class="fw-bold">₱<?= number_format((float)$sale['total_amount'], 2) ?></span>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Payment</span>
            <span class="fw-semibold">₱<?= number_format((float)$sale['payment'], 2) ?></span>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Change</span>
            <span class="fw-semibold">₱<?= number_format((float)$sale['change_amount'], 2) ?></span>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>