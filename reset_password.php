<?php
// reset_password.php
session_start();
require 'db.php';
$msg = '';
$token = $_GET['token'] ?? '';

if (!$token) { die('Invalid link'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'];
    $pw2 = $_POST['confirm'];
    if ($pw !== $pw2) $msg = 'Passwords do not match';
    elseif (strlen($pw) < 6) $msg = 'Password too short';
    else {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE reset_token = ?");
        $stmt->bind_param('ss', $hash, $token);
        $stmt->execute();
        if ($stmt->affected_rows) {
            $msg = 'Password updated. <a href="login.php">Login</a>';
        } else $msg = 'Invalid or expired token';
        $stmt->close();
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Reset</title>
<link rel="stylesheet" href="auth.css"></head>
<body>
<video id="bg-video" autoplay muted loop>
  <source src="assets/video/login-bg.mp4" type="video/mp4">
</video>

<div class="login-container"><div class="login-box">
  <h2>Set new password</h2>
  <?php if($msg): ?><p class="error"><?= $msg ?></p><?php endif; ?>

  <form method="post">
    <div class="input-group"><input name="password" type="password" required placeholder=" "><label>New password</label></div>
    <div class="input-group"><input name="confirm" type="password" required placeholder=" "><label>Confirm</label></div>
    <button class="btn-login" type="submit">Update password</button>
  </form>
</div></div>
</body></html>
