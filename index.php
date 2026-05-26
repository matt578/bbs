<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bohol Bicycle Inventory</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

<!-- Background video -->
<video autoplay muted loop id="bgVideo">
    <source src="BikeMartSG Introduction Video - Singapore First Premium Second Hand Road Bicycle Shop.mp4" type="video/mp4">
</video>

<!-- Overlay -->
<div class="overlay"></div>

<!-- Floating particles -->
<div class="particles" id="particles"></div>

<!-- MAIN CONTENT -->
<div class="landing-wrap">

    <!-- TOP BADGE -->
    <div class="top-badge" id="topBadge">
        <span class="badge-dot"></span>
        Est. 1991 · Tagbilaran, Bohol
    </div>

    <!-- LOGO -->
    <div class="logo-ring" id="logoRing">
        <img src="logo_bohol_Bicycle-removebg-preview.png" alt="BBS Logo" class="logo-img">
        <div class="logo-glow"></div>
    </div>

    <!-- TITLE -->
    <h1 class="brand-name" id="brandName">
        <span class="brand-word">Bohol</span>
        <span class="brand-word">Bicycle</span>
        <span class="brand-word brand-accent">Supply</span>
    </h1>

    <p class="brand-tagline" id="brandTagline">
        Inventory Management System
    </p>

    <!-- DIVIDER LINE -->
    <div class="divider-line" id="dividerLine"></div>

    <!-- ROLE BUTTONS -->
    <div class="role-section" id="roleSection">
        <div class="role-label">Select your role to continue</div>

        <div class="role-grid">

            <!-- ADMIN -->
            <a href="login.php?role=admin" class="role-card role-admin" id="cardAdmin">
                <div class="role-icon">🛡</div>
                <div class="role-card-body">
                    <div class="role-title">Administrator</div>
                    <div class="role-desc">Full system access — manage inventory, users, reports and settings.</div>
                </div>
                <div class="role-arrow">→</div>
            </a>

            <!-- Casher -->
            <a href="staff_login.php" class="role-card role-staff" id="cardStaff">
                <div class="role-icon">🧑‍💼</div>
                <div class="role-card-body">
                    <div class="role-title">Casher</div>
                    <div class="role-desc">POS, sales and daily inventory operations access.</div>
                </div>
                <div class="role-arrow">→</div>
            </a>

        </div>
    </div>

    <!-- FOOTER -->
    <div class="landing-footer" id="landingFooter">
        <span>© <?= date('Y') ?> Bohol Bicycle Supply</span>
        <span class="footer-dot">·</span>
        <span>All rights reserved</span>
    </div>

</div>

<script src="index.js"></script>
</body>
</html>