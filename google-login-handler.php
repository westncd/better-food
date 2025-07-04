<?php
require 'vendor/autoload.php';
require 'config/database.php';

use Kreait\Firebase\Factory;

session_start();

header('Content-Type: application/json'); // Đảm bảo luôn trả JSON

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
        throw new Exception('Phương thức không hợp lệ');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $idToken = $data['id_token'] ?? null;
    if (!$idToken) {
        throw new Exception('Thiếu id_token');
    }

    // Xác minh id_token
    $verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($idToken);
    $response = file_get_contents($verifyUrl);
    $payload = json_decode($response, true);

    if (!isset($payload['sub']) || $payload['aud'] !== '555580540304-pan2juv0g8vik6d71lhpgm151bk164k7.apps.googleusercontent.com') {
        throw new Exception('Token không hợp lệ hoặc sai audience');
    }

    // Tạo Firebase factory
    $factory = (new Factory)->withServiceAccount(__DIR__ . '/foodstore-1c8f1-firebase-adminsdk-fbsvc-41b5c32875.json');
    $firestore = $factory->createFirestore()->database();

    // Lấy thông tin từ payload
    $googleUid = $payload['sub'];
    $email     = $payload['email'] ?? '';
    $fullName  = $payload['name'] ?? '';
    $avatar    = $payload['picture'] ?? '';

    if (!$email) {
        throw new Exception('Thiếu thông tin người dùng');
    }

    // Truy vấn người dùng Firestore
    $userRef = $firestore->collection('users')->document($googleUid);
    $snapshot = $userRef->snapshot();

    if (!$snapshot->exists()) {
        $userRef->set([
            'uid'        => $googleUid,
            'email'      => $email,
            'full_name'  => $fullName,
            'username'   => explode('@', $email)[0],
            'avatar'     => $avatar,
            'role'       => 'user',
            'status'     => 1,
            'created_at' => date('c'),
        ]);
        $userData = $userRef->snapshot()->data();
    } else {
        $userData = $snapshot->data();
    }

    if (isset($userData['status']) && $userData['status'] == 0) {
        throw new Exception('Tài khoản đã bị khóa');
    }

    // Lưu session
    $_SESSION['user'] = [
        'uid'        => $googleUid,
        'email'      => $userData['email'] ?? '',
        'username'   => $userData['username'] ?? '',
        'full_name'  => $userData['full_name'] ?? '',
        'phone'      => $userData['phone'] ?? '',
        'role'       => $userData['role'] ?? 'user',
        'status'     => $userData['status'] ?? 1,
        'created_at' => $userData['created_at'] ?? '',
    ];

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>