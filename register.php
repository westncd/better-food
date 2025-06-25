<?php
require 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    // Kiểm tra reCAPTCHA
    if (empty($recaptchaResponse)) {
        $error = "Vui lòng xác nhận bạn không phải robot.";
    } else {
        $secretKey = '6Lf2LGwrAAAAAF8daYzodb5bXnn_35NuExgMDoeF';
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

        $response = file_get_contents($verifyUrl . '?secret=' . $secretKey . '&response=' . $recaptchaResponse . '&remoteip=' . $_SERVER['REMOTE_ADDR']);
        $responseData = json_decode($response);

        if (!$responseData->success) {
            $error = "Xác thực reCAPTCHA không thành công.";
        }
    }

    // Nếu không có lỗi, thực hiện đăng ký
    if (empty($error)) {
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $password]);

            header("Location: login.php?register=success");
            exit;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'UNIQUE')) {
                $error = "Tên đăng nhập hoặc email đã tồn tại!";
            } else {
                $error = "Lỗi khi đăng ký: " . $e->getMessage();
            }
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
   <!-- <style>
        .g-recaptcha {
            display: none;
        }
    </style>-->
</head>
<body>
<div class="auth-container" style="max-width: 500px; margin: 100px auto;">
    <h2 style="text-align:center;">Đăng ký</h2>
    <?php if (!empty($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Tên đăng nhập" required class="form-group"><br>
        <input type="email" name="email" placeholder="Email" required class="form-group"><br>
        <input type="password" name="password" placeholder="Mật khẩu" required class="form-group"><br>

        <!-- reCAPTCHA widget -->
        <div class="g-recaptcha" data-sitekey="6Lf2LGwrAAAAACTjX_uVV9GWgtf_-OdnpIh-QnUJ"></div>
        <br>

        <button type="submit" class="btn-register">Đăng ký</button>
    </form>
    <p style="text-align:center;">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
</div>
</body>
</html>
