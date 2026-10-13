<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

// DEBUG: Cek struktur tabel orders
try {
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("DEBUG - Columns in orders table: " . implode(', ', $columns));
    
    // Cek apakah order_date ada
    if (in_array('order_date', $columns)) {
        error_log("DEBUG - order_date column EXISTS");
        $date_column = 'order_date';
    } else {
        error_log("DEBUG - order_date column DOES NOT EXIST");
        // Cari kolom tanggal lainnya
        $date_columns = array_filter($columns, function($col) {
            return stripos($col, 'date') !== false || stripos($col, 'created') !== false || stripos($col, 'time') !== false;
        });
        error_log("DEBUG - Possible date columns: " . implode(', ', $date_columns));
        $date_column = !empty($date_columns) ? reset($date_columns) : 'id';
    }
    
} catch(PDOException $e) {
    error_log("DEBUG - Error checking table structure: " . $e->getMessage());
    $date_column = 'id'; // Fallback
}

// Set default date range (last 30 days)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? '';

// Build query for orders report - GUNAKAN created_at sebagai fallback
$where_conditions = [];
$params = [];

// Jika kolom tanggal ditemukan, gunakan filter tanggal
if ($date_column !== 'id' && $date_column !== 'order_date') {
    $where_conditions[] = "$date_column BETWEEN ? AND ?";
    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
    error_log("DEBUG - Using date column: " . $date_column);
} else {
    // Jika tidak ada kolom tanggal, tampilkan semua data
    $where_conditions[] = "1=1";
    error_log("DEBUG - No date column found, showing all data");
}

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

$where_clause = implode(" AND ", $where_conditions);

// Get orders report - QUERY YANG LEBIH SIMPLE DULU
$sql = "
    SELECT 
        o.id,
        o.customer_name,
        o.customer_email,
        o.customer_phone,
        o.customer_address,
        o.subtotal_amount,
        o.payment_fee,
        o.payment_method_name,
        o.total_amount,
        o.status,
        o.notes,
        COUNT(oi.id) as item_count,
        SUM(oi.quantity) as total_quantity,
        GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.quantity, 'x)') SEPARATOR ', ') as product_details
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE $where_clause
    GROUP BY o.id
    ORDER BY o.id DESC
    LIMIT 100
";

error_log("DEBUG - SQL Query: " . $sql);
error_log("DEBUG - Params: " . implode(', ', $params));

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    error_log("DEBUG - Found " . count($orders) . " orders");
} catch(PDOException $e) {
    error_log("DEBUG - Query Error: " . $e->getMessage());
    $orders = [];
    $error_message = "Error mengambil data: " . $e->getMessage();
}

// Calculate summary statistics (sederhana dulu)
$summary_sql = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as average_order_value
    FROM orders 
    WHERE $where_clause
";

try {
    $summary_stmt = $pdo->prepare($summary_sql);
    $summary_stmt->execute($params);
    $summary = $summary_stmt->fetch();
} catch(PDOException $e) {
    error_log("DEBUG - Summary Query Error: " . $e->getMessage());
    $summary = ['total_orders' => 0, 'total_revenue' => 0, 'average_order_value' => 0];
}

