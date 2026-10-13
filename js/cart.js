// cart.js - Enhanced cart system with payment methods

class CartManager {
    constructor() {
        this.cart = [];
        this.paymentMethods = [];
        this.selectedPaymentMethod = null;
        this.init();
    }
    
    init() {
        this.loadCart();
        this.loadPaymentMethods();
        this.bindEvents();
        this.initNotification();
    }
    
    initNotification() {
        // Buat element notifikasi jika belum ada
        if (!document.querySelector('.notification')) {
            const notification = document.createElement('div');
            notification.className = 'notification';
            document.body.appendChild(notification);
        }
    }
    
    // 🔥 UBAH KE METHOD BIASA (BUKAN showNotification)
    showCartNotification(message, type = 'success') {
        const notification = document.querySelector('.notification');
        if (!notification) {
            console.log(`${type}: ${message}`);
            return;
        }
        
        notification.textContent = message;
        notification.className = `notification ${type} show`;
        
        // Auto hide setelah 3 detik
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }
    
    loadCart() {
        const savedCart = localStorage.getItem('harmoniCart');
        this.cart = savedCart ? JSON.parse(savedCart) : [];
        this.updateCartDisplay();
    }
    
    saveCart() {
        localStorage.setItem('harmoniCart', JSON.stringify(this.cart));
    }
    
    async loadPaymentMethods() {
        try {
            const response = await fetch('includes/functions.php?action=getPaymentMethods');
            const methods = await response.json();
            
            if (methods.success) {
                this.paymentMethods = methods.data;
                this.renderPaymentMethods();
            } else {
                console.error('Failed to load payment methods');
                // Fallback methods
                this.paymentMethods = [
                    { id: 1, name: 'Transfer Bank', description: 'Transfer ke rekening BCA, BNI, atau Mandiri', fee: 0 },
                    { id: 2, name: 'E-Wallet', description: 'OVO, Gopay, Dana, atau ShopeePay', fee: 0 },
                    { id: 3, name: 'COD', description: 'Bayar di tempat saat barang diterima', fee: 5000 }
                ];
                this.renderPaymentMethods();
            }
        } catch (error) {
            console.error('Error loading payment methods:', error);
            // Fallback methods
            this.paymentMethods = [
                { id: 1, name: 'Transfer Bank', description: 'Transfer ke rekening BCA, BNI, atau Mandiri', fee: 0 },
                { id: 2, name: 'E-Wallet', description: 'OVO, Gopay, Dana, atau ShopeePay', fee: 0 },
                { id: 3, name: 'COD', description: 'Bayar di tempat saat barang diterima', fee: 5000 }
            ];
            this.renderPaymentMethods();
        }
    }
    
    renderPaymentMethods() {
        const paymentContainer = document.querySelector('.payment-methods');
        if (!paymentContainer) return;
        
        paymentContainer.innerHTML = '';
        
        this.paymentMethods.forEach(method => {
            const paymentMethod = document.createElement('div');
            paymentMethod.className = 'payment-method';
            paymentMethod.innerHTML = `
                <input type="radio" name="payment_method" id="payment_${method.id}" value="${method.id}" 
                       data-fee="${method.fee}" ${this.selectedPaymentMethod === method.id ? 'checked' : ''}>
                <label for="payment_${method.id}">
                    <div class="payment-info">
                        <div class="payment-name">${method.name}</div>
                        <div class="payment-desc">${method.description}</div>
                        ${method.fee > 0 ? `<div class="payment-fee">Biaya: ${formatRupiah(method.fee)}</div>` : ''}
                    </div>
                </label>
            `;
            paymentContainer.appendChild(paymentMethod);
        });
        
        this.bindPaymentEvents();
        this.updateTotalWithPayment();
    }
    
