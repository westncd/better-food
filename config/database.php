<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'food_store';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// SQL to create database and tables
$sql_create_db = "CREATE DATABASE IF NOT EXISTS food_store CHARACTER SET utf8 COLLATE utf8_unicode_ci";

// Create tables
$sql_create_tables = "
-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT 0,
    image VARCHAR(255),
    category_id INT,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'user') DEFAULT 'user',
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'confirmed', 'preparing', 'delivering', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    delivery_address TEXT,
    delivery_phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Create contacts table
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample categories
INSERT IGNORE INTO categories (id, name, description, image) VALUES
(1, 'Món chính', 'Các món ăn chính trong ngày', 'assets/images/categories/main-dish.jpg'),
(2, 'Đồ uống', 'Các loại nước uống, trà, cà phê', 'assets/images/categories/drinks.jpg'),
(3, 'Tráng miệng', 'Bánh ngọt, kem, chè', 'assets/images/categories/desserts.jpg'),
(4, 'Món ăn nhanh', 'Hamburger, pizza, sandwich', 'assets/images/categories/fast-food.jpg');

-- Insert sample products
INSERT IGNORE INTO products (id, name, description, price, sale_price, image, category_id) VALUES
(1, 'Phở bò đặc biệt', 'Phở bò truyền thống với thịt bò tái, chín, gầu', 65000, 0, 'assets/images/products/pho-bo.jpg', 1),
(2, 'Bánh mì thịt nướng', 'Bánh mì giòn với thịt nướng thơm ngon', 25000, 20000, 'assets/images/products/banh-mi.jpg', 1),
(3, 'Cà phê sữa đá', 'Cà phê phin truyền thống với sữa đặc', 18000, 0, 'assets/images/products/ca-phe-sua.jpg', 2),
(4, 'Trà sữa trân châu', 'Trà sữa thơm ngon với trân châu dai', 35000, 30000, 'assets/images/products/tra-sua.jpg', 2),
(5, 'Bánh flan', 'Bánh flan mềm mịn, thơm ngon', 15000, 0, 'assets/images/products/banh-flan.jpg', 3),
(6, 'Kem dừa', 'Kem dừa tươi mát, ngọt dịu', 20000, 0, 'assets/images/products/kem-dua.jpg', 3),
(7, 'Pizza hải sản', 'Pizza với tôm, mực, cua tươi ngon', 120000, 100000, 'assets/images/products/pizza.jpg', 4),
(8, 'Hamburger bò', 'Hamburger với thịt bò nướng, rau xanh', 45000, 0, 'assets/images/products/hamburger.jpg', 4);

-- Insert admin user (password: admin123)
INSERT IGNORE INTO users (id, username, email, password, full_name, role) VALUES
(1, 'admin', 'admin@foodstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');
";

// Execute table creation
$conn->multi_query($sql_create_tables);

// Clear any remaining results
while ($conn->next_result()) {
    if ($result = $conn->store_result()) {
        $result->free();
    }
}
?>