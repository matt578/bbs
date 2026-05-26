<?php
// register.php
session_start();
require 'db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $message = 'Invalid email';
    elseif ($password !== $confirm) $message = 'Passwords do not match';
    elseif (strlen($password) < 6) $message = 'Password must be at least 6 chars';
    else {
        // check exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = 'Email already registered';
            $stmt->close();
        } else {
            $stmt->close();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (email, password, name) VALUES (?, ?, ?)");
            $ins->bind_param('sss', $email, $hash, $name);
            if ($ins->execute()) {
                $_SESSION['user'] = $email;
                header('Location: Dashboard.php');
                exit;
            } else {
                $message = 'Registration failed';
            }
            $ins->close();
        }
    }
}
?>

<!doctype html>
<html>
<head><meta charset="utf-8"><title>Sign up</title>
<link rel="stylesheet" href="auth.css">
</head>
<body>
<video id="bg-video" autoplay muted loop>
  <!-- replace with mp4 when available -->
  <source src="assets/video/login-bg.mp4" type="video/mp4">
</video>

<div class="login-container">
  <div class="login-box">
    <img src="/mnt/data/68a26b15-f5bf-414c-8db9-23d802408b7c.png" alt="logo" style="width:80px;margin-bottom:10px;">
    <h2>Create account</h2>
    <?php if($message): ?><p class="error"><?=htmlspecialchars($message)?></p><?php endif; ?>

    <form method="post">
      <div class="input-group"><input name="name" placeholder="Full name" required></div>
      <div class="input-group"><input name="email" type="email" placeholder="Email" required></div>
      <div class="input-group"><input name="password" type="password" placeholder="Password" required></div>
      <div class="input-group"><input name="confirm" type="password" placeholder="Confirm password" required></div>
      <button class="btn-login" type="submit">Sign up</button>
    </form>

    <div class="links">
      <a href="login.php">Already have account? Login</a>
    </div>
  </div>
</div>
</body>
</html>
