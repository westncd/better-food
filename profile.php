<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Cập nhật thông tin cơ bản
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->execute([$full_name, $phone, $address, $user_id]);

    // Cập nhật mật khẩu nếu có
    if (!empty($new_password) && $new_password === $confirm_password) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
    }

    // Cập nhật lại session
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

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
