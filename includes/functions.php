<?php
// Fungsi utility untuk frontend

// Format Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Ambil semua produk dengan filter kategori
function getAllProducts($category = null) {
    global $pdo;
    
    try {
        if ($category && $category != 'all') {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY created_at DESC");
            $stmt->execute([$category]);
        } else {
            $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
        }
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting products: " . $e->getMessage());
        return [];
    }
}

// Ambil produk by ID
function getProductById($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting product: " . $e->getMessage());
        return null;
    }
}

// 🔥 FUNGSI BARU: Ambil semua metode pembayaran aktif
function getPaymentMethods() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT id, name, description, fee FROM payment_methods WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting payment methods: " . $e->getMessage());
        return [];
    }
}

// 🔥 FUNGSI BARU: Ambil metode pembayaran by ID
function getPaymentMethodById($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting payment method: " . $e->getMessage());
        return null;
    }
}

// 🔥 FUNGSI BARU: Tambah metode pembayaran baru (untuk admin)
function addPaymentMethod($name, $description, $fee = 0) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO payment_methods (name, description, fee) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $description, $fee]);
    } catch (PDOException $e) {
        error_log("Error adding payment method: " . $e->getMessage());
        return false;
    }
}

// 🔥 FUNGSI BARU: Update metode pembayaran (untuk admin)
function updatePaymentMethod($id, $name, $description, $fee, $is_active) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE payment_methods SET name = ?, description = ?, fee = ?, is_active = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $fee, $is_active, $id]);
    } catch (PDOException $e) {
        error_log("Error updating payment method: " . $e->getMessage());
        return false;
    }
}

// 🔥 FUNGSI BARU: Hapus metode pembayaran (soft delete)
function deletePaymentMethod($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE payment_methods SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error deleting payment method: " . $e->getMessage());
        return false;
    }
}

// Cek apakah admin logged in (untuk header)
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// 🔥 API ENDPOINT UNTUK PAYMENT METHODS
if (isset($_GET['action']) && $_GET['action'] == 'getPaymentMethods') {
    header('Content-Type: application/json');
    
    try {
        $methods = getPaymentMethods();
        echo json_encode([
            'success' => true,
            'data' => $methods
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengambil data metode pembayaran',
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// 🔥 API ENDPOINT UNTUK SINGLE PAYMENT METHOD
if (isset($_GET['action']) && $_GET['action'] == 'getPaymentMethod' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    try {
        $method = getPaymentMethodById($_GET['id']);
        if ($method) {
            echo json_encode([
                'success' => true,
                'data' => $method
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Metode pembayaran tidak ditemukan'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengambil data metode pembayaran',
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
?>