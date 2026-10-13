<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

// Ambil semua transaksi/pesanan dengan info pembayaran
$transactions = $pdo->query("
    SELECT 
        o.id,
        o.customer_name,
        o.customer_email,
        o.total_amount,
        o.subtotal_amount,
        o.payment_fee,
        o.payment_method_name,
        o.status,
        o.order_date,
        COUNT(oi.id) as item_count,
        GROUP_CONCAT(oi.product_name SEPARATOR ', ') as products
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    GROUP BY o.id 
    ORDER BY o.order_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Harmoni Music Store</title>
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
        .transactions-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .btn { background: #3498db; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .payment-info { background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .amount-breakdown { font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-music"></i> Harmoni Admin</h2>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage-products.php"><i class="fas fa-guitar"></i> Kelola Produk</a></li>
            <li><a href="manage-orders.php"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a></li>
            <li><a href="manage-payments.php"><i class="fas fa-credit-card"></i> Kelola Pembayaran</a></li>
            <li><a href="manage-transactions.php" class="active"><i class="fas fa-receipt"></i> Riwayat Transaksi</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-receipt"></i> Riwayat Transaksi</h1>
            <p>Lihat semua riwayat pembayaran dan transaksi pelanggan</p>
        </div>

        <div class="transactions-container">
            <h2>Daftar Transaksi</h2>
            
            <?php if(empty($transactions)): ?>
                <p>Belum ada transaksi.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Pelanggan</th>
                            <th>Produk</th>
                            <th>Metode Bayar</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($transactions as $transaction): ?>
                        <tr>
                            <td>#<?php echo str_pad($transaction['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <strong><?php echo $transaction['customer_name']; ?></strong><br>
                                <small><?php echo $transaction['customer_email']; ?></small>
                            </td>
                            <td>
                                <?php 
                                $products = explode(',', $transaction['products']);
                                echo $products[0] . (count($products) > 1 ? ' +' . (count($products)-1) . ' lainnya' : '');
                                ?>
                                <br><small><?php echo $transaction['item_count']; ?> items</small>
                            </td>
                            <td>
                                <strong><?php echo $transaction['payment_method_name'] ?? 'Transfer Bank'; ?></strong>
                                <?php if($transaction['payment_fee'] > 0): ?>
                                    <br><small>+Rp <?php echo number_format($transaction['payment_fee'], 0, ',', '.'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></strong>
                                <div class="amount-breakdown">
                                    Subtotal: Rp <?php echo number_format($transaction['subtotal_amount'], 0, ',', '.'); ?><br>
                                    <?php if($transaction['payment_fee'] > 0): ?>
                                        Biaya: +Rp <?php echo number_format($transaction['payment_fee'], 0, ',', '.'); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($transaction['order_date'])); ?></td>
                            <td>
                                <a href="manage-orders.php?view=<?php echo $transaction['id']; ?>" class="btn">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>