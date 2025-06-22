<?php
require 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user) {
        if ($user['status'] == 0) {
            $error = "Tài khoản của bạn đã bị khóa.";
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header("Location: index.php");
            exit;
        } else {
            $error = "Email hoặc mật khẩu không đúng!";
        }
    } else {
        $error = "Email hoặc mật khẩu không đúng!";
    }

}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container" style="max-width: 500px; margin-top: 100px;">
        <h2>Đăng nhập</h2>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required class="form-group"><br>
            <input type="password" name="password" placeholder="Mật khẩu" required class="form-group"><br>
            <button type="submit" class="btn-login">Đăng nhập</button>
        </form>
        <p>Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
    </div>
</body>
</html>
