<?php
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$error = '';
$success = '';
$cash_error_only = '';

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

$hasProductsTable   = tableExists($conn, 'products');
$hasSalesTable      = tableExists($conn, 'sales');
$hasSaleItemsTable  = tableExists($conn, 'sale_items');
$hasArchivedFlag    = $hasProductsTable && columnExists($conn, 'products', 'is_archived');
$hasCategory        = $hasProductsTable && columnExists($conn, 'products', 'category');
$hasSize            = $hasProductsTable && columnExists($conn, 'products', 'size');
$hasExpiry          = $hasProductsTable && columnExists($conn, 'products', 'expiry_date');

/* ===============================
   BARCODE SCAN
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_barcode'])) {
    $barcode = trim($_POST['barcode'] ?? '');

    if (!$hasProductsTable) {
        $error = 'Products table is missing.';
    } elseif ($barcode === '') {
        $error = 'Please enter or scan a barcode.';
    } else {
        $sql = $hasArchivedFlag
            ? "SELECT * FROM products WHERE barcode = ? AND is_archived = 0 LIMIT 1"
            : "SELECT * FROM products WHERE barcode = ? LIMIT 1";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = 'Failed to prepare barcode scan: ' . $conn->error;
        } else {
            $stmt->bind_param("s", $barcode);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if (!$product) {
                $error = 'Product not found.';
            } else {
                $id = (int)$product['id'];
                $stockQty = (int)($product['quantity'] ?? 0);
                $currentCartQty = (int)($_SESSION['cart'][$id]['qty'] ?? 0);

                if ($stockQty <= 0) {
                    $error = 'Product out of stock.';
                } elseif (!empty($product['expiry_date']) && strtotime($product['expiry_date']) < strtotime(date('Y-m-d'))) {
                    $error = 'This item is expired and cannot be added.';
                } elseif ($currentCartQty >= $stockQty) {
                    $error = 'Cannot add more. Cart quantity already matches available stock.';
                } else {
                    if (isset($_SESSION['cart'][$id])) {
                        $_SESSION['cart'][$id]['qty']++;
                    } else {
                        $_SESSION['cart'][$id] = [
                            'id'          => $id,
                            'barcode'     => $product['barcode'],
                            'name'        => $product['name'],
                            'category'    => $hasCategory ? ($product['category'] ?? '') : '',
                            'size'        => $hasSize ? ($product['size'] ?? '') : '',
                            'expiry_date' => $hasExpiry ? ($product['expiry_date'] ?? '') : '',
                            'price'       => (float)$product['price'],
                            'qty'         => 1
                        ];
                    }
                }
            }
        }
    }
}

/* ===============================
   DELETE SELECTED ITEMS
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
    $selectedItems = $_POST['selected_items'] ?? [];

    if (empty($_SESSION['cart'])) {
        $error = 'Cart is empty.';
    } elseif (!is_array($selectedItems) || count($selectedItems) === 0) {
        $error = 'Please check at least one item to delete.';
    } else {
        $deletedCount = 0;

        foreach ($selectedItems as $itemId) {
            $id = (int)$itemId;
            if (isset($_SESSION['cart'][$id])) {
                unset($_SESSION['cart'][$id]);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $success = $deletedCount === 1
                ? '1 selected item deleted.'
                : $deletedCount . ' selected items deleted.';
        } else {
            $error = 'No valid selected items found.';
        }
    }
}

/* ===============================
   CART TOTALS
================================ */
$grand_total = 0.0;
$total_items = 0;

foreach ($_SESSION['cart'] as $item) {
    $grand_total += ((float)$item['price'] * (int)$item['qty']);
    $total_items += (int)$item['qty'];
}

$cart_rows = count($_SESSION['cart']);

