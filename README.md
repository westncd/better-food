# Food Store (Better Food)

Chào mừng đến với dự án **Food Store** (Better Food)! Đây là một nền tảng web thương mại điện tử chuyên cung cấp dịch vụ đặt món ăn trực tuyến nhanh chóng và tiện lợi. Dự án được xây dựng bằng PHP thuần kết hợp với các công nghệ hiện đại để mang lại trải nghiệm người dùng tốt nhất.

## 🌟 Giới thiệu

Food Store không chỉ là một trang web đặt món đơn thuần mà còn tích hợp nhiều tính năng thông minh và quản lý sành điệu. Với giao diện thân thiện, người dùng có thể dễ dàng tìm kiếm món ăn yêu thích, xem video hướng dẫn nấu ăn, và thực hiện đặt hàng chỉ với vài cú nhấp chuột.

### Các tính năng chính:

*   **🛒 Đặt hàng trực tuyến**: Duyệt thực đơn đa dạng, thêm món vào giỏ hàng và đặt hàng nhanh chóng.
*   **🔍 Tìm kiếm & Lọc**: Tìm kiếm món ăn theo tên hoặc lọc theo danh mục.
*   **👤 Quản lý người dùng**:
    *   Đăng ký / Đăng nhập (Hỗ trợ xác thực truyền thống và **Google Login** qua Firebase).
    *   Trang cá nhân để xem và cập nhật thông tin.
*   **🔧 Quản trị (Admin Dashboard)**:
    *   **Quản lý món ăn**: Thêm, sửa, xóa, cập nhật giá và hình ảnh món ăn (`product-management.php`).
    *   **Quản lý người dùng**: Phân quyền và quản lý danh sách khách hàng (`user-management.php`).
*   **🌐 Tích hợp API & Công nghệ cao**:
    *   **YouTube Data API**: Hiển thị các video nấu ăn hấp dẫn liên quan ngay trên trang chủ.
    *   **Google Vision API**: Demo tính năng nhận diện văn bản (OCR) từ hình ảnh.
    *   **Google Translation API**: Hỗ trợ dịch văn bản đa ngôn ngữ trực tiếp trên web.
    *   **Google Analytics**: Theo dõi lưu lượng truy cập.
    *   **EmailJS**: Gửi form liên hệ trực tiếp qua email.

## 🛠️ Công nghệ sử dụng

*   **Backend**: PHP (Native), Composer.
*   **Frontend**: HTML5, CSS3, JavaScript (Vanilla JS).
*   **Database**: MySQL (sử dụng PDO để kết nối).
*   **Thư viện/Dịch vụ bên thứ 3**:
    *   `google/cloud-storage`: Lưu trữ đám mây.
    *   `kreait/firebase-php`: Xác thực Firebase.
    *   `sendgrid/sendgrid`: Gửi email.
    *   Google APIs (YouTube, Vision, Translate).

## 📂 Cấu trúc dự án

Dưới đây là cấu trúc thư mục và các tệp tin quan trọng của dự án:

```
better-food/
├── api/                        # Các API endpoints xử lý logic backend
├── assets/                     # Tài nguyên tĩnh
│   ├── css/                    # Các file CSS mở rộng
│   └── js/                     # Các script JavaScript
├── config/                     # Cấu hình hệ thống (Database connection...)
├── css/                        # Stylesheet (nếu có thêm ngoài assets)
├── tmp/                        # Thư mục tạm (Temporary files)
├── uploads/                    # Thư mục chứa ảnh upload (món ăn, avatar...)
├── vendor/                     # Thư mục chứa các thư viện Composer
├── .gcloudignore               # Cấu hình ignore cho Google Cloud
├── .gitignore                  # Cấu hình ignore cho Git
├── app.yaml                    # Cấu hình deploy Google App Engine
├── composer.json               # Khai báo các thư viện phụ thuộc (Dependencies)
├── composer.lock               # File lock version các thư viện
├── Dockerfile                  # Cấu hình Docker để build container
├── generate-hash.php           # Tiện ích tạo mã hash (mật khẩu/token)
├── get-product.php             # API/Script lấy thông tin sản phẩm
├── google-login-handler.php    # Xử lý logic đăng nhập bằng Google
├── index.php                   # Trang chủ (Homepage) - Entry point của ứng dụng
├── login.php                   # Trang đăng nhập
├── logout.php                  # Xử lý đăng xuất
├── order-confirmation.php      # Trang xác nhận đơn hàng
├── product-management.php      # Trang quản lý sản phẩm (Admin)
├── profile.php                 # Trang thông tin cá nhân người dùng
├── README.md                   # Tài liệu hướng dẫn (File này)
├── register.php                # Trang đăng ký tài khoản
├── style.css                   # File CSS chính của giao diện
├── user-management.php         # Trang quản lý người dùng (Admin)
└── verify-firebase.php         # Script xác thực token từ Firebase
```

## 🚀 Cài đặt & Chạy dự án

1.  **Clone dự án**:
    ```bash
    git clone <repository-url>
    ```
2.  **Cài đặt thư viện**:
    Chạy lệnh sau để cài đặt các gói phụ thuộc qua Composer:
    ```bash
    composer install
    ```
3.  **Cấu hình Database**:
    *   Tạo database MySQL.
    *   Cập nhật thông tin kết nối trong `config/database.php`.
    *   Import file SQL (nếu có) để khởi tạo bảng.
4.  **Cấu hình API Keys**:
    Cập nhật các API Key (Google, Firebase, EmailJS) trong các file tương ứng (`index.php`, `google-login-handler.php`, JS files).
5.  **Chạy ứng dụng**:
    Sử dụng XAMPP/WAMP hoặc PHP built-in server:
    ```bash
    php -S localhost:8000
    ```
    Truy cập `http://localhost:8000` trên trình duyệt.

---
*Dự án được phát triển bởi VyDang1010.*
