<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../controllers/PaymentController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Thêm debug logging
error_log("MoMo Return - GET Data: " . json_encode($_GET));
error_log("MoMo Return - Session Data: " . json_encode($_SESSION));
error_log("MoMo Return - Result Code Type: " . gettype($_GET['resultCode']));

try {
    $controller = new PaymentController();
    
    // Lấy thông tin từ URL
    $resultCode = $_GET['resultCode'] ?? '';
    $message = $_GET['message'] ?? '';
    $orderId = $_GET['orderId'] ?? '';
    $amount = $_GET['amount'] ?? 0;
    $transId = $_GET['transId'] ?? '';
    
    // Debug log
    error_log("MoMo Return Data - Result Code: " . $resultCode);
    error_log("MoMo Return Data - Order ID: " . $orderId);
    error_log("Session Data: " . json_encode($_SESSION['pending_momo_order'] ?? null));
    
    // Xử lý thanh toán
    if ($resultCode === '0' && isset($_SESSION['pending_momo_order'])) {
        // Lấy thông tin đơn hàng từ session
        $pendingOrder = $_SESSION['pending_momo_order'];
        $orderInfo = $pendingOrder['order_info'];
        
        error_log("Processing successful payment with order info: " . json_encode($orderInfo));
        
        // Thanh toán thành công
        $controller->handleMomoReturn($orderId, $amount, $orderInfo, $transId);
        
        $_SESSION['success'] = "Thanh toán thành công!";
        header("Location: /pages/order_success.php");
        exit();
    }
    
    // Nếu không thành công hoặc có lỗi
    error_log("Payment failed or invalid: resultCode = " . $resultCode . ", session exists = " . isset($_SESSION['pending_momo_order']));
    $_SESSION['error'] = "Thanh toán thất bại: " . $message;
    if (isset($_SESSION['pending_momo_order'])) {
        // Khôi phục giỏ hàng
        $userId = $_SESSION['user_id'];
        $_SESSION['carts'][$userId] = $_SESSION['pending_momo_order']['cart'];
        
        // Xóa session tạm
        unset($_SESSION['pending_cart']);
        unset($_SESSION['pending_order']);
        unset($_SESSION['pending_momo_order']);
    }
    
    header("Location: /pages/cart.php");
    exit();
    
} catch (Exception $e) {
    error_log("Lỗi xử lý MoMo return: " . $e->getMessage());
    $_SESSION['error'] = "Lỗi xử lý thanh toán: " . $e->getMessage();
    
    // Khôi phục giỏ hàng nếu có lỗi
    if (isset($_SESSION['pending_momo_order'])) {
        $userId = $_SESSION['user_id'];
        $_SESSION['carts'][$userId] = $_SESSION['pending_momo_order']['cart'];
        
        // Xóa session tạm
        unset($_SESSION['pending_cart']);
        unset($_SESSION['pending_order']);
        unset($_SESSION['pending_momo_order']);
    }
    
    header("Location: /pages/cart.php");
    exit();
}
?> 