<?php
require 'vendor/autoload.php';
require 'config/database.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\InvalidPassword;
use Kreait\Firebase\Exception\Auth\UserNotFound;

session_start();

// Khởi tạo Firebase
$factory = (new Factory)->withServiceAccount(__DIR__ . '/foodstore-1c8f1-firebase-adminsdk-fbsvc-41b5c32875.json');
$auth = $factory->createAuth();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_email'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    // Kiểm tra reCAPTCHA
    if (empty($recaptchaResponse)) {
        $error = "Vui lòng xác nhận bạn không phải robot.";
    } else {
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $secretKey = '6Lf2LGwrAAAAAF8daYzodb5bXnn_35NuExgMDoeF';

        $response = file_get_contents($verifyUrl . '?secret=' . $secretKey . '&response=' . $recaptchaResponse . '&remoteip=' . $_SERVER['REMOTE_ADDR']);
        $decoded = json_decode($response, true);

        if (empty($decoded['success'])) {
            $error = "Xác thực reCAPTCHA không thành công.";
        }
    }

    if (empty($error)) {
        try {
            $signInResult = $auth->signInWithEmailAndPassword($email, $password);
            $firebaseUser = $signInResult->data();
            $uid = $firebaseUser['localId'] ?? null;

            $userData = getUserDataFromFirestore($uid);

            if ($userData) {
                if (isset($userData['status']) && $userData['status'] == 0) {
                    $error = "Tài khoản của bạn đã bị khóa.";
                } else {
                    $_SESSION['user'] = [
                        'uid'        => $uid,
                        'username'   => $userData['username'] ?? '',
                        'email'      => $userData['email'] ?? '',
                        'full_name'  => $userData['full_name'] ?? '',
                        'phone'      => $userData['phone'] ?? '',
                        'role'       => $userData['role'] ?? 'user',
                        'status'     => isset($userData['status']) ? (int)$userData['status'] : 1,
                        'created_at' => $userData['created_at'] ?? '',
                    ];

                    header("Location: index.php");
                    exit;
                }
            } else {
                $error = "Không tìm thấy thông tin người dùng.";
            }

        } catch (InvalidPassword | UserNotFound $e) {
            $error = "Email hoặc mật khẩu không đúng!";
        } catch (Exception $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

// === Firestore REST API ===
function getUserDataFromFirestore($uid): ?array {
    $projectId = 'foodstore-1c8f1';
    $accessToken = getFirebaseAccessToken();

    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/users/$uid";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if (isset($decoded['fields'])) {
        return parseFirestoreFields($decoded['fields']);
    }
    return null;
}

function getFirebaseAccessToken(): string {
    $sa = json_decode(file_get_contents(__DIR__ . '/foodstore-1c8f1-firebase-adminsdk-fbsvc-41b5c32875.json'), true);
    $now = time();

    $jwtHeader = base64_encode(json_encode(["alg" => "RS256", "typ" => "JWT"]));
    $jwtPayload = base64_encode(json_encode([
        "iss" => $sa['client_email'],
        "scope" => "https://www.googleapis.com/auth/datastore",
        "aud" => "https://oauth2.googleapis.com/token",
        "exp" => $now + 3600,
        "iat" => $now
    ]));

    $data = "$jwtHeader.$jwtPayload";
    openssl_sign($data, $signature, $sa['private_key'], 'sha256WithRSAEncryption');
    $jwt = "$data." . base64_encode($signature);

    $postFields = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]);

    $ch = curl_init("https://oauth2.googleapis.com/token");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $res = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($res, true);
    return $json['access_token'] ?? '';
}

function parseFirestoreFields(array $fields): array {
    $parsed = [];
    foreach ($fields as $key => $val) {
        $parsed[$key] = reset($val);
    }
    return $parsed;
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
        <h2 style="text-align:center;">Đăng nhập</h2>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <!-- Đăng nhập bằng email -->
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required class="form-group"><br>
            <input type="password" name="password" placeholder="Mật khẩu" required class="form-group"><br>
            <div class="g-recaptcha" data-sitekey="6Lf2LGwrAAAAACTjX_uVV9GWgtf_-OdnpIh-QnUJ"></div><br>
            <button type="submit" name="login_email" class="btn-login">Đăng nhập bằng Email</button>
        </form>

        <hr style="margin: 20px 0;">

        <!-- Google Sign-In -->
        <div id="g_id_onload" data-client_id="555580540304-pan2juv0g8vik6d71lhpgm151bk164k7.apps.googleusercontent.com"
            data-context="signin" data-ux_mode="popup" data-callback="handleCredentialResponse"
            data-auto_prompt="false"></div>

        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="sign_in_with"
            data-shape="rectangular" data-logo_alignment="left">
        </div>

        <script>
        function handleCredentialResponse(response) {
            fetch('google-login-handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_token: response.credential
                    })
                })
                .then(res => res.json())
                .then(data => {
                    console.log('✅ Response:', data);
                    if (data.success) {
                        window.location.href = 'index.php';
                    } else {
                        alert("❌ Google login thất bại: " + data.message);
                    }
                })
                .catch(err => {
                    console.error('🔥 Lỗi fetch:', err);
                    alert("⚠️ Có lỗi xảy ra khi gửi token đến máy chủ: " + err.message);
                });
        }
        </script>

        <p style="text-align:center; margin-top: 1rem;">Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
    </div>
</body>

</html>