/* ===============================
   CHECKOUT
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $cash = (float)($_POST['cash'] ?? 0);

    if (!$hasSalesTable || !$hasSaleItemsTable || !$hasProductsTable) {
        $error = 'Sales module tables are missing.';
    } elseif (empty($_SESSION['cart'])) {
        $error = 'Cart is empty.';
    } else {
        if ($cash < $grand_total) {
        $cash_error_only = 'Not enough cash.';
    // ❌ STOP EXECUTION HERE (no reset, no transaction)
    } else {
            $change = $cash - $grand_total;

            try {
                $conn->begin_transaction();

                foreach ($_SESSION['cart'] as $id => $item) {
                    $qty = (int)$item['qty'];

                    $stmt = $conn->prepare("SELECT quantity, name, expiry_date FROM products WHERE id = ? LIMIT 1 FOR UPDATE");
                    if (!$stmt) {
                        throw new Exception('Failed to lock product row.');
                    }

                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $dbProduct = $res ? $res->fetch_assoc() : null;
                    $stmt->close();

                    if (!$dbProduct) {
                        throw new Exception('A product in the cart no longer exists.');
                    }

                    if (!empty($dbProduct['expiry_date']) && strtotime($dbProduct['expiry_date']) < strtotime(date('Y-m-d'))) {
                        throw new Exception('Expired item found in cart: "' . $dbProduct['name'] . '".');
                    }

                    if ((int)$dbProduct['quantity'] < $qty) {
                        throw new Exception('Not enough stock for "' . $dbProduct['name'] . '".');
                    }
                }

                $stmt = $conn->prepare("
                    INSERT INTO sales (total_amount, payment, change_amount, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                if (!$stmt) {
                    throw new Exception('Failed to prepare sales insert.');
                }

                $stmt->bind_param("ddd", $grand_total, $cash, $change);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to save sale.');
                }

                $saleId = (int)$stmt->insert_id;
                $stmt->close();

                foreach ($_SESSION['cart'] as $id => $item) {
                    $qty = (int)$item['qty'];
                    $price = (float)$item['price'];
                    $subtotal = $qty * $price;

                    $itemStmt = $conn->prepare("
                        INSERT INTO sale_items (sale_id, product_id, quantity, price, subtotal)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    if (!$itemStmt) {
                        throw new Exception('Failed to prepare sale item insert.');
                    }

                    $itemStmt->bind_param("iiidd", $saleId, $id, $qty, $price, $subtotal);
                    if (!$itemStmt->execute()) {
                        throw new Exception('Failed to save sale item.');
                    }
                    $itemStmt->close();

                    $stockStmt = $conn->prepare("
                        UPDATE products
                        SET quantity = quantity - ?, updated_at = NOW()
                        WHERE id = ? AND quantity >= ?
                    ");
                    if (!$stockStmt) {
                        throw new Exception('Failed to prepare stock update.');
                    }

                    $stockStmt->bind_param("iii", $qty, $id, $qty);
                    if (!$stockStmt->execute() || $stockStmt->affected_rows <= 0) {
                        throw new Exception('Failed to deduct stock.');
                    }
                    $stockStmt->close();
                }

                $conn->commit();

                $_SESSION['cart'] = [];
                $success = 'Transaction successful! Change: ₱' . number_format($change, 2);
                $grand_total = 0.0;
                $total_items = 0;
                $cart_rows = 0;
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

include 'header.php';
?>

<title>POS — Bohol Bicycle Inventory</title>
<link rel="stylesheet" href="pos.css">

<?php
$js_success = htmlspecialchars($success, ENT_QUOTES);
$js_error   = htmlspecialchars($error, ENT_QUOTES);
$js_cash_error = htmlspecialchars($cash_error_only, ENT_QUOTES);
?>



<div id="phpMsgs"
     data-success="<?= $js_success ?>"
     data-error="<?= $js_error ?>"
     data-cash-error="<?= $js_cash_error ?>"
     style="display:none;"></div>

<div class="page-shell">
    <div class="hero-card">
        <div class="hero-left">
            <div class="hero-eyebrow">POINT OF SALE · CASHIER · CHECKOUT</div>
            <h1>POS</h1>
            <p>Scan products, manage cart, and complete transactions.</p>
        </div>
        <div class="hero-right">
            <span>TODAY</span>
            <strong><?= date("M d, Y") ?></strong>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="sys-alert sys-alert-danger">
            <span><?= htmlspecialchars($error) ?></span>
            <button type="button" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="sys-alert sys-alert-success">
            <span><?= htmlspecialchars($success) ?></span>
            <button type="button" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endif; ?>

    <div class="main-card">
        <div class="card-toolbar">
            <div>
                <h3>Point of Sale</h3>
                <small>Showing <b><?= $cart_rows ?></b> cart item(s)</small>
            </div>

            <form method="POST" id="scanForm" class="toolbar-actions">
                <input type="hidden" name="scan_barcode" value="1">

                <input type="text"
                       name="barcode"
                       id="barcodeInput"
                       class="sys-input barcode-input"
                       placeholder="Scan barcode..."
                       autocomplete="off"
                       autofocus
                       required>

                <button type="submit" class="btn-primary-solid">+ Add</button>
                <button type="button" class="btn-outline-soft" id="btnPrintReceipt">Print</button>
            </form>
        </div>

        <div class="pos-layout">
            <div class="table-panel">
                <div class="table-wrap">
                    <form method="POST" id="deleteItemsForm">
                        <input type="hidden" name="delete_selected" value="1">

                        <table class="sys-table">
                            <thead>
                                <tr>
                                    <th style="width:70px;">Select</th>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Size</th>
                                    <th>Expire Date</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($_SESSION['cart'])): ?>
                                    <?php foreach ($_SESSION['cart'] as $item): ?>
                                        <?php $row_total = ((float)$item['price'] * (int)$item['qty']); ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox"
                                                       name="selected_items[]"
                                                       value="<?= (int)$item['id'] ?>"
                                                       class="row-check">
                                            </td>
                                            <td><?= htmlspecialchars($item['name']) ?></td>
                                            <td>
                                                <?php if (!empty($item['category'])): ?>
                                                    <span class="tag-blue"><?= htmlspecialchars($item['category']) ?></span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($item['size'] ?? '-') ?></td>
                                            <td>
                                                <?= !empty($item['expiry_date']) ? htmlspecialchars(date('M d, Y', strtotime($item['expiry_date']))) : '-' ?>
                                            </td>
                                            <td>₱<?= number_format((float)$item['price'], 2) ?></td>
                                            <td><span class="qty-pill"><?= (int)$item['qty'] ?></span></td>
                                            <td>₱<?= number_format($row_total, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="empty-state-cell">
                                            <div class="empty-state">
                                                <div class="empty-icon">🛒</div>
                                                <div class="empty-title">No cart items yet</div>
                                                <div class="empty-sub">Scan a barcode to add products.</div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>

            <div class="side-panel">
                <div class="summary-box">
                    <span>GRAND TOTAL</span>
                    <strong id="grandTotalDisplay">₱<?= number_format($grand_total, 2) ?></strong>
                </div>

                <form method="POST" id="checkoutForm" class="checkout-form">
                    <label class="field-label">ENTER CASH</label>
                    <input type="number"
                           name="cash"
                           id="cashInput"
                           class="sys-input"
                           placeholder="0.00"
                           min="0"
                           step="0.01"
                           <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>

                    <div id="cashNotice" class="cash-notice" style="display:<?= $cash_error_only !== '' ? 'block' : 'none' ?>;">
                        <?= $cash_error_only !== '' ? htmlspecialchars($cash_error_only) : '' ?>
                    </div>

                    <div class="change-box">
                        <span>CHANGE</span>
                        <strong id="changeVal">₱0.00</strong>
                    </div>

                    <button type="submit"
                            name="checkout"
                            class="btn-success-solid full-btn"
                            id="btnCheckout"
                            <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                        Checkout
                    </button>
                </form>

                <button type="button"
                        class="btn-danger-soft full-btn"
                        id="btnDeleteSelected"
                        <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                    Delete Selected
                </button>

                <div class="mini-stats">
                    <div class="mini-stat">
                        <span>Items</span>
                        <strong id="infoItems"><?= $total_items ?></strong>
                    </div>
                    <div class="mini-stat">
                        <span>Status</span>
                        <strong id="infoStatus" class="<?= empty($_SESSION['cart']) ? 'status-waiting' : 'status-ready' ?>">
                            <?= empty($_SESSION['cart']) ? 'Waiting for scan' : 'Ready to checkout' ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ✅ DEFINE FIRST (VERY IMPORTANT)
    window.POS_CART_ITEMS = <?= json_encode(array_values($_SESSION['cart'])) ?>;
    window.POS_GRAND_TOTAL = <?= json_encode((float)$grand_total) ?>;

    const total = window.POS_GRAND_TOTAL;
    const cartItems = window.POS_CART_ITEMS;

    const cashInput = document.getElementById('cashInput');
    const btnPrintReceipt = document.getElementById('btnPrintReceipt');
    const changeVal = document.getElementById('changeVal');
    const cashNotice = document.getElementById('cashNotice');
    const checkoutForm = document.getElementById('checkoutForm');
    const btnCheckout = document.getElementById('btnCheckout');

    const btnDeleteSelected = document.getElementById('btnDeleteSelected');
    const deleteForm = document.getElementById('deleteItemsForm');

    function peso(val) {
        return '₱' + Number(val).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // ✅ DELETE FIX
    if (btnDeleteSelected && deleteForm) {
        btnDeleteSelected.addEventListener('click', function () {
            const checked = document.querySelectorAll('.row-check:checked');

            if (checked.length === 0) {
                alert('Please select at least one item to delete.');
                return;
            }

            deleteForm.submit();
        });
    }

    // ✅ PRINT FIX (CLEAN VERSION)
    function printReceipt() {

        if (!cartItems.length) {
            alert('Cart is empty.');
            return;
        }

        if (!cashInput.value) {
            alert('Enter cash first.');
            cashInput.focus();
            return;
        }

        const cash = Number(cashInput.value || 0);

        if (cash < total) {
            alert('Not enough cash.');
            cashInput.focus();
            return;
        }

        const change = cash - total;

        let rows = '';
        cartItems.forEach(item => {
            const totalRow = item.qty * item.price;
            rows += `
                <tr>
                    <td>${item.name} (x${item.qty})</td>
                    <td style="text-align:right;">${peso(totalRow)}</td>
                </tr>
            `;
        });

        const receipt = `
        <html>
        <head>
            <title>Receipt</title>
        </head>
        <body style="font-family: monospace;">
            <h2>Bohol Bicycle Inventory</h2>
            <hr>
            <table width="100%">
                ${rows}
            </table>
            <hr>
            <p>Total: ${peso(total)}</p>
            <p>Cash: ${peso(cash)}</p>
            <p>Change: ${peso(change)}</p>
            <hr>
            <p>Thank you!</p>
            <script>
                window.print();
                window.onafterprint = () => window.close();
            <\/script>
        </body>
        </html>
        `;

        const w = window.open('', '', 'width=400,height=600');

        if (!w) {
            alert('Popup blocked. Please allow popups.');
            return;
        }

        w.document.open();
        w.document.write(receipt);
        w.document.close();
    }

    // ✅ ATTACH PRINT
    if (btnPrintReceipt) {
        btnPrintReceipt.addEventListener('click', printReceipt);
    }

    // ✅ CASH VALIDATION (KEEP YOUR LOGIC)
    function updateCashState() {
        const cash = parseFloat(cashInput.value || 0);

        if (!cashInput.value) {
            changeVal.textContent = peso(0);
            cashNotice.style.display = 'none';
            btnCheckout.disabled = true;
            return;
        }

        if (cash < total) {
            changeVal.textContent = peso(0);

            cashNotice.style.display = 'block';
            cashNotice.textContent = 'Not enough cash.';
            cashNotice.style.background = '#dc2626';
            cashNotice.style.color = '#fff';
            cashNotice.style.padding = '8px 12px';
            cashNotice.style.borderRadius = '6px';
            cashNotice.style.fontWeight = '600';

            btnCheckout.disabled = true;

        } else {
            const change = cash - total;
            changeVal.textContent = peso(change);

            cashNotice.style.display = 'none';
            btnCheckout.disabled = false;
        }
    }

    if (cashInput) {
        cashInput.addEventListener('input', updateCashState);
    }

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            const cash = parseFloat(cashInput.value || 0);

            if (cash < total) {
                e.preventDefault();
                updateCashState();
                cashInput.focus();
            }
        });
    }

});
</script>

</body>
</html>