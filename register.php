<?php
require 'vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\EmailExists;

session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    // reCAPTCHA xác minh
    if (empty($recaptchaResponse)) {
        $error = "Vui lòng xác nhận bạn không phải robot.";
    } else {
        $secretKey = '6Lf2LGwrAAAAAF8daYzodb5bXnn_35NuExgMDoeF'; // ← Sửa nếu bạn có key riêng
        $verifyUrl = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaResponse&remoteip=" . $_SERVER['REMOTE_ADDR'];
        $captchaResponse = file_get_contents($verifyUrl);
        $responseData = json_decode($captchaResponse, true);
        if (!$responseData['success']) {
            $error = "Xác thực reCAPTCHA không thành công.";
        }
    }

    // Nếu không có lỗi
    if (empty($error)) {
        try {
            $factory = (new Factory)->withServiceAccount(__DIR__ . '/foodstore-1c8f1-firebase-adminsdk-fbsvc-41b5c32875.json');
            $auth = $factory->createAuth();

            // Tạo người dùng trên Firebase Auth
            $user = $auth->createUser([
                'email' => $email,
                'password' => $password,
            ]);

            // Ghi vào Firestore
        // Ghi vào Firestore
        $uid = $user->uid;
        saveUserToFirestore($uid, [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT), // có thể lưu mã hóa nếu muốn
            'full_name' => '',
            'phone' => '',
            'address' => '',
            'role' => 'user',
            'status' => 1,
            'created_at' => date('c')
        ]);


            header("Location: login.php?register=success");
            exit;
        } catch (EmailExists $e) {
            $error = "Email đã tồn tại.";
        } catch (Exception $e) {
            $error = "Lỗi khi đăng ký: " . $e->getMessage();
        }
    }
}

// Ghi dữ liệu vào Firestore qua REST API
function saveUserToFirestore($uid, $data) {
    $projectId = 'foodstore-1c8f1';
    $accessToken = getFirebaseAccessToken();
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/users/$uid";

    $fields = [];
    foreach ($data as $key => $value) {
        if (is_int($value)) {
            $fields[$key] = ['integerValue' => $value];
        } else {
            $fields[$key] = ['stringValue' => (string) $value];
        }
    }

    $payload = json_encode(['fields' => $fields]);

    $headers = [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("❌ Firestore save failed: HTTP $httpCode - $response");
    }
}

// Tạo access token từ file JSON service account
function getFirebaseAccessToken(): string {
    $sa = json_decode(file_get_contents(__DIR__ . '/foodstore-1c8f1-firebase-adminsdk-fbsvc-41b5c32875.json'), true);
    $now = time();
    $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $jwtPayload = base64_encode(json_encode([
        'iss' => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/datastore',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]));
    $signatureInput = "$jwtHeader.$jwtPayload";
    openssl_sign($signatureInput, $signature, $sa['private_key'], 'sha256WithRSAEncryption');
    $jwt = "$signatureInput." . base64_encode($signature);

    $postFields = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]);

    $ch = curl_init("https://oauth2.googleapis.com/token");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['access_token'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="auth-container" style="max-width: 500px; margin: 100px auto;">
        <h2 style="text-align:center;">Đăng ký</h2>
        <?php if (!empty($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Tên hiển thị" required class="form-group"><br>
            <input type="email" name="email" placeholder="Email" required class="form-group"><br>
            <input type="password" name="password" placeholder="Mật khẩu" required class="form-group"><br>
            <div class="g-recaptcha" data-sitekey="6Lf2LGwrAAAAACTjX_uVV9GWgtf_-OdnpIh-QnUJ"></div>
            <br>
            <button type="submit" class="btn-register">Đăng ký</button>
        </form>
        <p style="text-align:center;">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
    </div>
</body>
</html>
