<?php
require_once 'vendor/autoload.php'; // Sử dụng firebase/php-jwt và google/apiclient

use Firebase\Auth\Token\Exception\InvalidToken;
use Kreait\Firebase\Factory;

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (empty($data['idToken'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID token']);
    exit;
}

$factory = (new Factory)->withServiceAccount('firebase-adminsdk.json'); // JSON tải từ Firebase Console
$auth = $factory->createAuth();

try {
    $verifiedIdToken = $auth->verifyIdToken($data['idToken']);
    $uid = $verifiedIdToken->claims()->get('sub');

    // Có thể tạo session ở đây
    $_SESSION['user'] = [
        'uid' => $uid,
        'email' => $verifiedIdToken->claims()->get('email'),
    ];

    echo json_encode(['success' => true]);
} catch (Error $e) {
    echo json_encode(['success' => false, 'message' => 'Token không hợp lệ']);
}
