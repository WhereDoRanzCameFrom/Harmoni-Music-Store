<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

// Gunakan folder images yang sudah ada
$uploadDir = '../images/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$success = $error = '';

// Handle tambah/update produk
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $image_type = $_POST['image_type'];
        
        // Handle gambar
        $image_path = '';
        
        if ($image_type === 'upload' && isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === 0) {
            // Upload gambar ke folder images
            $file = $_FILES['image_upload'];
            $fileName = time() . '_' . basename($file['name']);
            $targetPath = $uploadDir . $fileName;
            
            // Validasi file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Format file tidak didukung. Gunakan JPG, PNG, atau GIF.');
            }
            
            if ($file['size'] > $maxSize) {
                throw new Exception('Ukuran file terlalu besar. Maksimal 2MB.');
            }
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $image_path = 'images/' . $fileName; // Path untuk database
            } else {
                throw new Exception('Gagal upload gambar.');
            }
            
        } elseif ($image_type === 'url' && !empty($_POST['image_url'])) {
            // Gunakan URL external
            $image_path = $_POST['image_url'];
        } else {
            throw new Exception('Pilih salah satu: upload gambar atau masukkan URL.');
        }
        
        if(isset($_POST['add_product'])) {
            // Tambah produk baru
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, description, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $price, $description, $image_path]);
            $success = "Produk berhasil ditambahkan!";
            
        } elseif(isset($_POST['update_product'])) {
            // Update produk existing
            $product_id = $_POST['product_id'];
            
            // Jika edit produk & upload gambar baru, hapus gambar lama jika ada
            if (!empty($image_path) && $image_type === 'upload') {
                $oldProduct = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                $oldProduct->execute([$product_id]);
                $oldImage = $oldProduct->fetchColumn();
                
                // Hapus file lama jika ada di folder images (bukan URL external)
                if ($oldImage && strpos($oldImage, 'images/') === 0 && file_exists('../' . $oldImage)) {
                    unlink('../' . $oldImage);
                }
            }
            
            $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, price = ?, description = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $category, $price, $description, $image_path, $product_id]);
            $success = "Produk berhasil diupdate!";
        }
        
    } catch(PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle hapus produk (tambahkan hapus gambar)
if(isset($_GET['delete'])) {
    try {
        // Ambil data produk sebelum hapus
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $productImage = $stmt->fetchColumn();
        
        // Hapus gambar dari folder images jika ada
        if ($productImage && strpos($productImage, 'images/') === 0 && file_exists('../' . $productImage)) {
            unlink('../' . $productImage);
        }
        
        // Hapus dari database
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = "Produk berhasil dihapus!";
        
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil semua produk
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();

// Ambil data produk untuk edit
$edit_product = null;
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_product = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Harmoni Music Store</title>
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
        .form-container, .products-container { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #2ecc71; }
        .btn-success:hover { background: #27ae60; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        .actions { display: flex; gap: 10px; }
        .radio-group { display: flex; gap: 15px; margin-bottom: 10px; }
        .radio-group label { display: flex; align-items: center; cursor: pointer; }
        .radio-group input { width: auto; margin-right: 5px; }
        .section-toggle { display: none; }
        .section-toggle.active { display: block; }
        .image-preview img { max-width: 200px; max-height: 150px; border-radius: 5px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-music"></i> Harmoni Admin</h2>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage-products.php" class="active"><i class="fas fa-guitar"></i> Kelola Produk</a></li>
            <li><a href="manage-orders.php"><i class="fas fa-shopping-cart"></i> Kelola Pesanan</a></li>
            <li><a href="manage-payments.php"><i class="fas fa-credit-card"></i> Kelola Pembayaran</a></li>
            <li><a href="manage-transactions.php"><i class="fas fa-receipt"></i> Riwayat Transaksi</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Kelola Produk</h1>
            <p>Tambahkan, edit, atau hapus produk toko</p>
        </div>

        <?php if($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Form Tambah/Edit Produk -->
        <div class="form-container">
            <h2><?php echo $edit_product ? 'Edit Produk' : 'Tambah Produk Baru'; ?></h2>
            
            <!-- UBAH FORM MENJADI MULTIPART -->
            <form method="POST" enctype="multipart/form-data">
                <?php if($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Nama Produk</label>
                    <input type="text" name="name" value="<?php echo $edit_product['name'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category" required>
                        <option value="gitar" <?php echo ($edit_product['category'] ?? '') == 'gitar' ? 'selected' : ''; ?>>Gitar</option>
                        <option value="bass" <?php echo ($edit_product['category'] ?? '') == 'bass' ? 'selected' : ''; ?>>Bass</option>
                        <option value="cajon" <?php echo ($edit_product['category'] ?? '') == 'cajon' ? 'selected' : ''; ?>>Cajon</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="price" value="<?php echo $edit_product['price'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3" required><?php echo $edit_product['description'] ?? ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Gambar Produk</label>
                    
                    <!-- Pilihan: Upload atau URL -->
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="image_type" value="upload" checked> Upload Gambar
                        </label>
                        <label>
                            <input type="radio" name="image_type" value="url"> URL Gambar
                        </label>
                    </div>
                    
                    <!-- Input Upload -->
                    <div id="upload-section" class="section-toggle active">
                        <input type="file" name="image_upload" accept="image/*" style="margin-bottom: 5px;">
                        <small style="color: #666;">Format: JPG, PNG, GIF, WebP (Max 2MB)</small>
                    </div>
                    
                    <!-- Input URL -->
                    <div id="url-section" class="section-toggle">
                        <input type="url" name="image_url" placeholder="https://example.com/image.jpg" 
                               value="<?php echo (isset($edit_product['image']) && filter_var($edit_product['image'], FILTER_VALIDATE_URL)) ? $edit_product['image'] : ''; ?>">
                    </div>
                    
                    <!-- Preview Gambar -->
                    <div id="image-preview" class="image-preview">
                        <?php if(isset($edit_product['image'])): ?>
                            <img src="<?php echo $edit_product['image']; ?>" alt="Preview">
                            <div style="font-size: 0.8rem; color: #666; margin-top: 5px;">
                                Gambar saat ini
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if($edit_product): ?>
                    <button type="submit" name="update_product" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Produk
                    </button>
                    <a href="manage-products.php" class="btn">Batal</a>
                <?php else: ?>
                    <button type="submit" name="add_product" class="btn">
                        <i class="fas fa-plus"></i> Tambah Produk
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Daftar Produk -->
        <div class="products-container">
            <h2>Daftar Produk</h2>
            <?php if(empty($products)): ?>
                <p>Belum ada produk.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $product): ?>
                        <tr>
                            <td><img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-img"></td>
                            <td>
                                <strong><?php echo $product['name']; ?></strong>
                                <div style="font-size: 0.9em; color: #666;"><?php echo substr($product['description'], 0, 50); ?>...</div>
                            </td>
                            <td><?php echo ucfirst($product['category']); ?></td>
                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                            <td class="actions">
                                <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-danger" 
                                   onclick="return confirm('Yakin hapus produk <?php echo $product['name']; ?>?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Toggle antara upload dan URL
    document.addEventListener('DOMContentLoaded', function() {
        const uploadRadio = document.querySelector('input[value="upload"]');
        const urlRadio = document.querySelector('input[value="url"]');
        const uploadSection = document.getElementById('upload-section');
        const urlSection = document.getElementById('url-section');
        
        function toggleSections() {
            if (uploadRadio.checked) {
                uploadSection.classList.add('active');
                urlSection.classList.remove('active');
            } else {
                uploadSection.classList.remove('active');
                urlSection.classList.add('active');
            }
        }
        
        uploadRadio.addEventListener('change', toggleSections);
        urlRadio.addEventListener('change', toggleSections);
        
        // Preview gambar saat upload
        const fileInput = document.querySelector('input[name="image_upload"]');
        const preview = document.getElementById('image-preview');
        
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                reader.readAsDataURL(file);
            }
        });
    });
    </script>
</body>
</html>