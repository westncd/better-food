<?php
require 'config/database.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    // Verify reCAPTCHA
    if (empty($recaptchaResponse)) {
        $error = "Vui lòng xác nhận bạn không phải robot.";
    } else {
        $secretKey = '6Lf2LGwrAAAAAF8daYzodb5bXnn_35NuExgMDoeF';
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

        // Use cURL to verify
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verifyUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret'   => $secretKey,
            'response' => $recaptchaResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $apiResponse = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($apiResponse, true);

        if (empty($decoded['success']) || $decoded['success'] !== true) {
            $error = "Xác thực reCAPTCHA không thành công, vui lòng thử lại.";
        }
    }

    // Nếu reCAPTCHA hợp lệ, tiếp tục kiểm tra email/password
    if (empty($error)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

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
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <div class="auth-container" style="max-width: 500px; margin: 100px auto; padding: 2rem;">
        <h2 style="text-align:center; margin-bottom: 20px;">Đăng nhập</h2>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required class="form-group"><br>
            <input type="password" name="password" placeholder="Mật khẩu" required class="form-group"><br>

            <div class="g-recaptcha" data-sitekey="6Lf2LGwrAAAAACTjX_uVV9GWgtf_-OdnpIh-QnUJ"></div>
            <br>
            <button type="submit" class="btn-login">Đăng nhập</button>
        </form>

        <div style="margin-top: 1rem; text-align:center;">
            <div id="g_id_onload"
                data-client_id="555580540304-pan2juv0g8vik6d71lhpgm151bk164k7.apps.googleusercontent.com"
                data-context="signin"
                data-ux_mode="popup"
                data-callback="handleCredentialResponse"
                data-auto_prompt="false">
            </div>

            <div class="g_id_signin"
                data-type="standard"
                data-size="large"
                data-theme="outline"
                data-text="sign_in_with"
                data-shape="rectangular"
                data-logo_alignment="left">
            </div>
        </div>

        <script>
            function handleCredentialResponse(response) {
                console.log("Google ID Token:", response.credential);
                // Gửi ID token này lên server để xác thực và lấy thông tin người dùng nếu cần
                // Có thể dùng fetch() hoặc AJAX ở đây nếu muốn
            }
        </script>

        <p style="text-align:center; margin-top: 1rem;">Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
    </div>
</body>
</html>