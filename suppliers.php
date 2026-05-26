<?php
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

function validFacebookUrl(string $url): bool {
    if ($url === '') return true;
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;

    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    return str_contains($host, 'facebook.com') || str_contains($host, 'fb.com');
}

if (!tableExists($conn, 'suppliers')) {
    die('Suppliers table is missing.');
}

$hasFacebookLink = columnExists($conn, 'suppliers', 'facebook_link');

$addError = '';
$editError = '';

/* ===============================
   HANDLE ADD
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name    = trim($_POST['supplier_name'] ?? '');
    $contact = trim($_POST['contact_person'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $fbLink  = trim($_POST['facebook_link'] ?? '');

    if ($name === '') {
        $addError = 'Supplier name is required.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $addError = 'Invalid email address.';
    } elseif (!validFacebookUrl($fbLink)) {
        $addError = 'Facebook link must be a valid Facebook page URL.';
    } else {
        if ($hasFacebookLink) {
            $stmt = $conn->prepare("
                INSERT INTO suppliers (name, contact_person, phone, email, facebook_link, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            if (!$stmt) {
                $addError = 'Failed to prepare add supplier: ' . $conn->error;
            } else {
                $stmt->bind_param("sssss", $name, $contact, $phone, $email, $fbLink);
                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: suppliers.php?added=1');
                    exit();
                } else {
                    $addError = 'Failed to add supplier: ' . $stmt->error;
                    $stmt->close();
                }
            }
        } else {
            $stmt = $conn->prepare("
                INSERT INTO suppliers (name, contact_person, phone, email, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            if (!$stmt) {
                $addError = 'Failed to prepare add supplier: ' . $conn->error;
            } else {
                $stmt->bind_param("ssss", $name, $contact, $phone, $email);
                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: suppliers.php?added=1');
                    exit();
                } else {
                    $addError = 'Failed to add supplier: ' . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

/* ===============================
   HANDLE EDIT
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id      = (int) ($_POST['id'] ?? 0);
    $name    = trim($_POST['supplier_name'] ?? '');
    $contact = trim($_POST['contact_person'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $fbLink  = trim($_POST['facebook_link'] ?? '');

    if ($id <= 0) {
        $editError = 'Invalid supplier ID.';
    } elseif ($name === '') {
        $editError = 'Supplier name is required.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $editError = 'Invalid email address.';
    } elseif (!validFacebookUrl($fbLink)) {
        $editError = 'Facebook link must be a valid Facebook page URL.';
    } else {
        if ($hasFacebookLink) {
            $stmt = $conn->prepare("
                UPDATE suppliers
                SET name = ?, contact_person = ?, phone = ?, email = ?, facebook_link = ?, updated_at = NOW()
                WHERE id = ?
            ");
            if (!$stmt) {
                $editError = 'Failed to prepare update supplier: ' . $conn->error;
            } else {
                $stmt->bind_param("sssssi", $name, $contact, $phone, $email, $fbLink, $id);
                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: suppliers.php?updated=1');
                    exit();
                } else {
                    $editError = 'Failed to update supplier: ' . $stmt->error;
                    $stmt->close();
                }
            }
        } else {
            $stmt = $conn->prepare("
                UPDATE suppliers
                SET name = ?, contact_person = ?, phone = ?, email = ?, updated_at = NOW()
                WHERE id = ?
            ");
            if (!$stmt) {
                $editError = 'Failed to prepare update supplier: ' . $conn->error;
            } else {
                $stmt->bind_param("ssssi", $name, $contact, $phone, $email, $id);
                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: suppliers.php?updated=1');
                    exit();
                } else {
                    $editError = 'Failed to update supplier: ' . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

/* ===============================
   HANDLE DELETE
================================ */
if (isset($_GET['delete_id'])) {
    $id = (int) $_GET['delete_id'];

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header('Location: suppliers.php?deleted=1');
    exit();
}

