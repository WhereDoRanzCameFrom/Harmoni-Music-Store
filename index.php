<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero" id="home">
    <div class="container">
        <h2>Temukan Suara & Bakat Anda</h2>
        <p>Harmoni Music Store menyediakan berbagai alat musik berkualitas tinggi untuk musisi profesional dan pemula. Temukan gitar akustik, bass, dan cajon terbaik dengan harga terjangkau.</p>
        <a href="#products" class="btn">Jelajahi Produk</a>
    </div>
</section>

<!-- Produk Section -->
<section class="products" id="products">
    <div class="container">
        <div class="section-title">
            <h2>Produk Kami</h2>
        </div>
        
        <div class="category-filter">
            <button class="category-btn active" data-category="all">Semua</button>
            <button class="category-btn" data-category="gitar">Gitar Akustik</button>
            <button class="category-btn" data-category="bass">Bass Akustik</button>
            <button class="category-btn" data-category="cajon">Cajon</button>
        </div>
        
        <div class="products-grid">
            <?php
            // Ambil semua produk dari database
            $products = getAllProducts();
            
            if (empty($products)) {
                echo '<p class="no-products">Belum ada produk yang tersedia.</p>';
            } else {
                foreach ($products as $product) {
                    echo '
                    <div class="product-card" data-category="' . $product['category'] . '">
                        <div class="product-img">
                            <img src="' . $product['image'] . '" alt="' . $product['name'] . '" loading="lazy">
                        </div>
                        <div class="product-info">
                            <div class="product-category">' . ucfirst($product['category']) . '</div>
                            <h3 class="product-name">' . $product['name'] . '</h3>
                            <p class="product-description">' . $product['description'] . '</p>
                            <div class="product-price">' . formatRupiah($product['price']) . '</div>
                            <button class="add-to-cart" 
                                data-id="' . $product['id'] . '" 
                                data-name="' . htmlspecialchars($product['name']) . '" 
                                data-price="' . $product['price'] . '" 
                                data-image="' . $product['image'] . '">
                                <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                            </button>
                        </div>
                    </div>';
                }
            }
            ?>
        </div>
    </div>
</section>

<!-- Kontak & Lokasi -->
<section class="contact" id="contact">
    <div class="container">
        <div class="section-title">
            <h2>Kontak & Lokasi</h2>
        </div>
        
        <div class="contact-grid">
            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <h3>Alamat</h3>
                        <p>Jl. Gn Selatan Gg. Slamet, Tarakan Tengah, Kalimantan Utara, Indonesia</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div>
                        <h3>Telepon</h3>
                        <p>+6282112345678</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <h3>Email</h3>
                        <p>harmonimusicstore@gmail.com</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h3>Jam Operasional</h3>
                        <p>Senin - Jumat: 09:00 - 20:00<br>Sabtu - Minggu: 10:00 - 18:00</p>
                    </div>
                </div>
            </div>
            
            <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3983.0370168662826!2d117.5710939747311!3d3.3410208966337414!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32147598764034c7%3A0xe2591196e53249aa!2sWoles_second!5e0!3m2!1sid!2sid!4v1761323206269!5m2!1sid!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>