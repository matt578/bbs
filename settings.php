<?php
ob_start();
session_start();
require 'db.php';

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

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$error = '';
$success = '';

if (!tableExists($conn, 'users')) {
    die('Users table is missing.');
}

if (!tableExists($conn, 'system_settings')) {
    $conn->query("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INT NOT NULL PRIMARY KEY,
            theme_mode ENUM('light','dark') NOT NULL DEFAULT 'light'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

$checkSettings = $conn->query("SELECT id FROM system_settings WHERE id = 1 LIMIT 1");
if ($checkSettings && $checkSettings->num_rows === 0) {
    $conn->query("INSERT INTO system_settings (id, theme_mode) VALUES (1, 'light')");
}

$stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ? LIMIT 1");
if (!$stmt) {
    die('Failed to prepare user query: ' . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$account = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$account) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$_SESSION['role'] = $account['role'] ?? 'admin';
$_SESSION['user'] = $account['name'] ?? '';
$_SESSION['user_name'] = $account['name'] ?? '';
$_SESSION['user_email'] = $account['email'] ?? '';

$theme_mode = 'light';
$q = $conn->query("SELECT theme_mode FROM system_settings WHERE id = 1 LIMIT 1");
if ($q && $q->num_rows > 0) {
    $theme_mode = $q->fetch_assoc()['theme_mode'] ?? 'light';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $error = 'Full name is required.';
        } elseif (mb_strlen($name) < 2) {
            $error = 'Full name must be at least 2 characters.';
        } else {
            if (columnExists($conn, 'users', 'updated_at')) {
                $stmt = $conn->prepare("UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
            }

            if (!$stmt) {
                $error = 'Failed to prepare profile update: ' . $conn->error;
            } else {
                $stmt->bind_param("si", $name, $userId);
                if ($stmt->execute()) {
                    $_SESSION['user'] = $name;
                    $_SESSION['user_name'] = $name;
                    $account['name'] = $name;
                    $success = 'Profile updated successfully.';
                } else {
                    $error = 'Failed to update profile: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    if ($action === 'password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        if (!$stmt) {
            $error = 'Failed to prepare password check: ' . $conn->error;
        } else {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if (!$row) {
                $error = 'User account not found.';
            } elseif ($current_password === '') {
                $error = 'Current password is required.';
            } elseif (!password_verify($current_password, $row['password'])) {
                $error = 'Current password is incorrect.';
            } elseif ($new_password === '') {
                $error = 'New password is required.';
            } elseif (strlen($new_password) < 6) {
                $error = 'New password must be at least 6 characters.';
            } elseif ($confirm_password === '') {
                $error = 'Please confirm your new password.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match.';
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);

                if (columnExists($conn, 'users', 'updated_at')) {
                    $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                } else {
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                }

                if (!$stmt) {
                    $error = 'Failed to prepare password update: ' . $conn->error;
                } else {
                    $stmt->bind_param("si", $hashed, $userId);
                    if ($stmt->execute()) {
                        $success = 'Password changed successfully.';
                    } else {
                        $error = 'Failed to change password: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }

    if ($action === 'theme') {
        $new_theme = ($_POST['theme_mode'] ?? 'light') === 'dark' ? 'dark' : 'light';

        $stmt = $conn->prepare("UPDATE system_settings SET theme_mode = ? WHERE id = 1");
        if (!$stmt) {
            $error = 'Failed to prepare theme update: ' . $conn->error;
        } else {
            $stmt->bind_param("s", $new_theme);
            if ($stmt->execute()) {
                $theme_mode = $new_theme;
                $_SESSION['theme_mode'] = $new_theme;
                $success = 'Theme updated successfully.';
            } else {
                $error = 'Failed to update theme: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings — Bohol Bicycle Inventory</title>
    <link rel="stylesheet" href="settings.css">
</head>
<body class="theme-<?= htmlspecialchars($theme_mode) ?>">
<?php include 'header.php'; ?>

<div class="as-wrapper">

    <div class="as-header">
        <div class="as-header-left">
            <div class="as-eyebrow">Admin Portal · Account Settings</div>
            <h1 class="as-title">Settings</h1>
            <small>Manage your admin profile, password, and theme</small>
        </div>
        <div class="as-header-right">
            <div class="as-date-label">Today</div>
            <div class="as-date-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="as-alert as-alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="as-alert as-alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="as-grid">

        <div class="as-card">
            <div class="as-card-title">Account Information</div>
            <div class="as-info-list">
                <div class="as-info-row">
                    <span class="as-info-label">Full Name</span>
                    <span class="as-info-value"><?= htmlspecialchars($account['name'] ?? '') ?></span>
                </div>
                <div class="as-info-row">
                    <span class="as-info-label">Email</span>
                    <span class="as-info-value"><?= htmlspecialchars($account['email'] ?? '') ?></span>
                </div>
                <div class="as-info-row">
                    <span class="as-info-label">Role</span>
                    <span class="as-info-value"><?= htmlspecialchars(ucfirst($account['role'] ?? 'admin')) ?></span>
                </div>
                <div class="as-info-row">
                    <span class="as-info-label">Created</span>
                    <span class="as-info-value">
                        <?= !empty($account['created_at']) ? date('M d, Y', strtotime($account['created_at'])) : '—' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="as-card">
            <div class="as-card-title">Update Profile</div>
            <form method="POST" class="as-form">
                <input type="hidden" name="action" value="profile">

                <div class="as-field">
                    <label class="as-label">Full Name</label>
                    <input type="text" name="name" class="as-input" value="<?= htmlspecialchars($account['name'] ?? '') ?>" required>
                </div>

                <div class="as-field">
                    <label class="as-label">Email</label>
                    <input type="email" class="as-input" value="<?= htmlspecialchars($account['email'] ?? '') ?>" readonly>
                </div>

                <button type="submit" class="as-btn">Save Changes</button>
            </form>
        </div>

        <div class="as-card">
            <div class="as-card-title">System Theme</div>
            <form method="POST" class="as-form">
                <input type="hidden" name="action" value="theme">

                <div class="as-field">
                    <label class="as-label">Theme Mode</label>
                    <select name="theme_mode" class="as-input" required>
                        <option value="light" <?= $theme_mode === 'light' ? 'selected' : '' ?>>Light</option>
                        <option value="dark" <?= $theme_mode === 'dark' ? 'selected' : '' ?>>Dark</option>
                    </select>
                </div>

                <button type="submit" class="as-btn">Update Theme</button>
            </form>
        </div>

        <div class="as-card">
            <div class="as-card-title">Change Password</div>
            <form method="POST" class="as-form">
                <input type="hidden" name="action" value="password">

                <div class="as-field">
                    <label class="as-label">Current Password</label>
                    <input type="password" name="current_password" class="as-input" required>
                </div>

                <div class="as-field">
                    <label class="as-label">New Password</label>
                    <input type="password" name="new_password" class="as-input" required>
                </div>

                <div class="as-field">
                    <label class="as-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="as-input" required>
                </div>

                <button type="submit" class="as-btn">Update Password</button>
            </form>
        </div>

    </div>

</div>

</body>
</html>
<?php ob_end_flush(); ?>