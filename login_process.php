<?php
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Get user from database
    $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if email exists
    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user["password"])) {

            // Save session
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["email"] = $user["email"];

            // Redirect to Dashboard
            header("Location: Dashboard.php");
            exit();
        
        } else {
            // Wrong password
            header("Location: login.php?error=wrongpass");
            exit();
        }

    } else {
        // Email not found
        header("Location: login.php?error=invalidemail");
        exit();
    }
}
?>
