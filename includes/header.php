<?php
// Load config database
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$cache_buster = time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harmoni Music Store - Toko Alat Musik Terpercaya</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?ver=<?php echo $cache_buster; ?>">
    <link rel="stylesheet" href="css/responsive.css?ver=<?php echo $cache_buster; ?>">
</head>
<body>
    <!-- Header & Navigasi -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-music"></i>
                    <h1>Harmoni Music Store</h1>
                </div>
                
                <div class="mobile-menu">
                    <i class="fas fa-bars"></i>
                </div>
                
                <nav>
                    <ul>
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="index.php#products">Produk</a></li>
                        <li><a href="about.php">Tentang Kami</a></li>
                        <li><a href="order-status.php">Cek Status</a></li>
                        <li><a href="index.php#contact">Kontak</a></li>
                        <?php if(isAdminLoggedIn()): ?>
                            <li><a href="admin/dashboard.php">Admin</a></li>
                            <li><a href="admin/logout.php">Logout</a></li>
                        <?php endif; ?>
                        <li class="cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count">0</span>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>