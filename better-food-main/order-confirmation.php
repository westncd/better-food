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

// Thêm mã tạo sự kiện lịch
require_once 'vendor/autoload.php';
use Google\Client;
use Google\Service\Calendar;

if ($order) {
    $client = new Client();
    $client->setApplicationName('Food Delivery App');
    $client->setScopes(Google_Service_Calendar::CALENDAR);
    $client->setAuthConfig(__DIR__ . '/your-service-account.json'); // Thay bằng đường dẫn file JSON

    $service = new Calendar($client);

    // Giả sử giao hàng trong 2 ngày kể từ ngày đặt
    $deliveryDate = date('Y-m-d', strtotime($order['created_at'] . ' +2 days'));
    $event = new Google_Service_Calendar_Event([
        'summary' => 'Giao hàng đơn #' . $order_id,
        'description' => 'Địa chỉ: ' . ($order['delivery_address'] ?? 'Chưa cập nhật'),
        'start' => ['dateTime' => $deliveryDate . 'T09:00:00+07:00'],
        'end' => ['dateTime' => $deliveryDate . 'T10:00:00+07:00'],
    ]);

    $calendarId = 'primary'; // Sử dụng lịch chính
    $service->events->insert($calendarId, $event);
}
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

        <!-- Thêm thông báo lịch đã được thêm -->
        <p><strong>Lịch giao hàng</strong> đã được thêm cho ngày <?= date('d/m/Y', strtotime($order['created_at'] . ' +2 days')) ?>.</p>

        <a href="index.php" class="btn-primary">Quay lại trang chủ</a>
    </div>
</body>
</html>