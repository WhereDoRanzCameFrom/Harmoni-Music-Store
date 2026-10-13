<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_payment'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $fee = floatval($_POST['fee']);
        
        if (addPaymentMethod($name, $description, $fee)) {
            $message = "Metode pembayaran berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan metode pembayaran!";
        }
    }
    
    if (isset($_POST['update_payment'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $fee = floatval($_POST['fee']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (updatePaymentMethod($id, $name, $description, $fee, $is_active)) {
            $message = "Metode pembayaran berhasil diupdate!";
        } else {
            $error = "Gagal mengupdate metode pembayaran!";
        }
    }
    
    if (isset($_POST['delete_payment'])) {
        $id = intval($_POST['id']);
        
        if (deletePaymentMethod($id)) {
            $message = "Metode pembayaran berhasil dihapus!";
        } else {
            $error = "Gagal menghapus metode pembayaran!";
        }
    }
}

$paymentMethods = getPaymentMethods();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembayaran - Harmoni Music Store</title>
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
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-container, .payments-container { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #2ecc71; }
        .btn-success:hover { background: #27ae60; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .actions { display: flex; gap: 10px; }
        .status { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; }
        .status.active { background: #d4edda; color: #155724; }
        .status.inactive { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="sidebar">
        <h2><i class="fas fa-music"></i> Harmoni Admin</h2>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage-products.php"><i class="fas fa-guitar"></i> Kelola Produk</a></li>
            <li><a href="manage-orders.php"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a></li>
            <li><a href="manage-payments.php" class="active"><i class="fas fa-credit-card"></i> Kelola Pembayaran</a></li>
            <li><a href="manage-transactions.php"><i class="fas fa-receipt"></i> Riwayat Transaksi</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
       <div class="header">
            <h1><i class="fas fa-credit-card"></i> Kelola Metode Pembayaran</h1>
            </p>Kelola metode pembayaran yang tersedia untuk customer (Transfer Bank, E-Wallet, COD, dll)</p>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Form Tambah Payment Method -->
        <div class="form-container">
            <h2><i class="fas fa-plus"></i> Tambah Metode Pembayaran Baru</h2>
            <form method="POST" class="form">
                <div class="form-group">
                    <label for="name">Nama Metode *</label>
                    <input type="text" id="name" name="name" required placeholder="Contoh: Transfer Bank, E-Wallet, COD">
                </div>
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" rows="3" placeholder="Deskripsi metode pembayaran"></textarea>
                </div>
                <div class="form-group">
                    <label for="fee">Biaya Tambahan (Rp)</label>
                    <input type="number" id="fee" name="fee" value="0" min="0" step="1000" placeholder="0">
                </div>
                <button type="submit" name="add_payment" class="btn btn-success">
                    <i class="fas fa-save"></i> Tambah Metode
                </button>
            </form>
        </div>

        <!-- Daftar Payment Methods -->
        <div class="payments-container">
            <h2><i class="fas fa-list"></i> Daftar Metode Pembayaran</h2>
            <?php if (empty($paymentMethods)): ?>
                <p>Belum ada metode pembayaran.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Deskripsi</th>
                            <th>Biaya</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentMethods as $method): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($method['name']); ?></td>
                            <td><?php echo htmlspecialchars($method['description']); ?></td>
                            <td>Rp <?php echo number_format($method['fee'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="status <?php echo $method['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $method['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
                                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($method['name']); ?>">
                                    <input type="hidden" name="description" value="<?php echo htmlspecialchars($method['description']); ?>">
                                    <input type="hidden" name="fee" value="<?php echo $method['fee']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $method['is_active']; ?>">
                                    <button type="submit" name="update_payment" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus metode pembayaran ini?')">
                                    <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
                                    <button type="submit" name="delete_payment" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="form-container">
            <a href="dashboard.php" class="btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</body>
</html>