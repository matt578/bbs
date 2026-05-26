<?php
$current_page = strtolower(basename($_SERVER['PHP_SELF']));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';
$theme_mode = "light";
$q = $conn->query("SELECT theme_mode FROM system_settings WHERE id=1 LIMIT 1");
if ($q && $q->num_rows > 0) {
    $theme_mode = $q->fetch_assoc()['theme_mode'] ?? "light";
}
$_SESSION['theme_mode'] = $theme_mode;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Bohol Bicycle Inventory</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="custom.css" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: Arial;
    font-weight: 400;
    background: #0d0f14;
    padding-top: 58px;
}

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 0;
}

.navbar {
    height: 58px;
    background: rgba(13,15,20,0.9) !important;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255,255,255,0.07) !important;
    padding: 0 1.75rem !important;
    z-index: 1000;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-right: 2rem;
    text-decoration: none;
}

.navbar-brand img {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    object-fit: contain;
    background: #e8ff47;
    padding: 2px;
}

.navbar-brand span {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 14px;
    font-weight: 400;
    color: #f0f2f7 !important;
    letter-spacing: 0.01em;
    white-space: nowrap;
}

.navbar-nav .nav-link {
    font-family: Arial, Helvetica, sans-serif !important;
    font-size: 11px !important;
    font-weight: 400 !important;
    color: #6b7280 !important;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 0 1rem !important;
    height: 58px;
    display: flex !important;
    align-items: center;
    position: relative;
    transition: color 0.2s ease;
    text-shadow: none !important;
    background: transparent !important;
    border: none !important;
    text-decoration: none;
}

.navbar-nav .nav-link:hover {
    color: #f0f2f7 !important;
}

.navbar-nav .nav-link.active {
    color: #e8ff47 !important;
    text-shadow: none !important;
}

.navbar-nav .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 1rem;
    right: 1rem;
    height: 2px;
    background: #e8ff47;
    border-radius: 2px 2px 0 0;
    box-shadow: none !important;
    width: auto !important;
}

.navbar-nav .nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 1rem;
    right: 1rem;
    height: 2px;
    background: transparent;
    border-radius: 2px 2px 0 0;
    transition: background 0.2s;
}

.navbar-nav.ms-auto .nav-link {
    font-family: Arial, Helvetica, sans-serif !important;
    font-size: 11px !important;
    font-weight: 400 !important;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7280 !important;
    padding: 0 0.75rem !important;
    height: 58px;
    display: flex !important;
    align-items: center;
    transition: color 0.2s;
    background: transparent !important;
    border: none !important;
}

.navbar-nav.ms-auto .nav-link:hover {
    color: #f0f2f7 !important;
}

.navbar-nav.ms-auto .nav-link.text-danger {
    color: #ff6b47 !important;
    border: 1px solid rgba(255,107,71,0.35) !important;
    border-radius: 6px;
    padding: 0 14px !important;
    margin-left: 4px;
    height: 30px !important;
    font-size: 11px !important;
    font-weight: 400 !important;
}

.navbar-nav.ms-auto .nav-link.text-danger:hover {
    background: rgba(255,107,71,0.1) !important;
    border-color: #ff6b47 !important;
    color: #ff6b47 !important;
}

.navbar-toggler {
    border-color: rgba(255,255,255,0.15) !important;
    background: transparent !important;
}

.navbar-toggler-icon {
    filter: invert(1) brightness(0.6);
}

.content-card {
    background: #141720;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.07);
}
body.light-mode {
    background: #f5f7fb;
    color: #111827;
}

body.light-mode::before {
    background-image:
        linear-gradient(rgba(0,0,0,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.04) 1px, transparent 1px);
}

body.light-mode .navbar {
    background: rgba(255,255,255,0.9) !important;
    border-bottom: 1px solid rgba(0,0,0,0.08) !important;
}

body.light-mode .navbar-brand span {
    color: #111827 !important;
}

body.light-mode .navbar-nav .nav-link,
body.light-mode .navbar-nav.ms-auto .nav-link {
    color: #4b5563 !important;
}

body.light-mode .navbar-nav .nav-link:hover,
body.light-mode .navbar-nav.ms-auto .nav-link:hover {
    color: #111827 !important;
}

body.light-mode .navbar-nav .nav-link.active {
    color: #84cc16 !important;
}

body.light-mode .navbar-nav .nav-link.active::after {
    background: #84cc16;
}

body.light-mode .content-card {
    background: #ffffff;
    border: 1px solid #dbe2ea;
}
</style>
</head>

<body class="<?= ($theme_mode === 'dark') ? 'dark-mode' : 'light-mode' ?>">

<div class="container-fluid p-0">
<nav class="navbar navbar-expand-lg fixed-top">

    <a class="navbar-brand" href="#">
        <img src="logo_bohol_Bicycle-removebg-preview.png" alt="logo">
        <span>Bohol Bicycle Inventory</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
        <ul class="navbar-nav ms-2">
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'pos.php') ? 'active' : '' ?>" href="pos.php">POS</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'inventory.php') ? 'active' : '' ?>" href="inventory.php">Inventory</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'sales_order.php') ? 'active' : '' ?>" href="sales_order.php">Sales</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'suppliers.php') ? 'active' : '' ?>" href="suppliers.php">Suppliers</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'reports.php') ? 'active' : '' ?>" href="reports.php">Reports</a></li>
        </ul>

        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item">
                <a class="nav-link <?= ($current_page == 'settings.php') ? 'active' : '' ?>" href="settings.php">Settings</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="logout.php">Logout</a>
            </li>
        </ul>
    </div>

</nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function () {
        document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
        this.classList.add('active');
    });
});
</script>