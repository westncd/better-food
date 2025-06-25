<?php
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Bạn không có quyền truy cập trang này.');
}

$projectId = 'foodstore-1c8f1'; // Thay bằng project của bạn
$collectionUrl = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/users";

// Hàm lấy toàn bộ người dùng
function getAllUsers() {
    global $collectionUrl;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $collectionUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);

    $users = [];
    if (isset($data['documents'])) {
        foreach ($data['documents'] as $doc) {
            $uid = basename($doc['name']);
            $fields = $doc['fields'];

            $users[] = [
                'uid'        => $uid,
                'username'   => $fields['username']['stringValue'] ?? '',
                'email'      => $fields['email']['stringValue'] ?? '',
                'full_name'  => $fields['full_name']['stringValue'] ?? '',
                'phone'      => $fields['phone']['stringValue'] ?? '',
                'role'       => $fields['role']['stringValue'] ?? 'user',
                'status'     => isset($fields['status']['integerValue']) ? (int)$fields['status']['integerValue'] : 1,
                'created_at' => $fields['created_at']['timestampValue'] ?? '',
            ];
        }
    }
    return $users;
}

// Xử lý ban/unban
if (isset($_GET['toggle_ban']) && $_GET['toggle_ban']) {
    $uid = $_GET['toggle_ban'];
    $docUrl = "$collectionUrl/$uid";

    // Lấy trạng thái hiện tại
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $docUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $doc = json_decode($response, true);
    $currentStatus = isset($doc['fields']['status']['integerValue']) ? (int)$doc['fields']['status']['integerValue'] : 1;
    $newStatus = $currentStatus ? 0 : 1;

    // Gửi PATCH để cập nhật status
    $updateData = json_encode(['fields' => ['status' => ['integerValue' => $newStatus]]]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $docUrl . "?updateMask.fieldPaths=status");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $updateData);
    curl_exec($ch);
    curl_close($ch);

    header("Location: user-management.php");
    exit;
}

$users = getAllUsers();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-5xl mx-auto bg-white p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Quản lý người dùng</h2>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200 text-gray-700">
                        <th class="border border-gray-300 px-4 py-3 text-left">Tài khoản</th>
                        <th class="border border-gray-300 px-4 py-3 text-left">Email</th>
                        <th class="border border-gray-300 px-4 py-3 text-left">Họ tên</th>
                        <th class="border border-gray-300 px-4 py-3 text-left">Điện thoại</th>
                        <th class="border border-gray-300 px-4 py-3 text-left">Quyền</th>
                        <th class="border border-gray-300 px-4 py-3 text-left">Hành động</th>
                        <th class="border border-gray-300 px-4 py-3 text-left">Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="border border-gray-300 px-4 py-3"><?= htmlspecialchars($user['username']) ?></td>
                        <td class="border border-gray-300 px-4 py-3"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="border border-gray-300 px-4 py-3"><?= htmlspecialchars($user['full_name'] ?? '-') ?></td>
                        <td class="border border-gray-300 px-4 py-3"><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                        <td class="border border-gray-300 px-4 py-3">
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold 
                                <?= $user['role'] === 'admin' ? 'bg-blue-500 text-white' : 'bg-gray-500 text-white' ?>">
                                <?= $user['role'] === 'admin' ? 'Admin' : 'User' ?>
                            </span>
                        </td>
                        <td class="border border-gray-300 px-4 py-3">
                            <?php if ($user['uid'] !== $_SESSION['user']['uid']): ?>
                                <a href="user-management.php?toggle_ban=<?= $user['uid'] ?>"
                                   class="inline-block px-4 py-2 rounded-lg text-white text-sm font-medium
                                          <?= $user['status'] ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600' ?>">
                                    <?= $user['status'] ? 'Ban' : 'Unban' ?>
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="border border-gray-300 px-4 py-3">
                            <?= date('d/m/Y H:i:s', strtotime($user['created_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <a href="index.php" 
           class="mt-6 inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            ← Về trang chủ
        </a>
    </div>
</body>
</html>