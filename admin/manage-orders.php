<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

// 🔥 DEBUG: Cek parameter view
if(isset($_GET['view'])) {
    error_log("DEBUG: View parameter received: " . $_GET['view']);
}

// 🔥 NOTIFIKASI: Cek jika ada konfirmasi penerimaan dari user
$user_receipt_notification = '';
if(isset($_GET['user_received']) && $_GET['user_received'] == 'true') {
    $user_receipt_notification = "
        <div class='alert alert-info' style='background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #17a2b8;'>
            <i class='fas fa-box-open'></i> 
            <strong>Notifikasi Customer:</strong> Pesanan #" . ($_GET['order_id'] ?? '') . " telah dikonfirmasi DITERIMA oleh customer!
        </div>
    ";
}

// Handle update status order
if(isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    // Get current status first
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $current_status = $stmt->fetchColumn();
    
    // Update status
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    // Insert status log
    $stmt = $pdo->prepare("INSERT INTO order_status_logs (order_id, old_status, new_status, updated_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$order_id, $current_status, $new_status]);
    
    // Get customer email for notification
    $stmt = $pdo->prepare("SELECT customer_email, customer_name FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order_data = $stmt->fetch();
    
    // Send notification (simulated)
    function sendStatusUpdateNotification($order_id, $customer_email, $customer_name, $new_status) {
        $status_messages = [
            'pending' => 'Pesanan Anda sedang menunggu konfirmasi',
            'processing' => 'Pesanan Anda sedang diproses',
            'completed' => 'Pesanan Anda telah selesai dan siap diambil/dikirim',
            'received' => 'Pesanan Anda telah dikonfirmasi diterima oleh customer',
            'cancelled' => 'Pesanan Anda telah dibatalkan'
        ];
        
        $subject = "Status Order Diupdate - Harmoni Music Store";
        $message = "
        <html>
        <head>
            <title>Status Order Diupdate</title>
        </head>
        <body>
            <h2>Halo $customer_name!</h2>
            <p>Status order Anda <strong>#" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . "</strong> telah diupdate:</p>
            <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>
                <strong style='color: #2c3e50;'>Status Baru: " . ucfirst($new_status) . "</strong>
                <p style='margin: 5px 0 0 0;'>" . ($status_messages[$new_status] ?? 'Status pesanan telah diperbarui') . "</p>
            </div>
            <p>Anda bisa cek detail order di: 
               <a href='http://localhost/harmoni-music-store/order-status.php?email=$customer_email' style='color: #3498db; font-weight: bold;'>
               Cek Status Order</a>
            </p>
            <br>
            <p>Salam,<br><strong>Harmoni Music Store</strong></p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Harmoni Music Store <noreply@harmonimusicstore.com>" . "\r\n";
        
        // Simulate email sending (save to log)
        file_put_contents('../email_status_log.txt', 
            "To: $customer_email\nSubject: $subject\nStatus: $new_status\n\n$message\n\n", 
            FILE_APPEND);
    }
    
    // Send notification
    sendStatusUpdateNotification($order_id, $order_data['customer_email'], $order_data['customer_name'], $new_status);
    
    $success = "Status order berhasil diupdate! Customer akan menerima notifikasi.";
}

// Handle hapus order
if(isset($_GET['delete_order'])) {
    try {
        $order_id = $_GET['delete_order'];
        
        // Hapus dari order_items dulu (karena foreign key)
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Hapus dari orders
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        
        // Hapus status logs
        $stmt = $pdo->prepare("DELETE FROM order_status_logs WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        $success = "Pesanan berhasil dihapus! Mood admin: DELETE MODE! 🗑️";
        
        // Redirect untuk hindari resubmit
        header('Location: manage-orders.php');
        exit;
        
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// 🔥 NOTIFIKASI: Auto check for user receipt confirmations (HANYA jika tidak ada parameter view)
if(!isset($_GET['view'])) {
    $stmt = $pdo->prepare("
        SELECT o.id, o.customer_name, o.order_date 
        FROM orders o 
        WHERE o.status = 'received' 
        AND o.order_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY o.order_date DESC
    ");
    $stmt->execute();
    $recent_received_orders = $stmt->fetchAll();

    // Jika ada pesanan yang baru dikonfirmasi received dalam 1 jam terakhir
    if(!empty($recent_received_orders) && !isset($_GET['user_received'])) {
        $latest_received = $recent_received_orders[0];
        header('Location: manage-orders.php?user_received=true&order_id=' . str_pad($latest_received['id'], 6, '0', STR_PAD_LEFT));
        exit;
    }
}

// Ambil semua orders
$orders = $pdo->query("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           SUM(oi.quantity) as total_items
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    GROUP BY o.id 
    ORDER BY 
        CASE 
            WHEN o.status = 'pending' THEN 1
            WHEN o.status = 'processing' THEN 2
            WHEN o.status = 'completed' THEN 3
            WHEN o.status = 'received' THEN 4
            WHEN o.status = 'cancelled' THEN 5
            ELSE 6
        END,
        o.order_date DESC
")->fetchAll();

// 🔥 PERBAIKAN: Ambil detail order jika ada parameter view
$orderDetails = null;
if(isset($_GET['view'])) {
    $order_id = $_GET['view'];
    
    // Debug log
    error_log("DEBUG: Fetching order details for ID: " . $order_id);
    
    try {
        // Ambil data order utama
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order_main = $stmt->fetch();
        
        if($order_main) {
            // Ambil items order
            $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll();
            
            // Gabungkan data
            $orderDetails = [];
            foreach($order_items as $item) {
                $orderDetails[] = array_merge($order_main, $item);
            }
            
            error_log("DEBUG: Order details found: " . count($orderDetails) . " items");
        } else {
            error_log("DEBUG: No order found with ID: " . $order_id);
        }
        
    } catch(PDOException $e) {
        error_log("ERROR: " . $e->getMessage());
        $error = "Error mengambil detail order: " . $e->getMessage();
    }
}

// 🔥 DEBUG: Log hasil
if(isset($_GET['view'])) {
    error_log("DEBUG: orderDetails is " . ($orderDetails ? "NOT null" : "null"));
    if($orderDetails) {
        error_log("DEBUG: First item order_id: " . $orderDetails[0]['order_id']);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Harmoni Music Store</title>
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
        .alert.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .orders-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .btn { background: #3498db; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #2ecc71; }
        .btn-success:hover { background: #27ae60; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #138496; }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-received { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .order-details { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .actions { display: flex; gap: 10px; }
        .mood-admin { background: #ff6b6b; color: white; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; animation: pulse 2s infinite; }
        .notification-info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #3498db; }
        .status-info { 
            background: #fff3cd; 
            color: #856404; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
            border-left: 4px solid #ffc107;
            font-size: 0.9rem;
        }
        .user-receipt-notification {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            animation: slideIn 0.5s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
<div class="sidebar">
        <h2><i class="fas fa-music"></i> Harmoni Admin</h2>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage-products.php"><i class="fas fa-guitar"></i> Kelola Produk</a></li>
            <li><a href="manage-orders.php" class="active"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a></li>
            <li><a href="manage-payments.php"><i class="fas fa-credit-card"></i> Kelola Pembayaran</a></li>
            <li><a href="manage-transactions.php"><i class="fas fa-receipt"></i> Riwayat Transaksi</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Kelola Pesanan</h1>
            <p>Lihat dan kelola pesanan dari pelanggan 
               <span class="mood-admin">ADMIN HARMONI </span>
            </p>
        </div>

        <!-- 🔥 NOTIFIKASI: Tampilkan notifikasi user receipt -->
        <?php echo $user_receipt_notification; ?>

        <?php if(isset($success)): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <?php if(isset($_POST['update_status'])): ?>
                    <div class="notification-info">
                        <i class="fas fa-bell"></i> 
                        <strong>Notifikasi terkirim!</strong> Customer akan melihat update status di halaman <strong>Cek Status Order</strong>.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- 🔥 DEBUG: Tampilkan pesan jika orderDetails kosong -->
        <?php if(isset($_GET['view']) && empty($orderDetails)): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Error:</strong> Tidak dapat menemukan detail order #<?php echo $_GET['view']; ?>
            </div>
        <?php endif; ?>

        <div class="orders-container">
            <h2>Daftar Pesanan <small style="color: #666;">- Admin 😎</small></h2>
            
            <?php if(empty($orders)): ?>
                <p>Belum ada pesanan. Santai dulu admin! ☕</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Order</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <strong><?php echo $order['customer_name']; ?></strong><br>
                                <small><?php echo $order['customer_email']; ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                            <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                            <td><?php echo $order['total_items']; ?> items</td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php 
                                    $status_text = [
                                        'pending' => 'Menunggu',
                                        'processing' => 'Diproses',
                                        'completed' => 'Selesai',
                                        'received' => '✅ Diterima',
                                        'cancelled' => 'Dibatalkan'
                                    ];
                                    echo $status_text[$order['status']] ?? ucfirst($order['status']);
                                    ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="?view=<?php echo $order['id']; ?>" class="btn">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                <?php if($order['status'] == 'received'): ?>
                                    <a href="?delete_order=<?php echo $order['id']; ?>" class="btn btn-danger" 
                                       onclick="return confirm('Pesanan sudah diterima customer. Yakin hapus pesanan #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>? \n\nPelanggan: <?php echo $order['customer_name']; ?>\nTotal: Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                <?php else: ?>
                                    <a href="?delete_order=<?php echo $order['id']; ?>" class="btn btn-danger" 
                                       onclick="return confirm('Yakin hapus pesanan #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>? \n\nPelanggan: <?php echo $order['customer_name']; ?>\nTotal: Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Detail Order -->
        <?php if($orderDetails && !empty($orderDetails)): ?>
        <div class="orders-container">
            <h2>Detail Order #<?php echo str_pad($orderDetails[0]['id'], 6, '0', STR_PAD_LEFT); ?></h2>
            
            <div class="order-details">
                <h3>Info Pelanggan</h3>
                <p><strong>Nama:</strong> <?php echo $orderDetails[0]['customer_name']; ?></p>
                <p><strong>Email:</strong> <?php echo $orderDetails[0]['customer_email']; ?></p>
                <p><strong>Telepon:</strong> <?php echo $orderDetails[0]['customer_phone']; ?></p>
                <p><strong>Alamat:</strong> <?php echo $orderDetails[0]['customer_address']; ?></p>
                <?php if($orderDetails[0]['notes']): ?>
                    <p><strong>Catatan:</strong> <?php echo $orderDetails[0]['notes']; ?></p>
                <?php endif; ?>
                
                <h3 style="margin-top: 20px;">Items Pesanan</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orderDetails as $item): ?>
                        <tr>
                            <td><?php echo $item['product_name']; ?></td>
                            <td>Rp <?php echo number_format($item['product_price'], 0, ',', '.'); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>Rp <?php echo number_format($item['product_price'] * $item['quantity'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                            <td><strong>Rp <?php echo number_format($orderDetails[0]['total_amount'], 0, ',', '.'); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
                
                <h3 style="margin-top: 20px;">Update Status</h3>
                <div class="notification-info">
                    <i class="fas fa-info-circle"></i> 
                    Setiap update status akan mengirim notifikasi ke customer melalui email dan mereka bisa melihat perubahan di halaman <strong>Cek Status Order</strong>.
                </div>

                <?php if($orderDetails[0]['status'] == 'received'): ?>
                    <div class="status-info">
                        <i class="fas fa-check-circle"></i> 
                        <strong>Pesanan sudah diterima customer.</strong> Anda bisa menghapus pesanan ini dari sistem.
                    </div>
                <?php endif; ?>

                <form method="POST" style="display: flex; gap: 10px; align-items: center; margin-top: 15px;">
                    <input type="hidden" name="order_id" value="<?php echo $orderDetails[0]['id']; ?>">
                    <select name="status" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                        <option value="pending" <?php echo $orderDetails[0]['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $orderDetails[0]['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="completed" <?php echo $orderDetails[0]['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="received" <?php echo $orderDetails[0]['status'] == 'received' ? 'selected' : ''; ?>>Received</option>
                        <option value="cancelled" <?php echo $orderDetails[0]['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-success">
                        <i class="fas fa-sync-alt"></i> Update Status & Kirim Notifikasi
                    </button>
                </form>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <a href="manage-orders.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                </a>
                <?php if($orderDetails[0]['status'] == 'received'): ?>
                    <a href="?delete_order=<?php echo $orderDetails[0]['id']; ?>" class="btn btn-danger" 
                       onclick="return confirm('Pesanan sudah diterima customer. Yakin hapus pesanan #<?php echo str_pad($orderDetails[0]['id'], 6, '0', STR_PAD_LEFT); ?>? \n\nPelanggan: <?php echo $orderDetails[0]['customer_name']; ?>\nTotal: Rp <?php echo number_format($orderDetails[0]['total_amount'], 0, ',', '.'); ?>')">
                        <i class="fas fa-trash"></i> Hapus Pesanan (Sudah Diterima)
                    </a>
                <?php else: ?>
                    <a href="?delete_order=<?php echo $orderDetails[0]['id']; ?>" class="btn btn-danger" 
                       onclick="return confirm('Yakin hapus pesanan #<?php echo str_pad($orderDetails[0]['id'], 6, '0', STR_PAD_LEFT); ?>? \n\nPelanggan: <?php echo $orderDetails[0]['customer_name']; ?>\nTotal: Rp <?php echo number_format($orderDetails[0]['total_amount'], 0, ',', '.'); ?>')">
                        <i class="fas fa-trash"></i> Hapus Pesanan
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    // Auto-hide notification after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const notification = document.querySelector('.user-receipt-notification');
        if (notification) {
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }, 5000);
        }
    });
    </script>
</body>
</html>