    bindPaymentEvents() {
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.selectedPaymentMethod = parseInt(e.target.value);
                const fee = parseInt(e.target.getAttribute('data-fee'));
                this.updateTotalWithPayment(fee);
                
                // Notifikasi untuk perubahan payment method
                const methodName = e.target.closest('.payment-method').querySelector('.payment-name').textContent;
                this.showCartNotification(`Metode pembayaran: ${methodName}`, 'info');
            });
        });
    }
    
    bindEvents() {
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart')) {
                this.addToCart(e.target.closest('.add-to-cart'));
            }
        });
        
        // Cart modal events
        const cartIcon = document.querySelector('.cart-icon');
        const closeCart = document.querySelector('.close-cart');
        const overlay = document.querySelector('.overlay');
        const checkoutBtn = document.querySelector('.checkout-btn');
        
        if (cartIcon) cartIcon.addEventListener('click', () => this.openCartModal());
        if (closeCart) closeCart.addEventListener('click', () => this.closeCartModal());
        if (overlay) overlay.addEventListener('click', () => this.closeCartModal());
        if (checkoutBtn) checkoutBtn.addEventListener('click', () => this.checkout());
    }
    
    addToCart(button) {
        const productId = parseInt(button.getAttribute('data-id'));
        const productName = button.getAttribute('data-name');
        const productPrice = parseInt(button.getAttribute('data-price'));
        const productImage = button.getAttribute('data-image');
        
        const existingItem = this.cart.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.cart.push({
                id: productId,
                name: productName,
                price: productPrice,
                image: productImage,
                quantity: 1
            });
        }
        
        this.saveCart();
        this.updateCartDisplay();
        this.showCartNotification(`${productName} ditambahkan ke keranjang!`);
    }
    
    updateCartDisplay() {
        const cartItems = document.querySelector('.cart-items');
        const cartCount = document.querySelector('.cart-count');
        const subtotalElement = document.querySelector('.subtotal-price');
        const totalPrice = document.querySelector('.total-price');
        
        if (!cartItems || !cartCount || !subtotalElement || !totalPrice) return;
        
        cartItems.innerHTML = '';
        
        let subtotal = 0;
        let count = 0;
        
        this.cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            count += item.quantity;
            
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            cartItem.innerHTML = `
                <div class="cart-item-img">
                    <img src="${item.image}" alt="${item.name}" loading="lazy">
                </div>
                <div class="cart-item-details">
                    <h4 class="cart-item-name">${item.name}</h4>
                    <div class="cart-item-price">${formatRupiah(item.price)}</div>
                    <div class="cart-item-actions">
                        <button class="quantity-btn minus" data-id="${item.id}">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="quantity-btn plus" data-id="${item.id}">+</button>
                        <button class="remove-item" data-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            cartItems.appendChild(cartItem);
        });
        
        cartCount.textContent = count;
        subtotalElement.textContent = formatRupiah(subtotal);
        
        // Update total dengan payment method
        this.updateTotalWithPayment();
        
        // Bind dynamic events
        this.bindCartItemEvents();
    }
    
    updateTotalWithPayment(paymentFee = null) {
        const subtotalElement = document.querySelector('.subtotal-price');
        const paymentFeeElement = document.querySelector('.payment-fee');
        const paymentFeeRow = document.querySelector('.payment-fee-row');
        const totalPrice = document.querySelector('.total-price');
        
        if (!subtotalElement || !totalPrice) return;
        
        let subtotal = 0;
        this.cart.forEach(item => {
            subtotal += item.price * item.quantity;
        });
        
        // Calculate payment fee
        let fee = 0;
        if (paymentFee !== null) {
            fee = paymentFee;
        } else if (this.selectedPaymentMethod) {
            const selectedMethod = this.paymentMethods.find(m => m.id === this.selectedPaymentMethod);
            fee = selectedMethod ? selectedMethod.fee : 0;
        }
        
        const total = subtotal + fee;
        
        // Update display
        subtotalElement.textContent = formatRupiah(subtotal);
        
        // FIX: TAMPILKAN/SEMBUNYIKAN ROW BIAYA
        if (paymentFeeRow && paymentFeeElement) {
            if (fee > 0) {
                paymentFeeElement.textContent = formatRupiah(fee);
                paymentFeeRow.style.display = 'flex';
            } else {
                paymentFeeRow.style.display = 'none';
            }
        }
        
        totalPrice.textContent = formatRupiah(total);
    }
    
    bindCartItemEvents() {
        document.querySelectorAll('.quantity-btn.minus').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = parseInt(e.target.closest('.quantity-btn').getAttribute('data-id'));
                this.updateQuantity(productId, -1);
            });
        });
        
        document.querySelectorAll('.quantity-btn.plus').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = parseInt(e.target.closest('.quantity-btn').getAttribute('data-id'));
                this.updateQuantity(productId, 1);
            });
        });
        
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = parseInt(e.target.closest('.remove-item').getAttribute('data-id'));
                this.removeFromCart(productId);
            });
        });
    }
    
    updateQuantity(productId, change) {
        const item = this.cart.find(item => item.id === productId);
        
        if (item) {
            item.quantity += change;
            
            if (item.quantity <= 0) {
                this.removeFromCart(productId);
            } else {
                this.saveCart();
                this.updateCartDisplay();
                this.showCartNotification(`Jumlah ${item.name} diupdate: ${item.quantity}`, 'info');
            }
        }
    }
    
    removeFromCart(productId) {
        const item = this.cart.find(item => item.id === productId);
        this.cart = this.cart.filter(item => item.id !== productId);
        this.saveCart();
        this.updateCartDisplay();
        this.showCartNotification(`${item.name} dihapus dari keranjang`, 'warning');
    }
    
    openCartModal() {
        const cartModal = document.querySelector('.cart-modal');
        const overlay = document.querySelector('.overlay');
        
        if (cartModal && overlay) {
            cartModal.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Load payment methods setiap kali modal dibuka
            this.loadPaymentMethods();
        }
    }
    
    closeCartModal() {
        const cartModal = document.querySelector('.cart-modal');
        const overlay = document.querySelector('.overlay');
        
        if (cartModal && overlay) {
            cartModal.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }
    
    checkout() {
        if (this.cart.length === 0) {
            this.showCartNotification('Keranjang belanja Anda kosong!', 'error');
            return;
        }
        
        if (!this.selectedPaymentMethod) {
            this.showCartNotification('Pilih metode pembayaran terlebih dahulu!', 'error');
            return;
        }
        
        // Simpan data keranjang dan metode pembayaran ke localStorage untuk proses order
        const checkoutData = {
            cart: this.cart,
            payment_method: this.selectedPaymentMethod,
            payment_fee: this.getSelectedPaymentFee(),
            total: this.calculateTotal()
        };
        
        localStorage.setItem('checkoutData', JSON.stringify(checkoutData));
        
        this.closeCartModal();
        window.location.href = 'orders.php';
    }
    
    getSelectedPaymentFee() {
        if (!this.selectedPaymentMethod) return 0;
        const method = this.paymentMethods.find(m => m.id === this.selectedPaymentMethod);
        return method ? method.fee : 0;
    }
    
    calculateTotal() {
        let subtotal = 0;
        this.cart.forEach(item => {
            subtotal += item.price * item.quantity;
        });
        return subtotal + this.getSelectedPaymentFee();
    }
    
    getCartData() {
        return this.cart;
    }
    
    getPaymentData() {
        return {
            payment_method: this.selectedPaymentMethod,
            payment_fee: this.getSelectedPaymentFee()
        };
    }
    
    clearCart() {
        this.cart = [];
        this.selectedPaymentMethod = null;
        this.saveCart();
        this.updateCartDisplay();
        this.showCartNotification('Keranjang berhasil dikosongkan', 'info');
    }
}

// Global cart instance
let cartManager;

// Initialize cart
function initCart() {
    cartManager = new CartManager();
}

// Format Rupiah helper function
function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// 🔥 BUAT FUNGSI GLOBAL UNTUK NOTIFICATION (backup)
function showNotification(message, type = 'success') {
    // Coba pakai method dari cartManager dulu
    if (window.cartManager && typeof window.cartManager.showCartNotification === 'function') {
        window.cartManager.showCartNotification(message, type);
        return;
    }
    
    // Fallback: buat notifikasi manual
    let notification = document.querySelector('.notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    notification.textContent = message;
    notification.className = `notification ${type} show`;
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
    
    console.log(`${type}: ${message}`);
}