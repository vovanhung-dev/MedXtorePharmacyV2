<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config/google-config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../vendor/autoload.php';


session_start();

// Khởi tạo controller
$controller = new AuthController($dbConnection); // Pass the required argument

// Nếu đã đăng nhập, chuyển hướng
if ($controller->isLoggedIn()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Xử lý callback từ Google
if (isset($_GET['code'])) {
    try {
        $client = getGoogleClient();
        
        // Trao đổi code lấy token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        // Kiểm tra lỗi trong token
        if (isset($token['error'])) {
            throw new Exception($token['error_description'] ?? $token['error']);
        }
        
        $client->setAccessToken($token['access_token']);
        
        // Lấy thông tin người dùng
        $google_oauth = new Google\Service\Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        // Lấy thông tin cần thiết
        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $avatar = $google_account_info->picture;
        $google_id = $google_account_info->id;
        
        // Thực hiện đăng nhập hoặc đăng ký bằng tài khoản Google
        $result = $controller->loginWithGoogle($email, $name, $avatar, $google_id);
        
        if ($result['success']) {
            // Chuyển hướng dựa trên vai trò
            if ($controller->canAccessAdmin()) {
                header('Location: ' . BASE_URL . '/admin/');
            } else {
                header('Location: ' . BASE_URL);
            }
            exit;
        } else {
            // Nếu có lỗi, chuyển hướng về trang đăng nhập với thông báo
            $_SESSION['google_login_error'] = $result['message'];
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['google_login_error'] = "Lỗi khi đăng nhập với Google: " . $e->getMessage();
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
} else {
    // Nếu không có code, chuyển hướng về trang đăng nhập
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
