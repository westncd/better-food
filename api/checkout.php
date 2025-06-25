<?php
session_start();
require '../vendor/autoload.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']);
    exit;
}

$uid = $_SESSION['user']['uid'];
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!$data || !isset($data['items'], $data['total'], $data['timestamp'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

// Chuẩn bị dữ liệu gửi lên Firestore
$order = [
    'fields' => [
        'uid' => ['stringValue' => $uid],
        'items' => ['arrayValue' => [
            'values' => array_map(function ($item) {
                return ['mapValue' => [
                    'fields' => [
                        'id'       => ['stringValue' => (string)$item['id']],
                        'name'     => ['stringValue' => $item['name']],
                        'price'    => ['doubleValue' => (float)$item['price']],
                        'quantity' => ['integerValue' => (int)$item['quantity']],
                    ]
                ]];
            }, $data['items'])
        ]],
        'total' => ['doubleValue' => (float)$data['total']],
        'timestamp' => ['timestampValue' => $data['timestamp']],
        'status' => ['stringValue' => 'pending']
    ]
];

// Lấy access token từ file service account
function getAccessToken(): string {
    $sa = json_decode(file_get_contents(__DIR__ . '/../foodstore-1c8f1-firebase-adminsdk-fbsvc-41b5c32875.json'), true);
    $now = time();
    $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $jwtPayload = base64_encode(json_encode([
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/datastore',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now
    ]));
    $data = "$jwtHeader.$jwtPayload";
    openssl_sign($data, $signature, $sa['private_key'], 'sha256WithRSAEncryption');
    $jwt = "$data." . base64_encode($signature);

    $ch = curl_init("https://oauth2.googleapis.com/token");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ])
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($res, true);
    return $json['access_token'] ?? '';
}

// Gửi lên Firestore
$projectId = 'foodstore-1c8f1';
$url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/orders";
$accessToken = getAccessToken();

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "Authorization: Bearer $accessToken"
    ],
    CURLOPT_POSTFIELDS => json_encode($order)
]);
$response = curl_exec($ch);
curl_close($ch);

$resData = json_decode($response, true);

if (isset($resData['name'])) {
    $orderId = basename($resData['name']);
    echo json_encode(['success' => true, 'order_id' => $orderId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể tạo đơn hàng']);
}
