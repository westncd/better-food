<?php
session_start();
require_once 'config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Bạn không có quyền truy cập trang này.');
}

// Xử lý yêu cầu ban/unban
if (isset($_GET['toggle_ban']) && is_numeric($_GET['toggle_ban'])) {
    $uid = intval($_GET['toggle_ban']);
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $newStatus = $row['status'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $uid]);
        header("Location: user-management.php");
        exit;
    }
}

// Truy vấn danh sách người dùng
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-container {
            max-width: 1000px;
            margin: 100px auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .admin-container h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        table.user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        table th, table td {
            border: 1px solid #eee;
            padding: 10px;
            text-align: left;
        }
        .badge-admin {
            background: #2980b9;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .badge-user {
            background: #7f8c8d;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .btn-back {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 16px;
            background: #27ae60;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }
        .btn-back:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h2>Quản lý người dùng</h2>

        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tài khoản</th>
                    <th>Email</th>
                    <th>Họ tên</th>
                    <th>Điện thoại</th>
                    <th>Quyền</th>
                    <th>Hành động</th>
                    <th>Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['full_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
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
