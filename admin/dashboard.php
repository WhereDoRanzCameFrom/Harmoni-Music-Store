<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

// Ambil statistik
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(DISTINCT category) FROM products")->fetchColumn();
$total_payment_methods = $pdo->query("SELECT COUNT(*) FROM payment_methods WHERE is_active = 1")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$latest_products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Harmoni Music Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f5f6fa; }
        .sidebar { background: #2c3e50; color: white; width: 250px; height: 100vh; position: fixed; padding: 20px; }
        .sidebar h2 { margin-bottom: 30px; text-align: center; }
        .sidebar ul { list-style: none; }
        .sidebar li { margin-bottom: 15px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 10px; border-radius: 5px; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; }
        .main-content { margin-left: 250px; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card i { font-size: 2rem; margin-bottom: 10px; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #2c3e50; }
        .recent-products { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .product-item { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
        .product-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 15px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219653; }
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .quick-action { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-decoration: none; color: #2c3e50; transition: transform 0.3s ease; }
        .quick-action:hover { transform: translateY(-5px); }
        .quick-action i { font-size: 2rem; margin-bottom: 10px; display: block; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-music"></i> Harmoni Admin</h2>
        <ul>
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage-products.php"><i class="fas fa-guitar"></i> Kelola Produk</a></li>
            <li><a href="manage-orders.php"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a></li>
            <li><a href="manage-payments.php"><i class="fas fa-credit-card"></i> Kelola Pembayaran</a></li>
            <li><a href="manage-transactions.php"><i class="fas fa-receipt"></i> Riwayat Transaksi</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard Admin</h1>
            <p>Selamat datang, <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?>!</p>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="manage-products.php" class="quick-action">
                <i class="fas fa-plus" style="color: #3498db;"></i>
                <strong>Tambah Produk</strong>
            </a>
            <a href="manage-orders.php" class="quick-action">
                <i class="fas fa-eye" style="color: #9b59b6;"></i>
                <strong>Lihat Pesanan</strong>
            </a>
            <a href="manage-payments.php" class="quick-action">
                <i class="fas fa-credit-card" style="color: #27ae60;"></i>
                <strong>Kelola Pembayaran</strong>
            </a>
        </div>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-guitar" style="color: #3498db;"></i>
                <div class="stat-number"><?php echo $total_products; ?></div>
                <div>Total Produk</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-shopping-cart" style="color: #9b59b6;"></i>
                <div class="stat-number"><?php echo $total_orders; ?></div>
                <div>Total Pesanan</div>
            </div>

            <div class="stat-card">
                <i class="fas fa-tags" style="color: #e74c3c;"></i>
                <div class="stat-number"><?php echo $total_categories; ?></div>
                <div>Kategori</div>
            </div>
        </div>

        <!-- Recent Products -->
        <div class="recent-products">
            <h2>Produk Terbaru</h2>
            <?php if(empty($latest_products)): ?>
                <p>Belum ada produk.</p>
            <?php else: ?>
                <?php foreach($latest_products as $product): ?>
                    <div class="product-item">
                        <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                        <div>
                            <strong><?php echo $product['name']; ?></strong>
                            <div>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <a href="manage-products.php" class="btn">Kelola Semua Produk</a>
            </div>
        </div>
    </div>
</body>
</html>