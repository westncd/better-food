<?php
session_start();
require_once 'config/database.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    echo "<h2>Không tìm thấy đơn hàng</h2>";
    exit;
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<h2>Đơn hàng không tồn tại</h2>";
    exit;
}

// Lấy danh sách sản phẩm trong đơn
$stmt_items = $conn->prepare("
    SELECT p.name, oi.quantity, oi.price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn hàng #<?= $order_id ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="order-confirm" style="max-width: 700px; margin: 100px auto; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
        <h2 style="color: #27ae60;">🎉 Cảm ơn bạn đã đặt hàng!</h2>
        <p>Đơn hàng của bạn đã được ghi nhận thành công.</p>
        <h3>Chi tiết đơn hàng #<?= $order_id ?></h3>
        <ul style="padding-left: 1.2rem;">
            <?php foreach ($items as $item): ?>
                <li style="margin-bottom: 6px;">
                    <?= htmlspecialchars($item['name']) ?> x <?= $item['quantity'] ?> – 
                    <?= number_format($item['price']) ?>đ
                </li>
            <?php endforeach; ?>
        </ul>
        <p><strong>Tổng tiền:</strong> <?= number_format($order['total_amount']) ?>đ</p>
        <a href="index.php" class="btn-primary">Quay lại trang chủ</a>
    </div>
</body>
</html>
