<?php
session_start();
require_once('../includes/config.php');
require_once('../controllers/AuthController.php');

// Khởi tạo AuthController
$auth = new AuthController($conn);

// Thực hiện đăng xuất
$auth->logout();

// Chuyển hướng về trang đăng nhập
header('Location: ' . $base_url . '/pages/login.php');
exit;
?>