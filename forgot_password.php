<?php
// forgot_password.php
session_start();
require 'db.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $msg = 'Invalid email';
    else {
        // generate token
        $token = bin2hex(random_bytes(20));
        $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
        $stmt->bind_param('ss', $token, $email);
        $stmt->execute();
        if ($stmt->affected_rows) {
            // TODO: send email containing link to reset_password.php?token=$token
            // For development we show the link:
            $msg = "Reset link (dev): <a href='reset_password.php?token=$token'>reset_password.php?token=$token</a>";
        } else {
            $msg = 'Email not found';
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Forgot</title>
<link rel="stylesheet" href="auth.css"></head>
<body>
<video id="bg-video" autoplay muted loop>
  <source src="assets/video/login-bg.mp4" type="video/mp4">
</video>

<div class="login-container">
  <div class="login-box">
    <h2>Reset password</h2>
    <?php if($msg): ?><p class="error"><?= $msg ?></p><?php endif; ?>
    <form method="post">
      <div class="input-group"><input name="email" type="email" required placeholder=" "><label>Enter your email</label></div>
      <button class="btn-login">Send reset link</button>
    </form>
    <div class="links"><a href="login.php">Back to login</a></div>
  </div>
</div>
</body></html>
