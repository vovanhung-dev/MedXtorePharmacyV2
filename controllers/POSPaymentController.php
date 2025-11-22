<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/POSTransaction.php';
require_once __DIR__ . '/../models/DiscountLog.php';
require_once __DIR__ . '/../models/InventoryBatch.php';
require_once __DIR__ . '/../services/MomoService.php';

class POSPaymentController {
    private $conn;
    private $posTransaction;
    private $discountLog;
    private $inventoryBatch;
    private $momoService;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->posTransaction = new POSTransaction();
        $this->discountLog = new DiscountLog();
        $this->inventoryBatch = new InventoryBatch();

        // Khởi tạo MoMo service nếu có cấu hình
        $this->initMomoService();
    }

    /**
     * Khởi tạo MoMo service từ .env hoặc config
     */
    private function initMomoService() {
        try {
            // Load từ .env file nếu có
            $envFile = __DIR__ . '/../.env';
            if (file_exists($envFile)) {
                $env = parse_ini_file($envFile);

                $momoConfig = [
                    'partnerCode' => $env['MOMO_PARTNER_CODE'] ?? '',
                    'accessKey' => $env['MOMO_ACCESS_KEY'] ?? '',
                    'secretKey' => $env['MOMO_SECRET_KEY'] ?? '',
                    'momoApiUrl' => ($env['MOMO_API_ENDPOINT'] ?? 'https://test-payment.momo.vn') . '/v2/gateway/api/create',
                    'returnUrl' => $env['MOMO_RETURN_URL'] ?? '',
                    'notifyUrl' => $env['MOMO_IPN_URL'] ?? '',
                    'requestType' => 'captureWallet'
                ];

                $this->momoService = new MomoService($momoConfig);
            }
        } catch (Exception $e) {
            error_log("Failed to initialize MoMo service: " . $e->getMessage());
            $this->momoService = null;
        }
    }

    /**
     * Thanh toán bằng tiền mặt
     *
     * @param int $orderId ID đơn hàng
     * @param float $cashReceived Số tiền khách đưa
     * @return array Response JSON
     */
    public function processCashPayment($orderId, $cashReceived) {
        try {
            // Lấy thông tin đơn hàng
            $order = $this->getOrderInfo($orderId);
            if (!$order) {
                return $this->jsonResponse(false, 'Không tìm thấy đơn hàng');
            }

            // Kiểm tra số tiền nhận
            $totalAmount = (float)$order['tongtien'];
            $cashReceived = (float)$cashReceived;

            if ($cashReceived < $totalAmount) {
                return $this->jsonResponse(false, 'Số tiền khách đưa không đủ');
            }

            // Tính tiền thối
            $change = $cashReceived - $totalAmount;

            // Tạo giao dịch thanh toán
            $sessionId = $this->getCurrentSessionId();
            $transactionId = $this->posTransaction->createTransaction(
                $orderId,
                $sessionId,
                'cash',
                $totalAmount,
                [
                    'cash_received' => $cashReceived,
                    'change_given' => $change,
                    'status' => 'pending'
                ]
            );

            if (!$transactionId) {
                return $this->jsonResponse(false, 'Không thể tạo giao dịch thanh toán');
            }

            // Xác nhận thanh toán
            $result = $this->confirmPayment($transactionId, $orderId);

            if ($result['success']) {
                return $this->jsonResponse(true, 'Thanh toán thành công', [
                    'transaction_id' => $transactionId,
                    'order_id' => $orderId,
                    'cash_received' => $cashReceived,
                    'change' => $change,
                    'payment_method' => 'cash'
                ]);
            }

            return $result;

        } catch (Exception $e) {
            return $this->jsonResponse(false, 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Thanh toán bằng chuyển khoản ngân hàng
     *
     * @param int $orderId ID đơn hàng
     * @param array $bankInfo Thông tin ngân hàng
     * @return array Response JSON
     */
    public function processBankingPayment($orderId, $bankInfo = []) {
        try {
            // Lấy thông tin đơn hàng
            $order = $this->getOrderInfo($orderId);
            if (!$order) {
                return $this->jsonResponse(false, 'Không tìm thấy đơn hàng');
            }

            $totalAmount = (float)$order['tongtien'];

            // Tạo mã giao dịch tham chiếu
            $transactionRef = 'BNK' . time() . $orderId;

            // Tạo giao dịch thanh toán (chờ xác nhận)
            $sessionId = $this->getCurrentSessionId();
            $transactionId = $this->posTransaction->createTransaction(
                $orderId,
                $sessionId,
                'banking',
                $totalAmount,
                [
                    'transaction_ref' => $transactionRef,
                    'status' => 'pending',
                    'notes' => 'Chờ xác nhận chuyển khoản'
                ]
            );

            if (!$transactionId) {
                return $this->jsonResponse(false, 'Không thể tạo giao dịch thanh toán');
            }

            // Tạo QR code cho chuyển khoản
            $qrData = $this->generateQRCode($orderId, $totalAmount, $transactionRef);

            return $this->jsonResponse(true, 'Đã tạo mã QR chuyển khoản', [
                'transaction_id' => $transactionId,
                'order_id' => $orderId,
                'amount' => $totalAmount,
                'transaction_ref' => $transactionRef,
                'qr_data' => $qrData,
                'payment_method' => 'banking',
                'status' => 'pending'
            ]);

        } catch (Exception $e) {
            return $this->jsonResponse(false, 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Thanh toán bằng MoMo
     *
     * @param int $orderId ID đơn hàng
     * @return array Response JSON
     */
    public function processMomoPayment($orderId) {
        try {
            if (!$this->momoService) {
                return $this->jsonResponse(false, 'Dịch vụ MoMo chưa được cấu hình');
            }

            // Lấy thông tin đơn hàng
            $order = $this->getOrderInfo($orderId);
            if (!$order) {
                return $this->jsonResponse(false, 'Không tìm thấy đơn hàng');
            }

            $totalAmount = (float)$order['tongtien'];
            $orderInfo = "Thanh toán đơn hàng #" . $orderId;

            // Tạo yêu cầu thanh toán MoMo
            $momoResponse = $this->momoService->createPaymentUrl($orderId, $totalAmount, $orderInfo);

            if (!isset($momoResponse['payUrl'])) {
                return $this->jsonResponse(false, 'Không thể tạo link thanh toán MoMo');
            }

            // Tạo giao dịch thanh toán (chờ xác nhận)
            $sessionId = $this->getCurrentSessionId();
            $transactionId = $this->posTransaction->createTransaction(
                $orderId,
                $sessionId,
                'momo',
                $totalAmount,
                [
                    'transaction_ref' => $momoResponse['orderId'] ?? null,
                    'status' => 'pending',
                    'notes' => 'Chờ thanh toán qua MoMo'
                ]
            );

            return $this->jsonResponse(true, 'Đã tạo link thanh toán MoMo', [
                'transaction_id' => $transactionId,
                'order_id' => $orderId,
                'amount' => $totalAmount,
                'momo_payment_url' => $momoResponse['payUrl'],
                'momo_qr_code' => $momoResponse['qrCodeUrl'] ?? null,
                'payment_method' => 'momo',
                'status' => 'pending'
            ]);

        } catch (Exception $e) {
            return $this->jsonResponse(false, 'Lỗi MoMo: ' . $e->getMessage());
        }
    }

    /**
     * Thanh toán kết hợp nhiều phương thức
     *
     * @param int $orderId ID đơn hàng
     * @param array $splits Danh sách các phương thức thanh toán
     *                      Ví dụ: [
     *                          ['payment_method' => 'cash', 'amount' => 100000],
     *                          ['payment_method' => 'banking', 'amount' => 50000]
     *                      ]
     * @return array Response JSON
     */
    public function processSplitPayment($orderId, $splits) {
        try {
            // Lấy thông tin đơn hàng
            $order = $this->getOrderInfo($orderId);
            if (!$order) {
                return $this->jsonResponse(false, 'Không tìm thấy đơn hàng');
            }

            $totalAmount = (float)$order['tongtien'];

            // Validate splits
            if (empty($splits) || !is_array($splits)) {
                return $this->jsonResponse(false, 'Thông tin thanh toán không hợp lệ');
            }

            // Tính tổng số tiền từ các phương thức
            $splitTotal = 0;
            foreach ($splits as $split) {
                if (!isset($split['payment_method']) || !isset($split['amount'])) {
                    return $this->jsonResponse(false, 'Thông tin phương thức thanh toán không đầy đủ');
                }
                $splitTotal += (float)$split['amount'];
            }

            // Kiểm tra tổng tiền
            if (abs($splitTotal - $totalAmount) > 0.01) { // Cho phép sai số 0.01
                return $this->jsonResponse(false, 'Tổng tiền thanh toán không khớp với đơn hàng');
            }

            // Tạo mã tham chiếu cho từng phương thức
            $processedSplits = [];
            foreach ($splits as $split) {
                $transactionRef = strtoupper(substr($split['payment_method'], 0, 3)) . time() . $orderId;
                $processedSplits[] = [
                    'payment_method' => $split['payment_method'],
                    'amount' => $split['amount'],
                    'transaction_ref' => $transactionRef
                ];
            }

            // Tạo giao dịch split payment
            $sessionId = $this->getCurrentSessionId();
            $transactionId = $this->posTransaction->createSplitTransaction(
                $orderId,
                $sessionId,
                $processedSplits,
                $totalAmount
            );

            if (!$transactionId) {
                return $this->jsonResponse(false, 'Không thể tạo giao dịch thanh toán');
            }

            // Xác nhận thanh toán
            $result = $this->confirmPayment($transactionId, $orderId);

            if ($result['success']) {
                return $this->jsonResponse(true, 'Thanh toán kết hợp thành công', [
                    'transaction_id' => $transactionId,
                    'order_id' => $orderId,
                    'payment_method' => 'split',
                    'splits' => $processedSplits,
                    'total_amount' => $totalAmount
                ]);
            }

            return $result;

        } catch (Exception $e) {
            return $this->jsonResponse(false, 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Xác nhận thanh toán thành công
     *
     * @param int $transactionId ID giao dịch
     * @param int $orderId ID đơn hàng (optional, sẽ lấy từ transaction nếu không có)
     * @return array Response JSON
     */
    public function confirmPayment($transactionId, $orderId = null) {
        try {
            $this->conn->beginTransaction();

            // Lấy thông tin giao dịch nếu chưa có orderId
            if (!$orderId) {
                $query = "SELECT order_id FROM pos_transactions WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$transactionId]);
                $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$transaction) {
                    throw new Exception('Không tìm thấy giao dịch');
                }

                $orderId = $transaction['order_id'];
            }

            // 1. Cập nhật trạng thái giao dịch thành completed
            if (!$this->posTransaction->completeTransaction($transactionId)) {
                throw new Exception('Không thể hoàn thành giao dịch');
            }

            // 2. Cập nhật trạng thái đơn hàng
            $updateOrderQuery = "UPDATE donhang
                                SET trangthai = 'dathanhtoan',
                                    ngay_thanhtoan = NOW()
                                WHERE id = ?";
            $stmt = $this->conn->prepare($updateOrderQuery);
            if (!$stmt->execute([$orderId])) {
                throw new Exception('Không thể cập nhật trạng thái đơn hàng');
            }

            // 3. Xuất kho theo FIFO
            $this->deductInventoryFIFO($orderId);

            // 4. Xóa giỏ hàng POS nếu có
            $this->clearPOSCart($orderId);

            // 5. Ghi log thanh toán
            $this->logPaymentSuccess($transactionId, $orderId);

            $this->conn->commit();

            return $this->jsonResponse(true, 'Xác nhận thanh toán thành công', [
                'transaction_id' => $transactionId,
                'order_id' => $orderId
            ]);

        } catch (Exception $e) {
            $this->conn->rollBack();

            // Đánh dấu giao dịch thất bại
            $this->posTransaction->failTransaction($transactionId, $e->getMessage());

            return $this->jsonResponse(false, 'Lỗi xác nhận thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Tạo mã QR cho chuyển khoản ngân hàng
     *
     * @param int $orderId ID đơn hàng
     * @param float $amount Số tiền
     * @param string $transactionRef Mã tham chiếu
     * @return array Thông tin QR code
     */
    public function generateQRCode($orderId, $amount, $transactionRef = null) {
        // Thông tin ngân hàng (nên lấy từ config hoặc database)
        $bankInfo = [
            'bank_id' => 'VCB', // Vietcombank
            'account_no' => '1234567890',
            'account_name' => 'MEDXTORE PHARMACY',
            'amount' => (int)$amount,
            'description' => $transactionRef ?? "DH" . $orderId
        ];

        // Tạo URL QR theo chuẩn VietQR
        // Format: https://img.vietqr.io/image/{BANK_ID}-{ACCOUNT_NO}-{TEMPLATE}.jpg?amount={AMOUNT}&addInfo={DESCRIPTION}
        $qrUrl = sprintf(
            'https://img.vietqr.io/image/%s-%s-compact.jpg?amount=%d&addInfo=%s',
            $bankInfo['bank_id'],
            $bankInfo['account_no'],
            $bankInfo['amount'],
            urlencode($bankInfo['description'])
        );

        return [
            'qr_url' => $qrUrl,
            'bank_info' => $bankInfo,
            'transaction_ref' => $transactionRef
        ];
    }

    /**
     * Xuất kho theo nguyên tắc FIFO
     *
     * @param int $orderId ID đơn hàng
     * @throws Exception Nếu không đủ hàng trong kho
     */
    private function deductInventoryFIFO($orderId) {
        // Lấy chi tiết đơn hàng
        $query = "SELECT thuoc_id, donvi_id, soluong
                  FROM chitiet_donhang
                  WHERE donhang_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Xuất kho từng sản phẩm theo FIFO
        foreach ($items as $item) {
            $result = $this->inventoryBatch->deductStock(
                $item['thuoc_id'],
                $item['donvi_id'],
                $item['soluong'],
                $orderId
            );

            if (!$result) {
                throw new Exception("Không đủ hàng trong kho cho sản phẩm ID: " . $item['thuoc_id']);
            }
        }
    }

    /**
     * Xóa giỏ hàng POS sau khi thanh toán
     *
     * @param int $orderId ID đơn hàng
     */
    private function clearPOSCart($orderId) {
        try {
            // Lấy session_id từ order
            $query = "SELECT session_id FROM pos_transactions WHERE order_id = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$orderId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($transaction && $transaction['session_id']) {
                $sessionId = $transaction['session_id'];

                // Xóa giỏ hàng POS
                $deleteQuery = "DELETE FROM pos_cart WHERE session_id = ?";
                $stmt = $this->conn->prepare($deleteQuery);
                $stmt->execute([$sessionId]);
            }
        } catch (Exception $e) {
            // Log error nhưng không throw để không ảnh hưởng đến quá trình thanh toán
            error_log("Error clearing POS cart: " . $e->getMessage());
        }
    }

    /**
     * Ghi log thanh toán thành công
     *
     * @param int $transactionId ID giao dịch
     * @param int $orderId ID đơn hàng
     */
    private function logPaymentSuccess($transactionId, $orderId) {
        try {
            $query = "INSERT INTO payment_logs
                     (transaction_id, order_id, status, message, created_at)
                     VALUES (?, ?, 'success', 'Thanh toán thành công', NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$transactionId, $orderId]);
        } catch (Exception $e) {
            // Log error nhưng không throw
            error_log("Error logging payment: " . $e->getMessage());
        }
    }

    /**
     * Lấy thông tin đơn hàng
     *
     * @param int $orderId ID đơn hàng
     * @return array|null Thông tin đơn hàng
     */
    private function getOrderInfo($orderId) {
        $query = "SELECT * FROM donhang WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy session ID hiện tại
     *
     * @return string Session ID
     */
    private function getCurrentSessionId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return session_id();
    }

    /**
     * Tạo JSON response
     *
     * @param bool $success Trạng thái thành công
     * @param string $message Thông báo
     * @param array $data Dữ liệu trả về
     * @return array Response array
     */
    private function jsonResponse($success, $message, $data = []) {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
    }
}

// Xử lý AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $controller = new POSPaymentController();
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'cash_payment':
                $orderId = (int)($_POST['order_id'] ?? 0);
                $cashReceived = (float)($_POST['cash_received'] ?? 0);
                $result = $controller->processCashPayment($orderId, $cashReceived);
                echo json_encode($result);
                break;

            case 'banking_payment':
                $orderId = (int)($_POST['order_id'] ?? 0);
                $bankInfo = json_decode($_POST['bank_info'] ?? '{}', true);
                $result = $controller->processBankingPayment($orderId, $bankInfo);
                echo json_encode($result);
                break;

            case 'momo_payment':
                $orderId = (int)($_POST['order_id'] ?? 0);
                $result = $controller->processMomoPayment($orderId);
                echo json_encode($result);
                break;

            case 'split_payment':
                $orderId = (int)($_POST['order_id'] ?? 0);
                $splits = json_decode($_POST['splits'] ?? '[]', true);
                $result = $controller->processSplitPayment($orderId, $splits);
                echo json_encode($result);
                break;

            case 'confirm_payment':
                $transactionId = (int)($_POST['transaction_id'] ?? 0);
                $orderId = (int)($_POST['order_id'] ?? 0);
                $result = $controller->confirmPayment($transactionId, $orderId ?: null);
                echo json_encode($result);
                break;

            case 'generate_qr':
                $orderId = (int)($_POST['order_id'] ?? 0);
                $amount = (float)($_POST['amount'] ?? 0);
                $transactionRef = $_POST['transaction_ref'] ?? null;
                $qrData = $controller->generateQRCode($orderId, $amount, $transactionRef);
                echo json_encode([
                    'success' => true,
                    'data' => $qrData
                ]);
                break;

            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Action không hợp lệ'
                ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
}
