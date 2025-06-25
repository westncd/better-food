<?php
session_start();
require_once 'config/database.php';

// Lấy danh sách món ăn từ database
$stmt = $conn->prepare("SELECT * FROM products WHERE status = 1 ORDER BY created_at DESC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh mục
$stmt_cat = $conn->prepare("SELECT * FROM categories WHERE status = 1");
$stmt_cat->execute();
$categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Store - Cửa hàng đồ ăn online</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo">
                    <h1><i class="fas fa-utensils"></i> Food Store</h1>
                </div>
                <nav class="nav-menu">
                    <ul>
                        <li><a href="#home">Trang chủ</a></li>
                        <li><a href="#menu">Thực đơn</a></li>
                        <li><a href="#about">Giới thiệu</a></li>
                        <li><a href="#contact">Liên hệ</a></li>
                    </ul>
                </nav>
                <div class="header-actions">
                    <div class="cart-icon" onclick="toggleCart()">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cartCount">0</span>
                    </div>
                    <?php if(isset($_SESSION['user'])): ?>
                        <div class="user-menu">
                            <span>Xin chào, <?= htmlspecialchars($_SESSION['user']['username']) ?></span>
                            <a href="profile.php" class="btn-logout">Thông tin</a>
                            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                <a href="user-management.php" class="btn-logout">Quản lý người dùng</a>
                                <a href="product-management.php" class="btn-logout">Quản lý món ăn</a>
                            <?php endif; ?>
                            <a href="logout.php" class="btn-logout">Đăng xuất</a>
                        </div>
                    <?php else: ?>
                        <div class="auth-buttons">
                            <a href="login.php" class="btn-login">Đăng nhập</a>
                            <a href="register.php" class="btn-register">Đăng ký</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h2>Đồ ăn ngon, giao hàng nhanh</h2>
            <p>Thưởng thức những món ăn tuyệt vời được chế biến từ nguyên liệu tươi ngon nhất</p>
            <a href="#menu" class="btn-primary">Xem thực đơn</a>
        </div>
    </section>

    <!-- Categories -->
    <section class="categories">
        <div class="container">
            <h2>Danh mục sản phẩm</h2>
            <div class="category-grid">
                <?php foreach($categories as $category): ?>
                <div class="category-item" onclick="filterByCategory(<?php echo $category['id']; ?>)">
                    <img src="<?php echo $category['image']; ?>" alt="<?php echo $category['name']; ?>">
                    <h3><?php echo $category['name']; ?></h3>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="menu">
        <div class="container">
            <h2>Thực đơn của chúng tôi</h2>
            <div class="menu-filter">
                <button class="filter-btn active" onclick="filterProducts('all')">Tất cả</button>
                <?php foreach($categories as $category): ?>
                <button class="filter-btn" onclick="filterProducts(<?php echo $category['id']; ?>)"><?php echo $category['name']; ?></button>
                <?php endforeach; ?>
            </div>
            <div class="product-grid" id="productGrid">
                <?php foreach($products as $product): ?>
                <div class="product-card" data-category="<?php echo $product['category_id']; ?>">
                    <div class="product-image">
                        <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                        <div class="product-overlay">
                            <button class="btn-view" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3><?php echo $product['name']; ?></h3>
                        <p class="product-description"><?php echo substr($product['description'], 0, 100); ?>...</p>
                        <div class="product-price">
                            <?php if($product['sale_price'] > 0): ?>
                                <span class="original-price"><?php echo number_format($product['price']); ?>đ</span>
                                <span class="sale-price"><?php echo number_format($product['sale_price']); ?>đ</span>
                            <?php else: ?>
                                <span class="price"><?php echo number_format($product['price']); ?>đ</span>
                            <?php endif; ?>
                        </div>
                        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo $product['name']; ?>', <?php echo $product['sale_price'] > 0 ? $product['sale_price'] : $product['price']; ?>, '<?php echo $product['image']; ?>')">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>Về chúng tôi</h2>
                    <p>Food Store là cửa hàng đồ ăn online uy tín, chuyên cung cấp các món ăn ngon, chất lượng cao với giá cả hợp lý. Chúng tôi cam kết mang đến cho khách hàng những trải nghiệm ẩm thực tuyệt vời nhất.</p>
                    <div class="features">
                        <div class="feature-item">
                            <i class="fas fa-shipping-fast"></i>
                            <h4>Giao hàng nhanh</h4>
                            <p>Giao hàng trong vòng 30 phút</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-leaf"></i>
                            <h4>Nguyên liệu tươi</h4>
                            <p>Sử dụng nguyên liệu tươi ngon nhất</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-star"></i>
                            <h4>Chất lượng cao</h4>
                            <p>Đảm bảo chất lượng món ăn</p>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <img src="assets/images/about-us.jpg" alt="Về chúng tôi">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <h2>Liên hệ với chúng tôi</h2>
            <div class="contact-content">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Địa chỉ</h4>
                            <p>123 Đường ABC, Quận 1, TP.HCM</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Điện thoại</h4>
                            <p>0123 456 789</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>info@foodstore.com</p>
                        </div>
                    </div>
                </div>
                <form class="contact-form" id="contactForm">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Họ tên" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <textarea name="message" placeholder="Tin nhắn" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary">Gửi tin nhắn</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Food Store</h3>
                    <p>Cửa hàng đồ ăn online uy tín, chất lượng cao</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Liên kết nhanh</h4>
                    <ul>
                        <li><a href="#home">Trang chủ</a></li>
                        <li><a href="#menu">Thực đơn</a></li>
                        <li><a href="#about">Giới thiệu</a></li>
                        <li><a href="#contact">Liên hệ</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Giờ mở cửa</h4>
                    <p>Thứ 2 - Chủ nhật: 8:00 - 22:00</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Food Store. Tất cả quyền được bảo lưu.</p>
            </div>
        </div>
    </footer>

    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3>Giỏ hàng</h3>
            <button class="cart-close" onclick="toggleCart()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="cart-items" id="cartItems">
            <!-- Cart items sẽ được hiển thị bằng JavaScript -->
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <strong>Tổng: <span id="cartTotal">0đ</span></strong>
            </div>
            <button class="btn-checkout" onclick="checkout()">Thanh toán</button>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <div id="modalContent"></div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
