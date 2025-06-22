<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['items']) || $data['total'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu đơn hàng không hợp lệ']);
    exit;
}

// Giả sử bạn đã có kết nối $conn từ database.php
require_once '../config/database.php';

$stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, created_at) VALUES (?, ?, NOW())");
$stmt->bind_param("id", $_SESSION['user']['id'], $data['total']);
$stmt->execute();
$order_id = $stmt->insert_id;

// Lưu từng sản phẩm
$stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
foreach ($data['items'] as $item) {
    $stmt_item->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
    $stmt_item->execute();
}

echo json_encode(['success' => true, 'order_id' => $order_id]);
?>
