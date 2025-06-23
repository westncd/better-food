<?php
require 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);

        header("Location: login.php?register=success");
        exit;
    } catch (PDOException $e) {
        // Nếu lỗi vì email hoặc username trùng
        if (str_contains($e->getMessage(), 'UNIQUE')) {
            $error = "Tên đăng nhập hoặc email đã tồn tại!";
        } else {
            $error = "Lỗi khi đăng ký: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-container" style="max-width: 500px; margin: 100px auto;">
    <h2 style="text-align:center;">Đăng ký</h2>
    <?php if (!empty($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Tên đăng nhập" required class="form-group"><br>
        <input type="email" name="email" placeholder="Email" required class="form-group"><br>
        <input type="password" name="password" placeholder="Mật khẩu" required class="form-group"><br>
        <button type="submit" class="btn-register">Đăng ký</button>
    </form>
    <p style="text-align:center;">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
</div>
</body>
</html>
