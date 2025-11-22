<?php
require_once __DIR__ . '/../config/database.php';

class POSSession {
    private $conn;
    private $table = 'pos_sessions';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Mở ca làm việc mới
    public function openSession($userId, $openingCash = 0) {
        $query = "INSERT INTO {$this->table}
                  (user_id, start_time, opening_cash, status)
                  VALUES (?, NOW(), ?, 'open')";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $openingCash]);

        return $this->conn->lastInsertId();
    }

    // Lấy ca đang mở của user
    public function getActiveSession($userId) {
        $query = "SELECT * FROM {$this->table}
                  WHERE user_id = ? AND status = 'open'
                  ORDER BY start_time DESC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Đóng ca làm việc
    public function closeSession($sessionId, $closingCash, $notes = null) {
        // Tính tổng doanh thu và số đơn
        $stats = $this->getSessionStats($sessionId);

        $query = "UPDATE {$this->table}
                  SET end_time = NOW(),
                      closing_cash = ?,
                      expected_cash = ?,
                      total_sales = ?,
                      total_orders = ?,
                      status = 'closed',
                      notes = ?
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $closingCash,
            $stats['expected_cash'],
            $stats['total_sales'],
            $stats['total_orders'],
            $notes,
            $sessionId
        ]);
    }

    // Lấy thống kê ca làm việc
    public function getSessionStats($sessionId) {
        $query = "SELECT
                    s.opening_cash,
                    COALESCE(SUM(CASE WHEN t.payment_method = 'cash' THEN t.amount ELSE 0 END), 0) as cash_sales,
                    COALESCE(SUM(t.amount), 0) as total_sales,
                    COUNT(DISTINCT t.order_id) as total_orders
                  FROM {$this->table} s
                  LEFT JOIN pos_transactions t ON s.id = t.session_id AND t.status = 'completed'
                  WHERE s.id = ?
                  GROUP BY s.id, s.opening_cash";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['expected_cash'] = $result['opening_cash'] + $result['cash_sales'];
        }

        return $result ?: [
            'opening_cash' => 0,
            'cash_sales' => 0,
            'total_sales' => 0,
            'total_orders' => 0,
            'expected_cash' => 0
        ];
    }

    // Lấy chi tiết ca làm việc
    public function getSessionById($sessionId) {
        $query = "SELECT s.*, n.ten as cashier_name
                  FROM {$this->table} s
                  JOIN nguoidung n ON s.user_id = n.id
                  WHERE s.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$sessionId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy danh sách ca làm việc (có phân trang)
    public function getAllSessions($limit = 20, $offset = 0, $userId = null) {
        $query = "SELECT s.*, n.ten as cashier_name
                  FROM {$this->table} s
                  JOIN nguoidung n ON s.user_id = n.id";

        $params = [];

        if ($userId) {
            $query .= " WHERE s.user_id = ?";
            $params[] = $userId;
        }

        $query .= " ORDER BY s.start_time DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Kiểm tra user có ca đang mở không
    public function hasOpenSession($userId) {
        $query = "SELECT COUNT(*) FROM {$this->table}
                  WHERE user_id = ? AND status = 'open'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);

        return $stmt->fetchColumn() > 0;
    }
}
