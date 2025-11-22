<?php
require_once __DIR__ . '/../config/database.php';

class InventoryBatch {
    private $conn;
    private $table = 'inventory_batches';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Lấy lô hàng theo FIFO (ưu tiên hết hạn sớm nhất)
    public function getBatchesFIFO($thuocId, $donviId, $quantityNeeded) {
        $query = "SELECT * FROM {$this->table}
                  WHERE thuoc_id = ?
                  AND donvi_id = ?
                  AND status = 'active'
                  AND quantity_remaining > 0
                  AND expiry_date > CURDATE()
                  ORDER BY expiry_date ASC, received_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$thuocId, $donviId]);

        $batches = [];
        $remaining = $quantityNeeded;

        while ($batch = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($remaining <= 0) break;

            $takeQuantity = min($batch['quantity_remaining'], $remaining);

            $batches[] = [
                'batch_id' => $batch['id'],
                'batch_number' => $batch['batch_number'],
                'quantity' => $takeQuantity,
                'expiry_date' => $batch['expiry_date']
            ];

            $remaining -= $takeQuantity;
        }

        return [
            'batches' => $batches,
            'fulfilled' => $remaining <= 0
        ];
    }

    // Xuất kho theo lô
    public function deductStock($thuocId, $donviId, $quantity, $orderId = null) {
        try {
            $this->conn->beginTransaction();

            $result = $this->getBatchesFIFO($thuocId, $donviId, $quantity);

            if (!$result['fulfilled']) {
                throw new Exception('Không đủ hàng trong kho');
            }

            foreach ($result['batches'] as $batch) {
                // Cập nhật số lượng còn lại
                $updateQuery = "UPDATE {$this->table}
                               SET quantity_remaining = quantity_remaining - ?
                               WHERE id = ?";
                $stmt = $this->conn->prepare($updateQuery);
                $stmt->execute([$batch['quantity'], $batch['batch_id']]);

                // Cập nhật status nếu hết hàng
                $checkQuery = "UPDATE {$this->table}
                              SET status = 'out_of_stock'
                              WHERE id = ? AND quantity_remaining = 0";
                $stmt = $this->conn->prepare($checkQuery);
                $stmt->execute([$batch['batch_id']]);

                // Ghi log giao dịch
                $logQuery = "INSERT INTO inventory_batch_transactions
                            (batch_id, order_id, transaction_type, quantity, remaining_after)
                            VALUES (?, ?, 'sale', ?, (SELECT quantity_remaining FROM {$this->table} WHERE id = ?))";
                $stmt = $this->conn->prepare($logQuery);
                $stmt->execute([$batch['batch_id'], $orderId, $batch['quantity'], $batch['batch_id']]);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // Lấy tổng tồn kho của sản phẩm
    public function getTotalStock($thuocId, $donviId = null) {
        $query = "SELECT COALESCE(SUM(quantity_remaining), 0) as total
                  FROM {$this->table}
                  WHERE thuoc_id = ?
                  AND status = 'active'
                  AND expiry_date > CURDATE()";

        $params = [$thuocId];

        if ($donviId) {
            $query .= " AND donvi_id = ?";
            $params[] = $donviId;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    }

    // Lấy thông tin lô hàng sắp hết hạn (< 3 tháng)
    public function getExpiringBatches($months = 3) {
        $query = "SELECT b.*,
                         t.ten_thuoc,
                         dv.ten_donvi,
                         DATEDIFF(b.expiry_date, CURDATE()) as days_to_expiry
                  FROM {$this->table} b
                  JOIN thuoc t ON b.thuoc_id = t.id
                  JOIN donvi dv ON b.donvi_id = dv.id
                  WHERE b.status = 'active'
                  AND b.quantity_remaining > 0
                  AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? MONTH)
                  AND b.expiry_date > CURDATE()
                  ORDER BY b.expiry_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$months]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy lô hàng đã hết hạn
    public function getExpiredBatches() {
        $query = "SELECT b.*,
                         t.ten_thuoc,
                         dv.ten_donvi
                  FROM {$this->table} b
                  JOIN thuoc t ON b.thuoc_id = t.id
                  JOIN donvi dv ON b.donvi_id = dv.id
                  WHERE b.expiry_date <= CURDATE()
                  AND b.quantity_remaining > 0
                  AND b.status != 'expired'
                  ORDER BY b.expiry_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Đánh dấu lô hết hạn
    public function markAsExpired($batchId) {
        $query = "UPDATE {$this->table}
                  SET status = 'expired'
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$batchId]);
    }

    // Thêm lô hàng mới
    public function create($data) {
        $query = "INSERT INTO {$this->table}
                  (thuoc_id, donvi_id, batch_number, quantity_received, quantity_remaining,
                   cost_price, selling_price, received_date, manufacture_date, expiry_date,
                   supplier_id, notes, created_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['thuoc_id'],
            $data['donvi_id'],
            $data['batch_number'],
            $data['quantity_received'],
            $data['quantity_remaining'] ?? $data['quantity_received'],
            $data['cost_price'],
            $data['selling_price'],
            $data['received_date'],
            $data['manufacture_date'] ?? null,
            $data['expiry_date'],
            $data['supplier_id'] ?? null,
            $data['notes'] ?? null,
            $data['created_by'] ?? null
        ]);

        return $this->conn->lastInsertId();
    }

    // Lấy chi tiết lô hàng
    public function getById($id) {
        $query = "SELECT b.*,
                         t.ten_thuoc,
                         dv.ten_donvi,
                         ncc.ten_ncc
                  FROM {$this->table} b
                  JOIN thuoc t ON b.thuoc_id = t.id
                  JOIN donvi dv ON b.donvi_id = dv.id
                  LEFT JOIN nhacungcap ncc ON b.supplier_id = ncc.id
                  WHERE b.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy tất cả lô hàng (có phân trang)
    public function getAll($limit = 50, $offset = 0, $filters = []) {
        $query = "SELECT b.*,
                         t.ten_thuoc,
                         dv.ten_donvi,
                         ncc.ten_ncc
                  FROM {$this->table} b
                  JOIN thuoc t ON b.thuoc_id = t.id
                  JOIN donvi dv ON b.donvi_id = dv.id
                  LEFT JOIN nhacungcap ncc ON b.supplier_id = ncc.id
                  WHERE 1=1";

        $params = [];

        if (!empty($filters['thuoc_id'])) {
            $query .= " AND b.thuoc_id = ?";
            $params[] = $filters['thuoc_id'];
        }

        if (!empty($filters['status'])) {
            $query .= " AND b.status = ?";
            $params[] = $filters['status'];
        }

        $query .= " ORDER BY b.expiry_date ASC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
