<?php
// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Định nghĩa đường dẫn gốc
$base_url = ''; // Để trống khi dùng PHP built-in server

$config = [
    'base_url' => $base_url,
    'pages_url' => $base_url . '/pages',
    'admin_url' => $base_url . '/admin',
    'assets_url' => $base_url . '/assets',
];

// Hàm tạo URL
if (!function_exists('url')) {
    function url($path = '') {
        global $config;
        return $config['base_url'] . $path;
    }
}

if (!function_exists('page_url')) {
    function page_url($page = '') {
        global $config;
        return $config['pages_url'] . '/' . ltrim($page, '/');
    }
}

// 👉 Thêm các biến kết nối PDO
$db_host = 'localhost';
$db_name = 'pharmacy';       // tên CSDL của bạn
$db_user = 'root';           // user đăng nhập
$db_pass = 'root';           // mật khẩu

// Nếu muốn giữ kết nối mysqli cũ:
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    error_log("Lỗi kết nối MySQLi: " . $conn->connect_error);
    $conn = null;
}
