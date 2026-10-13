// products.js - Fungsi filter kategori produk

function initCategoryFilter() {
    console.log('Initializing category filter...');
    
    const categoryBtns = document.querySelectorAll('.category-btn');
    const productCards = document.querySelectorAll('.product-card');
    
    console.log('Category buttons found:', categoryBtns.length);
    console.log('Product cards found:', productCards.length);
    
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            categoryBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const selectedCategory = this.getAttribute('data-category');
            filterProductsByCategory(selectedCategory);
        });
    });
}

function filterProductsByCategory(category) {
    const productCards = document.querySelectorAll('.product-card');
    let visibleCount = 0;
    
    productCards.forEach(card => {
        const cardCategory = card.getAttribute('data-category');
        
        if (category === 'all' || cardCategory === category) {
            card.style.display = 'block';
            visibleCount++;
            card.style.animation = 'fadeInUp 0.5s ease';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show message if no products
    showNoProductsMessage(visibleCount === 0);
}

function showNoProductsMessage(show) {
    const productsGrid = document.querySelector('.products-grid');
    let message = productsGrid.querySelector('.no-products-message');
    
    if (show && !message) {
        message = document.createElement('p');
        message.className = 'no-products-message';
        message.textContent = 'Tidak ada produk dalam kategori ini.';
        message.style.cssText = `
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            color: #666;
            font-size: 1.2rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 2rem 0;
        `;
        productsGrid.appendChild(message);
    } else if (!show && message) {
        message.remove();
    }
}