<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Promotion.php';
require_once __DIR__ . '/../models/DiscountLog.php';

session_start();

class PromotionController {
    private $promotionModel;
    private $discountLogModel;

    public function __construct() {
        $this->promotionModel = new Promotion();
        $this->discountLogModel = new DiscountLog();
    }

    // Xử lý request
    public function handleRequest() {
        if (!isset($_SESSION['user_id'])) {
            $this->sendJSON(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            return;
        }

        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        try {
            switch ($action) {
                case 'get_active_promotions':
                    $this->getActivePromotions();
                    break;
                case 'validate_promo_code':
                    $this->validatePromoCode();
                    break;
                case 'apply_promotion':
                    $this->applyPromotion();
                    break;
                case 'get_auto_promotions':
                    $this->getAutoPromotions();
                    break;
                case 'create_promotion':
                    $this->createPromotion();
                    break;
                case 'update_promotion':
                    $this->updatePromotion();
                    break;
                case 'delete_promotion':
                    $this->deletePromotion();
                    break;
                case 'get_all_promotions':
                    $this->getAllPromotions();
                    break;
                case 'get_promotion':
                    $this->getPromotion();
                    break;
                default:
                    $this->sendJSON(['success' => false, 'message' => 'Action không hợp lệ']);
            }
        } catch (Exception $e) {
            $this->sendJSON(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    // Lấy khuyến mãi đang hoạt động
    private function getActivePromotions() {
        $promotions = $this->promotionModel->getActivePromotions();
        $this->sendJSON(['success' => true, 'data' => $promotions]);
    }

    // Validate mã khuyến mãi
    private function validatePromoCode() {
        $code = $_POST['code'] ?? '';
        $orderTotal = (float)($_POST['order_total'] ?? 0);

        if (empty($code)) {
            $this->sendJSON(['success' => false, 'message' => 'Vui lòng nhập mã khuyến mãi']);
            return;
        }

        $promotion = $this->promotionModel->getPromotionByCode($code);

        if (!$promotion) {
            $this->sendJSON(['success' => false, 'message' => 'Mã khuyến mãi không tồn tại hoặc đã hết hạn']);
            return;
        }

        // Kiểm tra đơn tối thiểu
        if ($orderTotal < $promotion['min_purchase']) {
            $this->sendJSON([
                'success' => false,
                'message' => "Đơn hàng tối thiểu " . number_format($promotion['min_purchase']) . "đ để áp dụng mã này"
            ]);
            return;
        }

        // Tính giá trị giảm giá
        $discount = $this->promotionModel->calculateDiscount($promotion['id'], $orderTotal);

        $this->sendJSON([
            'success' => true,
            'message' => 'Áp dụng mã khuyến mãi thành công',
            'data' => [
                'promotion' => $promotion,
                'discount_amount' => $discount,
                'new_total' => $orderTotal - $discount
            ]
        ]);
    }

    // Áp dụng khuyến mãi vào đơn hàng
    private function applyPromotion() {
        $promoId = $_POST['promotion_id'] ?? 0;
        $orderId = $_POST['order_id'] ?? 0;
        $orderTotal = (float)($_POST['order_total'] ?? 0);
        $userId = $_SESSION['user_id'];

        if (!$promoId || !$orderId) {
            $this->sendJSON(['success' => false, 'message' => 'Thiếu thông tin']);
            return;
        }

        // Tính giá trị giảm giá
        $discount = $this->promotionModel->calculateDiscount($promoId, $orderTotal);

        if ($discount <= 0) {
            $this->sendJSON(['success' => false, 'message' => 'Không áp dụng được khuyến mãi']);
            return;
        }

        // Lấy thông tin promotion
        $promotion = $this->promotionModel->getById($promoId);

        // Ghi log giảm giá
        $this->discountLogModel->logDiscount([
            'order_id' => $orderId,
            'promotion_id' => $promoId,
            'discount_type' => 'promotion',
            'discount_value' => $discount,
            'discount_percent' => $promotion['type'] === 'percentage' ? $promotion['discount_value'] : null,
            'reason' => 'Áp dụng khuyến mãi: ' . $promotion['name'],
            'applied_by' => $userId
        ]);

        // Tăng số lần sử dụng
        $this->promotionModel->incrementUsage($promoId);

        $this->sendJSON([
            'success' => true,
            'message' => 'Áp dụng khuyến mãi thành công',
            'data' => [
                'discount_amount' => $discount,
                'promotion_name' => $promotion['name']
            ]
        ]);
    }

    // Lấy khuyến mãi tự động
    private function getAutoPromotions() {
        $orderTotal = (float)($_POST['order_total'] ?? 0);

        $promotions = $this->promotionModel->getAutoApplyPromotions($orderTotal);

        // Tính giá trị giảm cho từng promotion
        foreach ($promotions as &$promo) {
            $promo['calculated_discount'] = $this->promotionModel->calculateDiscount($promo['id'], $orderTotal);
        }

        // Sắp xếp theo giá trị giảm (giảm nhiều nhất lên đầu)
        usort($promotions, function($a, $b) {
            return $b['calculated_discount'] - $a['calculated_discount'];
        });

        $this->sendJSON([
            'success' => true,
            'data' => $promotions
        ]);
    }

    // Tạo khuyến mãi mới (Admin only)
    private function createPromotion() {
        if (!$this->isAdmin()) {
            $this->sendJSON(['success' => false, 'message' => 'Bạn không có quyền thực hiện']);
            return;
        }

        $data = [
            'code' => $_POST['code'] ?? null,
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'type' => $_POST['type'] ?? 'percentage',
            'discount_value' => (float)($_POST['discount_value'] ?? 0),
            'min_purchase' => (float)($_POST['min_purchase'] ?? 0),
            'max_discount' => !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null,
            'start_date' => $_POST['start_date'] ?? date('Y-m-d H:i:s'),
            'end_date' => $_POST['end_date'] ?? date('Y-m-d H:i:s', strtotime('+1 month')),
            'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            'auto_apply' => isset($_POST['auto_apply']) ? (int)$_POST['auto_apply'] : 0,
            'priority' => (int)($_POST['priority'] ?? 0),
            'usage_limit' => !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null,
            'created_by' => $_SESSION['user_id']
        ];

        if (empty($data['name'])) {
            $this->sendJSON(['success' => false, 'message' => 'Vui lòng nhập tên khuyến mãi']);
            return;
        }

        $promoId = $this->promotionModel->create($data);

        $this->sendJSON([
            'success' => true,
            'message' => 'Tạo khuyến mãi thành công',
            'data' => ['id' => $promoId]
        ]);
    }

    // Cập nhật khuyến mãi (Admin only)
    private function updatePromotion() {
        if (!$this->isAdmin()) {
            $this->sendJSON(['success' => false, 'message' => 'Bạn không có quyền thực hiện']);
            return;
        }

        $id = $_POST['id'] ?? 0;

        if (!$id) {
            $this->sendJSON(['success' => false, 'message' => 'Thiếu ID']);
            return;
        }

        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'type' => $_POST['type'] ?? 'percentage',
            'discount_value' => (float)($_POST['discount_value'] ?? 0),
            'min_purchase' => (float)($_POST['min_purchase'] ?? 0),
            'max_discount' => !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null,
            'start_date' => $_POST['start_date'] ?? date('Y-m-d H:i:s'),
            'end_date' => $_POST['end_date'] ?? date('Y-m-d H:i:s'),
            'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            'auto_apply' => isset($_POST['auto_apply']) ? (int)$_POST['auto_apply'] : 0,
            'priority' => (int)($_POST['priority'] ?? 0),
            'usage_limit' => !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null
        ];

        $success = $this->promotionModel->update($id, $data);

        if ($success) {
            $this->sendJSON(['success' => true, 'message' => 'Cập nhật khuyến mãi thành công']);
        } else {
            $this->sendJSON(['success' => false, 'message' => 'Cập nhật thất bại']);
        }
    }

    // Xóa khuyến mãi (Admin only)
    private function deletePromotion() {
        if (!$this->isAdmin()) {
            $this->sendJSON(['success' => false, 'message' => 'Bạn không có quyền thực hiện']);
            return;
        }

        $id = $_POST['id'] ?? 0;

        if (!$id) {
            $this->sendJSON(['success' => false, 'message' => 'Thiếu ID']);
            return;
        }

        $success = $this->promotionModel->delete($id);

        if ($success) {
            $this->sendJSON(['success' => true, 'message' => 'Xóa khuyến mãi thành công']);
        } else {
            $this->sendJSON(['success' => false, 'message' => 'Xóa thất bại']);
        }
    }

    // Lấy tất cả khuyến mãi (Admin only)
    private function getAllPromotions() {
        if (!$this->isAdmin()) {
            $this->sendJSON(['success' => false, 'message' => 'Bạn không có quyền thực hiện']);
            return;
        }

        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        $promotions = $this->promotionModel->getAll($limit, $offset);

        $this->sendJSON(['success' => true, 'data' => $promotions]);
    }

    // Lấy chi tiết khuyến mãi
    private function getPromotion() {
        $id = $_GET['id'] ?? 0;

        if (!$id) {
            $this->sendJSON(['success' => false, 'message' => 'Thiếu ID']);
            return;
        }

        $promotion = $this->promotionModel->getById($id);

        if ($promotion) {
            $this->sendJSON(['success' => true, 'data' => $promotion]);
        } else {
            $this->sendJSON(['success' => false, 'message' => 'Không tìm thấy khuyến mãi']);
        }
    }

    // Helper: Kiểm tra quyền admin
    private function isAdmin() {
        return isset($_SESSION['vaitro_id']) && $_SESSION['vaitro_id'] == 1;
    }

    // Helper: Gửi JSON response
    private function sendJSON($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Xử lý request nếu được gọi trực tiếp
if (basename($_SERVER['PHP_SELF']) === 'PromotionController.php') {
    $controller = new PromotionController();
    $controller->handleRequest();
}
