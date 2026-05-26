<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: staff_login.php');
    exit();
}

include 'staff_header.php';

$userId = (int)$_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: staff_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $error = 'Full name is required.';
        } elseif (strlen($name) < 2) {
            $error = 'Full name must be at least 2 characters.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?");
            if (!$stmt) {
                $error = 'Failed to prepare update: ' . $conn->error;
            } else {
                $stmt->bind_param("si", $name, $userId);
                if ($stmt->execute()) {
                    $_SESSION['user'] = $name;
                    $_SESSION['user_name'] = $name;
                    $success = 'Profile updated successfully.';
                    $user['name'] = $name;
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

            $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
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
?>

<title>Staff Settings — Bohol Bicycle Inventory</title>
<link rel="stylesheet" href="staff_settings.css">

<div class="ss-wrapper">

    <div class="ss-header">
        <div class="ss-header-left">
            <div class="ss-eyebrow">Staff Portal · Account Settings</div>
            <h1 class="ss-title">Settings</h1>
            <small>Manage your staff profile and password</small>
        </div>
        <div class="ss-header-right">
            <div class="ss-date-label">Today</div>
            <div class="ss-date-value"><?= date("M d, Y") ?></div>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="ss-alert ss-alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="ss-alert ss-alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="ss-grid">

        <div class="ss-card">
            <div class="ss-card-title">Account Information</div>
            <div class="ss-info-list">
                <div class="ss-info-row">
                    <span class="ss-info-label">Full Name</span>
                    <span class="ss-info-value"><?= htmlspecialchars($user['name']) ?></span>
                </div>
                <div class="ss-info-row">
                    <span class="ss-info-label">Email</span>
                    <span class="ss-info-value"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="ss-info-row">
                    <span class="ss-info-label">Role</span>
                    <span class="ss-info-value"><?= htmlspecialchars(ucfirst($user['role'])) ?></span>
                </div>
                <div class="ss-info-row">
                    <span class="ss-info-label">Created</span>
                    <span class="ss-info-value"><?= !empty($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : '—' ?></span>
                </div>
            </div>
        </div>

        <div class="ss-card">
            <div class="ss-card-title">Update Profile</div>
            <form method="POST" class="ss-form">
                <input type="hidden" name="action" value="profile">

                <div class="ss-field">
                    <label class="ss-label">Full Name</label>
                    <input type="text" name="name" class="ss-input" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="ss-field">
                    <label class="ss-label">Email</label>
                    <input type="email" class="ss-input" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                </div>

                <button type="submit" class="ss-btn">Save Changes</button>
            </form>
        </div>

        <div class="ss-card ss-card-full">
            <div class="ss-card-title">Change Password</div>
            <form method="POST" class="ss-form ss-password-form">
                <input type="hidden" name="action" value="password">

                <div class="ss-field">
                    <label class="ss-label">Current Password</label>
                    <input type="password" name="current_password" class="ss-input" required>
                </div>

                <div class="ss-field">
                    <label class="ss-label">New Password</label>
                    <input type="password" name="new_password" class="ss-input" required>
                </div>

                <div class="ss-field">
                    <label class="ss-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="ss-input" required>
                </div>

                <button type="submit" class="ss-btn">Update Password</button>
            </form>
        </div>

    </div>

</div>

</body>
</html>