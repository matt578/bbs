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
$success = '';
$prefillEmail = '';

if (isset($_SESSION['signup_success'])) {
    $success = $_SESSION['signup_success'];
    unset($_SESSION['signup_success']);
}

if (isset($_SESSION['signup_email'])) {
    $prefillEmail = $_SESSION['signup_email'];
    unset($_SESSION['signup_email']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $error = 'Please enter your email address.';
    } elseif ($password === '') {
        $error = 'Please enter your password.';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email format. Please use a valid Gmail address.';
    } elseif (!isValidGmail($email)) {
        $error = 'Yahoo, Hotmail, Outlook and other email providers are not accepted. Only Gmail addresses are allowed.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");

        if (!$stmt) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if (!$row) {
                $error = 'This account is not registered. Please sign up first.';
            } elseif ((int)($row['is_active'] ?? 0) !== 1) {
                $error = 'Your account has been deactivated.';
            } elseif (strtolower(trim((string)($row['role'] ?? ''))) !== 'staff') {
                $error = 'This account is not allowed in the Staff portal.';
            } elseif (!password_verify($password, (string)($row['password'] ?? ''))) {
                $error = 'Incorrect password. Please try again.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user_id']    = (int)$row['id'];
                $_SESSION['user']       = $row['name'];
                $_SESSION['user_name']  = $row['name'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['role']       = 'staff';
                $_SESSION['user_role']  = 'staff';

                $uid = (int)$row['id'];

                $update = $conn->prepare("UPDATE users SET last_login = NOW(), updated_at = NOW() WHERE id = ?");
                if ($update) {
                    $update->bind_param("i", $uid);
                    $update->execute();
                    $update->close();
                }

                $history = $conn->prepare("INSERT INTO login_history (user_id, login_method, login_time) VALUES (?, 'email', NOW())");
                if ($history) {
                    $history->bind_param("i", $uid);
                    $history->execute();
                    $history->close();
                }

                header('Location: staff_dashboard.php');
                exit();
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
    <title>Casher Login — Bohol Bicycle Inventory</title>
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
        <div class="sauth-title">Casher Login</div>
        <div class="sauth-sub">Bohol Bicycle Inventory · Staff Portal</div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="salert salert-success" id="phpAlert">
            <span class="salert-icon">✓</span>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="salert salert-error" id="phpAlert">
            <span class="salert-icon">✕</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="loginForm" novalidate>
        <div class="sfield-group">

            <div class="sfield-wrap">
                <label class="sfield-label" for="emailInput">Email</label>
                <input
                    type="email"
                    id="emailInput"
                    name="email"
                    class="sfield-input"
                    placeholder="yourname@gmail.com"
                    autocomplete="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? $prefillEmail) ?>"
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
                        placeholder="••••••••"
                        autocomplete="current-password"
                    >
                    <button type="button" class="stoggle-pass" id="togglePass" title="Show/hide password" aria-label="Show or hide password">👁</button>
                </div>
                <div class="sfield-error" id="passError"></div>
            </div>

        </div>

        <button type="submit" class="sbtn-login" id="submitBtn">
            <span class="sbtn-text">Login as Casher</span>
            <span class="sbtn-spinner"></span>
        </button>
    </form>

    <div class="sauth-footer">
        Don't have an account? &nbsp;<a href="staff_signup.php">Sign Up</a>
    </div>

    <a href="index.php" class="sbtn-back">← Back to Home</a>

</div>

<script src="staff_login.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>