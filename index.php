<?php
session_start();
require_once 'config/database.php';

// YouTube API call
$apiKey = 'AIzaSyA7Wg-TthgHJzPp73GeXDR93t3JBNONq4s'; // ← Key của bạn
$searchQuery = 'food recipes'; // Có thể sửa thành từ khóa khác
$maxResults = 4;

$youtubeApiUrl = "https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&q=" . urlencode($searchQuery) . "&key={$apiKey}&maxResults={$maxResults}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $youtubeApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $youtubeData = json_decode($response, true);
} else {
    $youtubeData = ['items' => []]; // để tránh lỗi foreach
    error_log("❌ YouTube API error: HTTP $httpCode - $response");
}

if (isset($_SESSION['user']['uid'])) {
    $projectId = 'foodstore-1c8f1'; // ← Thay bằng Project ID của bạn
    $uid = $_SESSION['user']['uid'];
    $documentPath = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/users/$uid";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $documentPath);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    
    if (isset($result['fields'])) {
        $userData = [
            'username' => $result['fields']['username']['stringValue'] ?? '',
            'email'    => $result['fields']['email']['stringValue'] ?? '',
            'role'     => $result['fields']['role']['stringValue'] ?? 'user',
        ];
    }
}



// Lấy danh sách món ăn từ database
// $stmt = $conn->prepare("SELECT * FROM products WHERE status = 1 ORDER BY created_at DESC");
// $stmt->execute();
// $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$search = $_GET['search'] ?? '';

