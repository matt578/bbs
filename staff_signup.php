<?php
ob_start();
session_start();
require 'db.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'staff') {
    header('Location: staff_dashboard.php');
    exit();
}

function isValidEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isValidGmail(string $email): bool {
    return (bool) preg_match('/^[a-zA-Z0-9._%+\-]+@gmail\.com$/i', $email);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $error = 'Please enter your full name.';
    } elseif (strlen($name) < 2) {
        $error = 'Name must be at least 2 characters.';
    } elseif ($email === '') {
        $error = 'Please enter your email address.';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email format. Please use a valid Gmail address.';
    } elseif (!isValidGmail($email)) {
        $error = 'Yahoo, Hotmail, Outlook and other email providers are not accepted. Only Gmail addresses are allowed.';
    } elseif ($password === '') {
        $error = 'Please enter your password.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($confirm_password === '') {
        $error = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");

        if (!$check) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $check->bind_param("s", $email);
            $check->execute();
            $result = $check->get_result();
            $existing = $result ? $result->fetch_assoc() : null;
            $check->close();

            if ($existing) {
                $error = 'This email is already registered. Please log in.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    INSERT INTO users (
                        name, email, password, role, is_active, last_login, created_at, updated_at
                    ) VALUES (?, ?, ?, 'staff', 1, NULL, NOW(), NOW())
                ");

                if (!$stmt) {
                    $error = 'Failed to prepare account creation: ' . $conn->error;
                } else {
                    $stmt->bind_param('sss', $name, $email, $hashedPassword);

                    if ($stmt->execute()) {
                        $stmt->close();
                        $_SESSION['signup_success'] = 'Staff account created successfully. You can now log in.';
                        $_SESSION['signup_email'] = $email;
                        header('Location: staff_login.php');
                        exit();
                    } else {
                        $error = 'Failed to create account: ' . $stmt->error;
                        $stmt->close();
                    }
                }
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
    <title>Create Casher Account — Bohol Bicycle Inventory</title>
    <link rel="stylesheet" href="staff_auth.css">
</head>
<body>

<video autoplay muted loop id="bgVideo">
    <source src="BikeMartSG Introduction Video - Singapore First Premium Second Hand Road Bicycle Shop.mp4" type="video/mp4">
</video>

<div class="sauth-card">

    <div class="staff-badge">
        <span class="staff-badge-dot"></span>
        Casher Portal
    </div>

    <div class="sauth-brand">
        <img src="logo_bohol_Bicycle-removebg-preview.png" class="sauth-logo" alt="BBS Logo">
        <div class="sauth-title">Create Staff Account</div>
        <div class="sauth-sub">Bohol Bicycle Inventory · Casher Portal</div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="salert salert-error" id="phpAlert">
            <span class="salert-icon">✕</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="signupForm" novalidate>
        <div class="sfield-group">

            <div class="sfield-wrap">
                <label class="sfield-label" for="nameInput">Full Name</label>
                <input
                    type="text"
                    id="nameInput"
                    name="name"
                    class="sfield-input"
                    placeholder="Juan dela Cruz"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                >
                <div class="sfield-error" id="nameError"></div>
            </div>

            <div class="sfield-wrap">
                <label class="sfield-label" for="emailInput">Email</label>
                <input
                    type="email"
                    id="emailInput"
                    name="email"
                    class="sfield-input"
                    placeholder="yourname@gmail.com"
                    autocomplete="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >
                <div class="sfield-error" id="emailError"></div>
            </div>

            <div class="sfield-wrap">
                <label class="sfield-label" for="passInput">Password</label>
                <div class="spass-wrap">
                    <input
                        type="password"
                        id="passInput"
                        name="password"
                        class="sfield-input"
                        placeholder="Min. 6 characters"
                        autocomplete="new-password"
                    >
                    <button type="button" class="stoggle-pass" id="togglePass1" title="Show/hide password" aria-label="Show or hide password">👁</button>
                </div>
                <div class="sfield-error" id="passError"></div>
            </div>

            <div class="sfield-wrap">
                <label class="sfield-label" for="confirmPassInput">Confirm Password</label>
                <div class="spass-wrap">
                    <input
                        type="password"
                        id="confirmPassInput"
                        name="confirm_password"
                        class="sfield-input"
                        placeholder="Re-enter password"
                        autocomplete="new-password"
                    >
                    <button type="button" class="stoggle-pass" id="togglePass2" title="Show/hide password" aria-label="Show or hide confirm password">👁</button>
                </div>
                <div class="sfield-error" id="confirmError"></div>
            </div>

        </div>

        <button type="submit" class="sbtn-login" id="submitBtn">
            <span class="sbtn-text">Create Casher Account</span>
            <span class="sbtn-spinner"></span>
        </button>
    </form>

    <div class="sauth-footer">
        Already have an account? &nbsp;<a href="staff_login.php">Log In</a>
    </div>

    <a href="staff_login.php" class="sbtn-back">← Back to Login</a>

</div>

<script src="staff_signup.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>