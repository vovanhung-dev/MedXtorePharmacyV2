<?php
require_once __DIR__ . '/../config/database.php';

class HeldBill {
    private $conn;
    private $table = 'pos_held_bills';
    private $itemsTable = 'pos_held_bill_items';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Tạm giữ hóa đơn
    public function holdBill($sessionId, $heldBy, $billData, $items) {
        try {
            $this->conn->beginTransaction();

            // Insert held bill
            $query = "INSERT INTO {$this->table}
                      (session_id, customer_id, held_by, bill_name, subtotal, discount_amount, total, notes)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $sessionId,
                $billData['customer_id'] ?? null,
                $heldBy,
                $billData['bill_name'] ?? 'Bill ' . date('His'),
                $billData['subtotal'],
                $billData['discount_amount'] ?? 0,
                $billData['total'],
                $billData['notes'] ?? null
            ]);

            $heldBillId = $this->conn->lastInsertId();

            // Insert items
            $itemQuery = "INSERT INTO {$this->itemsTable}
                          (held_bill_id, thuoc_id, donvi_id, quantity, unit_price, discount_percent, discount_amount, subtotal)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $itemStmt = $this->conn->prepare($itemQuery);

            foreach ($items as $item) {
                $itemStmt->execute([
                    $heldBillId,
                    $item['thuoc_id'],
                    $item['donvi_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['discount_percent'] ?? 0,
                    $item['discount_amount'] ?? 0,
                    $item['subtotal']
                ]);
            }

            $this->conn->commit();
            return $heldBillId;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // Lấy danh sách hóa đơn tạm giữ
    public function getHeldBills($sessionId = null, $status = 'held') {
        $query = "SELECT hb.*,
                         k.ten_khachhang,
                         n.ten as held_by_name,
                         COUNT(hbi.id) as item_count
                  FROM {$this->table} hb
                  LEFT JOIN khachhang k ON hb.customer_id = k.id
                  JOIN nguoidung n ON hb.held_by = n.id
                  LEFT JOIN {$this->itemsTable} hbi ON hb.id = hbi.held_bill_id
                  WHERE hb.status = ?";

        $params = [$status];

        if ($sessionId) {
            $query .= " AND hb.session_id = ?";
            $params[] = $sessionId;
        }

        $query .= " GROUP BY hb.id ORDER BY hb.held_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy chi tiết hóa đơn tạm giữ
    public function getHeldBillById($heldBillId) {
        $query = "SELECT hb.*,
                         k.ten_khachhang, k.sodienthoai,
                         n.ten as held_by_name
                  FROM {$this->table} hb
                  LEFT JOIN khachhang k ON hb.customer_id = k.id
                  JOIN nguoidung n ON hb.held_by = n.id
                  WHERE hb.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$heldBillId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy items của hóa đơn tạm giữ
    public function getHeldBillItems($heldBillId) {
        $query = "SELECT hbi.*,
                         t.ten_thuoc, t.hinhanh,
                         dv.ten_donvi
                  FROM {$this->itemsTable} hbi
                  JOIN thuoc t ON hbi.thuoc_id = t.id
                  JOIN donvi dv ON hbi.donvi_id = dv.id
                  WHERE hbi.held_bill_id = ?
                  ORDER BY hbi.id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$heldBillId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Khôi phục hóa đơn tạm giữ
    public function retrieveHeldBill($heldBillId) {
        $query = "UPDATE {$this->table}
                  SET status = 'retrieved', retrieved_at = NOW()
                  WHERE id = ? AND status = 'held'";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$heldBillId]);
    }

    // Hủy hóa đơn tạm giữ
    public function cancelHeldBill($heldBillId) {
        $query = "UPDATE {$this->table}
                  SET status = 'cancelled'
                  WHERE id = ? AND status = 'held'";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$heldBillId]);
    }

    // Xóa hóa đơn tạm giữ
    public function deleteHeldBill($heldBillId) {
        try {
            $this->conn->beginTransaction();

            // Delete items first
            $deleteItems = "DELETE FROM {$this->itemsTable} WHERE held_bill_id = ?";
            $stmt = $this->conn->prepare($deleteItems);
            $stmt->execute([$heldBillId]);

            // Delete bill
            $deleteBill = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->conn->prepare($deleteBill);
            $stmt->execute([$heldBillId]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
