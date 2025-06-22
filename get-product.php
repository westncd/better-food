<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$result = $conn->query("SELECT * FROM products WHERE id = $id AND status = 1");

if ($result && $product = $result->fetch_assoc()) {
    echo json_encode($product);
} else {
    echo json_encode(['error' => 'Không tìm thấy sản phẩm']);
}
