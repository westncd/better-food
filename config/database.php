<?php
try {
    // Copy database từ thư mục chính vào thư mục tạm (App Engine cho phép ghi ở đây)
    $source = __DIR__ . '/database.sqlite';
    $target = sys_get_temp_dir() . '/food_delivery_database.sqlite';

    if (!file_exists($target)) {
        copy($source, $target);
    }

    // Kết nối đến bản ghi được
    $conn = new PDO("sqlite:" . $target);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (Exception $e) {
    die("Không thể kết nối SQLite: " . $e->getMessage());
}