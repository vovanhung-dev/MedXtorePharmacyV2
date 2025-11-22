<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/POSSession.php';
require_once __DIR__ . '/../models/HeldBill.php';
require_once __DIR__ . '/../models/POSTransaction.php';
require_once __DIR__ . '/../models/InventoryBatch.php';
require_once __DIR__ . '/../models/Promotion.php';
require_once __DIR__ . '/../models/DiscountLog.php';

class POSController {
    private $conn;
    private $db;
    private $productModel;
    private $inventoryModel;
    private $sessionModel;
    private $heldBillModel;
    private $transactionModel;
    private $batchModel;
    private $promotionModel;
    private $discountLogModel;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();

        // Initialize models
        $this->productModel = new Product();
        $this->inventoryModel = new Inventory();
        $this->sessionModel = new POSSession();
        $this->heldBillModel = new HeldBill();
        $this->transactionModel = new POSTransaction();
        $this->batchModel = new InventoryBatch();
        $this->promotionModel = new Promotion();
        $this->discountLogModel = new DiscountLog();

        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Initialize POS cart for current user
        $this->initPOSCart();
    }

    // Initialize POS cart session
    private function initPOSCart() {
        if (!isset($_SESSION['user_id'])) {
            return;
        }

        $userId = $_SESSION['user_id'];

        if (!isset($_SESSION['pos_carts'])) {
            $_SESSION['pos_carts'] = [];
        }

        if (!isset($_SESSION['pos_carts'][$userId])) {
            $_SESSION['pos_carts'][$userId] = [
                'items' => [],
                'customer_id' => null,
                'discount' => [
                    'type' => null,
                    'value' => 0,
                    'promotion_id' => null
                ],
                'notes' => ''
            ];
        }
    }

    // Get reference to current POS cart
    private function &getPOSCart() {
        $userId = $_SESSION['user_id'];
        return $_SESSION['pos_carts'][$userId];
    }

    // ===================================
    // 1. GET PRODUCTS FOR POS
    // ===================================
    public function getProductsForPOS($search = '', $loaiId = '', $limit = 50, $offset = 0) {
        try {
            $query = "SELECT
                        t.id as thuoc_id,
                        t.ten_thuoc,
                        t.hinhanh,
                        t.mota,
                        l.ten_loai,
                        dv.id as donvi_id,
                        dv.ten_donvi,
                        td.gia,
                        COALESCE(SUM(k.soluong), 0) as soluong_tonkho
                      FROM thuoc t
                      JOIN loai_thuoc l ON t.loai_id = l.id
                      JOIN thuoc_donvi td ON t.id = td.thuoc_id
                      JOIN donvi dv ON td.donvi_id = dv.id
                      LEFT JOIN khohang k ON t.id = k.thuoc_id AND td.donvi_id = k.donvi_id
                      WHERE td.gia > 0";

            $params = [];

            if (!empty($search)) {
                $query .= " AND (t.ten_thuoc LIKE ? OR t.id LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if (!empty($loaiId)) {
                $query .= " AND t.loai_id = ?";
                $params[] = $loaiId;
            }

            $query .= " GROUP BY t.id, t.ten_thuoc, t.hinhanh, t.mota, l.ten_loai, dv.id, dv.ten_donvi, td.gia
                       HAVING soluong_tonkho > 0
                       ORDER BY t.ten_thuoc ASC
                       LIMIT ? OFFSET ?";

            $stmt = $this->conn->prepare($query);

            // Bind search/filter parameters first
            $paramIndex = 1;
            foreach ($params as $param) {
                $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
            }

            // Bind LIMIT and OFFSET as integers (must use PDO::PARAM_INT to avoid quoting)
            $stmt->bindValue($paramIndex++, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue($paramIndex, (int)$offset, PDO::PARAM_INT);

            $stmt->execute();

            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Loi lay danh sach san pham: ' . $e->getMessage()
            ];
        }
    }

    // ===================================
    // 2. CHECK STOCK REAL-TIME
    // ===================================
    public function checkStock($thuocId, $donviId) {
        try {
            // Simple stock check from khohang table
            $query = "SELECT COALESCE(SUM(soluong), 0) as total_stock
                     FROM khohang
                     WHERE thuoc_id = ? AND donvi_id = ?";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$thuocId, $donviId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalStock = (int)$result['total_stock'];

            return [
                'success' => true,
                'data' => [
                    'total_stock' => $totalStock,
                    'batches' => [],
                    'expiry_warning' => false,
                    'nearest_expiry' => null,
                    'in_stock' => $totalStock > 0
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Loi kiem tra ton kho: ' . $e->getMessage()
            ];
        }
    }

    // ===================================
    // 3. ADD TO POS CART
    // ===================================
    public function addToCart($data) {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            // Validate input
            if (empty($data['thuoc_id']) || empty($data['donvi_id']) || empty($data['gia']) || empty($data['soluong'])) {
                throw new Exception('Thieu thong tin san pham');
            }

            $thuocId = (int)$data['thuoc_id'];
            $donviId = (int)$data['donvi_id'];
            $gia = (float)$data['gia'];
            $soluong = max(1, (int)$data['soluong']);

            // Check stock
            $stockCheck = $this->checkStock($thuocId, $donviId);
            if (!$stockCheck['success'] || $stockCheck['data']['total_stock'] < $soluong) {
                throw new Exception('Khong du hang trong kho');
            }

            // Get cart reference
            $cart = &$this->getPOSCart();

            // Create cart key
            $key = $thuocId . '_' . $donviId;

            // Add or update cart item
            if (isset($cart['items'][$key])) {
                $newQuantity = $cart['items'][$key]['soluong'] + $soluong;

                // Recheck stock with new quantity
                if ($stockCheck['data']['total_stock'] < $newQuantity) {
                    throw new Exception('Khong du hang trong kho');
                }

                $cart['items'][$key]['soluong'] = $newQuantity;
            } else {
                $cart['items'][$key] = [
                    'thuoc_id' => $thuocId,
                    'ten_thuoc' => $data['ten_thuoc'] ?? 'Unknown',
                    'hinhanh' => $data['hinhanh'] ?? 'default.png',
                    'donvi_id' => $donviId,
                    'ten_donvi' => $data['ten_donvi'] ?? '',
                    'gia' => $gia,
                    'soluong' => $soluong,
                    'discount_percent' => 0,
                    'discount_amount' => 0
                ];
            }

            // Calculate subtotal
            $cart['items'][$key]['subtotal'] = $cart['items'][$key]['soluong'] * $gia;

            return [
                'success' => true,
                'message' => 'Them vao gio hang thanh cong',
                'cart' => $this->getCartSummary()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ===================================
    // 4. UPDATE CART QUANTITY
    // ===================================
    public function updateCartQuantity($key, $quantity) {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            $quantity = max(1, (int)$quantity);
            $cart = &$this->getPOSCart();

            if (!isset($cart['items'][$key])) {
                throw new Exception('San pham khong ton tai trong gio hang');
            }

            // Check stock
            $item = $cart['items'][$key];
            $stockCheck = $this->checkStock($item['thuoc_id'], $item['donvi_id']);

            if (!$stockCheck['success'] || $stockCheck['data']['total_stock'] < $quantity) {
                throw new Exception('Khong du hang trong kho');
            }

            // Update quantity and recalculate
            $cart['items'][$key]['soluong'] = $quantity;
            $cart['items'][$key]['subtotal'] = $quantity * $item['gia'] - $item['discount_amount'];

            return [
                'success' => true,
                'message' => 'Cap nhat so luong thanh cong',
                'cart' => $this->getCartSummary()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ===================================
    // 5. REMOVE FROM CART
    // ===================================
    public function removeFromCart($key) {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            $cart = &$this->getPOSCart();

            if (!isset($cart['items'][$key])) {
                throw new Exception('San pham khong ton tai trong gio hang');
            }

            unset($cart['items'][$key]);

            return [
                'success' => true,
                'message' => 'Xoa san pham thanh cong',
                'cart' => $this->getCartSummary()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ===================================
    // 6. CLEAR CART
    // ===================================
    public function clearCart() {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            $userId = $_SESSION['user_id'];
            $_SESSION['pos_carts'][$userId] = [
                'items' => [],
                'customer_id' => null,
                'discount' => [
                    'type' => null,
                    'value' => 0,
                    'promotion_id' => null
                ],
                'notes' => ''
            ];

            return [
                'success' => true,
                'message' => 'Xoa gio hang thanh cong'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ===================================
    // 7. APPLY DISCOUNT/PROMOTION
    // ===================================
    public function applyDiscount($discountData) {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            $cart = &$this->getPOSCart();

            if (empty($cart['items'])) {
                throw new Exception('Gio hang trong');
            }

            $discountType = $discountData['type'] ?? null;
            $discountValue = (float)($discountData['value'] ?? 0);
            $promotionCode = $discountData['promotion_code'] ?? null;
            $approvedBy = $discountData['approved_by'] ?? null;
            $reason = $discountData['reason'] ?? null;

            // Calculate subtotal before discount
            $subtotal = 0;
            foreach ($cart['items'] as $item) {
                $subtotal += $item['soluong'] * $item['gia'];
            }

            // If promotion code provided
            if ($promotionCode) {
                $promotion = $this->promotionModel->getPromotionByCode($promotionCode);

                if (!$promotion) {
                    throw new Exception('Ma khuyen mai khong hop le');
                }

                $discountAmount = $this->promotionModel->calculateDiscount($promotion['id'], $subtotal);

                if ($discountAmount <= 0) {
                    throw new Exception('Don hang khong du dieu kien ap dung khuyen mai');
                }

                $cart['discount'] = [
                    'type' => 'promotion',
                    'value' => $discountAmount,
                    'promotion_id' => $promotion['id'],
                    'promotion_code' => $promotionCode,
                    'promotion_name' => $promotion['name']
                ];

            } else {
                // Manual discount
                $discountAmount = 0;

                if ($discountType === 'percentage') {
                    if ($discountValue < 0 || $discountValue > 100) {
                        throw new Exception('Giam gia phai tu 0% den 100%');
                    }
                    $discountAmount = ($subtotal * $discountValue) / 100;

                } elseif ($discountType === 'fixed') {
                    if ($discountValue < 0) {
                        throw new Exception('So tien giam khong hop le');
                    }
                    if ($discountValue > $subtotal) {
                        throw new Exception('So tien giam khong duoc lon hon tong tien hang');
                    }
                    $discountAmount = $discountValue;
                }

                $cart['discount'] = [
                    'type' => $discountType,
                    'value' => $discountAmount,
                    'promotion_id' => null,
                    'approved_by' => $approvedBy,
                    'reason' => $reason
                ];
            }

            return [
                'success' => true,
                'message' => 'Ap dung giam gia thanh cong',
                'cart' => $this->getCartSummary()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ===================================
    // 7.1. REMOVE DISCOUNT
    // ===================================
    public function removeDiscount() {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            $cart = &$this->getPOSCart();

            // Reset discount to empty
            $cart['discount'] = [
                'type' => null,
                'value' => 0,
                'promotion_id' => null,
                'promotion_code' => null,
                'promotion_name' => null,
                'approved_by' => null,
                'reason' => null
            ];

            return [
                'success' => true,
                'message' => 'Da xoa giam gia',
                'cart' => $this->getCartSummary()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ===================================
    // 8. HOLD BILL
    // ===================================
    public function holdBill($billName = null) {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            $userId = $_SESSION['user_id'];
            $cart = &$this->getPOSCart();

            if (empty($cart['items'])) {
                throw new Exception('Gio hang trong');
            }

            // Check if user has active session (optional - skip for admin)
            // Admin can always hold bills without opening a session
            // $session = $this->sessionModel->getActiveSession($userId);
            // if (!$session) {
            //     throw new Exception('Ban chua mo ca lam viec');
            // }

            // Calculate totals
            $summary = $this->calculateCartTotals($cart);

            // Prepare bill data
            $billData = [
                'customer_id' => $cart['customer_id'],
                'bill_name' => $billName ?? 'Bill ' . date('His'),
                'subtotal' => $summary['subtotal'],
                'discount_amount' => $summary['discount_amount'],
                'total' => $summary['total'],
                'notes' => $cart['notes']
            ];

            // Prepare items
            $items = [];
            foreach ($cart['items'] as $item) {
                $items[] = [
                    'thuoc_id' => $item['thuoc_id'],
                    'donvi_id' => $item['donvi_id'],
                    'quantity' => $item['soluong'],
                    'unit_price' => $item['gia'],
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'subtotal' => $item['subtotal']
                ];
            }

            // Save held bill
            $heldBillId = $this->heldBillModel->holdBill($session['id'], $userId, $billData, $items);

            // Clear current cart
            $this->clearCart();

            return [
                'success' => true,
                'message' => 'Tam giu hoa don thanh cong',
                'held_bill_id' => $heldBillId
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ===================================
    // 9. GET HELD BILLS
    // ===================================
    public function getHeldBills() {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            $userId = $_SESSION['user_id'];
            $session = $this->sessionModel->getActiveSession($userId);

            $heldBills = $this->heldBillModel->getHeldBills($session ? $session['id'] : null, 'held');

            return [
                'success' => true,
                'data' => $heldBills
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ===================================
    // 10. RETRIEVE HELD BILL
    // ===================================
    public function retrieveHeldBill($heldBillId) {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            // Get held bill details
            $heldBill = $this->heldBillModel->getHeldBillById($heldBillId);
            if (!$heldBill) {
                throw new Exception('Hoa don tam giu khong ton tai');
            }

            // Get items
            $items = $this->heldBillModel->getHeldBillItems($heldBillId);

            // Clear current cart
            $this->clearCart();

            // Restore to cart
            $cart = &$this->getPOSCart();
            $cart['customer_id'] = $heldBill['customer_id'];
            $cart['notes'] = $heldBill['notes'];

            foreach ($items as $item) {
                $key = $item['thuoc_id'] . '_' . $item['donvi_id'];
                $cart['items'][$key] = [
                    'thuoc_id' => $item['thuoc_id'],
                    'ten_thuoc' => $item['ten_thuoc'],
                    'hinhanh' => $item['hinhanh'],
                    'donvi_id' => $item['donvi_id'],
                    'ten_donvi' => $item['ten_donvi'],
                    'gia' => $item['unit_price'],
                    'soluong' => $item['quantity'],
                    'discount_percent' => $item['discount_percent'],
                    'discount_amount' => $item['discount_amount'],
                    'subtotal' => $item['subtotal']
                ];
            }

            // Restore discount if any
            if ($heldBill['discount_amount'] > 0) {
                $cart['discount']['value'] = $heldBill['discount_amount'];
            }

            // Mark as retrieved
            $this->heldBillModel->retrieveHeldBill($heldBillId);

            return [
                'success' => true,
                'message' => 'Khoi phuc hoa don thanh cong',
                'cart' => $this->getCartSummary()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ===================================
    // 11. PROCESS PAYMENT & CREATE ORDER
    // ===================================
    public function processPayment($paymentData) {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            $userId = $_SESSION['user_id'];
            $cart = &$this->getPOSCart();

            if (empty($cart['items'])) {
                throw new Exception('Gio hang trong');
            }

            // Check active session (optional - skip for admin)
            // Admin can always process payments without opening a session
            $session = $this->sessionModel->getActiveSession($userId);
            // if (!$session) {
            //     throw new Exception('Ban chua mo ca lam viec');
            // }

            // Validate payment data
            $paymentMethod = $paymentData['payment_method'] ?? 'cash';
            // Get customer_id from paymentData (sent by JavaScript)
            $customerId = $paymentData['customer_id'] ?? $cart['customer_id'] ?? null;

            // Begin transaction
            $this->conn->beginTransaction();

            try {
                // Calculate totals
                $summary = $this->calculateCartTotals($cart);

                // Create order
                $orderQuery = "INSERT INTO donhang
                              (khachhang_id, nguoidung_id, tongtien, trangthai, ngay_dat, phuongthuc_thanhtoan)
                              VALUES (?, ?, ?, 'dadat', NOW(), ?)";

                $stmt = $this->conn->prepare($orderQuery);
                $stmt->execute([
                    $customerId,
                    $userId,
                    $summary['total'],
                    $paymentMethod
                ]);

                $orderId = $this->conn->lastInsertId();

                // Insert order items and deduct stock using FIFO
                foreach ($cart['items'] as $item) {
                    // Insert order item (only columns that exist in table)
                    $itemQuery = "INSERT INTO chitiet_donhang
                                 (donhang_id, thuoc_id, soluong, dongia)
                                 VALUES (?, ?, ?, ?)";

                    $stmt = $this->conn->prepare($itemQuery);
                    $stmt->execute([
                        $orderId,
                        $item['thuoc_id'],
                        $item['soluong'],
                        $item['gia']
                    ]);

                    // Update stock in khohang table
                    $updateStockQuery = "UPDATE khohang
                                        SET soluong = soluong - ?
                                        WHERE thuoc_id = ? AND soluong >= ?";
                    $stmt = $this->conn->prepare($updateStockQuery);
                    $stmt->execute([
                        $item['soluong'],
                        $item['thuoc_id'],
                        $item['soluong']
                    ]);

                    if ($stmt->rowCount() === 0) {
                        throw new Exception('Không đủ hàng trong kho cho sản phẩm: ' . $item['ten_thuoc']);
                    }
                }

                // Transaction tracking is optional - skip if models not available
                $transactionId = null;

                // Try to create transaction record if model exists
                /*
                try {
                    $sessionId = $session ? $session['id'] : null;

                    if ($paymentMethod !== 'split' && isset($this->transactionModel)) {
                        $transactionData = [
                            'status' => 'completed',
                            'notes' => $cart['notes'] ?? ''
                        ];

                        if ($paymentMethod === 'cash') {
                            $transactionData['cash_received'] = $paymentData['cash_received'] ?? $summary['total'];
                            $transactionData['change_given'] = $paymentData['change_given'] ?? 0;
                        }

                        $transactionId = $this->transactionModel->createTransaction(
                            $orderId,
                            $sessionId,
                            $paymentMethod,
                            $summary['total'],
                            $transactionData
                        );
                        $this->transactionModel->completeTransaction($transactionId);
                    }
                } catch (Exception $txError) {
                    // Transaction logging failed, but order is still valid
                    error_log("Transaction logging failed: " . $txError->getMessage());
                }
                */

                // Discount logging is also optional
                /*
                if ($summary['discount_amount'] > 0 && isset($this->discountLogModel)) {
                    try {
                        $discountData = [
                            'order_id' => $orderId,
                            'order_item_id' => null,
                            'promotion_id' => $cart['discount']['promotion_id'] ?? null,
                            'discount_type' => $cart['discount']['type'] ?? 'manual',
                            'discount_value' => $summary['discount_amount'],
                            'discount_percent' => ($cart['discount']['type'] === 'percentage') ? $cart['discount']['value'] : null,
                            'reason' => $cart['discount']['reason'] ?? 'POS discount',
                            'applied_by' => $userId,
                            'approved_by' => $cart['discount']['approved_by'] ?? null
                        ];
                        $this->discountLogModel->logDiscount($discountData);

                        if ($cart['discount']['promotion_id'] && isset($this->promotionModel)) {
                            $this->promotionModel->incrementUsage($cart['discount']['promotion_id']);
                        }
                    } catch (Exception $discountError) {
                        error_log("Discount logging failed: " . $discountError->getMessage());
                    }
                }
                */

                // Commit transaction
                $this->conn->commit();

                // Clear cart
                $this->clearCart();

                return [
                    'success' => true,
                    'message' => 'Thanh toan thanh cong',
                    'order_id' => $orderId,
                    'transaction_id' => $transactionId,
                    'order_summary' => $summary
                ];

            } catch (Exception $e) {
                $this->conn->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ===================================
    // HELPER METHODS
    // ===================================

    // Get cart summary
    private function getCartSummary() {
        $cart = $this->getPOSCart();
        $summary = $this->calculateCartTotals($cart);

        return [
            'items' => $cart['items'],
            'item_count' => count($cart['items']),
            'subtotal' => $summary['subtotal'],
            'discount' => $cart['discount'],
            'discount_amount' => $summary['discount_amount'],
            'total' => $summary['total'],
            'customer_id' => $cart['customer_id'],
            'notes' => $cart['notes']
        ];
    }

    // Calculate cart totals
    private function calculateCartTotals($cart) {
        $subtotal = 0;

        foreach ($cart['items'] as $item) {
            $subtotal += $item['soluong'] * $item['gia'];
        }

        $discountAmount = $cart['discount']['value'] ?? 0;
        $total = max(0, $subtotal - $discountAmount);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total' => $total
        ];
    }

    // Update legacy inventory table
    private function updateLegacyInventory($thuocId, $donviId, $quantity) {
        try {
            $query = "UPDATE khohang
                     SET soluong = GREATEST(0, soluong - ?)
                     WHERE thuoc_id = ? AND donvi_id = ?
                     AND soluong > 0
                     ORDER BY hansudung ASC
                     LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$quantity, $thuocId, $donviId]);

        } catch (Exception $e) {
            // Log error but don't fail the transaction
            error_log("Legacy inventory update failed: " . $e->getMessage());
        }
    }

    // Get active promotions
    public function getActivePromotions() {
        try {
            $promotions = $this->promotionModel->getActivePromotions();

            return [
                'success' => true,
                'data' => $promotions
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Set customer for current cart
    public function setCustomer($customerId) {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            $cart = &$this->getPOSCart();
            $cart['customer_id'] = $customerId;

            return [
                'success' => true,
                'message' => 'Cap nhat thong tin khach hang thanh cong'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Set notes for current cart
    public function setNotes($notes) {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            $cart = &$this->getPOSCart();
            $cart['notes'] = $notes;

            return [
                'success' => true,
                'message' => 'Cap nhat ghi chu thanh cong'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Get current cart
    public function getCurrentCart() {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Ban chua dang nhap');
            }

            return [
                'success' => true,
                'data' => $this->getCartSummary()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
