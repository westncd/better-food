<?php
session_start();
require_once 'config/database.php';
require_once 'config/storage.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Bạn không có quyền truy cập trang này.');
}

    // // Upload ảnh
    // $uploadDir = 'uploads/';
    // $file = $_FILES['image_file'];
    // $fileName = basename($file['name']);
    // $targetPath = $uploadDir . time() . '_' . $fileName;

    // $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    // if (!in_array($file['type'], $allowedTypes)) {
    //     die("Chỉ cho phép ảnh JPG, PNG, WEBP.");
    // }

    // if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    //     die("Lỗi khi upload ảnh.");
    // }

    //Xử lý thêm món ăn
    // Xử lý thêm món ăn
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $sale_price = floatval($_POST['sale_price']);
    $category_id = intval($_POST['category_id']);

    $imageUrl = null;

    // Xử lý hình ảnh và lưu vào thư mục uploads/
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $file = $_FILES['image_file'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            die("Chỉ cho phép ảnh JPG, PNG, WEBP.");
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            die("Lỗi khi upload ảnh.");
        }

        $imageUrl = $targetPath; // Lưu đường dẫn vào DB
    }

    // Lưu vào DB
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, sale_price, image, category_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, datetime('now'))");
    $stmt->execute([$name, $description, $price, $sale_price, $imageUrl, $category_id]);

    header("Location: product-management.php");
    exit;
}


// Xử lý xóa món ăn
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Lấy đường dẫn hình ảnh từ cơ sở dữ liệu
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product && !empty($product['image'])) {
        $storage = new CloudStorage();
        $result = $storage->deleteFile($product['image']);
        if ($result) {
            echo "Hình ảnh đã được xóa thành công.";
        } else {
            echo "Không thể xóa hình ảnh hoặc không tồn tại.";
        }
    }

    // Xóa sản phẩm khỏi cơ sở dữ liệu
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: product-management.php");
    exit;
}

// Lấy danh sách sản phẩm và danh mục
$products = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$categories = $conn->query("SELECT * FROM categories WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý món ăn</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-container { max-width: 1100px; margin: 100px auto; background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .admin-container h2 { text-align: center; color: #e74c3c; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 2rem; }
        th, td { padding: 10px; border: 1px solid #eee; text-align: left; }
        th { background: #f8f8f8; }
        form.add-form input, select, textarea { width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #ccc; }
        .btn { padding: 6px 12px; border-radius: 5px; text-decoration: none; color: white; }
        .btn-danger { background: #e74c3c; }
        .btn-edit { background: #3498db; }
        .btn-submit { background: #2ecc71; border: none; font-weight: bold; }
        .btn:hover { opacity: 0.85; }
    </style>
</head>
<body>
<div class="admin-container">
    <h2>Quản lý món ăn</h2>

    <form class="add-form" method="POST" enctype="multipart/form-data">
        <h3>➕ Thêm món mới</h3>
        <input type="text" name="name" placeholder="Tên món ăn" required>
        <textarea name="description" placeholder="Mô tả món ăn" rows="3"></textarea>
        <input type="number" name="price" placeholder="Giá gốc" required>
        <input type="file" name="image_file" accept="image/*" required>
        <input type="number" name="sale_price" placeholder="Giá khuyến mãi (có thể để 0)">
        <select name="category_id" required>
            <option value="">Chọn danh mục</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="add" class="btn btn-submit">Thêm món</button>
    </form>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Ảnh</th>
            <th>Tên</th>
            <th>Giá</th>
            <th>Danh mục</th>
            <th>Ngày tạo</th>
            <th>Thao tác</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><img src="<?= $p['image'] ?>" alt="ảnh" width="50"></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td>
                    <?php if ($p['sale_price'] > 0): ?>
                        <span style="text-decoration: line-through; color: #999;"><?= number_format($p['price']) ?>đ</span>
                        <strong style="color: #e74c3c"><?= number_format($p['sale_price']) ?>đ</strong>
                    <?php else: ?>
                        <?= number_format($p['price']) ?>đ
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                <td><?= $p['created_at'] ?></td>
                <td>
                    <a href="product-management.php?delete=<?= $p['id'] ?>" class="btn btn-danger" onclick="return confirm('Xoá món ăn này?')">Xoá</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
