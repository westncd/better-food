<?php
session_start();
require_once 'config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Bạn không có quyền truy cập trang này.');
}

// Truy vấn danh sách người dùng
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <h2>Quản lý người dùng</h2>
        <?php
            // Xử lý yêu cầu ban/unban
            if (isset($_GET['toggle_ban']) && is_numeric($_GET['toggle_ban'])) {
                $uid = intval($_GET['toggle_ban']);
                $get = $conn->query("SELECT status FROM users WHERE id = $uid");
                if ($get && $row = $get->fetch_assoc()) {
                    $newStatus = $row['status'] ? 0 : 1;
                    $conn->query("UPDATE users SET status = $newStatus WHERE id = $uid");
                    header("Location: user-management.php");
                    exit;
                }
            }
        ?>

        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tài khoản</th>
                    <th>Email</th>
                    <th>Họ tên</th>
                    <th>Điện thoại</th>
                    <th>Quyền</th>
                    <th>Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= $user['full_name'] ?: '-' ?></td>
                    <td><?= $user['phone'] ?: '-' ?></td>
                    <td>
                        <span class="<?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                            <?= $user['role'] === 'admin' ? 'Admin' : 'User' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['id'] !== $_SESSION['user']['id']): ?>
                            <a href="user-management.php?toggle_ban=<?= $user['id'] ?>" 
                               style="color: white; padding: 5px 10px; border-radius: 6px;
                                      background: <?= $user['status'] ? '#e74c3c' : '#27ae60' ?>;">
                                <?= $user['status'] ? 'Ban' : 'Unban' ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td><?= $user['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="index.php" class="btn-back">← Về trang chủ</a>
    </div>
</body>
</html>
