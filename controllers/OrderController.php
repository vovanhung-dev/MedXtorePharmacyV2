<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/InventoryController.php';

class OrderController {
    private $conn;

    public function __construct() {
        global $db_host, $db_user, $db_pass, $db_name;
        $this->conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        $this->conn->set_charset("utf8");
    }

    // Lấy tất cả đơn hàng với thông tin chi tiết
    public function getAllOrders() {
        $sql = "SELECT dh.*, kh.ten_khachhang, kh.sodienthoai, kh.email, kh.diachi,
                       COUNT(ctdh.id) as so_san_pham,
                       (CASE 
                            WHEN dh.trangthai = 'choxacnhan' THEN 'Chờ xác nhận'
                            WHEN dh.trangthai = 'daxacnhan' THEN 'Đã xác nhận'
                            WHEN dh.trangthai = 'danggiao' THEN 'Đang giao'
                            WHEN dh.trangthai = 'dagiao' THEN 'Đã giao'
                            WHEN dh.trangthai = 'dahuy' THEN 'Đã hủy'
                            ELSE dh.trangthai
                        END) as trangthai_text
                FROM donhang dh
                LEFT JOIN khachhang kh ON dh.khachhang_id = kh.id
                LEFT JOIN chitiet_donhang ctdh ON dh.id = ctdh.donhang_id
                GROUP BY dh.id
                ORDER BY dh.ngay_dat DESC";

        $result = $this->conn->query($sql);
        $orders = [];
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        
        return $orders;
    }

    // Lấy chi tiết đơn hàng
    public function getOrderDetails($orderId) {
        // Lấy thông tin đơn hàng
        $sql = "SELECT dh.*, kh.ten_khachhang, kh.sodienthoai, kh.email, kh.diachi
                FROM donhang dh 
                JOIN khachhang kh ON dh.khachhang_id = kh.id
                WHERE dh.id = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            return null;
        }