/* ===============================
   FETCH SUPPLIERS
================================ */
$suppliers = [];
$result = $conn->query("SELECT * FROM suppliers ORDER BY id DESC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = [
            'id'       => (int) ($row['id'] ?? 0),
            'name'     => $row['name'] ?? '',
            'contact'  => $row['contact_person'] ?? '',
            'phone'    => $row['phone'] ?? '',
            'email'    => $row['email'] ?? '',
            'facebook' => $hasFacebookLink ? ($row['facebook_link'] ?? '') : '',
        ];
    }
}

include 'header.php';
?>

<title>Suppliers — Bohol Bicycle Inventory</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="suppliers.css">

<div class="sup-wrapper">

    <div class="sup-header">
        <div class="sup-header-left">
            <div class="sup-eyebrow">Supplier List · Contacts · Details</div>
            <h1 class="sup-title">Suppliers</h1>
            <small>Supplier List • Contacts • Details</small>
        </div>
        <div class="sup-header-right">
            <div class="sup-date-label">Today</div>
            <div class="sup-date-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <?php if (isset($_GET['added'])): ?>
        <div class="sup-alert sup-alert-success" id="flashMsg">
            <span>✓</span> Supplier added successfully.
            <button class="alert-close" type="button" onclick="this.parentElement.remove()">✕</button>
        </div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="sup-alert sup-alert-success" id="flashMsg">
            <span>✓</span> Supplier updated successfully.
            <button class="alert-close" type="button" onclick="this.parentElement.remove()">✕</button>
        </div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="sup-alert sup-alert-error" id="flashMsg">
            <span>✕</span> Supplier deleted.
            <button class="alert-close" type="button" onclick="this.parentElement.remove()">✕</button>
        </div>
    <?php endif; ?>

    <?php if ($addError !== ''): ?>
        <div class="sup-alert sup-alert-error">
            <span>✕</span> <?= htmlspecialchars($addError) ?>
        </div>
    <?php endif; ?>

    <?php if ($editError !== ''): ?>
        <div class="sup-alert sup-alert-error">
            <span>✕</span> <?= htmlspecialchars($editError) ?>
        </div>
    <?php endif; ?>

    <div class="sup-card">

        <div class="sup-toolbar">
            <div class="sup-toolbar-left">
                <div class="sup-card-title">Supplier List</div>
                <div class="sup-card-sub">
                    Showing <span id="visibleCount"><?= count($suppliers) ?></span> supplier(s)
                </div>
            </div>
            <div class="sup-toolbar-right">
                <input type="text" class="sup-search" id="searchInput" placeholder="Search supplier...">
                <button class="btn-add-supplier" id="btnOpenAdd" type="button">+ Add Supplier</button>
            </div>
        </div>

        <div class="sup-divider"></div>

        <div class="sup-table-wrap">
            <table class="sup-table" id="supplierTable">
                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th class="text-right" style="width:180px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($suppliers)): ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td class="td-id"><?= $s['id'] ?></td>
                            <td class="td-name"><?= htmlspecialchars($s['name']) ?></td>
                            <td class="td-muted"><?= htmlspecialchars($s['contact']) ?></td>
                            <td class="td-mono"><?= htmlspecialchars($s['phone']) ?></td>
                            <td class="td-email"><?= htmlspecialchars($s['email']) ?></td>
                            <td class="text-right">
                                <div class="action-btns">
                                    <?php if (!empty($s['facebook'])): ?>
                                        <a href="<?= htmlspecialchars($s['facebook']) ?>"
                                           class="icon-btn"
                                           title="Open Facebook Page"
                                           target="_blank"
                                           rel="noopener noreferrer">
                                            <i class="bi bi-facebook"></i>
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="icon-btn" title="No Facebook Link" disabled>
                                            <i class="bi bi-facebook"></i>
                                        </button>
                                    <?php endif; ?>

                                    <button class="icon-btn icon-edit btn-edit"
                                            type="button"
                                            data-id="<?= $s['id'] ?>"
                                            data-name="<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>"
                                            data-contact="<?= htmlspecialchars($s['contact'], ENT_QUOTES) ?>"
                                            data-phone="<?= htmlspecialchars($s['phone'], ENT_QUOTES) ?>"
                                            data-email="<?= htmlspecialchars($s['email'], ENT_QUOTES) ?>"
                                            data-facebook="<?= htmlspecialchars($s['facebook'], ENT_QUOTES) ?>"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <a href="suppliers.php?delete_id=<?= $s['id'] ?>"
                                       class="icon-btn icon-delete btn-delete"
                                       data-name="<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>"
                                       title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="td-empty">
                            <span class="empty-icon">🏭</span>
                            <span class="empty-text">No suppliers found. Add your first supplier.</span>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="sup-modal-backdrop" id="addModal">
    <div class="sup-modal">
        <div class="sup-modal-header">
            <div class="sup-modal-title">Add Supplier</div>
            <button class="sup-modal-close" id="btnCloseAdd" type="button">✕</button>
        </div>
        <form method="POST" action="suppliers.php" id="addForm">
            <input type="hidden" name="action" value="add">
            <div class="sup-modal-body">
                <div class="field-wrap">
                    <label class="field-label">Supplier Name <span class="required">*</span></label>
                    <input type="text" name="supplier_name" class="field-input" placeholder="e.g. Shimano-MTB" required>
                </div>
                <div class="field-wrap">
                    <label class="field-label">Contact Person</label>
                    <input type="text" name="contact_person" class="field-input" placeholder="Full name">
                </div>
                <div class="field-wrap">
                    <label class="field-label">Phone</label>
                    <input type="text" name="phone" class="field-input" placeholder="09XXXXXXXXX">
                </div>
                <div class="field-wrap">
                    <label class="field-label">Email</label>
                    <input type="email" name="email" class="field-input" placeholder="supplier@email.com">
                </div>
                <div class="field-wrap">
                    <label class="field-label">Facebook Page Link</label>
                    <input type="url" name="facebook_link" class="field-input" placeholder="https://facebook.com/...">
                </div>
            </div>
            <div class="sup-modal-footer">
                <button type="button" class="btn-modal-cancel" id="btnCloseAdd2">Cancel</button>
                <button type="submit" class="btn-modal-save">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<div class="sup-modal-backdrop" id="editModal">
    <div class="sup-modal">
        <div class="sup-modal-header">
            <div class="sup-modal-title">Edit Supplier</div>
            <button class="sup-modal-close" id="btnCloseEdit" type="button">✕</button>
        </div>
        <form method="POST" action="suppliers.php" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="sup-modal-body">
                <div class="field-wrap">
                    <label class="field-label">Supplier Name <span class="required">*</span></label>
                    <input type="text" name="supplier_name" id="editName" class="field-input" required>
                </div>
                <div class="field-wrap">
                    <label class="field-label">Contact Person</label>
                    <input type="text" name="contact_person" id="editContact" class="field-input">
                </div>
                <div class="field-wrap">
                    <label class="field-label">Phone</label>
                    <input type="text" name="phone" id="editPhone" class="field-input">
                </div>
                <div class="field-wrap">
                    <label class="field-label">Email</label>
                    <input type="email" name="email" id="editEmail" class="field-input">
                </div>
                <div class="field-wrap">
                    <label class="field-label">Facebook Page Link</label>
                    <input type="url" name="facebook_link" id="editFacebook" class="field-input">
                </div>
            </div>
            <div class="sup-modal-footer">
                <button type="button" class="btn-modal-cancel" id="btnCloseEdit2">Cancel</button>
                <button type="submit" class="btn-modal-update">Update Supplier</button>
            </div>
        </form>
    </div>
</div>

<div class="sup-modal-backdrop" id="deleteModal">
    <div class="sup-modal sup-modal-sm">
        <div class="sup-modal-icon">🗑</div>
        <div class="sup-modal-title">Delete Supplier?</div>
        <div class="sup-modal-msg">
            "<span id="deleteSupplierName"></span>" will be permanently removed.
        </div>
        <div class="sup-modal-footer" style="margin-top:1.5rem;">
            <button type="button" class="btn-modal-cancel" id="btnDeleteCancel">Cancel</button>
            <a href="#" class="btn-modal-delete" id="btnDeleteConfirm">Yes, Delete</a>
        </div>
    </div>
</div>

<script src="suppliers.js"></script>
</body>
</html>