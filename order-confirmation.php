<?php
session_start();

$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    echo "<h2>Không tìm thấy đơn hàng</h2>";
    exit;
}

require 'vendor/autoload.php';

// Hàm lấy access token từ service account
function getAccessToken(): string {
    $sa = json_decode(file_get_contents(__DIR__ . '/foodstore-1c8f1-firebase-adminsdk-fbsvc-41b5c32875.json'), true);
    $now = time();
    $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $jwtPayload = base64_encode(json_encode([
        'iss' => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/datastore',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]));
    $data = "$jwtHeader.$jwtPayload";
    openssl_sign($data, $signature, $sa['private_key'], 'sha256WithRSAEncryption');
    $jwt = "$data." . base64_encode($signature);

    $ch = curl_init("https://oauth2.googleapis.com/token");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($res, true);
    return $json['access_token'] ?? '';
}

// Lấy thông tin đơn hàng từ Firestore
function getOrderFromFirestore(string $orderId): ?array {
    $projectId = 'foodstore-1c8f1';
    $accessToken = getAccessToken();
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/orders/$orderId";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken"
        ]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (!isset($data['fields'])) return null;

    return parseOrderFields($data['fields']);
}

function parseOrderFields(array $fields): array {
    $items = [];
    if (isset($fields['items']['arrayValue']['values'])) {
        foreach ($fields['items']['arrayValue']['values'] as $item) {
            $map = $item['mapValue']['fields'];
            $items[] = [
                'name' => $map['name']['stringValue'] ?? '',
                'price' => (float)($map['price']['doubleValue'] ?? 0),
                'quantity' => (int)($map['quantity']['integerValue'] ?? 1),
            ];
        }
    }

    return [
        'uid' => $fields['uid']['stringValue'] ?? '',
        'total' => (float)($fields['total']['doubleValue'] ?? 0),
        'timestamp' => $fields['timestamp']['timestampValue'] ?? '',
        'status' => $fields['status']['stringValue'] ?? 'pending',
        'items' => $items
    ];
}

$order = getOrderFromFirestore($order_id);
if (!$order) {
    echo "<h2>Không tìm thấy đơn hàng</h2>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn hàng #<?= htmlspecialchars($order_id) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="order-confirm" style="max-width: 700px; margin: 100px auto; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
        <h2 style="color: #27ae60;">🎉 Cảm ơn bạn đã đặt hàng!</h2>
        <p>Đơn hàng của bạn đã được ghi nhận thành công.</p>
        <h3>Chi tiết đơn hàng #<?= htmlspecialchars($order_id) ?></h3>
        <ul style="padding-left: 1.2rem;">
            <?php foreach ($order['items'] as $item): ?>
                <li style="margin-bottom: 6px;">
                    <?= htmlspecialchars($item['name']) ?> x <?= $item['quantity'] ?> – 
                    <?= number_format($item['price']) ?>đ
                </li>
            <?php endforeach; ?>
        </ul>
        <p><strong>Tổng tiền:</strong> <?= number_format($order['total']) ?>đ</p>
        <a href="index.php" class="btn-primary">Quay lại trang chủ</a>
    </div>
</body>
</html>
