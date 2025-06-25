// Global variables
let cart = [];
let isCartOpen = false;

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    loadCartFromStorage();
    updateCartUI();
    setupEventListeners();
    setupSmoothScrolling();
});

// Setup Event Listeners
function setupEventListeners() {
    // Contact form submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactSubmit);
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('productModal');
        if (event.target === modal) {
            closeModal();
        }
    });
    
    // Close cart when clicking outside
    document.addEventListener('click', function(event) {
        const cartSidebar = document.getElementById('cartSidebar');
        const cartIcon = document.querySelector('.cart-icon');
        
        if (isCartOpen && !cartSidebar.contains(event.target) && !cartIcon.contains(event.target)) {
            toggleCart();
        }
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
            if (isCartOpen) {
                toggleCart();
            }
        }
    });
}

// Smooth scrolling for navigation links
function setupSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Cart Functions
function toggleCart() {
    const cartSidebar = document.getElementById('cartSidebar');
    isCartOpen = !isCartOpen;
    
    if (isCartOpen) {
        cartSidebar.classList.add('active');
        document.body.style.overflow = 'hidden';
    } else {
        cartSidebar.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function addToCart(id, name, price, image) {
    const existingItem = cart.find(item => item.id === id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: id,
            name: name,
            price: price,
            image: image,
            quantity: 1
        });
    }
    
    updateCartUI();
    saveCartToStorage();
    showNotification('Đã thêm sản phẩm vào giỏ hàng!', 'success');
    
    // Auto open cart briefly to show the addition
    if (!isCartOpen) {
        toggleCart();
        setTimeout(() => {
            if (isCartOpen) toggleCart();
        }, 2000);
    }
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    updateCartUI();
    saveCartToStorage();
    showNotification('Đã xóa sản phẩm khỏi giỏ hàng!', 'info');
}

function updateQuantity(id, change) {
    const item = cart.find(item => item.id === id);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(id);
        } else {
            updateCartUI();
            saveCartToStorage();
        }
    }
}

