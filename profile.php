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
    $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, address=? WHERE id=?");
    $stmt->bind_param("sssi", $full_name, $phone, $address, $user_id);
    $stmt->execute();

    // Cập nhật mật khẩu nếu có
    if (!empty($new_password) && $new_password === $confirm_password) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $user_id);
        $stmt->execute();
    }

    // Cập nhật lại session
    $res = $conn->query("SELECT * FROM users WHERE id = $user_id");
    $_SESSION['user'] = $res->fetch_assoc();

    // ➤ Quay về trang chủ
    header("Location: index.php");
    exit;
}


// Lấy lại dữ liệu user để hiển thị
$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông tin cá nhân</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="profile-container">
        <h2>Thông tin cá nhân</h2>
        <?php if (!empty($message)) echo "<div class='success-msg'>$message</div>"; ?>
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
