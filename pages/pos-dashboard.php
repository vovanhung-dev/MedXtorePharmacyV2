<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/POSController.php';
require_once __DIR__ . '/../controllers/CategoryController.php';

// Initialize controllers
$posController = new POSController();
$categoryController = new CategoryController();

// Get categories for filter
$categories = $categoryController->getAllCategories();

// Get current cart
$cartResponse = $posController->getCurrentCart();
$cart = $cartResponse['success'] ? $cartResponse['data'] : ['items' => [], 'total' => 0, 'subtotal' => 0, 'discount_amount' => 0];

// Get held bills
$heldBillsResponse = $posController->getHeldBills();
$heldBills = $heldBillsResponse['success'] ? $heldBillsResponse['data'] : [];

// Get active promotions
$promotionsResponse = $posController->getActivePromotions();
$activePromotions = $promotionsResponse['success'] ? $promotionsResponse['data'] : [];

$pageTitle = "POS - Bán hàng tại quầy";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Custom POS CSS -->
    <link rel="stylesheet" href="../assets/css/pos.css">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="pos-container">
        <!-- Header -->
        <div class="pos-header">
            <div class="header-left">
                <h1 class="pos-title">
                    <i class="bi bi-shop"></i>
                    Bán hàng tại quầy
                </h1>
                <span class="user-badge">
                    <i class="bi bi-person-circle"></i>
                    <?php
                    $displayName = $_SESSION['ten'] ?? $_SESSION['username'] ?? $_SESSION['email'] ?? 'Nhân viên';
                    $role = $_SESSION['role'] ?? '';
                    $roleText = '';
                    if ($role === 'admin') $roleText = ' (Quản trị)';
                    elseif ($role === 'staff') $roleText = ' (Nhân viên)';
                    echo htmlspecialchars($displayName . $roleText);
                    ?>
                </span>
            </div>
            <div class="header-right">
                <button class="btn btn-outline-secondary btn-sm" onclick="location.href='../index.php'">
                    <i class="bi bi-house"></i> Trang chủ
                </button>
                <button class="btn btn-outline-danger btn-sm" onclick="location.href='logout.php'">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="pos-main">
            <!-- LEFT COLUMN: Product List -->
            <div class="pos-column pos-products">
                <div class="section-header">
                    <h2>Danh sách sản phẩm</h2>
                </div>

                <!-- Search and Filter -->
                <div class="search-filter-container">
                    <!-- Search Row -->
                    <div class="search-row">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="productSearch" class="form-control" placeholder="Tìm kiếm thuốc (tên, mã)..." autocomplete="off">
                        </div>
                    </div>

                    <!-- Filter Row -->
                    <div class="filter-row">
                        <select id="categoryFilter" class="form-select">
                            <option value="">Tất cả loại thuốc</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['ten_loai']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <div id="productsGrid" class="products-grid">
                    <div class="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div id="productsPagination" class="pagination-container">
                    <!-- Pagination will be generated by JavaScript -->
                </div>
            </div>

            <!-- MIDDLE COLUMN: Shopping Cart -->
            <div class="pos-column pos-cart">
                <div class="section-header">
                    <h2>Giỏ hàng</h2>
                    <button class="btn btn-sm btn-outline-danger" onclick="clearCart()" id="clearCartBtn">
                        <i class="bi bi-trash"></i> Xóa tất cả
                    </button>
                </div>

                <!-- Cart Items -->
                <div id="cartItems" class="cart-items">
                    <?php if (empty($cart['items'])): ?>
                        <div class="empty-cart">
                            <i class="bi bi-cart-x"></i>
                            <p>Giỏ hàng trống</p>
                            <small>Thêm sản phẩm để bắt đầu</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cart['items'] as $key => $item): ?>
                            <?php include 'components/cart-item.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span id="cartSubtotal" class="fw-bold"><?php echo number_format($cart['subtotal'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="summary-row discount-row" style="display: <?php echo $cart['discount_amount'] > 0 ? 'flex' : 'none'; ?>">
                        <span>
                            <i class="bi bi-tag-fill"></i> Giảm giá:
                        </span>
                        <span id="cartDiscount" class="text-danger">
                            <?php if ($cart['discount_amount'] > 0): ?>
                                -<?php echo number_format($cart['discount_amount'], 0, ',', '.'); ?>đ
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="summary-row total-row">
                        <span>Tổng cộng:</span>
                        <span id="cartTotal" class="total-amount"><?php echo number_format($cart['total'], 0, ',', '.'); ?>đ</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Actions & Customer -->
            <div class="pos-column pos-actions">
                <!-- Customer Section -->
                <div class="customer-section">
                    <h3 class="section-title">Khách hàng</h3>
                    <div class="customer-search">
                        <input type="text" id="customerSearch" class="form-control" placeholder="Tìm SĐT hoặc tên...">
                        <button class="btn btn-primary btn-sm" onclick="searchCustomer()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <div id="customerInfo" class="customer-info">
                        <div class="customer-default">
                            <i class="bi bi-person"></i>
                            <span>Khách vãng lai</span>
                        </div>
                    </div>
                </div>

                <!-- Promotion Section -->
                <div class="promotion-section">
                    <h3 class="section-title">Khuyến mãi</h3>
                    <div class="voucher-input-group">
                        <input type="text" id="voucherCode" class="form-control" placeholder="Nhập mã voucher">
                        <button class="btn btn-success" onclick="applyVoucher()">
                            <i class="bi bi-check-circle"></i> Áp dụng
                        </button>
                    </div>

                    <!-- Active Promotions List -->
                    <?php if (!empty($activePromotions)): ?>
                        <div class="active-promotions">
                            <label class="form-label small text-muted">Khuyến mãi đang chạy:</label>
                            <?php foreach (array_slice($activePromotions, 0, 3) as $promo): ?>
                                <div class="promo-tag" onclick="applyPromotionCode('<?php echo htmlspecialchars($promo['code'] ?? ''); ?>')">
                                    <i class="bi bi-gift"></i>
                                    <span><?php echo htmlspecialchars($promo['name']); ?></span>
                                    <small>
                                        <?php
                                        if ($promo['type'] === 'percentage') {
                                            echo '-' . $promo['discount_value'] . '%';
                                        } else {
                                            echo '-' . number_format($promo['discount_value']) . 'đ';
                                        }
                                        ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Manual Discount -->
                    <button class="btn btn-sm btn-outline-secondary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#manualDiscountModal">
                        <i class="bi bi-percent"></i> Giảm giá trực tiếp
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn btn-warning btn-lg w-100" onclick="holdBill()" id="holdBillBtn">
                        <i class="bi bi-pause-circle"></i>
                        Tạm giữ hóa đơn
                    </button>

                    <button class="btn btn-success btn-lg w-100" onclick="proceedToPayment()" id="checkoutBtn" disabled>
                        <i class="bi bi-credit-card"></i>
                        Thanh toán
                    </button>

                    <button class="btn btn-danger btn-lg w-100" onclick="cancelOrder()">
                        <i class="bi bi-x-circle"></i>
                        Hủy
                    </button>
                </div>

                <!-- Held Bills Section -->
                <div class="held-bills-section">
                    <h3 class="section-title">
                        Hóa đơn tạm giữ
                        <span class="badge bg-warning" id="heldBillsCount"><?php echo count($heldBills); ?></span>
                    </h3>
                    <div id="heldBillsList" class="held-bills-list">
                        <?php if (empty($heldBills)): ?>
                            <div class="text-center text-muted small">
                                Không có hóa đơn tạm giữ
                            </div>
                        <?php else: ?>
                            <?php foreach ($heldBills as $bill): ?>
                                <div class="held-bill-item" onclick="retrieveHeldBill(<?php echo $bill['id']; ?>)">
                                    <div class="held-bill-info">
                                        <strong><?php echo htmlspecialchars($bill['bill_name']); ?></strong>
                                        <small><?php echo date('H:i', strtotime($bill['created_at'])); ?></small>
                                    </div>
                                    <div class="held-bill-total">
                                        <?php echo number_format($bill['total'], 0, ',', '.'); ?>đ
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-credit-card"></i> Thanh toán
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Payment Details - Direct Form -->
                    <div id="paymentDetails" class="payment-details">
                        <!-- Cash payment form will be shown here -->
                    </div>

                    <!-- Payment Note -->
                    <div class="payment-note">
                        <i class="bi bi-info-circle"></i>
                        <span>Thanh toán tiền mặt tại quầy</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-success" onclick="confirmPayment()" id="confirmPaymentBtn" disabled>
                        <i class="bi bi-check-circle"></i> Xác nhận thanh toán
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus"></i> Thêm khách hàng mới
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCustomerForm">
                        <div class="mb-3">
                            <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ten" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="sdt" required pattern="[0-9]{10}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <textarea class="form-control" name="diachi" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveCustomer()">
                        <i class="bi bi-save"></i> Lưu
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Discount Modal -->
    <div class="modal fade" id="manualDiscountModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-percent"></i> Giảm giá trực tiếp
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Loại giảm giá</label>
                        <select class="form-select" id="discountType" onchange="updateDiscountPreview()">
                            <option value="percentage">Theo phần trăm (%)</option>
                            <option value="fixed">Theo số tiền (VNĐ)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Giá trị giảm</label>
                        <input type="number" class="form-control" id="discountValue" min="0" step="any" oninput="updateDiscountPreview()" placeholder="Nhập giá trị...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lý do</label>
                        <input type="text" class="form-control" id="discountReason" placeholder="VD: Khách hàng thân thiết, Giảm giá cuối ngày...">
                    </div>
                    <div id="discountPreview" class="alert alert-info small" style="display: none;">
                        <i class="bi bi-info-circle"></i>
                        <span id="discountPreviewText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-success" onclick="applyManualDiscount()" id="applyDiscountBtn">
                        <i class="bi bi-check-circle"></i> Áp dụng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hold Bill Modal -->
    <div class="modal fade" id="holdBillModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pause-circle"></i> Tạm giữ hóa đơn
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Đặt tên cho hóa đơn (tùy chọn)</label>
                        <input type="text" class="form-control" id="holdBillName" placeholder="VD: Khách hàng A">
                    </div>
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle"></i>
                        Hóa đơn sẽ được lưu để bạn phục vụ khách hàng khác
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-warning" onclick="confirmHoldBill()">
                        <i class="bi bi-check-circle"></i> Tạm giữ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom POS JavaScript -->
    <script src="../assets/js/pos.js?v=<?php echo time(); ?>"></script>

    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            updateCartUI();

            // Auto-search on typing (debounced)
            let searchTimeout;
            document.getElementById('productSearch').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const searchValue = document.getElementById('productSearch').value.trim();
                    const categoryValue = document.getElementById('categoryFilter').value;
                    loadProducts(searchValue, categoryValue);
                }, 500);
            });

            // Filter by category
            document.getElementById('categoryFilter').addEventListener('change', function() {
                const searchValue = document.getElementById('productSearch').value.trim();
                const categoryValue = document.getElementById('categoryFilter').value;
                loadProducts(searchValue, categoryValue);
            });
        });
    </script>
</body>
</html>
