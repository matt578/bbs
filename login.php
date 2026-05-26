<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'auth_helpers.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$emailError = '';
$passError = '';
$success = '';

if (isset($_SESSION['signup_success'])) {
    $success = $_SESSION['signup_success'];
    unset($_SESSION['signup_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $emailError = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = 'Invalid account. Only Gmail is allowed.';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@gmail\.com$/i', $email)) {
        $emailError = 'Invalid account. Only Gmail is allowed.';
    }

    if ($password === '') {
        $passError = 'Password is required.';
    }

    if ($emailError === '' && $passError === '') {
        $stmt = $conn->prepare("SELECT id, name, email, password, is_active FROM users WHERE email = ? LIMIT 1");

        if (!$stmt) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("s", $email);

            if (!$stmt->execute()) {
                $error = 'Login query failed: ' . $stmt->error;
            } else {
                $result = $stmt->get_result();
                $row = $result ? $result->fetch_assoc() : null;

                if (!$row) {
                    $emailError = 'No account found with that email.';
                } elseif ((int)$row['is_active'] === 0) {
                    $error = 'Your account has been deactivated.';
                } elseif (!password_verify($password, $row['password'])) {
                    $passError = 'Incorrect password.';
                } else {
                    $stmt->close();
                    completeLogin($row, $conn, 'email');
                }
            }

            if ($stmt) {
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — Bohol Bicycle Inventory</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>

<video autoplay muted loop id="bgVideo">
    <source src="BikeMartSG Introduction Video - Singapore First Premium Second Hand Road Bicycle Shop.mp4" type="video/mp4">
</video>

<div class="auth-card">

    <div class="admin-badge">
        <span class="admin-badge-dot"></span>
        Admin Portal
    </div>

    <div class="auth-brand">
        <img src="logo_bohol_Bicycle-removebg-preview.png" class="auth-logo" alt="BBS Logo">
        <div class="auth-title">Admin Login</div>
        <div class="auth-sub">Bohol Bicycle Inventory · Admin Portal</div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success" id="phpAlert">
            <span class="alert-icon">✓</span>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" id="phpAlert">
            <span class="alert-icon">✕</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="loginForm" novalidate>
        <div class="field-group">

            <div class="field-wrap">
                <label class="field-label" for="emailInput">Email</label>
                <input
                    type="email"
                    id="emailInput"
                    name="email"
                    class="field-input <?= !empty($emailError) ? 'input-error' : '' ?>"
                    placeholder="yourname@gmail.com"
                    autocomplete="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >
                <div class="field-error-text" id="emailError"><?= htmlspecialchars($emailError) ?></div>
            </div>

            <div class="field-wrap">
                <label class="field-label" for="passInput">Password</label>
                <div class="pass-wrap">
                    <input
                        type="password"
                        id="passInput"
                        name="password"
                        class="field-input <?= !empty($passError) ? 'input-error' : '' ?>"
                        placeholder="••••••••"
                        autocomplete="current-password"
                    >
                    <button type="button" class="toggle-pass" id="togglePass" title="Show/hide password">👁</button>
                </div>
                <div class="field-error-text" id="passError"><?= htmlspecialchars($passError) ?></div>
            </div>

        </div>

        <button type="submit" class="btn-primary" id="submitBtn">
            <span class="btn-text">Login</span>
            <span class="btn-spinner"></span>
        </button>
    </form>

    <div class="auth-footer">
        Don't have an account? &nbsp;<a href="signup.php">Sign Up</a>
    </div>

    <a href="index.php" class="btn-back-home">← Back to Home</a>

</div>

<script src="login.js"></script>
</body>
</html>
