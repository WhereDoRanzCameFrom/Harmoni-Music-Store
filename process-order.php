<?php
require_once 'config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['customer']) || !isset($input['items'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Data tidak lengkap'
        ]);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders 
            (customer_name, customer_email, customer_phone, customer_address, total_amount, notes, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            trim($input['customer']['customer_name']),
            trim($input['customer']['customer_email']),
            trim($input['customer']['customer_phone']),
            trim($input['customer']['customer_address']),
            floatval($input['total']),
            trim($input['customer']['notes'] ?? '')
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insert order items
        $stmt = $pdo->prepare("
            INSERT INTO order_items 
            (order_id, product_id, product_name, product_price, quantity) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($input['items'] as $item) {
            $stmt->execute([
                $orderId,
                intval($item['id']),
                trim($item['name']),
                floatval($item['price']),
                intval($item['quantity'])
            ]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'message' => 'Order berhasil dibuat!'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order Error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Method tidak diizinkan'
    ]);
}
?>