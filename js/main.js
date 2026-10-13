// main.js - Initialize semua modul
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Harmoni Music Store Initializing...');
    
    try {
        // Initialize semua modul
        if (typeof initSmoothScroll === 'function') {
            initSmoothScroll();
        }
        
        if (typeof initCategoryFilter === 'function') {
            initCategoryFilter();
        }
        
        if (typeof initCart === 'function') {
            initCart();
        }
        
        // Mobile menu
        const mobileMenu = document.querySelector('.mobile-menu');
        const nav = document.querySelector('nav');
        if (mobileMenu && nav) {
            mobileMenu.addEventListener('click', function() {
                nav.classList.toggle('active');
            });
            
            // Close mobile menu when clicking links
            document.querySelectorAll('nav a').forEach(link => {
                link.addEventListener('click', () => {
                    nav.classList.remove('active');
                });
            });
        }
        
        console.log('✅ Harmoni Music Store initialized successfully!');
        
    } catch (error) {
        console.error('❌ Error during initialization:', error);
    }
});