// Export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="laporan-order-' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='10' style='background:#f0f0f0;'>LAPORAN ORDER HARMONI MUSIC STORE</th></tr>";
    echo "<tr><th colspan='10'>Periode: $start_date s/d $end_date</th></tr>";
    echo "<tr>
            <th>No Order</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Telepon</th>
            <th>Produk</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Status</th>
            <th>Metode Bayar</th>
        </tr>";
    
    foreach ($orders as $order) {
        echo "<tr>
                <td>#" . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . "</td>
                <td>" . htmlspecialchars($order['customer_name']) . "</td>
                <td>" . htmlspecialchars($order['customer_email']) . "</td>
                <td>" . htmlspecialchars($order['customer_phone']) . "</td>
                <td>" . htmlspecialchars($order['product_details']) . "</td>
                <td>" . $order['total_quantity'] . "</td>
                <td>Rp " . number_format($order['total_amount'], 0, ',', '.') . "</td>
                <td>" . $order['status'] . "</td>
                <td>" . ($order['payment_method_name'] ?? 'Transfer Bank') . "</td>
            </tr>";
    }
    
    echo "<tr><td colspan='10' style='background:#f9f9f9;'>&nbsp;</td></tr>";
    echo "<tr><td colspan='6'><strong>SUMMARY</strong></td>
          <td><strong>Rp " . number_format($summary['total_revenue'] ?? 0, 0, ',', '.') . "</strong></td>
          <td><strong>" . $summary['total_orders'] . " Orders</strong></td>
          <td colspan='2'></td></tr>";
    
    echo "</table>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Harmoni Music Store</title>
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
        .report-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .filters { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .filter-form { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end; }
        .form-group { margin-bottom: 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219653; }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .summary-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-left: 4px solid #3498db; }
        .summary-card.revenue { border-left-color: #27ae60; }
        .summary-card.avg { border-left-color: #e74c3c; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #2c3e50; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; font-weight: 600; }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-received { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .export-section { text-align: right; margin-bottom: 20px; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .table-container { overflow-x: auto; }
        .debug-info { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 0.9em; }
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
            <li><a href="manage-transactions.php"><i class="fas fa-receipt"></i> Riwayat Transaksi</a></li>
            <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> Laporan Penjualan</h1>
            <p>Analisis data penjualan dan performa toko harmoni</p>
        </div>

        <div class="report-container">
            <?php if(isset($error_message)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="filters">
                <h3><i class="fas fa-filter"></i> Filter Laporan</h3>
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label>Tanggal Mulai:</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Akhir:</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="form-group">
                        <label>Status Order:</label>
                        <select name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="received" <?php echo $status_filter == 'received' ? 'selected' : ''; ?>>Received</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3><i class="fas fa-shopping-cart"></i> Total Orders</h3>
                    <div class="stat-number"><?php echo $summary['total_orders'] ?? 0; ?></div>
                    <small>Data ditemukan</small>
                </div>
                <div class="summary-card revenue">
                    <h3><i class="fas fa-money-bill-wave"></i> Total Revenue</h3>
                    <div class="stat-number">Rp <?php echo number_format($summary['total_revenue'] ?? 0, 0, ',', '.'); ?></div>
                    <small>Pendapatan kotor</small>
                </div>
                <div class="summary-card avg">
                    <h3><i class="fas fa-chart-line"></i> Rata-rata Order</h3>
                    <div class="stat-number">Rp <?php echo number_format($summary['average_order_value'] ?? 0, 0, ',', '.'); ?></div>
                    <small>Nilai per order</small>
                </div>
            </div>

            <!-- Export Button -->
            <div class="export-section">
                <a href="?<?php echo http_build_query($_GET); ?>&export=excel" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export ke Excel
                </a>
            </div>

            <!-- Orders Table -->
            <h3>Detail Orders (<?php echo count($orders); ?> orders ditemukan)</h3>
            
            <?php if(empty($orders)): ?>
                <div class="alert info">
                    <i class="fas fa-info-circle"></i> Tidak ada data order yang ditemukan.
                    <?php if($date_column === 'id'): ?>
                        <br><small>Note: Filter tanggal tidak tersedia karena kolom tanggal tidak ditemukan di database.</small>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Order</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Metode Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                <td title="<?php echo htmlspecialchars($order['product_details']); ?>">
                                    <?php 
                                    $products = explode(', ', $order['product_details']);
                                    echo count($products) . ' produk';
                                    ?>
                                </td>
                                <td><?php echo $order['total_quantity']; ?></td>
                                <td>
                                    <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong>
                                    <?php if($order['payment_fee'] > 0): ?>
                                        <br><small>+Rp <?php echo number_format($order['payment_fee'], 0, ',', '.'); ?> biaya</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php 
                                        $status_text = [
                                            'pending' => 'Pending',
                                            'processing' => 'Processing', 
                                            'completed' => 'Completed',
                                            'received' => 'Received',
                                            'cancelled' => 'Cancelled'
                                        ];
                                        echo $status_text[$order['status']] ?? ucfirst($order['status']);
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo $order['payment_method_name'] ?? 'Transfer Bank'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>