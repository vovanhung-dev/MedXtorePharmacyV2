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
                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#prescriptionScannerModal">
                    <i class="bi bi-file-earmark-medical"></i> Scan Đơn Thuốc
                </button>
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

    <!-- Prescription Scanner Modal -->
    <div class="modal fade" id="prescriptionScannerModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-file-earmark-medical"></i> Đọc Đơn Thuốc Bằng AI
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Upload Section -->
                    <div id="prescriptionUploadSection">
                        <!-- Hidden file input - placed outside dropzone -->
                        <input type="file" id="prescriptionFile" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf" style="display: none;" onchange="handlePrescriptionFileChange(this)">

                        <div class="upload-area" id="prescriptionDropZone">
                            <div class="upload-content" onclick="openPrescriptionFileDialog(event)">
                                <i class="bi bi-cloud-upload" style="font-size: 3rem; color: #17a2b8;"></i>
                                <h5>Tải lên đơn thuốc</h5>
                                <p class="text-muted">Kéo thả file hoặc click để chọn</p>
                                <p class="small text-muted">Hỗ trợ: JPG, PNG, PDF (tối đa 10MB)</p>
                            </div>
                            <button type="button" class="btn btn-info mt-3" onclick="openPrescriptionFileDialog(event)">
                                <i class="bi bi-folder2-open"></i> Chọn file
                            </button>
                        </div>

                        <!-- Preview -->
                        <div id="prescriptionPreview" style="display: none;" class="mt-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span id="prescriptionFileName" class="text-muted"></span>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearPrescriptionFile()">
                                    <i class="bi bi-x"></i> Xóa
                                </button>
                            </div>
                            <div id="prescriptionImagePreview" class="text-center">
                                <img id="prescriptionPreviewImg" src="" style="max-height: 300px; max-width: 100%; border-radius: 8px;">
                            </div>
                        </div>
                    </div>

                    <!-- Scanning Progress -->
                    <div id="prescriptionScanningSection" style="display: none;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-info" style="width: 3rem; height: 3rem;" role="status">
                                <span class="visually-hidden">Đang phân tích...</span>
                            </div>
                            <h5 class="mt-3">Đang phân tích đơn thuốc...</h5>
                            <p class="text-muted">AI đang đọc và nhận diện các loại thuốc</p>
                            <div class="progress mt-3" style="height: 10px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div id="prescriptionResultsSection" style="display: none;">
                        <!-- Patient & Doctor Info -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white py-2">
                                        <i class="bi bi-person"></i> Thông tin bệnh nhân
                                    </div>
                                    <div class="card-body py-2" id="patientInfoDisplay">
                                        <small class="text-muted">Không có thông tin</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-secondary">
                                    <div class="card-header bg-secondary text-white py-2">
                                        <i class="bi bi-hospital"></i> Thông tin bác sĩ / Cơ sở
                                    </div>
                                    <div class="card-body py-2" id="doctorInfoDisplay">
                                        <small class="text-muted">Không có thông tin</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Diagnosis -->
                        <div id="diagnosisSection" class="mb-3" style="display: none;">
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-clipboard2-pulse"></i> <strong>Chẩn đoán:</strong> <span id="diagnosisText"></span>
                            </div>
                        </div>

                        <!-- Medicines List -->
                        <div class="card">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-capsule"></i> Danh sách thuốc được nhận diện</span>
                                <span class="badge bg-light text-dark" id="medicinesCount">0 thuốc</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="scannedMedicinesTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 30px;">
                                                    <input type="checkbox" id="selectAllMedicines" onchange="toggleSelectAllMedicines(this)">
                                                </th>
                                                <th>Thuốc trong đơn</th>
                                                <th>Sản phẩm khớp</th>
                                                <th style="width: 100px;">SL</th>
                                                <th style="width: 120px;">Giá</th>
                                                <th style="width: 120px;">Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody id="scannedMedicinesList">
                                            <!-- Will be populated by JavaScript -->
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="5" class="text-end"><strong>Tổng cộng (đã chọn):</strong></td>
                                                <td><strong id="scannedMedicinesTotal">0đ</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Raw Text (collapsible) -->
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#rawTextCollapse">
                                <i class="bi bi-file-text"></i> Xem văn bản gốc
                            </button>
                            <div class="collapse mt-2" id="rawTextCollapse">
                                <div class="card card-body bg-light">
                                    <pre id="prescriptionRawText" style="white-space: pre-wrap; font-size: 0.85rem; margin: 0;"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-info" id="scanPrescriptionBtn" onclick="scanPrescription()" disabled>
                        <i class="bi bi-cpu"></i> Phân tích đơn thuốc
                    </button>
                    <button type="button" class="btn btn-success" id="addScannedToCartBtn" onclick="addScannedMedicinesToCart()" style="display: none;">
                        <i class="bi bi-cart-plus"></i> Thêm vào giỏ hàng
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

            // Initialize prescription scanner
            initPrescriptionScanner();
        });

        // ==================== PRESCRIPTION SCANNER ====================

        let scannedPrescriptionData = null;
        let selectedPrescriptionFile = null;

        // Open file dialog
        function openPrescriptionFileDialog(event) {
            event.preventDefault();
            event.stopPropagation();
            console.log('Opening file dialog...');
            const fileInput = document.getElementById('prescriptionFile');
            if (fileInput) {
                fileInput.click();
            }
        }

        // Handle file input change
        function handlePrescriptionFileChange(input) {
            console.log('File input changed', input.files);
            if (input.files && input.files.length > 0) {
                handlePrescriptionFile(input.files[0]);
            }
        }

        function initPrescriptionScanner() {
            console.log('Initializing prescription scanner...');
            const dropZone = document.getElementById('prescriptionDropZone');

            if (!dropZone) {
                console.log('DropZone not found, will retry later');
                return;
            }

            // Drag and drop handlers
            dropZone.addEventListener('dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('drag-over');
            });

            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('drag-over');
            });

            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('drag-over');
            });

            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('drag-over');
                console.log('File dropped');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handlePrescriptionFile(files[0]);
                }
            });

            // Reset modal on close
            const modal = document.getElementById('prescriptionScannerModal');
            if (modal) {
                modal.addEventListener('hidden.bs.modal', function() {
                    resetPrescriptionScanner();
                });
            }

            console.log('Prescription scanner initialized');
        }

        function handlePrescriptionFile(file) {
            console.log('Handling file:', file.name, file.type, file.size);

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                alert('Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP) hoặc PDF\nLoại file nhận được: ' + file.type);
                return;
            }

            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File không được vượt quá 10MB');
                return;
            }

            selectedPrescriptionFile = file;

            // Show preview
            const fileNameEl = document.getElementById('prescriptionFileName');
            const previewEl = document.getElementById('prescriptionPreview');
            const imagePreviewDiv = document.getElementById('prescriptionImagePreview');

            if (fileNameEl) {
                fileNameEl.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
            }

            if (previewEl) {
                previewEl.style.display = 'block';
            }

            // Preview image or PDF icon
            if (imagePreviewDiv) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreviewDiv.innerHTML = '<img src="' + e.target.result + '" style="max-height: 300px; max-width: 100%; border-radius: 8px;">';
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreviewDiv.innerHTML = '<div class="p-4 bg-light rounded"><i class="bi bi-file-pdf text-danger" style="font-size: 4rem;"></i><p class="mt-2 mb-0">File PDF: ' + file.name + '</p></div>';
                }
            }

            // Enable scan button
            const scanBtn = document.getElementById('scanPrescriptionBtn');
            if (scanBtn) {
                scanBtn.disabled = false;
            }

            console.log('File ready for scanning');
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        function clearPrescriptionFile() {
            selectedPrescriptionFile = null;
            const fileInput = document.getElementById('prescriptionFile');
            if (fileInput) fileInput.value = '';

            const previewEl = document.getElementById('prescriptionPreview');
            if (previewEl) previewEl.style.display = 'none';

            const scanBtn = document.getElementById('scanPrescriptionBtn');
            if (scanBtn) scanBtn.disabled = true;
        }

        function resetPrescriptionScanner() {
            clearPrescriptionFile();
            scannedPrescriptionData = null;

            const uploadSection = document.getElementById('prescriptionUploadSection');
            const scanningSection = document.getElementById('prescriptionScanningSection');
            const resultsSection = document.getElementById('prescriptionResultsSection');
            const scanBtn = document.getElementById('scanPrescriptionBtn');
            const addBtn = document.getElementById('addScannedToCartBtn');
            const medicinesList = document.getElementById('scannedMedicinesList');

            if (uploadSection) uploadSection.style.display = 'block';
            if (scanningSection) scanningSection.style.display = 'none';
            if (resultsSection) resultsSection.style.display = 'none';
            if (scanBtn) scanBtn.style.display = 'inline-block';
            if (addBtn) addBtn.style.display = 'none';
            if (medicinesList) medicinesList.innerHTML = '';
        }

        async function scanPrescription() {
            if (!selectedPrescriptionFile) {
                alert('Vui lòng chọn file đơn thuốc');
                return;
            }

            // Show scanning section
            document.getElementById('prescriptionUploadSection').style.display = 'none';
            document.getElementById('prescriptionScanningSection').style.display = 'block';
            document.getElementById('prescriptionResultsSection').style.display = 'none';
            document.getElementById('scanPrescriptionBtn').disabled = true;

            try {
                const formData = new FormData();
                formData.append('prescription', selectedPrescriptionFile);
                formData.append('action', 'scan');

                const response = await fetch('/api/pos/scan-prescription.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    scannedPrescriptionData = result;
                    displayScanResults(result);
                } else {
                    alert('Lỗi: ' + (result.message || 'Không thể phân tích đơn thuốc'));
                    resetPrescriptionScanner();
                }
            } catch (error) {
                console.error('Scan error:', error);
                alert('Lỗi kết nối: ' + error.message);
                resetPrescriptionScanner();
            }
        }

        function displayScanResults(data) {
            // Hide scanning, show results
            document.getElementById('prescriptionScanningSection').style.display = 'none';
            document.getElementById('prescriptionResultsSection').style.display = 'block';

            // Display patient info
            const patientInfo = data.patient_info;
            const patientDiv = document.getElementById('patientInfoDisplay');
            if (patientInfo && (patientInfo.name || patientInfo.age || patientInfo.gender)) {
                let html = '';
                if (patientInfo.name) html += `<div><strong>Tên:</strong> ${patientInfo.name}</div>`;
                if (patientInfo.age) html += `<div><strong>Tuổi:</strong> ${patientInfo.age}</div>`;
                if (patientInfo.gender) html += `<div><strong>Giới tính:</strong> ${patientInfo.gender}</div>`;
                if (patientInfo.address) html += `<div><strong>Địa chỉ:</strong> ${patientInfo.address}</div>`;
                patientDiv.innerHTML = html;
            } else {
                patientDiv.innerHTML = '<small class="text-muted">Không có thông tin</small>';
            }

            // Display doctor info
            const doctorInfo = data.doctor_info;
            const doctorDiv = document.getElementById('doctorInfoDisplay');
            if (doctorInfo && (doctorInfo.name || doctorInfo.hospital)) {
                let html = '';
                if (doctorInfo.name) html += `<div><strong>Bác sĩ:</strong> ${doctorInfo.name}</div>`;
                if (doctorInfo.hospital) html += `<div><strong>Cơ sở:</strong> ${doctorInfo.hospital}</div>`;
                if (data.date) html += `<div><strong>Ngày:</strong> ${data.date}</div>`;
                doctorDiv.innerHTML = html;
            } else {
                doctorDiv.innerHTML = '<small class="text-muted">Không có thông tin</small>';
            }

            // Display diagnosis
            if (data.diagnosis) {
                document.getElementById('diagnosisSection').style.display = 'block';
                document.getElementById('diagnosisText').textContent = data.diagnosis;
            } else {
                document.getElementById('diagnosisSection').style.display = 'none';
            }

            // Display medicines
            const medicines = data.medicines || [];
            document.getElementById('medicinesCount').textContent = medicines.length + ' thuốc';

            const tbody = document.getElementById('scannedMedicinesList');
            tbody.innerHTML = '';

            medicines.forEach((med, index) => {
                const bestMatch = med.best_match;
                const hasMatch = bestMatch && med.match_status === 'found';
                const price = hasMatch ? bestMatch.gia_ban : 0;
                const total = price * med.quantity;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <input type="checkbox" class="medicine-checkbox" data-index="${index}"
                               ${hasMatch ? 'checked' : ''} ${!hasMatch ? 'disabled' : ''}
                               onchange="updateScannedTotal()">
                    </td>
                    <td>
                        <div><strong>${med.name}</strong></div>
                        <small class="text-muted">
                            ${med.dosage ? 'Hàm lượng: ' + med.dosage : ''}
                            ${med.usage ? '<br>Cách dùng: ' + med.usage : ''}
                        </small>
                    </td>
                    <td>
                        ${hasMatch ? `
                            <div class="d-flex align-items-center">
                                ${bestMatch.hinhanh ? `<img src="/assets/images/product-images/${bestMatch.hinhanh}" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">` : ''}
                                <div>
                                    <strong>${bestMatch.ten_thuoc}</strong>
                                    <small class="d-block text-muted">${bestMatch.ten_loai || ''}</small>
                                    <small class="d-block ${bestMatch.ton_kho > 0 ? 'text-success' : 'text-danger'}">
                                        Tồn kho: ${bestMatch.ton_kho}
                                    </small>
                                </div>
                            </div>
                            <select class="form-select form-select-sm mt-1 product-select" data-index="${index}" onchange="changeMatchedProduct(${index}, this.value)">
                                ${med.matched_products.map((p, i) => `
                                    <option value="${i}" ${i === 0 ? 'selected' : ''}>
                                        ${p.ten_thuoc} - ${formatCurrency(p.gia_ban)} (Tồn: ${p.ton_kho})
                                    </option>
                                `).join('')}
                            </select>
                        ` : `
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-exclamation-triangle"></i> Không tìm thấy
                            </span>
                            <button class="btn btn-sm btn-outline-primary mt-1" onclick="searchAlternativeProduct(${index}, '${med.name.replace(/'/g, "\\'")}')">
                                <i class="bi bi-search"></i> Tìm thủ công
                            </button>
                        `}
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm quantity-input"
                               data-index="${index}" value="${med.quantity}" min="1"
                               onchange="updateScannedTotal()" ${!hasMatch ? 'disabled' : ''}>
                    </td>
                    <td class="price-cell" data-index="${index}">
                        ${hasMatch ? formatCurrency(price) : '-'}
                    </td>
                    <td class="total-cell" data-index="${index}">
                        ${hasMatch ? formatCurrency(total) : '-'}
                    </td>
                `;
                tbody.appendChild(row);
            });

            // Raw text
            document.getElementById('prescriptionRawText').textContent = data.raw_text || 'Không có dữ liệu';

            // Update total and show add button
            updateScannedTotal();
            document.getElementById('scanPrescriptionBtn').style.display = 'none';
            document.getElementById('addScannedToCartBtn').style.display = 'inline-block';
        }

        function changeMatchedProduct(medicineIndex, productIndex) {
            if (!scannedPrescriptionData) return;

            const med = scannedPrescriptionData.medicines[medicineIndex];
            const newProduct = med.matched_products[productIndex];

            if (newProduct) {
                med.best_match = newProduct;

                // Update display
                const row = document.querySelector(`tr:has(.medicine-checkbox[data-index="${medicineIndex}"])`);
                if (row) {
                    const quantity = parseInt(row.querySelector('.quantity-input').value) || 1;
                    const price = newProduct.gia_ban;

                    row.querySelector('.price-cell').textContent = formatCurrency(price);
                    row.querySelector('.total-cell').textContent = formatCurrency(price * quantity);
                }
            }

            updateScannedTotal();
        }

        function toggleSelectAllMedicines(checkbox) {
            const allCheckboxes = document.querySelectorAll('.medicine-checkbox:not(:disabled)');
            allCheckboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateScannedTotal();
        }

        function updateScannedTotal() {
            if (!scannedPrescriptionData) return;

            let total = 0;
            const checkboxes = document.querySelectorAll('.medicine-checkbox:checked');

            checkboxes.forEach(cb => {
                const index = parseInt(cb.dataset.index);
                const med = scannedPrescriptionData.medicines[index];
                const quantityInput = document.querySelector(`.quantity-input[data-index="${index}"]`);
                const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : med.quantity;

                if (med.best_match) {
                    const itemTotal = med.best_match.gia_ban * quantity;
                    total += itemTotal;

                    // Update row total
                    const totalCell = document.querySelector(`.total-cell[data-index="${index}"]`);
                    if (totalCell) {
                        totalCell.textContent = formatCurrency(itemTotal);
                    }
                }
            });

            document.getElementById('scannedMedicinesTotal').textContent = formatCurrency(total);
        }

        async function searchAlternativeProduct(medicineIndex, searchTerm) {
            const newTerm = prompt('Nhập tên thuốc cần tìm:', searchTerm);
            if (!newTerm) return;

            try {
                const response = await fetch('/api/pos/scan-prescription.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'search_product',
                        search: newTerm
                    })
                });

                const result = await response.json();

                if (result.success && result.products && result.products.length > 0) {
                    // Update the medicine with new matches
                    scannedPrescriptionData.medicines[medicineIndex].matched_products = result.products;
                    scannedPrescriptionData.medicines[medicineIndex].best_match = result.products[0];
                    scannedPrescriptionData.medicines[medicineIndex].match_status = 'found';

                    // Redisplay results
                    displayScanResults(scannedPrescriptionData);
                    alert('Đã tìm thấy ' + result.products.length + ' sản phẩm phù hợp');
                } else {
                    alert('Không tìm thấy sản phẩm nào với từ khóa: ' + newTerm);
                }
            } catch (error) {
                console.error('Search error:', error);
                alert('Lỗi tìm kiếm: ' + error.message);
            }
        }

        async function addScannedMedicinesToCart() {
            if (!scannedPrescriptionData) return;

            const checkedBoxes = document.querySelectorAll('.medicine-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Vui lòng chọn ít nhất một thuốc để thêm vào giỏ');
                return;
            }

            const medicines = [];
            checkedBoxes.forEach(cb => {
                const index = parseInt(cb.dataset.index);
                const med = scannedPrescriptionData.medicines[index];
                const quantityInput = document.querySelector(`.quantity-input[data-index="${index}"]`);
                const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : med.quantity;

                if (med.best_match) {
                    medicines.push({
                        product_id: med.best_match.id,
                        name: med.best_match.ten_thuoc,
                        quantity: quantity
                    });
                }
            });

            if (medicines.length === 0) {
                alert('Không có thuốc hợp lệ để thêm');
                return;
            }

            const addBtn = document.getElementById('addScannedToCartBtn');
            addBtn.disabled = true;
            addBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang thêm...';

            try {
                const response = await fetch('/api/pos/scan-prescription.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add_to_cart',
                        medicines: medicines
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert(`Đã thêm ${result.added_count} thuốc vào giỏ hàng`);

                    // Close modal and reload cart
                    const modal = bootstrap.Modal.getInstance(document.getElementById('prescriptionScannerModal'));
                    if (modal) modal.hide();

                    // Reload page to update cart
                    location.reload();
                } else {
                    alert('Lỗi: ' + (result.message || 'Không thể thêm vào giỏ'));
                    if (result.errors && result.errors.length > 0) {
                        console.error('Errors:', result.errors);
                    }
                }
            } catch (error) {
                console.error('Add to cart error:', error);
                alert('Lỗi: ' + error.message);
            } finally {
                addBtn.disabled = false;
                addBtn.innerHTML = '<i class="bi bi-cart-plus"></i> Thêm vào giỏ hàng';
            }
        }
    </script>

    <style>
    /* Prescription Scanner Styles */
    .upload-area {
        border: 2px dashed #17a2b8;
        border-radius: 12px;
        padding: 40px 40px 30px;
        text-align: center;
        background: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .upload-area:hover,
    .upload-area.drag-over {
        border-color: #0d6efd;
        background: #e7f3ff;
    }

    .upload-area.drag-over {
        transform: scale(1.02);
    }

    .upload-area .upload-content {
        cursor: pointer;
    }

    #scannedMedicinesTable .form-select-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    #scannedMedicinesTable td {
        vertical-align: middle;
    }

    .product-select {
        max-width: 250px;
    }

    #prescriptionScannerModal .modal-body {
        min-height: 300px;
    }
    </style>
</body>
</html>
