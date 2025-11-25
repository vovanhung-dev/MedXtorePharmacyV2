<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../services/MomoService.php';
require_once __DIR__ . '/../models/MomoInfo.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/OrderController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class PaymentController {
    private $momoService;
    private $db;

    public function __construct() {
        try {
            global $conn;
            $this->db = $conn;
            $this->db->set_charset("utf8mb4");  // Set UTF-8 for this connection
            
            // Cấu hình Momo với URL callback mới
            $momoConfig = [
                'partnerCode' => 'MOMO',
                'accessKey' => 'F8BBA842ECF85',
                'secretKey' => 'K951B6PE1waDMi640xX08PD3vg6EkVlz',
                'momoApiUrl' => 'https://test-payment.momo.vn/v2/gateway/api/create',
                'returnUrl' => 'http://localhost:81/pages/momo_return.php',
                'notifyUrl' => 'http://localhost:81/controllers/PaymentController.php?action=notify',
                'requestType' => 'captureWallet'
            ];

            $this->momoService = new MomoService($momoConfig);
        } catch (Exception $e) {
            throw new Exception("Lỗi khởi tạo PaymentController: " . $e->getMessage());
        }
    }

    private function generateUniqueOrderId($originalOrderId) {
        // Tạo orderId mới chỉ chứa số, kết hợp orderId gốc với timestamp
        return $originalOrderId . substr(time(), -4) . rand(1000, 9999);
    }

    public function processMomoPayment() {
        try {
            // Kiểm tra thông tin đơn hàng trong session
            if (!isset($_SESSION['pending_order'])) {
                throw new Exception("Không tìm thấy thông tin đơn hàng");
            }

            $orderInfo = $_SESSION['pending_order'];

            // Tạo orderId mới để tránh trùng lặp
            $momoOrderId = $this->generateUniqueOrderId($orderInfo['order_id']);
            
            // Lưu mapping giữa momoOrderId và orderId gốc
            if (!isset($_SESSION['momo_order_mapping'])) {
                $_SESSION['momo_order_mapping'] = array();
            }
            $_SESSION['momo_order_mapping'][$momoOrderId] = $orderInfo['order_id'];

            // Đảm bảo amount là số nguyên
            $amount = (int)$orderInfo['amount'];
            
            // Tạo mô tả đơn hàng
            $orderDescription = "Thanh toan don hang #" . $orderInfo['order_id'];

            // Lưu thông tin đơn hàng vào session để xử lý sau
            $_SESSION['pending_momo_order'] = [
                'cart' => $_SESSION['pending_cart'],
                'order_info' => [
                    'order_id' => $orderInfo['order_id'],
                    'khachhang_id' => $_SESSION['user_id'], // Ensure we're using the logged-in user's ID
                    'tongtien' => $orderInfo['amount'],
                    'amount' => $amount
                ],
                'momo_order_id' => $momoOrderId
            ];

            // Xóa đơn hàng tạm và chi tiết đơn hàng
            $this->db->begin_transaction();
            try {
                // Xóa chi tiết đơn hàng
                $sql = "DELETE FROM chitiet_donhang WHERE donhang_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $orderInfo['order_id']);
                $stmt->execute();

                // Xóa đơn hàng
                $sql = "DELETE FROM donhang WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $orderInfo['order_id']);
                $stmt->execute();

                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollback();
                error_log("Lỗi xóa đơn hàng tạm: " . $e->getMessage());
            }

            // Tạo URL thanh toán với orderId mới
            $response = $this->momoService->createPaymentUrl(
                $momoOrderId,
                $amount,
                $orderDescription
            );

            // Kiểm tra response và chuyển hướng
            if (isset($response['payUrl'])) {
                header("Location: " . $response['payUrl']);
                exit();
            } else {
                throw new Exception("Không thể tạo URL thanh toán Momo: " . json_encode($response));
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Lỗi thanh toán Momo: " . $e->getMessage();
            
            // Khôi phục giỏ hàng nếu có lỗi
            if (isset($_SESSION['pending_cart'])) {
                $userId = $_SESSION['user_id'];
                $_SESSION['carts'][$userId] = $_SESSION['pending_cart'];
                unset($_SESSION['pending_cart']);
            }
            
            header("Location: /pages/checkout.php");
            exit();
        }
    }

    public function handleMomoReturn($momoOrderId, $amount, $orderInfo, $transId) {
        try {
            // Kiểm tra kết quả thanh toán từ MoMo
            $resultCode = isset($_GET['resultCode']) ? $_GET['resultCode'] : null;
            error_log("MoMo resultCode: " . $resultCode . " (type: " . gettype($resultCode) . ")");
            
            // Kiểm tra session tồn tại
            if (!isset($_SESSION['pending_momo_order'])) {
                throw new Exception("Không tìm thấy thông tin đơn hàng trong session");
            }

            // So sánh chính xác với string '0' hoặc số 0
            if ($resultCode === '0' || $resultCode === 0) {
                error_log("Payment successful, processing order...");
                $pendingOrder = $_SESSION['pending_momo_order'];
                $orderInfo = $pendingOrder['order_info'];

                error_log("Order Info: " . json_encode($orderInfo));

                $this->db->begin_transaction();
                try {
                    // Tạo đơn hàng mới
                    $sql = "INSERT INTO donhang (khachhang_id, nguoidung_id, tongtien, trangthai, phuongthuc_thanhtoan, ngay_dat) 
                           VALUES (?, ?, ?, 'dathanhtoan', 'momo', NOW())";
                    $stmt = $this->db->prepare($sql);
                    error_log("Creating order with khachhang_id: " . $orderInfo['khachhang_id'] . ", nguoidung_id: " . $_SESSION['user_id'] . ", amount: " . $orderInfo['tongtien']);
                    $stmt->bind_param("iid", 
                        $orderInfo['khachhang_id'],
                        $_SESSION['user_id'],
                        $orderInfo['tongtien']
                    );
                    if (!$stmt->execute()) {
                        error_log("SQL Error (donhang): " . $stmt->error);
                        throw new Exception("Không thể tạo đơn hàng mới: " . $stmt->error);
                    }

                    $newOrderId = $this->db->insert_id;
                    error_log("Created new order with ID: " . $newOrderId);

                    // Thêm chi tiết đơn hàng
                    $cart = $pendingOrder['cart'];
                    foreach ($cart as $item) {
                        error_log("Processing cart item: " . json_encode($item));
                        
                        // Lấy đơn giá từ giá gốc của sản phẩm
                        $dongia = isset($item['gia']) ? $item['gia'] : 
                                (isset($item['dongia']) ? $item['dongia'] : 
                                (isset($item['price']) ? $item['price'] : 0));
                                
                        $sql = "INSERT INTO chitiet_donhang (donhang_id, thuoc_id, soluong, dongia) 
                               VALUES (?, ?, ?, ?)";
                        $stmt = $this->db->prepare($sql);
                        $stmt->bind_param("iiid",
                            $newOrderId,
                            $item['thuoc_id'],
                            $item['soluong'],
                            $dongia
                        );
                        
                        error_log("Inserting order detail - donhang_id: $newOrderId, thuoc_id: {$item['thuoc_id']}, soluong: {$item['soluong']}, dongia: $dongia");
                        
                        if (!$stmt->execute()) {
                            error_log("SQL Error (chitiet_donhang): " . $stmt->error);
                            throw new Exception("Không thể thêm chi tiết đơn hàng: " . $stmt->error);
                        }
                    }

                    // Lưu thông tin thanh toán MoMo
                    $sql = "INSERT INTO momo_payments (order_id, customer_id, amount, order_info, date_paid) 
                           VALUES (?, ?, ?, ?, NOW())";
                    $stmt = $this->db->prepare($sql);
                    $orderInfoJson = json_encode($orderInfo);
                    $stmt->bind_param("iids", 
                        $newOrderId,
                        $orderInfo['khachhang_id'],
                        $amount,
                        $orderInfoJson
                    );
                    if (!$stmt->execute()) {
                        error_log("SQL Error (momo_payments): " . $stmt->error);
                        throw new Exception("Không thể lưu thông tin thanh toán MoMo: " . $stmt->error);
                    }

                    // Cập nhật số lượng trong kho
                    foreach ($cart as $item) {
                        $updateSql = "UPDATE khohang 
                                    SET soluong = soluong - ? 
                                    WHERE thuoc_id = ? 
                                    AND soluong >= ?";
                        
                        $updateStmt = $this->db->prepare($updateSql);
                        $updateStmt->bind_param("iii", 
                            $item['soluong'], 
                            $item['thuoc_id'],
                            $item['soluong']
                        );
                        
                        if (!$updateStmt->execute() || $updateStmt->affected_rows === 0) {
                            error_log("SQL Error (khohang): " . $updateStmt->error);
                            throw new Exception("Không đủ số lượng trong kho cho sản phẩm ID: " . $item['thuoc_id']);
                        }
                    }

                    $this->db->commit();
                    error_log("Transaction committed successfully");

                    // Xóa các session
                    if (isset($_SESSION['user_id'])) {
                        unset($_SESSION['carts'][$_SESSION['user_id']]);
                    }
                    unset($_SESSION['pending_cart']);
                    unset($_SESSION['pending_order']);
                    unset($_SESSION['pending_momo_order']);
                    if (isset($_SESSION['momo_order_mapping'][$momoOrderId])) {
                        unset($_SESSION['momo_order_mapping'][$momoOrderId]);
                    }

                    // Chuyển hướng đến trang thông báo thành công
                    $_SESSION['success'] = "Thanh toán thành công! Cảm ơn bạn đã mua hàng.";
                    header("Location: /pages/order_success.php");
                    exit();

                } catch (Exception $e) {
                    $this->db->rollback();
                    error_log("Transaction rolled back: " . $e->getMessage());
                    throw $e;
                }
            } else {
                // Thanh toán không thành công
                error_log("Payment failed with resultCode: " . $resultCode);
                
                // Khôi phục giỏ hàng từ session
                $userId = $_SESSION['user_id'];
                $_SESSION['carts'][$userId] = $_SESSION['pending_momo_order']['cart'];
                
                // Xóa các session tạm
                unset($_SESSION['pending_cart']);
                unset($_SESSION['pending_order']);
                unset($_SESSION['pending_momo_order']);
                if (isset($_SESSION['momo_order_mapping'][$momoOrderId])) {
                    unset($_SESSION['momo_order_mapping'][$momoOrderId]);
                }
                
                $_SESSION['error'] = "Thanh toán MoMo không thành công. Vui lòng thử lại.";
                header("Location: /pages/cart.php");
                exit();
            }

        } catch (Exception $e) {
            error_log("Error in handleMomoReturn: " . $e->getMessage());
            $_SESSION['error'] = "Có lỗi xảy ra trong quá trình xử lý thanh toán: " . $e->getMessage();
            
            // Khôi phục giỏ hàng nếu có lỗi
            if (isset($_SESSION['pending_momo_order'])) {
                $userId = $_SESSION['user_id'];
                $_SESSION['carts'][$userId] = $_SESSION['pending_momo_order']['cart'];
            }
            
            header("Location: /pages/cart.php");
            exit();
        }
    }

    public function handleCallback() {
        try {
            // Log toàn bộ dữ liệu callback để debug
            error_log("MoMo Callback Data: " . json_encode($_GET));
            error_log("Request URI: " . $_SERVER['REQUEST_URI']);
            
            // Chỉ xử lý notify URL
            if (strpos($_SERVER['REQUEST_URI'], 'action=notify') !== false) {
                $resultCode = $_GET['resultCode'] ?? '';
                
                // Trả về response cho MoMo
                echo json_encode(['message' => 'Received']);
                exit();
            }
            
        } catch (Exception $e) {
            error_log("Lỗi xử lý MoMo callback: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            if (strpos($_SERVER['REQUEST_URI'], 'action=notify') !== false) {
                echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
                exit();
            }
            
            throw $e;
        }
    }

    private function extractOriginalOrderId($momoOrderId) {
        // Lấy orderId gốc từ momoOrderId (loại bỏ timestamp và số random)
        return preg_replace('/\d{8}$/', '', $momoOrderId);
    }
}

// Xử lý request
if (isset($_GET['action'])) {
    $controller = new PaymentController();
    
    switch ($_GET['action']) {
        case 'momo':
            $controller->processMomoPayment();
            break;
        case 'notify':
            $controller->handleCallback();
            break;
        case 'callback':
            // Chuyển hướng callback về trang xử lý riêng
            $queryString = http_build_query($_GET);
            header("Location: /pages/momo_return.php?" . $queryString);
            exit();
        default:
            header("Location: /pages/checkout.php");
            exit();
    }
} 