        // Lấy chi tiết sản phẩm trong đơn
        $sql = "SELECT ctdh.*, t.ten_thuoc, t.hinhanh
                FROM chitiet_donhang ctdh
                JOIN thuoc t ON ctdh.thuoc_id = t.id
                WHERE ctdh.donhang_id = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'order' => $order,
            'items' => $items
        ];
    }

    // Cập nhật trạng thái đơn hàng
    public function updateOrderStatus($orderId, $status) {
        try {
            $this->conn->begin_transaction();

            // Cập nhật trạng thái đơn hàng
            $sql = "UPDATE donhang SET trangthai = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $status, $orderId);
            $success = $stmt->execute();

            if (!$success) {
                throw new Exception("Không thể cập nhật trạng thái đơn hàng");
            }

            // Nếu trạng thái là đã đặt, cập nhật số lượng trong kho
            if ($status === 'dadat') {
                // Lấy thông tin chi tiết đơn hàng
                $sql = "SELECT thuoc_id, donvi_id, soluong 
                       FROM chitiet_donhang 
                       WHERE donhang_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Cập nhật số lượng trong kho cho từng sản phẩm
                foreach ($items as $item) {
                    // Kiểm tra và cập nhật số lượng trong kho
                    $updateSql = "UPDATE khohang 
                                SET soluong = soluong - ? 
                                WHERE thuoc_id = ? 
                                AND donvi_id = ? 
                                AND soluong >= ?";
                    
                    $updateStmt = $this->conn->prepare($updateSql);
                    $updateStmt->bind_param("iiii", 
                        $item['soluong'], 
                        $item['thuoc_id'], 
                        $item['donvi_id'],
                        $item['soluong']
                    );
                    
                    if (!$updateStmt->execute() || $updateStmt->affected_rows === 0) {
                        throw new Exception("Không đủ số lượng trong kho cho sản phẩm ID: " . $item['thuoc_id']);
                    }
                }

                // Đánh dấu đơn hàng đã cập nhật kho
                $markSql = "UPDATE donhang SET da_capnhat_kho = 1 WHERE id = ?";
                $markStmt = $this->conn->prepare($markSql);
                $markStmt->bind_param("i", $orderId);
                $markStmt->execute();
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Lỗi cập nhật đơn hàng: " . $e->getMessage());
            return false;
        }
    }

    // Thống kê đơn hàng theo trạng thái
    public function getOrderStats() {
        $sql = "SELECT 
                    trangthai,
                    COUNT(*) as so_luong,
                    SUM(tongtien) as tong_tien
                FROM donhang 
                GROUP BY trangthai";
                
        $result = $this->conn->query($sql);
        $stats = [];
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $stats[$row['trangthai']] = $row;
            }
        }
        
        return $stats;
    }

    // Xóa đơn hàng
    public function deleteOrder($orderId) {
        $sql = "DELETE FROM donhang WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        return $stmt->execute();
    }

    /**
     * Cập nhật số lượng kho khi tạo đơn hàng mới
     */
    public function updateInventoryForNewOrder($orderId) {
        try {
            $this->conn->begin_transaction();

            // Kiểm tra trạng thái đơn hàng và phương thức thanh toán
            $checkSql = "SELECT trangthai, phuongthuc_thanhtoan, da_capnhat_kho FROM donhang WHERE id = ?";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->bind_param("i", $orderId);
            $checkStmt->execute();
            $order = $checkStmt->get_result()->fetch_assoc();

            // Điều kiện cập nhật kho:
            $shouldUpdateInventory = 
                ($order['phuongthuc_thanhtoan'] === 'cod' && $order['trangthai'] === 'dadat') ||
                ($order['phuongthuc_thanhtoan'] === 'momo' && $order['trangthai'] === 'dathanhtoan');

            if ($shouldUpdateInventory && (!$order['da_capnhat_kho'] || $order['da_capnhat_kho'] === 0)) {
                // Lấy thông tin chi tiết đơn hàng
                $sql = "SELECT ctdh.thuoc_id, ctdh.soluong, t.ten_thuoc 
                       FROM chitiet_donhang ctdh
                       JOIN thuoc t ON ctdh.thuoc_id = t.id
                       WHERE ctdh.donhang_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Cập nhật số lượng trong kho cho từng sản phẩm
                foreach ($items as $item) {
                    // Kiểm tra và cập nhật số lượng trong kho
                    $updateSql = "UPDATE khohang 
                                SET soluong = soluong - ? 
                                WHERE thuoc_id = ? 
                                AND soluong >= ?";
                    
                    $updateStmt = $this->conn->prepare($updateSql);
                    $updateStmt->bind_param("iii", 
                        $item['soluong'], 
                        $item['thuoc_id'],
                        $item['soluong']
                    );
                    
                    if (!$updateStmt->execute() || $updateStmt->affected_rows === 0) {
                        throw new Exception("Không đủ số lượng trong kho cho sản phẩm: " . $item['ten_thuoc']);
                    }
                }

                // Đánh dấu đơn hàng đã cập nhật kho
                $markSql = "UPDATE donhang SET da_capnhat_kho = 1 WHERE id = ?";
                $markStmt = $this->conn->prepare($markSql);
                $markStmt->bind_param("i", $orderId);
                if (!$markStmt->execute()) {
                    throw new Exception("Không thể đánh dấu đơn hàng đã cập nhật kho");
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Lỗi cập nhật kho: " . $e->getMessage());
            throw $e;
        }
    }
}

// Xử lý AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new OrderController();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_status':
            $success = $controller->updateOrderStatus($_POST['order_id'], $_POST['status']);
            echo json_encode(['success' => $success]);
            break;

        case 'delete_order':
            $success = $controller->deleteOrder($_POST['order_id']);
            echo json_encode(['success' => $success]);
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_details') {
    header('Content-Type: application/json');
    $controller = new OrderController();
    $details = $controller->getOrderDetails($_GET['order_id']);

    if ($details) {
        echo json_encode([
            'success' => true,
            'data' => $details['order'],
            'items' => $details['items']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy đơn hàng'
        ]);
    }
    exit;
}