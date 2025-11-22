<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/PaymentController.php';

session_start();

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $paymentController = new PaymentController($db);
    $response = $paymentController->handleCallback();

    // Redirect based on payment result
    if ($response['isSuccess']) {
        header("Location: /order-success.php?order_id=" . $response['orderId']);
    } else {
        header("Location: /checkout.php?error=payment_failed");
    }
    exit();
} catch (Exception $e) {
    error_log("Payment callback error: " . $e->getMessage());
    $_SESSION['error'] = "Có lỗi xảy ra trong quá trình xử lý thanh toán.";
    header("Location: /checkout.php?error=system_error");
    exit();
}
?> 