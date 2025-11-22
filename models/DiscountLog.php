<?php
require_once __DIR__ . '/../config/database.php';

class DiscountLog {
    private $conn;
    private $table = 'discount_logs';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Ghi log giảm giá
    public function logDiscount($data) {
        $query = "INSERT INTO {$this->table}
                  (order_id, order_item_id, promotion_id, discount_type, discount_value,
                   discount_percent, reason, applied_by, approved_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['order_id'] ?? null,
            $data['order_item_id'] ?? null,
            $data['promotion_id'] ?? null,
            $data['discount_type'],
            $data['discount_value'],
            $data['discount_percent'] ?? null,
            $data['reason'] ?? null,
            $data['applied_by'],
            $data['approved_by'] ?? null
        ]);

        return $this->conn->lastInsertId();
    }

    // Lấy log theo đơn hàng
    public function getByOrderId($orderId) {
        $query = "SELECT dl.*,
                         n1.ten as applier_name,
                         n2.ten as approver_name,
                         p.name as promotion_name
                  FROM {$this->table} dl
                  JOIN nguoidung n1 ON dl.applied_by = n1.id
                  LEFT JOIN nguoidung n2 ON dl.approved_by = n2.id
                  LEFT JOIN promotions p ON dl.promotion_id = p.id
                  WHERE dl.order_id = ?
                  ORDER BY dl.created_at";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy log theo người thực hiện
    public function getByApplier($userId, $limit = 50) {
        $query = "SELECT dl.*,
                         d.id as order_number,
                         p.name as promotion_name
                  FROM {$this->table} dl
                  LEFT JOIN donhang d ON dl.order_id = d.id
                  LEFT JOIN promotions p ON dl.promotion_id = p.id
                  WHERE dl.applied_by = ?
                  ORDER BY dl.created_at DESC
                  LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, (int)$limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Thống kê giảm giá
    public function getDiscountStats($startDate = null, $endDate = null) {
        $query = "SELECT
                    discount_type,
                    COUNT(*) as count,
                    SUM(discount_value) as total_discount
                  FROM {$this->table}
                  WHERE 1=1";

        $params = [];

        if ($startDate) {
            $query .= " AND DATE(created_at) >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $query .= " AND DATE(created_at) <= ?";
            $params[] = $endDate;
        }

        $query .= " GROUP BY discount_type";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
