<?php
session_start();

if (!isset($_SESSION['user']['uid'])) {
    header("Location: login.php");
    exit;
}

$projectId = 'foodstore-1c8f1'; // ← Thay bằng Project ID của bạn
$uid = $_SESSION['user']['uid'];
$documentPath = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/users/$uid";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Chuẩn bị dữ liệu cập nhật
    $updateFields = [
        'fields' => [
            'full_name' => ['stringValue' => $full_name],
            'phone'     => ['stringValue' => $phone],
            'address'   => ['stringValue' => $address],
        ]
    ];

    // Nếu đổi mật khẩu
    if (!empty($new_password) && $new_password === $confirm_password) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $updateFields['fields']['password'] = ['stringValue' => $hashed];
    }

    // Gửi PATCH request để cập nhật Firestore
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $documentPath."?updateMask.fieldPaths=full_name&updateMask.fieldPaths=phone&updateMask.fieldPaths=address".(isset($updateFields['fields']['password']) ? "&updateMask.fieldPaths=password" : ""));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateFields));
    $response = curl_exec($ch);
    curl_close($ch);

    // Reload lại dữ liệu người dùng
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $documentPath);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);

    if (isset($result['fields'])) {
        $_SESSION['user'] = [
            'uid'       => $uid,
            'username'  => $result['fields']['username']['stringValue'] ?? '',
            'email'     => $result['fields']['email']['stringValue'] ?? '',
            'role'      => $result['fields']['role']['stringValue'] ?? 'user',
            'full_name' => $result['fields']['full_name']['stringValue'] ?? '',
            'phone'     => $result['fields']['phone']['stringValue'] ?? '',
            'address'   => $result['fields']['address']['stringValue'] ?? '',
        ];
    }

    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông tin cá nhân</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 100px auto;
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .profile-container h2 {
            text-align: center;
            margin-bottom: 1rem;
            color: #e67e22;
        }
        .form-group {
            margin-bottom: 15px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .btn-save {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-save:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
<div class="profile-container">
    <h2>Thông tin cá nhân</h2>
    <form method="POST">
        <div class="form-group">
            <input type="text" name="full_name" placeholder="Họ tên" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <input type="text" name="phone" placeholder="Số điện thoại" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <input type="text" name="address" placeholder="Địa chỉ" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
        </div>
        <hr>
        <div class="form-group">
            <input type="password" name="new_password" placeholder="Mật khẩu mới (nếu muốn đổi)">
        </div>
        <div class="form-group">
            <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu mới">
        </div>
        <button type="submit" class="btn-save">Lưu thay đổi</button>
    </form>
</div>
</body>
</html>
