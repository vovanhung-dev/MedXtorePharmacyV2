<?php
require_once __DIR__ . '/../models/Inventory.php';

class InventoryController {
    private $model;

    public function __construct() {
        $this->model = new Inventory();
    }

    // Lấy toàn bộ danh sách kho
    public function getAll() {
        return $this->model->getAllInventory();
    }

    // Danh sách thuốc để hiển thị trong select
    public function getThuocList() {
        return $this->model->getThuocList();
    }

    // Danh sách đơn vị tính
    public function getDonViList() {
        return $this->model->getDonViList();
    }

    // Danh sách nhà cung cấp
    public function getNhaCungCapList() {
        return $this->model->getNhaCungCapList();
    }

    /**
     * Xử lý nhập kho + cập nhật giá bán tương ứng trong bảng thuoc_donvi
     */
    public function importInventory($data) {
        $thuoc_id       = $data['thuoc_id'];
        $donvi_id       = $data['donvi_id'];
        $nhacungcap_id  = $data['nhacungcap_id'];
        $gia_nhap       = $data['gia'];
        $soluong        = $data['soluong'];
        $hansudung      = $data['hansudung'];
        $capnhat        = date('Y-m-d H:i:s');

        // ✅ Bước 1: Lưu vào bảng khohang (giá nhập, số lượng, hạn...)
        $this->model->insertInventory(
            $thuoc_id,
            $donvi_id,
            $nhacungcap_id,
            $gia_nhap,
            $soluong,
            $hansudung,
            $capnhat
        );

        // ✅ Bước 2: Tính giá bán (lợi nhuận 30%)
        $tyle_loinhuan = 0.3;
        $gia_ban = ceil($gia_nhap * (1 + $tyle_loinhuan));

        // ✅ Bước 3: Thêm hoặc cập nhật giá bán trong bảng thuoc_donvi
        $this->model->updateOrInsertGiaBan($thuoc_id, $donvi_id, $gia_ban);
    }