if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE status = 1 AND name LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%" . $search . "%"]);
} else {
    $stmt = $conn->prepare("SELECT * FROM products WHERE status = 1 ORDER BY created_at DESC");
    $stmt->execute();
}
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
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-89L907G3JE"></script>
    <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }
    gtag('js', new Date());

    gtag('config', 'G-89L907G3JE');
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
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
                    <div class="search-icon" onclick="openSearchModal()" title="Tìm kiếm">
                        <i class="fas fa-search"></i>
                    </div>

                    <?php if(isset($userData)): ?>
                    <div class="user-menu">
                        <span>Xin chào, <?= htmlspecialchars($userData['username']) ?></span>
                        <a href="profile.php" class="btn-logout">Thông tin</a>
                        <?php if ($userData['role'] === 'admin'): ?>
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
                <button class="filter-btn"
                    onclick="filterProducts(<?php echo $category['id']; ?>)"><?php echo $category['name']; ?></button>
                <?php endforeach; ?>
            </div>
            <div class="product-grid" id="productGrid">
                <?php foreach($products as $product): ?>
                <div class="product-card"
                     data-category="<?php echo $product['category_id']; ?>"
                     data-name="<?php echo strtolower($product['name']); ?>">
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
                        <button class="btn-add-cart"
                            onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo $product['name']; ?>', <?php echo $product['sale_price'] > 0 ? $product['sale_price'] : $product['price']; ?>, '<?php echo $product['image']; ?>')">
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
                    <p>Food Store là cửa hàng đồ ăn online uy tín, chuyên cung cấp các món ăn ngon, chất lượng cao với
                        giá cả hợp lý. Chúng tôi cam kết mang đến cho khách hàng những trải nghiệm ẩm thực tuyệt vời
                        nhất.</p>
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
                    <img src="uploads/about-us.jpg" alt="Về chúng tôi">
                </div>
            </div>
        </div>
    </section>

    <section class="survey-wrapper">
        <div class="survey-section">
            <h3>Điền khảo sát nhé!</h3>
            <p>Ý kiến của bạn giúp chúng mình cải thiện dịch vụ nè:</p>
            <iframe src="https://survey.zohopublic.com/zs/ldD5Zm" title="Khảo sát khách hàng" allow="autoplay" allowfullscreen></iframe>
        </div>
    </section>

    <!-- YouTube Section -->
    <section class="youtube-section">
        <div class="container">
            <h2>Video Món Ăn Hấp Dẫn</h2>
            <div class="video-grid">
                <?php foreach ($youtubeData['items'] as $video): ?>
                <div class="video-item">
                    <iframe width="100%" height="215"
                        src="https://www.youtube.com/embed/<?php echo $video['id']['videoId']; ?>" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                    <p><?php echo $video['snippet']['title']; ?></p>
                </div>
                <?php endforeach; ?>
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
                            <p>Hà Nội</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Điện thoại</h4>
                            <p>0123456789</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>khanhhuyendao240304@gmail.com.</p>
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

    <section class="translate-section section">
        <div class="container">
            <h2 class="section-title" style="text-align:center; margin-bottom: 2rem;">🌐 Dịch văn bản</h2>
            <div class="form-group">
                <textarea id="textToTranslate" rows="4" class="form-control"
                    placeholder="Nhập văn bản cần dịch..."></textarea>
            </div>

            <div class="form-group">
                <select id="targetLang" class="form-control">
                    <option value="en">Tiếng Anh</option>
                    <option value="ja">Tiếng Nhật</option>
                    <option value="ko">Tiếng Hàn</option>
                    <option value="zh-CN">Tiếng Trung</option>
                    <option value="fr">Tiếng Pháp</option>
                </select>
            </div>

            <button class="btn-primary" onclick="translateText()" style="width: 100%;">Dịch ngay</button>

            <div id="output" class="result"
                style="margin-top: 1.5rem; background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.05);">
            </div>
        </div>
    </section>

    <h2>Google Vision API Demo (OCR)</h2>
    <input type="file" id="imageInput" accept="image/*">
    <button onclick="analyzeImage()">Phân tích ảnh</button>
    <pre id="result"></pre>

    <script>
    async function analyzeImage() {
        const fileInput = document.getElementById("imageInput");
        const resultBox = document.getElementById("result");

        if (!fileInput.files.length) {
            alert("Hãy chọn một ảnh trước!");
            return;
        }

        const reader = new FileReader();
        reader.onload = async function() {
            const base64Image = reader.result.split(',')[1]; // Bỏ phần header
            const response = await fetch(
                "https://vision.googleapis.com/v1/images:annotate?key=AIzaSyDFaLfXEg66QyP1mvXjoz8urzo_3VACf4k", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        requests: [{
                            image: {
                                content: base64Image
                            },
                            features: [{
                                type: "TEXT_DETECTION"
                            }],
                        }, ],
                    }),
                }
            );

            const data = await response.json();
            const text = data.responses[0]?.fullTextAnnotation?.text || "Không nhận diện được.";
            resultBox.textContent = text;
        };

        reader.readAsDataURL(fileInput.files[0]);
    }
    </script>

    <section class="google-form-section">
        <div class="container">
            <div class="form-wrapper">
                <iframe
                    src="https://docs.google.com/forms/d/e/1FAIpQLSck80da4nb9JuPb1zVG69UVjJrJNCFLgZXFTdtVdL6Wmn1wGQ/viewform?embedded=true"
                    width="100%" height="600" frameborder="0" marginheight="0" marginwidth="0">
                    Đang tải…
                </iframe>
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



    <script>
    const API_KEY = "AIzaSyDFaLfXEg66QyP1mvXjoz8urzo_3VACf4k"; // ← Thay bằng API key của bạn

    function translateText() {
        const text = document.getElementById("textToTranslate").value.trim();
        const target = document.getElementById("targetLang").value;
        const outputDiv = document.getElementById("output");

        if (!text) {
            outputDiv.innerHTML = "❗ Vui lòng nhập văn bản cần dịch.";
            return;
        }

        const url = `https://translation.googleapis.com/language/translate/v2?key=${API_KEY}`;

        fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    q: text,
                    target: target
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    outputDiv.innerHTML = `❌ Lỗi: ${data.error.message}`;
                } else {
                    const translatedText = data.data.translations[0].translatedText;
                    outputDiv.innerHTML = `✅ <strong>Bản dịch:</strong><br>${translatedText}`;
                }
            })
            .catch(error => {
                outputDiv.innerHTML = "❌ Lỗi kết nối API.";
                console.error(error);
            });
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    <script>
    // Khởi tạo EmailJS với Public Key
    emailjs.init('CALbNSEplitYOlJs1'); // Thay bằng public key của bạn

    // Bắt sự kiện gửi form
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Ngăn reload trang

        // Gửi form qua EmailJS
        emailjs.sendForm('service_o8xrukb', 'template_5zo08dk', this)
            .then(function() {
                alert('✅ Gửi tin nhắn thành công!');
            }, function(error) {
                alert('❌ Lỗi khi gửi: ' + JSON.stringify(error));
            });

        this.reset(); // Reset form
    });
    </script>

    <script src="assets/js/script.js"></script>

    <div class="modal" id="searchModal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="modal-close" onclick="closeSearchModal()">&times;</span>
            <h3>Tìm kiếm sản phẩm</h3>
            <form method="GET" action="#menu">
                <input type="text" name="search" id="searchInput" placeholder="Nhập tên món ăn..." style="width: 100%; padding: 10px; margin-bottom: 1rem;" required>
                <button type="submit" style="padding: 10px 20px; background: #e67e22; color: white; border: none; border-radius: 6px;">🔍 Tìm kiếm</button>
            </form>
        </div>
    </div>

    <script>
        function openSearchModal() {
            document.getElementById('searchModal').style.display = 'block';
            document.getElementById('searchInput').focus();
        }

        function closeSearchModal() {
            document.getElementById('searchModal').style.display = 'none';
        }

        // Đóng modal nếu bấm ngoài vùng nội dung
        window.onclick = function(event) {
            const modal = document.getElementById('searchModal');
            if (event.target === modal) {
                closeSearchModal();
            }
        }
        document.getElementById("searchInput").addEventListener("input", function () {
            const keyword = this.value.toLowerCase().trim();
            const products = document.querySelectorAll(".product-card");

            products.forEach(product => {
                function removeAccents(str) {
    return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
}

        document.getElementById("searchInput").addEventListener("input", function () {
            const keyword = removeAccents(this.value.trim());
            const products = document.querySelectorAll(".product-card");
            products.forEach(product => {
                const name = removeAccents(product.dataset.name || "");
                product.style.display = name.includes(keyword) ? "block" : "none";
            });
        });

            });
        });

    </script>
</body>
</html>