function updateCartUI() {
    const cartCount = document.getElementById('cartCount');
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    
    // Update cart count
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
    
    // Update cart items
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Giỏ hàng trống</p>
            </div>
        `;
    } else {
        cartItems.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="cart-item-info">
                    <h4>${item.name}</h4>
                    <div class="cart-item-price">${formatPrice(item.price)}đ</div>
                </div>
                <div class="cart-item-controls">
                    <button class="quantity-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                    <span class="quantity">${item.quantity}</span>
                    <button class="quantity-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                    <button class="remove-item" onclick="removeFromCart(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    // Update total
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    cartTotal.textContent = formatPrice(total) + 'đ';
}

function saveCartToStorage() {
    try {
        localStorage.setItem('food_store_cart', JSON.stringify(cart));
    } catch (error) {
        console.warn('Could not save cart to localStorage:', error);
    }
}

function loadCartFromStorage() {
    try {
        const savedCart = localStorage.getItem('food_store_cart');
        if (savedCart) {
            cart = JSON.parse(savedCart);
        }
    } catch (error) {
        console.warn('Could not load cart from localStorage:', error);
        cart = [];
    }
}

// Product Functions
function filterProducts(categoryId) {
    const products = document.querySelectorAll('.product-card');
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    // Update active button
    filterButtons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Show/hide products
    products.forEach(product => {
        if (categoryId === 'all' || product.dataset.category == categoryId) {
            product.style.display = 'block';
            product.style.animation = 'fadeInUp 0.5s ease';
        } else {
            product.style.display = 'none';
        }
    });
}

function filterByCategory(categoryId) {
    // Scroll to menu section
    document.getElementById('menu').scrollIntoView({ behavior: 'smooth' });
    
    // Wait for scroll then filter
    setTimeout(() => {
        const filterBtn = document.querySelector(`[onclick="filterProducts(${categoryId})"]`);
        if (filterBtn) {
            filterBtn.click();
        }
    }, 500);
}

function viewProduct(productId) {
    fetch('get-product.php?id=' + productId)
        .then(res => res.json())
        .then(product => {
            const modal = document.getElementById('productModal');
            const modalContent = document.getElementById('modalContent');

            const priceHTML = product.sale_price > 0
                ? `<span style="text-decoration: line-through; color: #999;">${product.price.toLocaleString()}đ</span>
                   <span style="color: #e74c3c; font-weight: bold; margin-left: 10px;">${product.sale_price.toLocaleString()}đ</span>`
                : `<span style="color: #333; font-weight: bold;">${product.price.toLocaleString()}đ</span>`;

            modalContent.innerHTML = `
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <h2 style="color: #e74c3c;">Chi tiết sản phẩm</h2>
                <div class="product-detail">
                    <div class="product-detail-image">
                        <img src="${product.image}" alt="${product.name}" style="width: 100%; border-radius: 10px;">
                    </div>
                    <div class="product-detail-info" style="margin-top: 1rem;">
                        <h3>${product.name}</h3>
                        <p>${product.description}</p>
                        <div class="product-detail-price" style="margin: 1rem 0; font-size: 1.5rem;">
                            ${priceHTML}
                        </div>
                        <button class="btn-primary" onclick="addToCart(${product.id}, '${product.name}', ${product.sale_price > 0 ? product.sale_price : product.price}, '${product.image}')">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ hàng
                        </button>
                    </div>
                </div>
            `;

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        })
        .catch(err => {
            alert("Không tải được chi tiết sản phẩm.");
            console.error(err);
        });
}

window.onclick = function(event) {
    const modal = document.getElementById("productModal");
    if (event.target == modal) {
        closeModal();
    }
};


function closeModal() {
    document.getElementById("productModal").style.display = "none";
}


// Checkout Function
function checkout() {
    if (cart.length === 0) {
        showNotification('Giỏ hàng trống!', 'warning');
        return;
    }
    
    // Check if user is logged in
    const isLoggedIn = document.querySelector('.user-menu') !== null;
    
    if (!isLoggedIn) {
        showNotification('Vui lòng đăng nhập để thanh toán!', 'warning');
        window.location.href = 'login.php';
        return;
    }
    
    // Create order data
    const orderData = {
        items: cart,
        total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
        timestamp: new Date().toISOString()
    };
    
    // Send to server (implement your checkout logic here)
    fetch('api/checkout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cart = [];
            updateCartUI();
            saveCartToStorage();
            toggleCart();
            showNotification('Đặt hàng thành công!', 'success');
            // Redirect to order confirmation page
            window.location.href = 'order-confirmation.php?order_id=' + data.order_id;
        } else {
            showNotification('Có lỗi xảy ra khi đặt hàng!', 'error');
        }
    })
    .catch(error => {
        console.error('Checkout error:', error);
        showNotification('Có lỗi xảy ra khi đặt hàng!', 'error');
    });
}

// Contact Form Handler
function handleContactSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const contactData = {
        name: formData.get('name'),
        email: formData.get('email'),
        message: formData.get('message')
    };
    
    // Send contact data to server
    fetch('api/contact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(contactData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.', 'success');
            event.target.reset();
        } else {
            showNotification('Có lỗi xảy ra khi gửi tin nhắn!', 'error');
        }
    })
    .catch(error => {
        console.error('Contact error:', error);
        showNotification('Có lỗi xảy ra khi gửi tin nhắn!', 'error');
    });
}

// Utility Functions
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Add notification styles if not already added
    if (!document.getElementById('notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 4000;
                max-width: 400px;
                padding: 1rem;
                border-radius: 8px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.2);
                animation: slideInRight 0.3s ease;
            }
            
            .notification-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            
            .notification-error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            
            .notification-warning {
                background: #fff3cd;
                color: #856404;
                border: 1px solid #ffeaa7;
            }
            
            .notification-info {
                background: #d1ecf1;
                color: #0c5460;
                border: 1px solid #bee5eb;
            }
            
            .notification-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .notification-close {
                background: none;
                border: none;
                font-size: 1.2rem;
                cursor: pointer;
                opacity: 0.7;
                margin-left: 1rem;
            }
            
            .notification-close:hover {
                opacity: 1;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Search functionality (if needed)
function searchProducts(query) {
    const products = document.querySelectorAll('.product-card');
    const searchQuery = query.toLowerCase();
    
    products.forEach(product => {
        const productName = product.querySelector('h3').textContent.toLowerCase();
        const productDescription = product.querySelector('.product-description').textContent.toLowerCase();
        
        if (productName.includes(searchQuery) || productDescription.includes(searchQuery)) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

// Loading state management
function showLoading() {
    const loading = document.createElement('div');
    loading.id = 'loading-overlay';
    loading.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Đang tải...</p>
        </div>
    `;
    
    // Add loading styles
    const styles = document.createElement('style');
    styles.textContent = `
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5000;
        }
        
        .loading-spinner {
            text-align: center;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #e74c3c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    
    document.head.appendChild(styles);
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.remove();
    }
}

// Lazy loading for images
function setupLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Initialize lazy loading when DOM is ready
document.addEventListener('DOMContentLoaded', setupLazyLoading);

// Handle window resize
window.addEventListener('resize', function() {
    // Close cart on mobile when rotating
    if (window.innerWidth > 768 && isCartOpen) {
        toggleCart();
    }
});

// Handle page visibility change
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        // Refresh cart when page becomes visible again
        updateCartUI();
    }
});