    /**
     * Cập nhật số lượng trong kho sau khi đơn hàng được xác nhận
     */
    public function updateInventoryAfterOrder($orderId) {
        try {
            // Bắt đầu transaction
            $this->model->conn->begin_transaction();

            // Lấy chi tiết đơn hàng
            $sql = "SELECT ctdh.thuoc_id, ctdh.donvi_id, ctdh.soluong
                    FROM chitiet_donhang ctdh
                    WHERE ctdh.donhang_id = ?";
                    
            $stmt = $this->model->conn->prepare($sql);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Cập nhật số lượng trong kho cho từng sản phẩm
            foreach ($orderItems as $item) {
                // Kiểm tra số lượng tồn kho
                $checkSql = "SELECT soluong FROM khohang 
                           WHERE thuoc_id = ? AND donvi_id = ? 
                           ORDER BY hansudung ASC LIMIT 1";
                $checkStmt = $this->model->conn->prepare($checkSql);
                $checkStmt->bind_param("ii", $item['thuoc_id'], $item['donvi_id']);
                $checkStmt->execute();
                $result = $checkStmt->get_result()->fetch_assoc();

                if (!$result || $result['soluong'] < $item['soluong']) {
                    throw new Exception("Không đủ số lượng trong kho cho sản phẩm ID: " . $item['thuoc_id']);
                }

                // Cập nhật số lượng trong kho
                $updateSql = "UPDATE khohang 
                            SET soluong = soluong - ?
                            WHERE thuoc_id = ? AND donvi_id = ? 
                            ORDER BY hansudung ASC LIMIT 1";
                
                $updateStmt = $this->model->conn->prepare($updateSql);
                $updateStmt->bind_param("iii", $item['soluong'], $item['thuoc_id'], $item['donvi_id']);
                $updateStmt->execute();

                if ($updateStmt->affected_rows === 0) {
                    throw new Exception("Không thể cập nhật số lượng cho sản phẩm ID: " . $item['thuoc_id']);
                }
            }

            // Commit transaction nếu tất cả thành công
            $this->model->conn->commit();
            return true;

        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $this->model->conn->rollback();
            error_log("Lỗi cập nhật kho: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy danh sách thuốc gần hết
     */
    public function getLowStockItems($threshold = 20) {
        return $this->model->getLowStockItems($threshold);
    }

    /**
     * Lấy danh sách thuốc gần hết hạn hoặc đã hết hạn
     */
    public function getExpiringItems($daysThreshold = 30) {
        return $this->model->getExpiringItems($daysThreshold);
    }

    /**
     * Cập nhật số lượng kho hàng dựa trên trạng thái đơn hàng
     */
    public function updateInventoryByOrderStatus() {
        try {
            // Bắt đầu transaction
            $this->model->conn->begin_transaction();

            // Lấy tất cả đơn hàng có trạng thái "dadat" và chưa cập nhật kho
            $sql = "SELECT dh.id as donhang_id, ctdh.thuoc_id, ctdh.donvi_id, ctdh.soluong
                   FROM donhang dh
                   JOIN chitiet_donhang ctdh ON dh.id = ctdh.donhang_id
                   WHERE dh.trangthai = 'dadat' 
                   AND (dh.da_capnhat_kho = 0 OR dh.da_capnhat_kho IS NULL)";

            $result = $this->model->conn->query($sql);
            
            if (!$result) {
                error_log("Lỗi SQL: " . $this->model->conn->error);
                throw new Exception("Lỗi khi truy vấn đơn hàng");
            }

            $orderItems = $result->fetch_all(MYSQLI_ASSOC);
            error_log("Số đơn hàng cần cập nhật: " . count($orderItems));

            if (empty($orderItems)) {
                error_log("Không có đơn hàng nào cần cập nhật kho");
                return true;
            }

            // Nhóm các sản phẩm theo đơn hàng
            $orderGroups = [];
            foreach ($orderItems as $item) {
                $orderGroups[$item['donhang_id']][] = $item;
            }

            // Xử lý từng đơn hàng
            foreach ($orderGroups as $orderId => $items) {
                error_log("Đang xử lý đơn hàng ID: " . $orderId);
                
                foreach ($items as $item) {
                    // Kiểm tra và cập nhật số lượng trong kho
                    $checkSql = "SELECT id, soluong 
                               FROM khohang 
                               WHERE thuoc_id = ? AND donvi_id = ? 
                               ORDER BY hansudung ASC 
                               LIMIT 1";

                    $checkStmt = $this->model->conn->prepare($checkSql);
                    $checkStmt->bind_param("ii", $item['thuoc_id'], $item['donvi_id']);
                    $checkStmt->execute();
                    $inventory = $checkStmt->get_result()->fetch_assoc();

                    if (!$inventory) {
                        error_log("Không tìm thấy sản phẩm trong kho - Thuốc ID: " . $item['thuoc_id']);
                        throw new Exception("Không tìm thấy sản phẩm trong kho - Thuốc ID: " . $item['thuoc_id']);
                    }

                    if ($inventory['soluong'] < $item['soluong']) {
                        error_log("Không đủ số lượng - Thuốc ID: " . $item['thuoc_id'] . ", Tồn kho: " . $inventory['soluong'] . ", Cần: " . $item['soluong']);
                        throw new Exception("Không đủ số lượng trong kho cho sản phẩm ID: " . $item['thuoc_id']);
                    }

                    // Cập nhật số lượng trong kho
                    $updateSql = "UPDATE khohang 
                                SET soluong = soluong - ? 
                                WHERE id = ?";

                    $updateStmt = $this->model->conn->prepare($updateSql);
                    $updateStmt->bind_param("ii", $item['soluong'], $inventory['id']);
                    $updateStmt->execute();

                    if ($updateStmt->affected_rows === 0) {
                        error_log("Không thể cập nhật số lượng - Thuốc ID: " . $item['thuoc_id']);
                        throw new Exception("Không thể cập nhật số lượng cho sản phẩm ID: " . $item['thuoc_id']);
                    }

                    error_log("Đã cập nhật số lượng thành công - Thuốc ID: " . $item['thuoc_id']);
                }

                // Đánh dấu đơn hàng đã được cập nhật kho
                $markSql = "UPDATE donhang SET da_capnhat_kho = 1 WHERE id = ?";
                $markStmt = $this->model->conn->prepare($markSql);
                $markStmt->bind_param("i", $orderId);
                if (!$markStmt->execute()) {
                    error_log("Không thể đánh dấu đơn hàng đã cập nhật - Đơn hàng ID: " . $orderId);
                    throw new Exception("Không thể đánh dấu đơn hàng đã cập nhật");
                }
                error_log("Đã đánh dấu đơn hàng đã cập nhật - Đơn hàng ID: " . $orderId);
            }

            // Commit transaction nếu tất cả thành công
            $this->model->conn->commit();
            error_log("Đã commit transaction thành công");
            return true;

        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $this->model->conn->rollback();
            error_log("Lỗi cập nhật kho: " . $e->getMessage());
            return false;
        }
    }
}
