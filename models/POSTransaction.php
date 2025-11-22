<?php
require_once __DIR__ . '/../config/database.php';

class POSTransaction {
    private $conn;
    private $table = 'pos_transactions';
    private $splitsTable = 'pos_transaction_splits';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Tạo giao dịch thanh toán
    public function createTransaction($orderId, $sessionId, $paymentMethod, $amount, $data = []) {
        $query = "INSERT INTO {$this->table}
                  (order_id, session_id, payment_method, amount, cash_received, change_given, transaction_ref, status, notes)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $orderId,
            $sessionId,
            $paymentMethod,
            $amount,
            $data['cash_received'] ?? null,
            $data['change_given'] ?? null,
            $data['transaction_ref'] ?? null,
            $data['status'] ?? 'pending',
            $data['notes'] ?? null
        ]);

        return $this->conn->lastInsertId();
    }

    // Tạo giao dịch split payment
    public function createSplitTransaction($orderId, $sessionId, $splits, $total) {
        try {
            $this->conn->beginTransaction();

            // Tạo transaction chính
            $mainTransId = $this->createTransaction($orderId, $sessionId, 'split', $total, ['status' => 'pending']);

            // Thêm các phần thanh toán
            $splitQuery = "INSERT INTO {$this->splitsTable}
                           (transaction_id, payment_method, amount, transaction_ref)
                           VALUES (?, ?, ?, ?)";

            $stmt = $this->conn->prepare($splitQuery);

            foreach ($splits as $split) {
                $stmt->execute([
                    $mainTransId,
                    $split['payment_method'],
                    $split['amount'],
                    $split['transaction_ref'] ?? null
                ]);
            }

            $this->conn->commit();
            return $mainTransId;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // Hoàn thành giao dịch
    public function completeTransaction($transactionId) {
        $query = "UPDATE {$this->table}
                  SET status = 'completed', completed_at = NOW()
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$transactionId]);
    }

    // Đánh dấu giao dịch thất bại
    public function failTransaction($transactionId, $reason = null) {
        $query = "UPDATE {$this->table}
                  SET status = 'failed', notes = CONCAT(COALESCE(notes, ''), ' | Failed: ', COALESCE(?, ''))
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$reason, $transactionId]);
    }

    // Lấy giao dịch theo order
    public function getByOrderId($orderId) {
        $query = "SELECT * FROM {$this->table}
                  WHERE order_id = ?
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy chi tiết split payment
    public function getSplitDetails($transactionId) {
        $query = "SELECT * FROM {$this->splitsTable}
                  WHERE transaction_id = ?
                  ORDER BY id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$transactionId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy giao dịch theo session
    public function getBySessionId($sessionId) {
        $query = "SELECT t.*, d.id as order_number
                  FROM {$this->table} t
                  JOIN donhang d ON t.order_id = d.id
                  WHERE t.session_id = ?
                  ORDER BY t.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$sessionId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Thống kê thanh toán theo phương thức
    public function getPaymentMethodStats($sessionId) {
        $query = "SELECT
                    payment_method,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                  FROM {$this->table}
                  WHERE session_id = ?
                  AND status = 'completed'
                  GROUP BY payment_method";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$sessionId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
