<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Kiểm tra quyền admin
requireAdmin();

header('Content-Type: application/json');

if (!isset($_GET['customer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu ID khách hàng'
    ]);
    exit;
}

$customerId = intval($_GET['customer_id']);

try {
    // Lấy thông tin khách hàng
    $sql = "SELECT * FROM khachhang WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();

    if (!$customer) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy khách hàng'
        ]);
        exit;
    }

    // Lấy lịch sử đơn hàng
    $sql_orders = "SELECT * FROM donhang WHERE khachhang_id = ? ORDER BY ngay_dat DESC";
    $stmt_orders = $conn->prepare($sql_orders);
    $stmt_orders->bind_param("i", $customerId);
    $stmt_orders->execute();
    $orders = $stmt_orders->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'customer' => $customer,
        'orders' => $orders
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
