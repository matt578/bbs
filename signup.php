<?php
session_start();
require 'auth_helpers.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$nameError = '';
$emailError = '';
$passError = '';
$confirmError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name'] ?? '');
    $email           = strtolower(trim($_POST['email'] ?? ''));
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $nameError = 'Full name is required.';
    } elseif (mb_strlen($name) < 2) {
        $nameError = 'Full name must be at least 2 characters.';
    }

    if ($email === '') {
        $emailError = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = 'Invalid account. Only Gmail is allowed.';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@gmail\.com$/i', $email)) {
        $emailError = 'Invalid account. Only Gmail is allowed.';
    }

    if ($password === '') {
        $passError = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $passError = 'Password must be at least 6 characters.';
    }

    if ($confirmPassword === '') {
        $confirmError = 'Please confirm your password.';
    } elseif ($password !== '' && $password !== $confirmPassword) {
        $confirmError = 'Passwords do not match.';
    }

    if ($nameError === '' && $emailError === '' && $passError === '' && $confirmError === '') {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");

        if (!$checkStmt) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $checkStmt->bind_param("s", $email);

            if (!$checkStmt->execute()) {
                $error = 'Signup query failed: ' . $checkStmt->error;
            } else {
                $result = $checkStmt->get_result();

                if ($result && $result->num_rows > 0) {
                    $emailError = 'The user already exists.';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $insertStmt = $conn->prepare("
                        INSERT INTO users (name, email, password, is_active, created_at)
                        VALUES (?, ?, ?, 1, NOW())
                    ");

                    if (!$insertStmt) {
                        $error = 'Database error: ' . $conn->error;
                    } else {
                        $insertStmt->bind_param("sss", $name, $email, $hashedPassword);

                        if ($insertStmt->execute()) {
                            $_SESSION['signup_success'] = 'Account created successfully. You can now log in.';
                            $insertStmt->close();
                            $checkStmt->close();
                            header('Location: login.php');
                            exit();
                        } else {
                            $error = 'Failed to create account: ' . $insertStmt->error;
                        }

                        $insertStmt->close();
                    }
                }
            }

            $checkStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Sign Up — Bohol Bicycle Inventory</title>
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
        <div class="auth-title">Admin Sign Up</div>
        <div class="auth-sub">Bohol Bicycle Inventory · Admin Portal</div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" id="phpAlert">
            <span class="alert-icon">✕</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="signupForm" novalidate>
        <div class="field-group">

            <div class="field-wrap">
                <label class="field-label" for="nameInput">Full Name</label>
                <input
                    type="text"
                    id="nameInput"
                    name="name"
                    class="field-input <?= !empty($nameError) ? 'input-error' : '' ?>"
                    placeholder="Enter full name"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                >
                <small class="field-error-text" id="nameError"><?= htmlspecialchars($nameError) ?></small>
            </div>

            <div class="field-wrap">
                <label class="field-label" for="emailInput">Email</label>
                <input
                    type="email"
                    id="emailInput"
                    name="email"
                    class="field-input <?= !empty($emailError) ? 'input-error' : '' ?>"
                    placeholder="yourname@gmail.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >
                <small class="field-error-text" id="emailError"><?= htmlspecialchars($emailError) ?></small>
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
                    >
                    <button type="button" class="toggle-pass" id="togglePass1" title="Show/hide password">👁</button>
                </div>
                <div class="field-error-text" id="passError"><?= htmlspecialchars($passError) ?></div>
            </div>

            <div class="field-wrap">
                <label class="field-label" for="confirmInput">Confirm Password</label>
                <div class="pass-wrap">
                    <input
                        type="password"
                        id="confirmInput"
                        name="confirm_password"
                        class="field-input <?= !empty($confirmError) ? 'input-error' : '' ?>"
                        placeholder="••••••••"
                    >
                    <button type="button" class="toggle-pass" id="togglePass2" title="Show/hide password">👁</button>
                </div>
                <div class="field-error-text" id="confirmError"><?= htmlspecialchars($confirmError) ?></div>
            </div>

        </div>

        <button type="submit" class="btn-primary" id="submitBtn">
            <span class="btn-text">Sign Up</span>
            <span class="btn-spinner"></span>
        </button>
    </form>

    <div class="auth-footer">
        Already have an account? &nbsp;<a href="login.php">Login</a>
    </div>

    <a href="index.php" class="btn-back-home">← Back to Home</a>

</div>

<script src="signup.js"></script>
</body>
</html>