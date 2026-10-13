<?php include 'includes/header.php'; ?>

<!-- Order Section -->
<section class="order" id="order">
    <div class="container">
        <div class="section-title">
            <h2>Checkout Pesanan</h2>
        </div>
        
        <div class="order-container">
            <div class="order-summary">
                <h3>Ringkasan Pesanan</h3>
                <div class="order-items" id="order-items">
                    <!-- Item order akan dimuat via JavaScript -->
                </div>
                <div class="order-total">
                    <strong>Total: <span id="order-total">Rp 0</span></strong>
                </div>
            </div>
            
            <div class="customer-form">
                <h3>Data Pelanggan</h3>
                <form id="order-form" method="POST">
                    <div class="form-group">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="customer_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="customer_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>No. Telepon *</label>
                        <input type="tel" name="customer_phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Alamat Lengkap *</label>
                        <textarea name="customer_address" rows="3" required placeholder="Jl. Contoh No. 123, Kota, Kode Pos"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Catatan (Opsional)</label>
                        <textarea name="notes" rows="2" placeholder="Catatan untuk penjual..."></textarea>
                    </div>

                    <!-- INFO CEK STATUS ORDER -->
                    <div class="form-group" style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-top: 2rem;">
                        <h4><i class="fas fa-info-circle"></i> Info Penting</h4>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                            Setelah checkout, Anda bisa cek status pesanan di halaman 
                            <a href="order-status.php" style="color: var(--accent); font-weight: 600;">Cek Status Order</a>. 
                            Status akan diupdate oleh admin dan Anda akan bisa melihat perkembangannya.
                        </p>
                    </div>
                    
                    <button type="submit" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Konfirmasi Pesanan
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- CSS untuk Order Page -->
<style>
.order-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.order-summary, .customer-form {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: var(--shadow);
}

.order-items {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 1rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #eee;
}

.order-item:last-child {
    border-bottom: none;
}

.order-item img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 5px;
}

.order-item-details {
    flex: 1;
    margin-left: 1rem;
}

.order-item-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.order-item-price {
    color: var(--secondary);
    font-weight: 700;
}

.order-total {
    text-align: right;
    padding-top: 1rem;
    border-top: 2px solid var(--primary);
    font-size: 1.2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--primary);
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--accent);
}

@media (max-width: 768px) {
    .order-container {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- JavaScript untuk Order -->
<script>
// Load cart items ke order page
function loadOrderSummary() {
    const cart = JSON.parse(localStorage.getItem('harmoniCart')) || [];
    const orderItems = document.getElementById('order-items');
    const orderTotal = document.getElementById('order-total');
    
    let total = 0;
    orderItems.innerHTML = '';
    
    if (cart.length === 0) {
        orderItems.innerHTML = '<p>Keranjang belanja kosong.</p>';
        orderTotal.textContent = 'Rp 0';
        return;
    }
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        const orderItem = document.createElement('div');
        orderItem.className = 'order-item';
        orderItem.innerHTML = `
            <img src="${item.image}" alt="${item.name}">
            <div class="order-item-details">
                <div class="order-item-name">${item.name}</div>
                <div class="order-item-price">${formatRupiah(item.price)} x ${item.quantity}</div>
            </div>
            <div class="order-item-total">${formatRupiah(itemTotal)}</div>
        `;
        orderItems.appendChild(orderItem);
    });
    
    orderTotal.textContent = formatRupiah(total);
}

// Handle form submission
document.getElementById('order-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const cart = JSON.parse(localStorage.getItem('harmoniCart')) || [];
    if (cart.length === 0) {
        alert('Keranjang belanja kosong!');
        return;
    }
    
    const formData = new FormData(this);
    const orderData = {
        customer: Object.fromEntries(formData),
        items: cart,
        total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
    };
    
    console.log('Sending order data:', orderData);
    
    // Kirim data ke server
    fetch('process-order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
    console.log('Response data:', data);
    if (data.success) {
        alert(`Pesanan berhasil dikirim! No. Order: ${data.order_id}\n\nAnda bisa cek status order di halaman "Cek Status Order" dengan email: ${orderData.customer.customer_email}`);
        localStorage.removeItem('harmoniCart');
        window.location.href = 'order-status.php?email=' + encodeURIComponent(orderData.customer.customer_email);
    } else {
        alert('Error: ' + data.message);
    }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        alert('Terjadi kesalahan saat mengirim pesanan. Lihat console untuk detail.');
    });
});
</script>

<?php include 'includes/footer.php'; ?>