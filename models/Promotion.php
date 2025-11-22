<?php
require_once __DIR__ . '/../config/database.php';

class Promotion {
    private $conn;
    private $table = 'promotions';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Lấy tất cả khuyến mãi đang hoạt động
    public function getActivePromotions() {
        $query = "SELECT * FROM {$this->table}
                  WHERE is_active = 1
                  AND start_date <= NOW()
                  AND end_date >= NOW()
                  AND (usage_limit IS NULL OR usage_count < usage_limit)
                  ORDER BY priority DESC, start_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tìm khuyến mãi theo mã
    public function getPromotionByCode($code) {
        $query = "SELECT * FROM {$this->table}
                  WHERE code = ?
                  AND is_active = 1
                  AND start_date <= NOW()
                  AND end_date >= NOW()
                  AND (usage_limit IS NULL OR usage_count < usage_limit)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$code]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy khuyến mãi tự động áp dụng
    public function getAutoApplyPromotions($orderTotal = 0) {
        $query = "SELECT * FROM {$this->table}
                  WHERE is_active = 1
                  AND auto_apply = 1
                  AND start_date <= NOW()
                  AND end_date >= NOW()
                  AND min_purchase <= ?
                  AND (usage_limit IS NULL OR usage_count < usage_limit)
                  ORDER BY priority DESC, discount_value DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderTotal]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tính giá trị giảm giá
    public function calculateDiscount($promotionId, $orderTotal) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$promotionId]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$promo) {
            return 0;
        }

        // Kiểm tra đơn tối thiểu
        if ($orderTotal < $promo['min_purchase']) {
            return 0;
        }

        $discount = 0;

        if ($promo['type'] === 'percentage') {
            $discount = ($orderTotal * $promo['discount_value']) / 100;

            // Áp dụng giảm tối đa
            if ($promo['max_discount'] && $discount > $promo['max_discount']) {
                $discount = $promo['max_discount'];
            }
        } elseif ($promo['type'] === 'fixed_amount') {
            $discount = $promo['discount_value'];
        }

        return $discount;
    }

    // Tăng số lần sử dụng
    public function incrementUsage($promotionId) {
        $query = "UPDATE {$this->table}
                  SET usage_count = usage_count + 1
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$promotionId]);
    }

    // Tạo khuyến mãi mới
    public function create($data) {
        $query = "INSERT INTO {$this->table}
                  (code, name, description, type, discount_value, min_purchase, max_discount,
                   start_date, end_date, is_active, auto_apply, priority, usage_limit, created_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['code'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['type'],
            $data['discount_value'],
            $data['min_purchase'] ?? 0,
            $data['max_discount'] ?? null,
            $data['start_date'],
            $data['end_date'],
            $data['is_active'] ?? 1,
            $data['auto_apply'] ?? 0,
            $data['priority'] ?? 0,
            $data['usage_limit'] ?? null,
            $data['created_by'] ?? null
        ]);

        return $this->conn->lastInsertId();
    }

    // Cập nhật khuyến mãi
    public function update($id, $data) {
        $query = "UPDATE {$this->table}
                  SET name = ?, description = ?, type = ?, discount_value = ?,
                      min_purchase = ?, max_discount = ?, start_date = ?, end_date = ?,
                      is_active = ?, auto_apply = ?, priority = ?, usage_limit = ?
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['type'],
            $data['discount_value'],
            $data['min_purchase'] ?? 0,
            $data['max_discount'] ?? null,
            $data['start_date'],
            $data['end_date'],
            $data['is_active'] ?? 1,
            $data['auto_apply'] ?? 0,
            $data['priority'] ?? 0,
            $data['usage_limit'] ?? null,
            $id
        ]);
    }

    // Xóa khuyến mãi
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    // Lấy tất cả khuyến mãi (có phân trang)
    public function getAll($limit = 20, $offset = 0) {
        $query = "SELECT p.*, n.ten as creator_name
                  FROM {$this->table} p
                  LEFT JOIN nguoidung n ON p.created_by = n.id
                  ORDER BY p.created_at DESC
                  LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([(int)$limit, (int)$offset]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy chi tiết khuyến mãi
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
