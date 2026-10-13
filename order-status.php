<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// Handle konfirmasi pesanan diterima
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_receipt'])) {
    $order_id = $_POST['order_id'];
    $customer_email = $_POST['customer_email'];
    
    // Update status order menjadi 'received'
    $stmt = $pdo->prepare("UPDATE orders SET status = 'received' WHERE id = ? AND customer_email = ?");
    $stmt->execute([$order_id, $customer_email]);
    
    $success_message = "Terima kasih! Pesanan telah dikonfirmasi sebagai diterima.";
}

// Ambil order berdasarkan email customer
$customer_email = $_GET['email'] ?? '';
$orders = [];

if (!empty($customer_email)) {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_items,
               GROUP_CONCAT(oi.product_name SEPARATOR ', ') as product_names
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.customer_email = ?
        GROUP BY o.id 
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$customer_email]);
    $orders = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Order - Harmoni Music Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .status-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        .search-form {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .order-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s ease;
        }
        .order-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-received { background: #d1ecf1; color: #0c5460; }
        .order-items {
            margin-top: 1rem;
        }
        .order-item {
            display: flex;
            justify-content: between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .notification-bell {
            position: fixed;
            top: 100px;
            right: 20px;
            background: #e74c3c;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: bounce 2s infinite;
        }
        .confirm-receipt-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 1rem;
        }
        .confirm-receipt-btn:hover {
            background: #219653;
            transform: translateY(-2px);
        }
        .confirm-receipt-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        .receipt-confirmed {
            background: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .status-timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 1.5rem 0;
        }
        .status-timeline::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 3px;
            background: #ecf0f1;
            z-index: 1;
        }
        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        .timeline-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ecf0f1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        .timeline-step.active .timeline-dot {
            background: #3498db;
            color: white;
        }
        .timeline-step.completed .timeline-dot {
            background: #27ae60;
            color: white;
        }
        .timeline-label {
            font-size: 0.8rem;
            text-align: center;
            color: #7f8c8d;
        }
        .timeline-step.active .timeline-label {
            color: #3498db;
            font-weight: 600;
        }
        .timeline-step.completed .timeline-label {
            color: #27ae60;
            font-weight: 600;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-10px);}
            60% {transform: translateY(-5px);}
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <?php 
    // 🔥 INCLUDE HEADER SETELAH SEMUA PHP CODE
    include 'includes/header.php'; 
    ?>

    <!-- Notification Bell -->
    <div class="notification-bell" onclick="scrollToStatus()">
        <i class="fas fa-bell"></i>
        <span class="notification-count" style="display: none;"></span>
    </div>

    <section class="hero" style="padding: 3rem 0; background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(44, 62, 80, 0.9));">
        <div class="container">
            <h2>Cek Status Pesanan Anda</h2>
            <p>Masukkan email anda untuk melihat status pesanan terbaru</p>
        </div>
    </section>

    <div class="container">
        <div class="status-container">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <div class="search-form">
                <h3><i class="fas fa-search"></i> Cari Pesanan</h3>
                <form method="GET" class="newsletter-form" style="margin-top: 1rem;">
                    <input type="email" name="email" placeholder="Masukkan email Anda" 
                           value="<?php echo htmlspecialchars($customer_email); ?>" required
                           class="newsletter-input">
                    <button type="submit" class="newsletter-btn">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </form>
            </div>

            <?php if (!empty($customer_email)): ?>
                <?php if (empty($orders)): ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>Tidak ada pesanan ditemukan</h3>
                        <p>Belum ada pesanan untuk email <?php echo htmlspecialchars($customer_email); ?></p>
                    </div>
                <?php else: ?>
                    <h3>Pesanan untuk <?php echo htmlspecialchars($customer_email); ?></h3>
                    
                    <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <h4>Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h4>
                                <small>Tanggal: <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></small>
                                <div style="margin-top: 0.5rem;">
                                    <small><strong>Metode Bayar:</strong> <?php echo $order['payment_method_name'] ?? 'Transfer Bank'; ?></small>
                                </div>
                            </div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php 
                                $status_text = [
                                    'pending' => 'Menunggu Konfirmasi',
                                    'processing' => 'Sedang Diproses',
                                    'completed' => 'Siap Diambil',
                                    'received' => 'Sudah Diterima',
                                    'cancelled' => 'Dibatalkan'
                                ];
                                echo $status_text[$order['status']] ?? ucfirst($order['status']);
                                ?>
                            </span>
                        </div>
                        
                        <div class="order-details">
                            <p><strong>Total:</strong> Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>
                            <p><strong>Jumlah Item:</strong> <?php echo $order['total_items']; ?> item</p>
                            <p><strong>Produk:</strong> <?php echo htmlspecialchars($order['product_names']); ?></p>
                            
                            <?php if (!empty($order['notes'])): ?>
                                <p><strong>Catatan:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Status Timeline -->
                        <div class="status-timeline">
                            <div class="timeline-step <?php echo in_array($order['status'], ['pending', 'processing', 'completed', 'received', 'cancelled']) ? 'completed' : ''; ?> <?php echo $order['status'] == 'pending' ? 'active' : ''; ?>">
                                <div class="timeline-dot">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="timeline-label">Pending</div>
                            </div>
                            <div class="timeline-step <?php echo in_array($order['status'], ['processing', 'completed', 'received']) ? 'completed' : ''; ?> <?php echo $order['status'] == 'processing' ? 'active' : ''; ?>">
                                <div class="timeline-dot">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="timeline-label">Processing</div>
                            </div>
                            <div class="timeline-step <?php echo in_array($order['status'], ['completed', 'received']) ? 'completed' : ''; ?> <?php echo $order['status'] == 'completed' ? 'active' : ''; ?>">
                                <div class="timeline-dot">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-label">Completed</div>
                            </div>
                            <div class="timeline-step <?php echo $order['status'] == 'received' ? 'completed' : ''; ?> <?php echo $order['status'] == 'received' ? 'active' : ''; ?>">
                                <div class="timeline-dot">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <div class="timeline-label">Received</div>
                            </div>
                        </div>

                        <!-- Tombol Konfirmasi Penerimaan -->
                        <?php if ($order['status'] == 'completed'): ?>
                            <form method="POST" onsubmit="return confirm('Apakah Anda yakin pesanan sudah diterima?')">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="customer_email" value="<?php echo $customer_email; ?>">
                                <button type="submit" name="confirm_receipt" class="confirm-receipt-btn">
                                    <i class="fas fa-check-circle"></i> Konfirmasi Pesanan Diterima
                                </button>
                                <div style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">
                                    <i class="fas fa-info-circle"></i> Klik tombol ini ketika Anda sudah menerima pesanan
                                </div>
                            </form>
                        <?php elseif ($order['status'] == 'received'): ?>
                            <div class="receipt-confirmed">
                                <i class="fas fa-check-circle"></i> Pesanan telah dikonfirmasi diterima
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function scrollToStatus() {
        document.querySelector('.status-container').scrollIntoView({ 
            behavior: 'smooth' 
        });
    }

    // Cek status otomatis jika ada parameter email
    <?php if (!empty($customer_email) && !empty($orders)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate the notification bell
        const bell = document.querySelector('.notification-bell');
        bell.style.background = '#2ecc71';
        bell.innerHTML = '<i class="fas fa-check"></i>';
        
        setTimeout(() => {
            bell.style.background = '#e74c3c';
            bell.innerHTML = '<i class="fas fa-bell"></i>';
        }, 3000);

        // Auto refresh status every 30 seconds
        setInterval(() => {
            window.location.reload();
        }, 30000);
    });
    <?php endif; ?>
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>