<?php
try {
    $conn = new PDO("sqlite:database.sqlite");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        image TEXT,
        status INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        sale_price REAL DEFAULT 0,
        image TEXT,
        category_id INTEGER,
        status INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    );

    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE,
        email TEXT UNIQUE,
        password TEXT,
        full_name TEXT,
        phone TEXT,
        address TEXT,
        role TEXT DEFAULT 'user',
        status INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        total_amount REAL,
        status TEXT DEFAULT 'pending',
        payment_method TEXT,
        payment_status TEXT DEFAULT 'pending',
        delivery_address TEXT,
        delivery_phone TEXT,
        notes TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER,
        product_id INTEGER,
        quantity INTEGER NOT NULL,
        price REAL NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    );

    CREATE TABLE IF NOT EXISTS contacts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        message TEXT NOT NULL,
        status TEXT DEFAULT 'new',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    INSERT OR IGNORE INTO categories (id, name, description, image) VALUES
    (1, 'Món chính', 'Các món ăn chính trong ngày',   'uploads/main-dish.jpg'),
    (2, 'Đồ uống', 'Các loại nước uống, trà, cà phê', 'uploads/drinks.jpg'),
    (3, 'Tráng miệng', 'Bánh ngọt, kem, chè',         'uploads/desserts.jpg'),
    (4, 'Món ăn nhanh', 'Hamburger, pizza, sandwich', 'uploads/fast-food.jpg');

    INSERT OR IGNORE INTO products (id, name, description, price, sale_price, image, category_id) VALUES
    (1, 'Phở bò đặc biệt', 'Phở bò truyền thống với thịt bò tái, chín, gầu', 65000, 0, 'uploads/pho-bo.jpg', 1),
    (2, 'Bánh mì thịt nướng', 'Bánh mì giòn với thịt nướng thơm ngon', 25000, 20000,   'uploads/banh-mi.jpg', 1),
    (3, 'Cà phê sữa đá', 'Cà phê phin truyền thống với sữa đặc', 18000, 0,             'uploads/ca-phe-sua.jpg', 2),
    (4, 'Trà sữa trân châu', 'Trà sữa thơm ngon với trân châu dai', 35000, 30000,      'uploads/tra-sua.jpg', 2),
    (5, 'Bánh flan', 'Bánh flan mềm mịn, thơm ngon', 15000, 0,                         'uploads/banh-flan.jpg', 3),
    (6, 'Kem dừa', 'Kem dừa tươi mát, ngọt dịu', 20000, 0,                             'uploads/kem-dua.jpg', 3),
    (7, 'Pizza hải sản', 'Pizza với tôm, mực, cua tươi ngon', 120000, 100000,          'uploads/pizza.jpg', 4),
    (8, 'Hamburger bò', 'Hamburger với thịt bò nướng, rau xanh', 45000, 0,             'uploads/hamburger.jpg', 4);

    INSERT OR IGNORE INTO users (id, username, email, password, full_name, role) VALUES
    (1, 'admin', 'admin@foodstore.com', '$2y$10$6Kq6L3kc2cRD90CBmfl1F.oCoDOokCNmB0a4C.UQIGWM0yDCgcwwK', 'Administrator', 'admin');
    ";

    $conn->exec($sql);
    echo "✅ Database SQLite đã khởi tạo thành công!";
} catch (Exception $e) {
    die("❌ Lỗi: " . $e->getMessage());
}
?>
