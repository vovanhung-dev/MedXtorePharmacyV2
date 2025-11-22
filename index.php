<?php
/**
 * MedXtore Pharmacy - POS System
 * Entry Point - Automatic Redirect
 */

session_start();

// Kiểm tra xem user đã đăng nhập chưa
if (isset($_SESSION['user_id']) && isset($_SESSION['vaitro_id'])) {
    // Nếu là Admin hoặc nhân viên, redirect đến POS Dashboard
    if ($_SESSION['vaitro_id'] == 1 || $_SESSION['vaitro_id'] == 2) {
        header('Location: /pages/pos-dashboard.php');
        exit;
    }
    // User thường, redirect đến trang chủ
    header('Location: /pages/home.php');
    exit;
} else {
    // Chưa đăng nhập, redirect đến login
    header('Location: /pages/login.php');
    exit;
}
