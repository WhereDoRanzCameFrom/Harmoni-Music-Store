<!-- Footer -->
<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-about">
                <div class="footer-logo">
                    <i class="fas fa-music"></i>
                    <h3>Harmoni Music Store</h3>
                </div>
                <p>Toko alat musik terpercaya untuk gitar akustik, bass, dan cajon.
                    Hubungi admin di sosial media untuk detail lebih lanjut.
                </p>
                <div class="social-links">
                    <a href="https://www.facebook.com/share/17JtYKbnGn/" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/ranggasaputraaaa_?igsh=MXF1MzVkN2R6eTd3cA%3D%3D&utm_source=qr" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.youtube.com/@Ranggasaputra-k4p" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <h4>Link Cepat</h4>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="index.php#products">Produk</a></li>
                    <li><a href="index.php#contact">Kontak</a></li>
                    <li><a href="about.php">Tentang Kami</a></li>
                    <li><a href="order-status.php">Cek Status</a></li>
                </ul>
            </div>
            
            <div class="footer-newsletter">
                <h4>Newsletter</h4>
                <p>Berlangganan newsletter untuk mendapatkan informasi terbaru tentang produk dan promo.</p>
                <form class="newsletter-form" method="POST">
                    <input type="email" name="email" class="newsletter-input" placeholder="Email Anda" required>
                    <button type="submit" class="newsletter-btn"><i class="fas fa-paper-plane"></i></button>
                </form>
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
                    $email = $_POST['email'];
                    // Simpan ke database atau file
                    echo '<p style="color: var(--success); margin-top: 10px;">Terima kasih telah berlangganan!</p>';
                }
                ?>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Harmoni Music Store. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<!-- Cart Modal -->
<div class="cart-modal">
    <div class="cart-header">
        <h3>Keranjang Belanja</h3>
        <button class="close-cart"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="cart-items">
        <!-- Item keranjang akan dimuat melalui JavaScript -->
    </div>
    
    <!-- 🔥 BAGIAN PAYMENT METHODS - DIPINDAHKAN KE SINI -->
    <div class="payment-section">
        <h3>Metode Pembayaran</h3>
        <div class="payment-methods">
            <!-- Payment methods akan di-render oleh JavaScript -->
        </div>
    </div>

    <!-- 🔥 BAGIAN CART SUMMARY - DIPINDAHKAN KE SINI -->
    <div class="cart-summary">
        <div class="summary-row">
            <span>Subtotal:</span>
            <span class="subtotal-price">Rp 0</span>
        </div>
        <div class="summary-row payment-fee-row" style="display: none;">
            <span>Biaya Pembayaran:</span>
            <span class="payment-fee">Rp 0</span>
        </div>
        <div class="summary-row total">
            <span>Total:</span>
            <span class="total-price">Rp 0</span>
        </div>
    </div>
    
    <!-- 🔥 BUTTON CHECKOUT YANG BENAR -->
    <div class="cart-footer" style="padding: 0 1.5rem 1.5rem;">
        <button class="checkout-btn" style="display: block; text-align: center; text-decoration: none; width: 100%;">
            <i class="fas fa-shopping-cart"></i> Checkout Sekarang
        </button>
    </div>
</div>

<div class="overlay"></div>

<!-- JavaScript Files -->
<script src="js/utils.js"></script>
<script src="js/products.js"></script>
<script src="js/cart.js"></script>
<script src="js/main.js"></script>
</body>
</html>