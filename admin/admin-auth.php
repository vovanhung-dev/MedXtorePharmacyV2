<?php
// admin-auth.php – kiểm tra quyền admin, dùng kèm .htaccess (auto_prepend_file)

// Khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tải config để sử dụng $base_url
require_once(__DIR__ . '/../includes/config.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập để truy cập trang quản trị.";
    header('Location: ' . $base_url . '/pages/login.php');
    exit;
}

// Kiểm tra quyền admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    // Trả mã lỗi 403 Forbidden
    http_response_code(403);
    echo "
        <div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            <h1 style='color: red;'>Access Denied</h1>
            <p>Bạn không có quyền truy cập khu vực quản trị.</p>
            <a href='" . $base_url . "/pages/home.php' style='color: #007bff;'>← Quay về trang chủ</a>
        </div>
    ";
    exit;
}
