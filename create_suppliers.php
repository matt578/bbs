<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];

    $query = "INSERT INTO suppliers (name, email, contact)
              VALUES ('$name', '$email', '$contact')";

    mysqli_query($conn, $query);

    header("Location: suppliers.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Supplier</title>
    <link rel="stylesheet" href="suppliers.css">
</head>
<body>

<div class="wrapper">

    <div class="sidebar">
        <h3>Menu</h3>
        <a href="suppliers.php" class="active">Suppliers</a>
    </div>

    <div class="main">
        <div class="header-row">
            <h2>Add New Supplier</h2>
        </div>

        <div class="card form-card">
            <form method="POST">

                <label>Supplier Name</label>
                <input type="text" name="name" required>

                <label>Email</label>
                <input type="email" name="email" required>

                <label>Contact Number</label>
                <input type="text" name="contact" required>

                <button type="submit" class="btn">Save Supplier</button>

            </form>
        </div>
    </div>

</div>

</body>
</html>
