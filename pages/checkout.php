<?php
session_start();

// ✅ Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/pages/checkout.php';
    header("Location: /pages/login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$cart = $_SESSION['carts'][$userId] ?? [];

// ✅ Nếu giỏ hàng trống thì quay lại
if (empty($cart)) {
    header("Location: /pages/cart.php");
    exit();
}

// ✅ Tính tổng tiền đơn hàng
$tongTien = array_sum(array_column($cart, 'tongtien'));
$phiVanChuyen = 0;
$tongThanhToan = $tongTien + $phiVanChuyen;

// ✅ Xử lý voucher nếu có
$appliedVoucher = $_SESSION['applied_voucher'] ?? null;
$voucherId = $appliedVoucher['voucher_id'] ?? null; 
$discountPercent = $appliedVoucher['discount_percent'] ?? 0;
$discountAmount = round($tongTien * ($discountPercent / 100));

// ✅ Tổng sau giảm giá
$tongThanhToan -= $discountAmount;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
    .checkout-section {
        min-height: 100vh;
        padding-bottom: 120px;
        /* Giảm padding ở dưới */
        background-color: #f8f9fa;
        position: relative;
        z-index: 1;
    }

    .checkout-header {
        background: linear-gradient(135deg, #13b0c9 0%, #3498db 100%);
        color: white;
        padding: 1.5rem 0;
        /* Giảm padding */
        border-radius: 0 0 20px 20px;
        margin-bottom: 1.5rem;
        /* Giảm margin */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }

    .checkout-header::after {
        content: "";
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background: url("/assets/images/pattern.svg") repeat;
        opacity: 0.1;
    }

    .checkout-header h2 {
        position: relative;
        z-index: 2;
    }

    .checkout-container {
        background-color: white;
        border-radius: 15px;
        padding: 1.5rem;
        /* Giảm padding */
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        height: 100%;
    }

    .checkout-title {
        position: relative;
        display: inline-block;
    }

    .checkout-title::after {
        content: "";
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 3px;
        background: #3498db;
        border-radius: 3px;
    }

    .section-title {
        position: relative;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
        /* Giảm margin */
        font-weight: 600;
        color: #2c3e50;
    }

    .section-title::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 3px;
        background: #3498db;
        border-radius: 3px;
    }

    .order-summary {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 20px;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px dashed #dee2e6;
    }

    .summary-product {
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
        /* Giảm margin */
        padding-bottom: 0.75rem;
        /* Giảm padding */
        border-bottom: 1px solid #eee;
    }

    .summary-product:last-child {
        border-bottom: none;
    }

    .summary-product-img {
        width: 50px;
        /* Giảm kích thước */
        height: 50px;
        /* Giảm kích thước */
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #eee;
        padding: 2px;
        background-color: white;
        transition: transform 0.3s ease;
    }

    .summary-product:hover .summary-product-img {
        transform: scale(1.05);
    }

    .summary-product-info {
        margin-left: 1rem;
        flex-grow: 1;
    }

    .total-price {
        font-size: 1.5rem;
        font-weight: bold;
        color: #3498db;
    }

    .checkout-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        /* Giảm margin */
        position: relative;
    }

    .checkout-steps::before {
        content: "";
        position: absolute;
        top: 25px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #e9ecef;
        z-index: 1;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
    }

    .step-number {
        width: 40px;
        /* Giảm kích thước */
        height: 40px;
        /* Giảm kích thước */
        border-radius: 50%;
        background-color: white;
        border: 2px solid #3498db;
        color: #3498db;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1rem;
        /* Giảm font-size */
        transition: all 0.3s ease;
        position: relative;
        margin-bottom: 0.25rem;
        /* Giảm margin */
    }

    .step.active .step-number {
        background-color: #3498db;
        color: white;
        transform: scale(1.1);
        box-shadow: 0 0 15px rgba(52, 152, 219, 0.5);
    }

    .step.completed .step-number {
        background-color: #2ecc71;
        border-color: #2ecc71;
        color: white;
    }

    .step.completed .step-number::after {
        content: "✓";
        position: absolute;
        font-weight: bold;
    }

    .step-text {
        font-size: 0.8rem;
        /* Giảm font-size */
        color: #7f8c8d;
        font-weight: 500;
        transition: all 0.3s ease;
        text-align: center;
    }

    .step.active .step-text {
        color: #3498db;
        font-weight: 600;
    }

    .step.completed .step-text {
        color: #2ecc71;
        font-weight: 600;
    }

    .form-floating {
        margin-bottom: 1rem;
        /* Giảm margin */
    }

    .form-floating>label {
        padding-left: 1rem;
    }

    .form-control:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
    }

    .payment-method {
        border: 1px solid #dee2e6;
        /* Giảm border */
        border-radius: 10px;
        padding: 1rem;
        /* Giảm padding */
        margin-bottom: 1rem;
        /* Giảm margin */
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .payment-method:hover {
        border-color: #bdc3c7;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .payment-method.selected {
        border-color: #3498db;
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.1);
    }

    .payment-method.selected::before {
        content: "✓";
        position: absolute;
        top: -10px;
        right: -10px;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        background-color: #3498db;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.8rem;
    }

    .payment-logo {
        height: 30px;
        /* Giảm kích thước */
        object-fit: contain;
        margin-right: 0.75rem;
        /* Giảm margin */
        filter: grayscale(100%);
        transition: all 0.3s ease;
    }

    .payment-method:hover .payment-logo,
    .payment-method.selected .payment-logo {
        filter: grayscale(0%);
    }

    .btn-action {
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-back {
        background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
        border: none;
        color: white;
    }

    .btn-back:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(127, 140, 141, 0.3);
        color: white;
    }

    .btn-place-order {
        background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        /* Giảm padding */
        font-size: 1.1rem;
    }

    .btn-place-order:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(39, 174, 96, 0.3);
        color: white;
    }

    .shipping-option {
        border: 1px solid #dee2e6;
        /* Giảm border */
        border-radius: 10px;
        padding: 0.75rem;
        /* Giảm padding */
        margin-bottom: 0.75rem;
        /* Giảm margin */
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .shipping-option:hover {
        border-color: #bdc3c7;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }

    .shipping-option.selected {
        border-color: #3498db;
        box-shadow: 0 3px 10px rgba(52, 152, 219, 0.1);
    }

    .form-check-input:checked {
        background-color: #3498db;
        border-color: #3498db;
    }

    /* Animations */
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .bounce-in {
        animation: bounceIn 0.5s ease;
    }

    @keyframes bounceIn {
        0% {
            transform: scale(0.8);
            opacity: 0;
        }

        70% {
            transform: scale(1.1);
            opacity: 1;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .stagger-animation {
        opacity: 0;
        animation: staggerFadeIn 0.5s ease forwards;
    }

    @keyframes staggerFadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .promo-code {
        position: relative;
        overflow: hidden;
        border-radius: 10px;
        border: 1px dashed #3498db;
        padding: 0.75rem;
        /* Giảm padding */
        margin-bottom: 1rem;
        /* Giảm margin */
        background-color: rgba(52, 152, 219, 0.05);
    }

    .promo-code::before {
        content: "";
        position: absolute;
        top: -10px;
        right: -10px;
        width: 40px;
        height: 40px;
        background-color: #3498db;
        opacity: 0.1;
        border-radius: 50%;
    }

    .promo-btn {
        background-color: #3498db;
        color: white;
        border: none;
        border-radius: 0 5px 5px 0;
        transition: all 0.3s ease;
    }

    .promo-btn:hover {
        background-color: #2980b9;
    }

    .secure-checkout {
        padding: 0.75rem;
        /* Giảm padding */
        margin-top: 0.75rem;
        /* Giảm margin */
        border-radius: 10px;
        background-color: rgba(46, 204, 113, 0.05);
        border: 1px solid rgba(46, 204, 113, 0.2);
    }

    .secure-icon {
        color: #2ecc71;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    /* Tab styling */
    .checkout-tabs {
        display: flex;
        margin-bottom: 1rem;
        border-bottom: 1px solid #dee2e6;
    }

    .checkout-tab {
        padding: 0.5rem 1rem;
        margin-right: 0.5rem;
        border: 1px solid #dee2e6;
        border-bottom: none;
        border-radius: 5px 5px 0 0;
        cursor: pointer;
        background-color: #f8f9fa;
    }

    .checkout-tab.active {
        background-color: #3498db;
        color: white;
        border-color: #3498db;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    /* Thêm kiểu cho table hiển thị thông tin đơn hàng */
    .order-info-table {
        width: 100%;
        margin-bottom: 1rem;
    }

    .order-info-table td {
        padding: 0.5rem;
    }

    .order-info-table td:first-child {
        font-weight: 600;
        width: 30%;
    }
</style>

<main class="checkout-section">
    <div class="checkout-header position-relative">
        <div class="container">
            <h2 class="text-center checkout-title fw-bold">
                <i class="bi bi-credit-card-fill me-2"></i>Thanh toán
            </h2>
        </div>
    </div>

    <div class="container fade-in">
        <!-- Checkout Steps -->
        <div class="checkout-steps mb-3">
            <div class="step completed">
                <div class="step-number">1</div>
                <div class="step-text">Giỏ hàng</div>
            </div>
            <div class="step active">
                <div class="step-number">2</div>
                <div class="step-text">Thanh toán</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-text">Xác nhận</div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-text">Hoàn tất</div>
            </div>
        </div>

        <form action="/controllers/CheckoutController.php" method="POST">
            <div class="row">
                <!-- Phần thông tin thanh toán -->
                <div class="col-lg-8">
                    <div class="checkout-container mb-4">
                        <!-- Tabs cho phần thông tin -->
                        <div class="checkout-tabs">
                            <div class="checkout-tab active" data-tab="shipping-info">
                                <i class="bi bi-person-fill me-1"></i>Thông tin giao hàng
                            </div>
                            <div class="checkout-tab" data-tab="shipping-method">
                                <i class="bi bi-truck me-1"></i>Phương thức vận chuyển
                            </div>
                            <div class="checkout-tab" data-tab="payment-method">
                                <i class="bi bi-wallet2 me-1"></i>Phương thức thanh toán
                            </div>
                        </div>

                        <!-- Nội dung tab thông tin giao hàng -->
                        <div class="tab-content active" id="shipping-info-content">
                            <h4 class="section-title">
                                <i class="bi bi-person-fill me-2"></i>Thông tin giao hàng
                            </h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="fullName" name="fullName" placeholder="Họ và tên" required value="<?php echo isset($_SESSION['user_fullname']) ? htmlspecialchars($_SESSION['user_fullname']) : ''; ?>">
                                        <label for="fullName">Họ và tên</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>">
                                        <label for="email">Email</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="Số điện thoại" required pattern="[0-9]{10}" title="Vui lòng nhập số điện thoại 10 chữ số">
                                        <label for="phone">Số điện thoại</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="address" name="address" placeholder="Địa chỉ" required>
                                        <label for="address">Địa chỉ đầy đủ</label>
                                    </div>
                                </div>

                                <div class="col-12 mt-2">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="notes" name="notes" placeholder="Ghi chú" style="height: 80px"></textarea>
                                        <label for="notes">Ghi chú (không bắt buộc)</label>
                                    </div>
                                </div>
                            </div>

                            <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger mt-3">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                            <?php endif; ?>

                            <div class="mt-3 text-end">
                                <button type="button" class="btn btn-primary next-tab" data-next="shipping-method">
                                    Tiếp tục <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Nội dung tab phương thức vận chuyển -->
                        <div class="tab-content" id="shipping-method-content">
                            <h4 class="section-title">
                                <i class="bi bi-truck me-2"></i>Phương thức vận chuyển
                            </h4>
                            <div class="shipping-option selected" data-shipping="standard">
                                <div class="d-flex align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shipping_method" id="standardShipping" value="standard" checked>
                                    </div>
                                    <div class="ms-3">
                                        <label class="form-check-label fw-bold" for="standardShipping">
                                            <i class="bi bi-truck me-2"></i>Giao hàng tiêu chuẩn
                                        </label>
                                        <p class="text-muted mb-0 small">Nhận hàng trong 3-5 ngày làm việc</p>
                                    </div>
                                    <div class="ms-auto">
                                        <span class="fw-bold text-success">Miễn phí</span>
                                    </div>
                                </div>
                            </div>

                            <div class="shipping-option" data-shipping="express">
                                <div class="d-flex align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shipping_method" id="expressShipping" value="express">
                                    </div>
                                    <div class="ms-3">
                                        <label class="form-check-label fw-bold" for="expressShipping">
                                            <i class="bi bi-lightning-fill me-2 text-warning"></i>Giao hàng nhanh
                                        </label>
                                        <p class="text-muted mb-0 small">Nhận hàng trong 1-2 ngày làm việc</p>
                                    </div>
                                    <div class="ms-auto">
                                        <span class="fw-bold">30.000đ</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary prev-tab" data-prev="shipping-info">
                                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                                </button>
                                <button type="button" class="btn btn-primary next-tab" data-next="payment-method">
                                    Tiếp tục <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Nội dung tab phương thức thanh toán -->
                        <div class="tab-content" id="payment-method-content">
                            <h4 class="section-title">
                                <i class="bi bi-wallet2 me-2"></i>Phương thức thanh toán
                            </h4>
                            <div class="payment-method selected" data-payment="cod">
                                <div class="d-flex align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="codPayment" value="cod" checked>
                                    </div>
                                    <div class="ms-3">
                                        <label class="form-check-label fw-bold" for="codPayment">
                                            <i class="bi bi-cash-coin me-2"></i>Thanh toán khi nhận hàng (COD)
                                        </label>
                                        <p class="text-muted mb-0 small">Quý khách sẽ thanh toán khi nhận được hàng</p>
                                    </div>
                                </div>
                            </div>

                            <div class="payment-method mt-2" data-payment="banking">
                                <div class="d-flex align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="bankingPayment" value="banking">
                                    </div>
                                    <div class="ms-3">
                                        <label class="form-check-label fw-bold" for="bankingPayment">
                                            <i class="bi bi-bank me-2"></i>Chuyển khoản ngân hàng
                                        </label>
                                        <p class="text-muted mb-0 small">Quý khách vui lòng chuyển khoản trước khi đơn hàng được xử lý</p>
                                    </div>
                                </div>

                                <div class="mt-2 banking-details" style="display: none;">
                                    <div class="alert alert-info">
                                        <p class="mb-1"><strong>Thông tin tài khoản:</strong></p>
                                        <p class="mb-1">Ngân hàng: Vietcombank</p>
                                        <p class="mb-1">Số tài khoản: 1234567890</p>
                                        <p class="mb-1">Chủ tài khoản: MedXtore Pharmacy</p>
                                        <p class="mb-0">Nội dung: [Họ tên] thanh toán đơn hàng</p>
                                    </div>
                                </div>
                            </div>

                            <div class="payment-method mt-2" data-payment="credit">
                                <div class="d-flex align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="creditPayment" value="credit">
                                    </div>
                                    <div class="ms-3 d-flex align-items-center">
                                        <label class="form-check-label fw-bold me-3" for="creditPayment">
                                            <i class="bi bi-credit-card me-2"></i>Thẻ tín dụng/Ghi nợ
                                        </label>
                                        <div>
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/960px-Visa_Inc._logo.svg.png" alt="Visa" class="payment-logo">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/960px-Mastercard-logo.svg.png" alt="Mastercard" class="payment-logo">
                                            <img src="https://banner2.cleanpng.com/20180816/tk/39d7d0c8923c377e276836db08230277.webp" alt="JCB" class="payment-logo">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 credit-details" style="display: none;">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-floating mb-2">
                                                <input type="text" class="form-control" id="cardNumber" placeholder="Số thẻ">
                                                <label for="cardNumber">Số thẻ</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating mb-2">
                                                <input type="text" class="form-control" id="cardName" placeholder="Tên chủ thẻ">
                                                <label for="cardName">Tên chủ thẻ</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating mb-2">
                                                <input type="text" class="form-control" id="cardExpiry" placeholder="MM/YY">
                                                <label for="cardExpiry">Ngày hết hạn</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating mb-2">
                                                <input type="text" class="form-control" id="cardCVV" placeholder="CVV">
                                                <label for="cardCVV">CVV</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="payment-method mt-2" data-payment="momo">
                                <div class="d-flex align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="momoPayment" value="momo">
                                    </div>
                                    <div class="ms-3">
                                        <label class="form-check-label fw-bold" for="momoPayment">
                                            <img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png?20201011055544" alt="MoMo" class="payment-logo">
                                            Thanh toán qua MoMo
                                        </label>
                                        <p class="text-muted mb-0 small">Thanh toán nhanh chóng và an toàn qua ví MoMo</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="termsCheckbox" required>
                                    <label class="form-check-label" for="termsCheckbox">
                                        Tôi đã đọc và đồng ý với <a href="#" class="text-decoration-none">điều khoản và điều kiện</a> của MedXtore Pharmacy
                                    </label>
                                </div>
                            </div>

                            <div class="secure-checkout mt-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-shield-check secure-icon fs-3 me-2"></i>
                                    <div>
                                        <h6 class="mb-0">Thanh toán an toàn & bảo mật</h6>
                                        <p class="mb-0 small text-muted">Mọi thông tin thanh toán của bạn được bảo vệ an toàn</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary prev-tab" data-prev="shipping-method">
                                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                                </button>
                                <button type="submit" class="btn btn-place-order">
                                    <i class="bi bi-check-circle me-2"></i> Đặt hàng
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phần tóm tắt đơn hàng -->
                <div class="col-lg-4">
                    <div class="checkout-container mb-4">
                        <h4 class="section-title">
                            <i class="bi bi-cart-check me-2"></i>Tóm tắt đơn hàng
                        </h4>

                        <div class="order-summary">
                            <div class="summary-products">
                                <?php foreach ($cart as $item) : ?>
                                    <div class="summary-product">
                                        <img src="/assets/images/product-images/<?php echo htmlspecialchars($item['hinhanh']); ?>"
                                            alt="<?php echo htmlspecialchars($item['ten_thuoc']); ?>"
                                            class="summary-product-img">

                                        <div class="summary-product-info">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($item['ten_thuoc']); ?></h6>
                                            <p class="mb-0 small text-muted">
                                                SL: <?php echo $item['soluong']; ?> x <?php echo number_format($item['gia'], 0, ',', '.'); ?>đ
                                            </p>
                                        </div>

                                        <div class="text-end">
                                            <p class="mb-0 fw-bold"><?php echo number_format($item['tongtien'], 0, ',', '.'); ?>đ</p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="promo-code mt-3">
                                <h6 class="mb-2"><i class="bi bi-ticket-perforated me-2"></i>Mã giảm giá</h6>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Nhập mã giảm giá" id="promoCode" name="promo_code">
                                    <button class="btn promo-btn" type="button" id="applyPromo">Áp dụng</button>
                                </div>
                            </div>

                            <div class="summary-details mt-3">
                                <div class="summary-item">
                                    <span>Tạm tính</span>
                                    <span id="subTotalDisplay"><?= number_format($tongTien, 0, ',', '.') ?>đ</span>
                                </div>
                                <div class="summary-item">
                                    <span>Phí vận chuyển</span>
                                    <span id="shippingFee"><?= $phiVanChuyen > 0 ? number_format($phiVanChuyen, 0, ',', '.') . 'đ' : 'Miễn phí' ?></span>
                                </div>
                                <div class="summary-item">
                                    <span>Giảm giá</span>
                                    <span id="discountAmount"><?= $discountAmount > 0 ? number_format($discountAmount, 0, ',', '.') . 'đ' : '0đ' ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="fw-bold">Tổng thanh toán</span>
                                    <span class="total-price" id="totalPayment"><?= number_format($tongThanhToan, 0, ',', '.') ?>đ</span>
                                </div>
                            </div>
                            <!-- ✅ Thêm hidden input để gửi tổng tiền -->
                            <input type="hidden" name="total_amount" id="totalAmountInput" value="<?php echo $tongThanhToan; ?>">
                            <input type="hidden" name="shipping_fee" id="shippingFeeInput" value="<?= $phiVanChuyen ?>">
                            <input type="hidden" name="discount" id="discountInput" value="<?= $discountAmount ?>">
                            <input type="hidden" name="voucher_id" id="voucherIdInput" value="<?= $voucherId ?>">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching
        const tabs = document.querySelectorAll('.checkout-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');

                // Remove active class from all tabs and content
                document.querySelectorAll('.checkout-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                // Add active class to current tab and content
                this.classList.add('active');
                document.getElementById(`${tabId}-content`).classList.add('active');
            });
        });

        // Next tab buttons
        const nextButtons = document.querySelectorAll('.next-tab');
        nextButtons.forEach(button => {
            button.addEventListener('click', function() {
                const nextTabId = this.getAttribute('data-next');

                // Activate the next tab
                document.querySelectorAll('.checkout-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                const nextTab = document.querySelector(`.checkout-tab[data-tab="${nextTabId}"]`);
                nextTab.classList.add('active');
                document.getElementById(`${nextTabId}-content`).classList.add('active');
            });
        });

        // Previous tab buttons
        const prevButtons = document.querySelectorAll('.prev-tab');
        prevButtons.forEach(button => {
            button.addEventListener('click', function() {
                const prevTabId = this.getAttribute('data-prev');

                // Activate the previous tab
                document.querySelectorAll('.checkout-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                const prevTab = document.querySelector(`.checkout-tab[data-tab="${prevTabId}"]`);
                prevTab.classList.add('active');
                document.getElementById(`${prevTabId}-content`).classList.add('active');
            });
        });

        // Payment method selection
        const paymentMethods = document.querySelectorAll('.payment-method');
        paymentMethods.forEach(method => {
            method.addEventListener('click', function() {
                const paymentType = this.getAttribute('data-payment');
                const radioButton = this.querySelector('input[type="radio"]');

                // Remove selected class from all methods
                paymentMethods.forEach(m => m.classList.remove('selected'));

                // Add selected class to current method
                this.classList.add('selected');
                radioButton.checked = true;

                // Hide all payment details
                document.querySelectorAll('.banking-details, .credit-details, .momo-details').forEach(detail => {
                    detail.style.display = 'none';
                });

                // Show current payment details if applicable
                if (paymentType === 'banking') {
                    document.querySelector('.banking-details').style.display = 'block';
                } else if (paymentType === 'credit') {
                    document.querySelector('.credit-details').style.display = 'block';
                } else if (paymentType === 'momo') {
                    const momoDetails = document.querySelector('.momo-details');
                    momoDetails.style.display = 'block';
                    
                    // Update Momo payment information
                    const totalAmount = document.getElementById('totalAmountInput').value;
                    const orderId = Date.now(); // Generate a unique order ID
                    
                    document.getElementById('momoAmount').textContent = parseInt(totalAmount).toLocaleString('vi-VN');
                    document.getElementById('momoOrderId').textContent = orderId;
                    
                    document.getElementById('momoOrderIdInput').value = orderId;
                    document.getElementById('momoAmountInput').value = totalAmount;
                    document.getElementById('momoOrderInfo').value = `Thanh toán đơn hàng #${orderId}`;
                }
            });
        });

        // Shipping option selection
        const shippingOptions = document.querySelectorAll('.shipping-option');
        shippingOptions.forEach(option => {
            option.addEventListener('click', function() {
                const shippingType = this.getAttribute('data-shipping');
                const radioButton = this.querySelector('input[type="radio"]');

                // Remove selected class from all options
                shippingOptions.forEach(o => o.classList.remove('selected'));

                // Add selected class to current option
                this.classList.add('selected');
                radioButton.checked = true;

                // Update shipping fee
                updateShippingFee(shippingType);
            });
        });

        // Function to update shipping fee and total
        function updateShippingFee(shippingType) {
    const shippingFeeElement = document.getElementById('shippingFee');
    const totalPaymentElement = document.getElementById('totalPayment');
    const totalAmountInput = document.getElementById('totalAmountInput');
    const shippingFeeInput = document.getElementById('shippingFeeInput');
    const subTotalDisplay = document.getElementById('subTotalDisplay');
    let shippingFee = 0;

    // Lấy tạm tính ban đầu (không bao gồm phí ship)
    const subTotal = <?= $tongTien ?>;

    // Hiển thị tạm tính
    subTotalDisplay.textContent = subTotal.toLocaleString('vi-VN') + 'đ';

    // Cập nhật phí ship
    if (shippingType === 'express') {
        shippingFee = 30000;
        shippingFeeElement.textContent = '30.000đ';
    } else {
        shippingFeeElement.textContent = 'Miễn phí';
    }

    // Lấy giảm giá 
    const discountText = document.getElementById('discountAmount').textContent;
    const discount = parseInt(discountText.replace(/[^\d]/g, '')) || 0;

    // Tính tổng tiền cuối = tạm tính + phí ship - giảm giá
    const finalTotal = subTotal + shippingFee - discount;

    // Cập nhật hiển thị và inputs
    totalPaymentElement.textContent = finalTotal.toLocaleString('vi-VN') + 'đ';
    totalAmountInput.value = finalTotal;
    shippingFeeInput.value = shippingFee;

    // Debug log
    console.log({
        subTotal,
        shippingFee, 
        discount,
        finalTotal
    });
}

         // Promo code application (dùng AJAX kiểm tra từ controller)
         document.getElementById('applyPromo').addEventListener('click', function () {
    const code = document.getElementById('promoCode').value.trim();

    if (!code) {
        alert('Vui lòng nhập mã giảm giá!');
        return;
    }

    fetch('/controllers/VoucherController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            action: 'validate',
            code: code
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            const discountPercent = parseInt(data.discount_percent);
            const subTotal = <?= $tongTien ?>;
            const discount = Math.round((subTotal * discountPercent) / 100);

            document.getElementById('discountAmount').textContent = discount.toLocaleString('vi-VN') + 'đ';

            const shippingText = document.getElementById('shippingFee').textContent;
            const shippingFee = shippingText.includes('Miễn') ? 0 : parseInt(shippingText.replace(/[^\d]/g, ''));

            const newTotal = subTotal + shippingFee - discount;

            document.getElementById('totalPayment').textContent = newTotal.toLocaleString('vi-VN') + 'đ';
            document.getElementById('totalAmountInput').value = newTotal;
            document.getElementById('voucherIdInput').value = data.voucher_id;

            // ✅ Cập nhật hidden input discount
            const discountInput = document.getElementById('discountInput');
            if (discountInput) {
                discountInput.value = discount;
            }

            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error("Lỗi áp dụng mã:", error);
        alert('Có lỗi xảy ra khi kiểm tra mã giảm giá!');
    });
});


        // Xử lý form Momo
        const momoForm = document.getElementById('momoPaymentForm');
        const momoPaymentRadio = document.getElementById('momoPayment');
        
        if (momoForm && momoPaymentRadio) {
            momoPaymentRadio.addEventListener('change', function() {
                if (this.checked) {
                    const totalAmount = document.getElementById('totalAmountInput').value;
                    const orderId = Date.now(); // Tạo mã đơn hàng unique
                    
                    // Cập nhật thông tin hiển thị
                    document.getElementById('momoAmount').textContent = parseInt(totalAmount).toLocaleString('vi-VN');
                    document.getElementById('momoOrderId').textContent = orderId;
                    
                    // Cập nhật giá trị form
                    document.getElementById('momoOrderIdInput').value = orderId;
                    document.getElementById('momoAmountInput').value = totalAmount;
                    document.getElementById('momoOrderInfo').value = `Thanh toán đơn hàng #${orderId}`;
                    
                    // Hiển thị form Momo
                    document.querySelector('.momo-details').style.display = 'block';
                }
            });
        }

        // Xử lý submit form Momo
        if (momoForm) {
            momoForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Kiểm tra điều khoản
                const termsCheckbox = document.getElementById('termsCheckbox');
                if (!termsCheckbox.checked) {
                    alert('Vui lòng đồng ý với điều khoản và điều kiện trước khi thanh toán');
                    return;
                }
                
                // Submit form
                this.submit();
            });
        }

        // Xử lý form chính khi submit
        const mainForm = document.querySelector('.main-checkout-form');
        if (mainForm) {
            mainForm.addEventListener('submit', function(e) {
                // Kiểm tra nếu chọn thanh toán Momo
                const momoPayment = document.getElementById('momoPayment');
                if (momoPayment && momoPayment.checked) {
                    e.preventDefault();
                    
                    // Kiểm tra điều khoản
                    const termsCheckbox = document.getElementById('termsCheckbox');
                    if (!termsCheckbox.checked) {
                        alert('Vui lòng đồng ý với điều khoản và điều kiện trước khi thanh toán');
                        return;
                    }

                    // Submit form để tạo đơn hàng trước
                    this.submit();
                }
            });
        }

        // Apply stagger animation
        const staggerElements = document.querySelectorAll('.stagger-animation');
        staggerElements.forEach((element, index) => {
            element.style.animationDelay = `${index * 0.1}s`;
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>