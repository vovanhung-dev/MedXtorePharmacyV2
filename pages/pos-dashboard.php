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
                                <div class="promo-tag" onclick="applyPromotionWithCheck(<?php echo htmlspecialchars(json_encode($promo)); ?>)">
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
        // Check if pos.js loaded successfully
        console.log('Checking if pos.js functions are loaded...');
        console.log('searchCustomer available:', typeof searchCustomer !== 'undefined');
        console.log('clearCart available:', typeof clearCart !== 'undefined');
        console.log('holdBill available:', typeof holdBill !== 'undefined');

        // Fallback function definitions (only used if pos.js didn't load)
        if (typeof searchCustomer === 'undefined') {
            console.warn('⚠️ searchCustomer not loaded from pos.js, using fallback');
            window.searchCustomer = function() {
                console.log('🔍 searchCustomer() called (fallback version)');

                const searchInput = document.getElementById('customerSearch');
                const keyword = searchInput ? searchInput.value.trim() : '';

                if (!keyword) {
                    alert('Vui lòng nhập SĐT hoặc tên khách hàng');
                    return;
                }

                const customerInfo = document.getElementById('customerInfo');
                if (customerInfo) {
                    customerInfo.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Đang tìm...</div>';
                }

                fetch('/api/pos/customer?action=search&keyword=' + encodeURIComponent(keyword))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data && data.data.length > 0) {
                            // Found customers
                            if (data.data.length === 1) {
                                // Auto-select if only one result
                                const customer = data.data[0];
                                if (customerInfo) {
                                    customerInfo.innerHTML = `
                                        <div class="customer-selected">
                                            <div class="customer-details">
                                                <i class="bi bi-person-check-fill text-success"></i>
                                                <div>
                                                    <strong>${customer.ten || customer.name}</strong>
                                                    <small>${customer.sdt || customer.phone}</small>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                }
                                alert('✅ Đã chọn khách hàng: ' + (customer.ten || customer.name));
                            } else {
                                // Multiple results - show list
                                let html = '<div class="customer-results">';
                                data.data.forEach(customer => {
                                    const customerJson = JSON.stringify(customer).replace(/"/g, '&quot;');
                                    html += `
                                        <div class="customer-result-item" onclick="selectCustomerFromSearch(${customerJson})" style="cursor: pointer; padding: 10px; border-bottom: 1px solid #eee; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f3f4f6'" onmouseout="this.style.backgroundColor='white'">
                                            <div class="customer-result-info">
                                                <strong>${customer.ten || customer.name}</strong><br>
                                                <small><i class="bi bi-telephone"></i> ${customer.sdt || customer.phone}</small>
                                                ${customer.email ? '<br><small><i class="bi bi-envelope"></i> ' + customer.email + '</small>' : ''}
                                            </div>
                                            <i class="bi bi-chevron-right" style="float: right; margin-top: 10px; color: #9ca3af;"></i>
                                        </div>
                                    `;
                                });
                                html += '</div>';
                                customerInfo.innerHTML = html;
                            }
                        } else {
                            // No customers found
                            if (customerInfo) {
                                customerInfo.innerHTML = `
                                    <div class="customer-not-found">
                                        <i class="bi bi-exclamation-circle"></i>
                                        <p>Không tìm thấy khách hàng</p>
                                    </div>
                                `;
                            }
                            alert('⚠️ Không tìm thấy khách hàng');
                        }
                    })
                    .catch(error => {
                        console.error('❌ Search customer error:', error);
                        alert('❌ Lỗi tìm kiếm khách hàng: ' + error.message);
                        if (customerInfo) {
                            customerInfo.innerHTML = `
                                <div class="customer-default">
                                    <i class="bi bi-person"></i>
                                    <span>Khách vãng lai</span>
                                </div>
                            `;
                        }
                    });
            };
        }

        if (typeof clearCart === 'undefined') {
            console.warn('⚠️ clearCart not loaded from pos.js, using fallback');
            window.clearCart = function() {
                if (!confirm('Xóa toàn bộ giỏ hàng?')) {
                    return;
                }

                fetch('/api/pos/cart', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'clear'})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('❌ Lỗi: ' + (data.message || 'Không thể xóa giỏ hàng'));
                    }
                })
                .catch(error => {
                    console.error('Clear cart error:', error);
                    alert('❌ Lỗi kết nối: ' + error.message);
                });
            };
        }

        if (typeof holdBill === 'undefined') {
            console.warn('⚠️ holdBill not loaded from pos.js, using fallback');
            window.holdBill = function() {
                const modal = new bootstrap.Modal(document.getElementById('holdBillModal'));
                modal.show();
            };
        }

        if (typeof confirmHoldBill === 'undefined') {
            window.confirmHoldBill = function() {
                const billNameInput = document.getElementById('holdBillName');
                const billName = billNameInput ? billNameInput.value.trim() : '';

                fetch('/api/pos/held-bills', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'hold', bill_name: billName || null})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('holdBillModal'));
                        if (modal) modal.hide();
                        if (billNameInput) billNameInput.value = '';
                        alert('✅ Đã tạm giữ hóa đơn');
                        location.reload();
                    } else {
                        alert('❌ ' + (data.message || 'Lỗi tạm giữ hóa đơn'));
                    }
                })
                .catch(error => {
                    console.error('Hold bill error:', error);
                    alert('❌ Lỗi kết nối: ' + error.message);
                });
            };
        }

        if (typeof retrieveHeldBill === 'undefined') {
            window.retrieveHeldBill = function(billId) {
                fetch('/api/pos/held-bills', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'retrieve', bill_id: billId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('❌ ' + (data.message || 'Lỗi khôi phục hóa đơn'));
                    }
                })
                .catch(error => {
                    console.error('Retrieve bill error:', error);
                    alert('❌ Lỗi kết nối');
                });
            };
        }

        if (typeof proceedToPayment === 'undefined') {
            window.proceedToPayment = function() {
                const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
                modal.show();
                setTimeout(() => {
                    if (typeof showCashPaymentForm === 'function') {
                        showCashPaymentForm();
                    }
                }, 100);
            };
        }

        // Function to select customer from search results
        window.selectCustomerFromSearch = function(customer) {
            console.log('✅ Selected customer:', customer);

            const customerInfo = document.getElementById('customerInfo');
            if (customerInfo) {
                customerInfo.innerHTML = `
                    <div class="customer-selected" style="padding: 15px; background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div class="customer-details" style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <i class="bi bi-person-check-fill text-success" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <strong style="display: block; font-size: 1.1rem;">${customer.ten || customer.name}</strong>
                                        <small style="color: #6b7280;"><i class="bi bi-telephone"></i> ${customer.sdt || customer.phone}</small>
                                        ${customer.email ? '<br><small style="color: #6b7280;"><i class="bi bi-envelope"></i> ' + customer.email + '</small>' : ''}
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" onclick="clearSelectedCustomer()" style="margin-left: 10px;">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                `;
            }

            // Clear search input
            const searchInput = document.getElementById('customerSearch');
            if (searchInput) {
                searchInput.value = '';
            }

            alert('✅ Đã chọn khách hàng: ' + (customer.ten || customer.name));
        };

        // Function to clear selected customer
        window.clearSelectedCustomer = function() {
            const customerInfo = document.getElementById('customerInfo');
            if (customerInfo) {
                customerInfo.innerHTML = `
                    <div class="customer-default" style="padding: 15px; text-align: center; color: #6b7280;">
                        <i class="bi bi-person" style="font-size: 2rem;"></i>
                        <p style="margin: 5px 0 0 0;">Khách vãng lai</p>
                    </div>
                `;
            }
        };

        if (typeof cancelOrder === 'undefined') {
            window.cancelOrder = function() {
                console.log('🚫 cancelOrder() called');

                // Check if cart has items
                const cartItems = document.querySelectorAll('#cartItems .cart-item');
                console.log('Cart items count:', cartItems.length);

                if (cartItems.length === 0) {
                    alert('ℹ️ Giỏ hàng đã trống');
                    return;
                }

                if (!confirm('⚠️ Bạn có chắc muốn hủy đơn hàng này?\n\nGiỏ hàng sẽ được xóa và khách hàng sẽ được đặt lại về khách vãng lai.')) {
                    return;
                }

                // Call clearCart if available, otherwise reload
                if (typeof clearCart === 'function') {
                    clearCart();
                } else {
                    fetch('/api/pos/cart', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({action: 'clear'})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ Đã hủy đơn hàng');
                            location.reload();
                        } else {
                            alert('❌ Lỗi: ' + (data.message || 'Không thể hủy đơn hàng'));
                        }
                    })
                    .catch(error => {
                        console.error('Cancel order error:', error);
                        alert('❌ Lỗi kết nối: ' + error.message);
                    });
                }

                // Clear selected customer
                clearSelectedCustomer();
            };
        }

        // Manual Discount Functions (defined here to ensure availability)
        function updateDiscountPreview() {
            const discountType = document.getElementById('discountType')?.value;
            const discountValue = parseFloat(document.getElementById('discountValue')?.value) || 0;
            const previewDiv = document.getElementById('discountPreview');
            const previewText = document.getElementById('discountPreviewText');

            if (!previewDiv || !previewText) return;

            // Get current cart total from the display
            const totalText = document.getElementById('cartTotal')?.textContent || '0';
            const cartSubtotal = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;

            if (discountValue <= 0 || cartSubtotal <= 0) {
                previewDiv.style.display = 'none';
                return;
            }

            let discountAmount = 0;
            let previewMessage = '';

            if (discountType === 'percentage') {
                if (discountValue > 100) {
                    previewDiv.className = 'alert alert-danger small';
                    previewText.textContent = 'Giảm giá không được vượt quá 100%';
                    previewDiv.style.display = 'block';
                    return;
                }
                discountAmount = (cartSubtotal * discountValue) / 100;
                previewMessage = `Giảm ${discountValue}% = ${formatCurrency(discountAmount)}`;
            } else {
                if (discountValue > cartSubtotal) {
                    previewDiv.className = 'alert alert-danger small';
                    previewText.textContent = 'Số tiền giảm không được lớn hơn tổng tiền hàng';
                    previewDiv.style.display = 'block';
                    return;
                }
                discountAmount = discountValue;
                previewMessage = `Giảm ${formatCurrency(discountAmount)}`;
            }

            const finalTotal = cartSubtotal - discountAmount;
            previewMessage += ` | Còn lại: ${formatCurrency(finalTotal)}`;

            previewDiv.className = 'alert alert-info small';
            previewText.textContent = previewMessage;
            previewDiv.style.display = 'block';
        }

        function applyManualDiscount() {
            console.log('Applying manual discount...');

            const discountType = document.getElementById('discountType')?.value;
            const discountValue = parseFloat(document.getElementById('discountValue')?.value) || 0;
            const discountReason = document.getElementById('discountReason')?.value.trim();

            if (discountValue <= 0) {
                showNotification('Vui lòng nhập giá trị giảm giá', 'warning');
                return;
            }

            // Get current cart total
            const totalText = document.getElementById('cartTotal')?.textContent || '0';
            const cartSubtotal = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;

            if (cartSubtotal <= 0) {
                showNotification('Giỏ hàng trống', 'warning');
                return;
            }

            if (discountType === 'percentage' && (discountValue < 0 || discountValue > 100)) {
                showNotification('Giảm giá phải từ 0% đến 100%', 'error');
                return;
            }

            if (discountType === 'fixed' && discountValue > cartSubtotal) {
                showNotification('Số tiền giảm không được lớn hơn tổng tiền hàng', 'error');
                return;
            }

            const applyBtn = document.getElementById('applyDiscountBtn');
            if (applyBtn) {
                applyBtn.disabled = true;
                applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang áp dụng...';
            }

            fetch('/api/pos/discount', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'apply_manual',
                    type: discountType,
                    value: discountValue,
                    reason: discountReason || 'Giảm giá trực tiếp'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof updateCartFromServer === 'function') {
                        updateCartFromServer();
                    } else {
                        location.reload();
                    }

                    const modal = bootstrap.Modal.getInstance(document.getElementById('manualDiscountModal'));
                    if (modal) {
                        modal.hide();
                    }

                    document.getElementById('discountValue').value = '';
                    document.getElementById('discountReason').value = '';

                    const discountText = discountType === 'percentage'
                        ? `${discountValue}%`
                        : `${formatCurrency(discountValue)}`;
                    showNotification(`Đã áp dụng giảm giá ${discountText}`, 'success');
                } else {
                    showNotification(data.message || 'Lỗi áp dụng giảm giá', 'error');
                }
            })
            .catch(error => {
                console.error('Apply discount error:', error);
                showNotification('Lỗi kết nối: ' + error.message, 'error');
            })
            .finally(() => {
                if (applyBtn) {
                    applyBtn.disabled = false;
                    applyBtn.innerHTML = '<i class="bi bi-check-circle"></i> Áp dụng';
                }
            });
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        }

        function showNotification(message, type) {
            // Simple notification using alert for now
            // You can enhance this with a better UI
            if (typeof window.POSSystem !== 'undefined' && typeof window.POSSystem.showNotification === 'function') {
                window.POSSystem.showNotification(message, type);
            } else {
                console.log(`[${type.toUpperCase()}] ${message}`);
                if (type === 'error') {
                    alert(message);
                }
            }
        }

        function clearDiscount() {
            console.log('clearDiscount() called');

            if (!confirm('Bạn có chắc muốn xóa mã giảm giá đang áp dụng?')) {
                return;
            }

            fetch('/api/pos/discount', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'remove_discount'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof updateCartFromServer === 'function') {
                        updateCartFromServer();
                    } else {
                        location.reload();
                    }
                    showNotification('Đã xóa mã giảm giá', 'success');

                    // Clear voucher input if exists
                    const voucherInput = document.getElementById('voucherCode');
                    if (voucherInput) {
                        voucherInput.value = '';
                    }
                } else {
                    showNotification(data.message || 'Lỗi xóa mã giảm giá', 'error');
                }
            })
            .catch(error => {
                console.error('Clear discount error:', error);
                showNotification('Lỗi kết nối: ' + error.message, 'error');
            });
        }

        function applyVoucher() {
            console.log('applyVoucher() called');

            const voucherInput = document.getElementById('voucherCode');
            const code = voucherInput ? voucherInput.value.trim() : '';

            console.log('Voucher code:', code);

            if (!code) {
                alert('⚠️ Vui lòng nhập mã voucher');
                return;
            }

            // Get current cart total to check conditions
            const totalText = document.getElementById('cartSubtotal')?.textContent ||
                             document.getElementById('cartTotal')?.textContent || '0';
            const cartTotal = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;

            if (cartTotal <= 0) {
                alert('⚠️ Giỏ hàng trống. Vui lòng thêm sản phẩm trước khi áp dụng mã.');
                return;
            }

            console.log('Applying voucher:', code);

            // Disable button and show loading
            const applyBtn = voucherInput.nextElementSibling;
            if (applyBtn) {
                applyBtn.disabled = true;
                applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';
            }

            fetch('/api/pos/discount', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'apply_promotion',
                    promotion_code: code
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Apply voucher response:', data);

                if (data.success) {
                    console.log('Voucher applied successfully');
                    if (typeof updateCartFromServer === 'function') {
                        updateCartFromServer();
                    } else {
                        location.reload();
                    }
                    showNotification('✅ Đã áp dụng mã khuyến mãi: ' + code, 'success');

                    // Clear input after successful application
                    voucherInput.value = '';
                } else {
                    console.error('Failed to apply voucher:', data.message);
                    alert('❌ ' + (data.message || 'Mã khuyến mãi không hợp lệ hoặc không đủ điều kiện'));
                }
            })
            .catch(error => {
                console.error('Apply voucher error:', error);
                alert('❌ Lỗi kết nối: ' + error.message);
            })
            .finally(() => {
                // Re-enable button
                if (applyBtn) {
                    applyBtn.disabled = false;
                    applyBtn.innerHTML = '<i class="bi bi-check-circle"></i> Áp dụng';
                }
            });
        }

        function applyPromotionWithCheck(promotion) {
            console.log('applyPromotionWithCheck() called with:', promotion);

            if (!promotion || !promotion.code) {
                alert('Thông tin khuyến mãi không hợp lệ');
                return;
            }

            // Get current cart total
            const totalText = document.getElementById('cartSubtotal')?.textContent ||
                             document.getElementById('cartTotal')?.textContent || '0';
            const cartTotal = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;

            console.log('Cart total:', cartTotal);
            console.log('Promotion:', promotion);

            // Check minimum order amount
            const minAmount = parseFloat(promotion.min_order_amount || promotion.minimum_order_amount || 0);

            if (minAmount > 0 && cartTotal < minAmount) {
                const formattedMin = formatCurrency(minAmount);
                const formattedCurrent = formatCurrency(cartTotal);

                alert(
                    `❌ Không đủ điều kiện áp dụng khuyến mãi "${promotion.name}"\n\n` +
                    `Yêu cầu: Tổng đơn hàng tối thiểu ${formattedMin}\n` +
                    `Hiện tại: ${formattedCurrent}\n\n` +
                    `Vui lòng thêm ${formatCurrency(minAmount - cartTotal)} nữa để sử dụng mã này.`
                );
                return;
            }

            // Check if cart is empty
            if (cartTotal <= 0) {
                alert('⚠️ Giỏ hàng trống. Vui lòng thêm sản phẩm trước khi áp dụng mã khuyến mãi.');
                return;
            }

            // Fill voucher code into input
            const voucherInput = document.getElementById('voucherCode');
            if (voucherInput) {
                voucherInput.value = promotion.code;
                voucherInput.focus();

                // Show notification with promotion details
                let discountText = '';
                if (promotion.type === 'percentage') {
                    discountText = `giảm ${promotion.discount_value}%`;
                } else {
                    discountText = `giảm ${formatCurrency(promotion.discount_value)}`;
                }

                showNotification(
                    `✅ Đã điền mã "${promotion.code}" (${discountText}). Nhấn "Áp dụng" để sử dụng.`,
                    'success'
                );
            } else {
                console.error('Voucher input not found');
                alert('Không tìm thấy ô nhập mã voucher');
            }
        }

        function proceedToPayment() {
            console.log('💳 proceedToPayment() called');

            // Check if cart has items
            const cartItems = document.querySelectorAll('#cartItems .cart-item');
            if (cartItems.length === 0) {
                showNotification('⚠️ Giỏ hàng trống', 'warning');
                return;
            }

            // Open payment modal
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();

            // Show cash payment form by default
            setTimeout(() => {
                showCashPaymentForm();
            }, 100);
        }

        function showCashPaymentForm() {
            const totalText = document.getElementById('cartTotal')?.textContent || '0đ';
            const total = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;
            const paymentDetails = document.getElementById('paymentDetails');

            if (!paymentDetails) return;

            paymentDetails.innerHTML = `
                <div class="cash-payment-form">
                    <div class="mb-3">
                        <label class="form-label">Tổng tiền phải trả:</label>
                        <div class="alert alert-info fw-bold fs-4">${formatCurrency(total)}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tiền khách đưa:</label>
                        <input type="number"
                               class="form-control form-control-lg"
                               id="cashReceived"
                               placeholder="Nhập số tiền..."
                               min="${total}"
                               value="${total}"
                               oninput="calculateCashChange()">
                    </div>

                    <div class="quick-amount-buttons mb-3">
                        <button class="btn btn-outline-primary btn-sm" onclick="setCashAmount(${Math.ceil(total / 1000) * 1000})">
                            ${formatCurrency(Math.ceil(total / 1000) * 1000)}
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="setCashAmount(${Math.ceil(total / 10000) * 10000})">
                            ${formatCurrency(Math.ceil(total / 10000) * 10000)}
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="setCashAmount(${Math.ceil(total / 50000) * 50000})">
                            ${formatCurrency(Math.ceil(total / 50000) * 50000)}
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tiền thối:</label>
                        <div class="alert alert-success fw-bold" id="changeAmount">0đ</div>
                    </div>
                </div>
            `;

            // Auto calculate change
            calculateCashChange();

            // Enable confirm button
            const confirmBtn = document.getElementById('confirmPaymentBtn');
            if (confirmBtn) {
                confirmBtn.disabled = false;
            }
        }

        function setCashAmount(amount) {
            const cashInput = document.getElementById('cashReceived');
            if (cashInput) {
                cashInput.value = amount;
                calculateCashChange();
            }
        }

        function calculateCashChange() {
            const totalText = document.getElementById('cartTotal')?.textContent || '0đ';
            const total = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;
            const cashInput = document.getElementById('cashReceived');
            const changeDisplay = document.getElementById('changeAmount');
            const confirmBtn = document.getElementById('confirmPaymentBtn');

            if (!cashInput || !changeDisplay) return;

            const received = parseFloat(cashInput.value) || 0;
            const change = received - total;

            if (change >= 0) {
                changeDisplay.textContent = formatCurrency(change);
                changeDisplay.className = 'alert alert-success fw-bold';
                if (confirmBtn) confirmBtn.disabled = false;
            } else {
                changeDisplay.textContent = 'Chưa đủ tiền';
                changeDisplay.className = 'alert alert-danger fw-bold';
                if (confirmBtn) confirmBtn.disabled = true;
            }
        }

        function confirmPayment() {
            const cashInput = document.getElementById('cashReceived');
            const totalText = document.getElementById('cartTotal')?.textContent || '0đ';
            const total = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;
            const received = cashInput ? parseFloat(cashInput.value) || 0 : 0;

            if (received < total) {
                showNotification('⚠️ Số tiền khách đưa chưa đủ', 'warning');
                return;
            }

            const confirmBtn = document.getElementById('confirmPaymentBtn');
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';
            }

            // Process payment via API
            fetch('/api/pos/payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'process',
                    payment_method: 'cash',
                    cash_received: received
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('✅ Thanh toán thành công!', 'success');

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                    if (modal) modal.hide();

                    // Reload page to clear cart
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('❌ ' + (data.message || 'Lỗi thanh toán'), 'error');
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="bi bi-check-circle"></i> Xác nhận thanh toán';
                    }
                }
            })
            .catch(error => {
                console.error('Payment error:', error);
                showNotification('❌ Lỗi kết nối: ' + error.message, 'error');
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-check-circle"></i> Xác nhận thanh toán';
                }
            });
        }

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
