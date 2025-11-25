<?php
session_start();

// Check if user is logged in and has POS access
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

// Get order information from session or URL parameter
$orderId = $_GET['order_id'] ?? $_SESSION['pos_current_order'] ?? null;

if (!$orderId) {
    header("Location: /pages/pos.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/POSPaymentController.php';

// Fetch order details
$query = "SELECT d.*, k.ten as khachhang_ten, k.sodienthoai
          FROM donhang d
          LEFT JOIN khachhang k ON d.khachhang_id = k.id
          WHERE d.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "<script>alert('Không tìm thấy đơn hàng'); window.location.href='/pages/pos.php';</script>";
    exit();
}

// Fetch order items
$itemsQuery = "SELECT cd.*, t.ten as thuoc_ten, dv.ten as donvi_ten, cd.dongia, cd.soluong, cd.thanhtien
               FROM chitiet_donhang cd
               JOIN thuoc t ON cd.thuoc_id = t.id
               JOIN donvitinh dv ON cd.donvi_id = dv.id
               WHERE cd.donhang_id = ?";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$orderItems = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalAmount = (float)$order['tongtien'];
$discountAmount = (float)($order['giamgia'] ?? 0);
$subtotal = $totalAmount + $discountAmount;

require_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán tại quầy - MedXtore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .pos-checkout-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Header */
        .checkout-header {
            background: linear-gradient(135deg, #13b0c9 0%, #0891a8 100%);
            color: white;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .checkout-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }

        .order-info-badge {
            background: rgba(255,255,255,0.2);
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 16px;
        }

        /* Main Layout */
        .checkout-main {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 24px;
            padding: 32px;
        }

        /* Order Summary Section */
        .order-summary {
            background: #f8fafc;
            border-radius: 16px;
            padding: 24px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .order-summary h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1e293b;
        }

        .order-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-details h4 {
            font-size: 15px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 4px;
        }

        .item-meta {
            font-size: 13px;
            color: #64748b;
        }

        .item-price {
            text-align: right;
        }

        .item-price .price {
            font-size: 16px;
            font-weight: 700;
            color: #0891a8;
        }

        .item-price .qty {
            font-size: 13px;
            color: #64748b;
        }

        /* Total Summary */
        .total-summary {
            border-top: 2px solid #e2e8f0;
            padding-top: 16px;
            margin-top: 16px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 15px;
        }

        .summary-row.total {
            font-size: 24px;
            font-weight: 800;
            color: #0891a8;
            padding-top: 16px;
            border-top: 2px solid #0891a8;
            margin-top: 12px;
        }

        .discount {
            color: #dc2626;
        }

        /* Payment Methods Section */
        .payment-methods {
            background: white;
        }

        .payment-methods h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1e293b;
        }

        /* Payment Tabs */
        .payment-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0;
        }

        .payment-tab {
            flex: 1;
            padding: 16px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .payment-tab:hover {
            color: #0891a8;
            background: #f1f5f9;
        }

        .payment-tab.active {
            color: #0891a8;
            border-bottom-color: #0891a8;
            background: #f0fdfa;
        }

        .payment-tab i {
            font-size: 20px;
        }

        /* Payment Content */
        .payment-content {
            display: none;
        }

        .payment-content.active {
            display: block;
        }

        /* Cash Payment */
        .quick-amounts {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .amount-btn {
            padding: 20px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            color: #0891a8;
            transition: all 0.2s ease;
        }

        .amount-btn:hover {
            border-color: #0891a8;
            background: #f0fdfa;
            transform: translateY(-2px);
        }

        .amount-btn.selected {
            border-color: #0891a8;
            background: #0891a8;
            color: white;
        }

        .cash-input-group {
            margin-bottom: 24px;
        }

        .cash-input-group label {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .cash-input-group input {
            width: 100%;
            padding: 16px 20px;
            font-size: 24px;
            font-weight: 700;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            text-align: right;
            color: #0891a8;
        }

        .cash-input-group input:focus {
            outline: none;
            border-color: #0891a8;
        }

        .change-display {
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .change-display .label {
            font-size: 14px;
            color: #0f766e;
            margin-bottom: 8px;
        }

        .change-display .amount {
            font-size: 36px;
            font-weight: 800;
            color: #0d9488;
        }

        .rounding-options {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .rounding-option {
            flex: 1;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
        }

        .rounding-option input[type="checkbox"] {
            display: none;
        }

        .rounding-option:has(input:checked) {
            border-color: #0891a8;
            background: #f0fdfa;
        }

        /* Banking Payment */
        .qr-display {
            text-align: center;
            padding: 32px;
            background: #f8fafc;
            border-radius: 16px;
            margin-bottom: 24px;
        }

        .qr-code-container {
            background: white;
            padding: 24px;
            border-radius: 12px;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .qr-code-container img {
            width: 280px;
            height: 280px;
        }

        .bank-info {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .bank-info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .bank-info-row:last-child {
            border-bottom: none;
        }

        .bank-info-row .label {
            color: #64748b;
            font-size: 14px;
        }

        .bank-info-row .value {
            font-weight: 600;
            color: #1e293b;
            font-size: 15px;
        }

        .confirm-checkbox {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        .confirm-checkbox input {
            width: 24px;
            height: 24px;
            cursor: pointer;
        }

        .confirm-checkbox label {
            font-size: 15px;
            font-weight: 600;
            color: #92400e;
            cursor: pointer;
            margin: 0;
        }

        /* MoMo Payment */
        .momo-qr {
            text-align: center;
            padding: 32px;
        }

        .momo-status {
            background: #fef3c7;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-top: 24px;
        }

        .momo-status .icon {
            font-size: 48px;
            color: #fbbf24;
            margin-bottom: 12px;
        }

        .momo-status .message {
            font-size: 16px;
            color: #92400e;
            font-weight: 600;
        }

        .check-payment-btn {
            width: 100%;
            padding: 16px;
            background: #d946ef;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 16px;
            transition: all 0.3s ease;
        }

        .check-payment-btn:hover {
            background: #c026d3;
            transform: translateY(-2px);
        }

        /* Split Payment */
        .split-payment-section {
            display: grid;
            gap: 24px;
        }

        .payment-split-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
        }

        .payment-split-item h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
        }

        .split-input-group {
            margin-bottom: 16px;
        }

        .split-input-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 8px;
        }

        .split-input-group input,
        .split-input-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
        }

        .split-input-group input:focus,
        .split-input-group select:focus {
            outline: none;
            border-color: #0891a8;
        }

        .remaining-amount {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .remaining-amount .label {
            font-size: 14px;
            color: #92400e;
            margin-bottom: 8px;
        }

        .remaining-amount .amount {
            font-size: 32px;
            font-weight: 800;
            color: #b45309;
        }

        /* Action Buttons */
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 16px;
            margin-top: 32px;
            padding-top: 32px;
            border-top: 2px solid #e2e8f0;
        }

        .btn-back {
            padding: 18px 32px;
            background: #f1f5f9;
            color: #64748b;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-confirm {
            padding: 18px 32px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-confirm:hover:not(:disabled) {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .btn-confirm:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* Loading State */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-content {
            background: white;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
        }

        .spinner {
            width: 64px;
            height: 64px;
            border: 4px solid #e2e8f0;
            border-top-color: #0891a8;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 992px) {
            .checkout-main {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .quick-amounts {
                grid-template-columns: repeat(2, 1fr);
            }

            .payment-tabs {
                flex-wrap: wrap;
            }

            .payment-tab {
                flex: 1 1 calc(50% - 6px);
            }
        }
    </style>
</head>
<body>
    <div class="pos-checkout-container">
        <!-- Header -->
        <div class="checkout-header">
            <div>
                <h1><i class="fas fa-cash-register"></i> Thanh toán tại quầy</h1>
            </div>
            <div class="order-info-badge">
                Đơn hàng #<?= $orderId ?>
                <?php if ($order['khachhang_ten']): ?>
                    | <?= htmlspecialchars($order['khachhang_ten']) ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="checkout-main">
            <!-- Order Summary -->
            <div class="order-summary">
                <h2><i class="fas fa-file-invoice"></i> Chi tiết đơn hàng</h2>

                <div class="order-items">
                    <?php foreach ($orderItems as $item): ?>
                    <div class="order-item">
                        <div class="item-details">
                            <h4><?= htmlspecialchars($item['thuoc_ten']) ?></h4>
                            <div class="item-meta">
                                <?= $item['soluong'] ?> x <?= htmlspecialchars($item['donvi_ten']) ?>
                                @ <?= number_format($item['dongia'], 0, ',', '.') ?>đ
                            </div>
                        </div>
                        <div class="item-price">
                            <div class="price"><?= number_format($item['thanhtien'], 0, ',', '.') ?>đ</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="total-summary">
                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span><?= number_format($subtotal, 0, ',', '.') ?>đ</span>
                    </div>
                    <?php if ($discountAmount > 0): ?>
                    <div class="summary-row discount">
                        <span>Giảm giá:</span>
                        <span>-<?= number_format($discountAmount, 0, ',', '.') ?>đ</span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row total">
                        <span>TỔNG THANH TOÁN:</span>
                        <span id="total-amount"><?= number_format($totalAmount, 0, ',', '.') ?>đ</span>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="payment-methods">
                <h2>Chọn phương thức thanh toán</h2>

                <!-- Payment Tabs -->
                <div class="payment-tabs">
                    <button class="payment-tab active" data-tab="cash">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Tiền mặt</span>
                    </button>
                    <button class="payment-tab" data-tab="banking">
                        <i class="fas fa-university"></i>
                        <span>Chuyển khoản</span>
                    </button>
                    <button class="payment-tab" data-tab="momo">
                        <i class="fas fa-wallet"></i>
                        <span>MoMo</span>
                    </button>
                    <button class="payment-tab" data-tab="split">
                        <i class="fas fa-layer-group"></i>
                        <span>Kết hợp</span>
                    </button>
                </div>

                <!-- Cash Payment Content -->
                <div class="payment-content active" id="cash-content">
                    <div class="quick-amounts">
                        <button class="amount-btn" data-amount="20000">20.000đ</button>
                        <button class="amount-btn" data-amount="50000">50.000đ</button>
                        <button class="amount-btn" data-amount="100000">100.000đ</button>
                        <button class="amount-btn" data-amount="200000">200.000đ</button>
                        <button class="amount-btn" data-amount="500000">500.000đ</button>
                        <button class="amount-btn" data-amount="1000000">1.000.000đ</button>
                    </div>

                    <div class="cash-input-group">
                        <label><i class="fas fa-hand-holding-usd"></i> Tiền khách đưa</label>
                        <input type="number" id="cash-received" placeholder="0" value="<?= $totalAmount ?>">
                    </div>

                    <div class="change-display">
                        <div class="label">Tiền thối lại:</div>
                        <div class="amount" id="change-amount">0đ</div>
                    </div>

                    <div class="rounding-options">
                        <label class="rounding-option">
                            <input type="checkbox" name="rounding" value="500">
                            <span>Làm tròn 500đ</span>
                        </label>
                        <label class="rounding-option">
                            <input type="checkbox" name="rounding" value="1000">
                            <span>Làm tròn 1.000đ</span>
                        </label>
                    </div>
                </div>

                <!-- Banking Payment Content -->
                <div class="payment-content" id="banking-content">
                    <div class="qr-display">
                        <div class="qr-code-container">
                            <img id="banking-qr" src="" alt="QR Code chuyển khoản">
                        </div>
                        <p style="margin-top: 16px; color: #64748b;">Quét mã QR để chuyển khoản</p>
                    </div>

                    <div class="bank-info">
                        <div class="bank-info-row">
                            <span class="label">Ngân hàng:</span>
                            <span class="value">Vietcombank (VCB)</span>
                        </div>
                        <div class="bank-info-row">
                            <span class="label">Số tài khoản:</span>
                            <span class="value">1234567890</span>
                        </div>
                        <div class="bank-info-row">
                            <span class="label">Chủ tài khoản:</span>
                            <span class="value">MEDXTORE PHARMACY</span>
                        </div>
                        <div class="bank-info-row">
                            <span class="label">Số tiền:</span>
                            <span class="value" style="color: #0891a8; font-weight: 800;"><?= number_format($totalAmount, 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="bank-info-row">
                            <span class="label">Nội dung:</span>
                            <span class="value" id="banking-ref">DH<?= $orderId ?></span>
                        </div>
                    </div>

                    <label class="confirm-checkbox">
                        <input type="checkbox" id="banking-confirmed">
                        <label for="banking-confirmed">
                            <i class="fas fa-check-circle"></i> Đã nhận được tiền chuyển khoản
                        </label>
                    </label>
                </div>

                <!-- MoMo Payment Content -->
                <div class="payment-content" id="momo-content">
                    <div class="momo-qr">
                        <div class="qr-code-container">
                            <img id="momo-qr" src="" alt="QR Code MoMo" style="display: none;">
                            <div class="spinner" id="momo-loading"></div>
                        </div>
                        <p style="margin-top: 16px; color: #64748b;">Quét mã QR bằng ứng dụng MoMo</p>
                    </div>

                    <div class="momo-status">
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <div class="message" id="momo-status-text">Đang chờ thanh toán...</div>
                    </div>

                    <button class="check-payment-btn" id="check-momo-payment">
                        <i class="fas fa-sync-alt"></i> Kiểm tra thanh toán
                    </button>
                </div>

                <!-- Split Payment Content -->
                <div class="payment-content" id="split-content">
                    <div class="split-payment-section">
                        <div class="payment-split-item">
                            <h3><i class="fas fa-money-bill-wave"></i> Phương thức 1: Tiền mặt</h3>
                            <div class="split-input-group">
                                <label>Số tiền thanh toán bằng tiền mặt:</label>
                                <input type="number" id="split-cash-amount" placeholder="0" value="0">
                            </div>
                        </div>

                        <div class="payment-split-item">
                            <h3><i class="fas fa-credit-card"></i> Phương thức 2</h3>
                            <div class="split-input-group">
                                <label>Chọn phương thức:</label>
                                <select id="split-second-method">
                                    <option value="banking">Chuyển khoản</option>
                                    <option value="momo">MoMo</option>
                                </select>
                            </div>
                            <div class="split-input-group">
                                <label>Số tiền:</label>
                                <input type="number" id="split-second-amount" placeholder="0" readonly>
                            </div>
                        </div>

                        <div class="remaining-amount">
                            <div class="label">Số tiền còn lại:</div>
                            <div class="amount" id="split-remaining"><?= number_format($totalAmount, 0, ',', '.') ?>đ</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn-back" onclick="window.location.href='pos.php'">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </button>
                    <button class="btn-confirm" id="confirm-payment-btn">
                        <i class="fas fa-check-circle"></i> Xác nhận thanh toán
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <h3>Đang xử lý thanh toán...</h3>
            <p>Vui lòng đợi trong giây lát</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const orderId = <?= $orderId ?>;
        const totalAmount = <?= $totalAmount ?>;
        let currentPaymentMethod = 'cash';
        let momoTransactionId = null;

        $(document).ready(function() {
            // Initialize
            initializePayment();

            // Payment tab switching
            $('.payment-tab').click(function() {
                const tab = $(this).data('tab');
                switchPaymentTab(tab);
            });

            // Cash payment - Quick amounts
            $('.amount-btn').click(function() {
                const amount = parseInt($(this).data('amount'));
                $('.amount-btn').removeClass('selected');
                $(this).addClass('selected');

                // Calculate how many bills needed
                let cashReceived = 0;
                let remaining = totalAmount;
                while (remaining > 0) {
                    cashReceived += amount;
                    remaining -= amount;
                }

                $('#cash-received').val(cashReceived);
                calculateChange();
            });

            // Cash received input
            $('#cash-received').on('input', function() {
                calculateChange();
            });

            // Rounding options
            $('input[name="rounding"]').change(function() {
                if (this.checked) {
                    $('input[name="rounding"]').not(this).prop('checked', false);
                }
                calculateChange();
            });

            // Banking confirmation
            $('#banking-confirmed').change(function() {
                updateConfirmButton();
            });

            // Split payment calculations
            $('#split-cash-amount').on('input', function() {
                calculateSplitPayment();
            });

            // Confirm payment button
            $('#confirm-payment-btn').click(function() {
                confirmPayment();
            });

            // Check MoMo payment
            $('#check-momo-payment').click(function() {
                checkMomoPayment();
            });
        });

        function initializePayment() {
            // Generate banking QR code
            generateBankingQR();

            // Calculate initial change
            calculateChange();

            // Update confirm button state
            updateConfirmButton();
        }

        function switchPaymentTab(tab) {
            currentPaymentMethod = tab;

            // Update tabs
            $('.payment-tab').removeClass('active');
            $(`.payment-tab[data-tab="${tab}"]`).addClass('active');

            // Update content
            $('.payment-content').removeClass('active');
            $(`#${tab}-content`).addClass('active');

            // Initialize tab-specific features
            if (tab === 'momo' && !momoTransactionId) {
                initializeMomoPayment();
            }

            updateConfirmButton();
        }

        function calculateChange() {
            const cashReceived = parseFloat($('#cash-received').val()) || 0;
            let change = cashReceived - totalAmount;

            // Apply rounding
            const roundingValue = parseInt($('input[name="rounding"]:checked').val()) || 0;
            if (roundingValue > 0 && change > 0) {
                change = Math.floor(change / roundingValue) * roundingValue;
            }

            $('#change-amount').text(formatMoney(Math.max(0, change)));
            updateConfirmButton();
        }

        function generateBankingQR() {
            const bankInfo = {
                bank_id: 'VCB',
                account_no: '1234567890',
                amount: totalAmount,
                description: 'DH' + orderId
            };

            const qrUrl = `https://img.vietqr.io/image/${bankInfo.bank_id}-${bankInfo.account_no}-compact.jpg?amount=${bankInfo.amount}&addInfo=${encodeURIComponent(bankInfo.description)}`;
            $('#banking-qr').attr('src', qrUrl);
        }

        function initializeMomoPayment() {
            $('#momo-loading').show();
            $('#momo-qr').hide();

            $.ajax({
                url: '/controllers/POSPaymentController.php',
                method: 'POST',
                data: {
                    action: 'momo_payment',
                    order_id: orderId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        momoTransactionId = response.data.transaction_id;

                        if (response.data.momo_qr_code) {
                            $('#momo-qr').attr('src', response.data.momo_qr_code).show();
                            $('#momo-loading').hide();
                        } else if (response.data.momo_payment_url) {
                            // If only payment URL is available, show it
                            $('#momo-status-text').html(
                                `<a href="${response.data.momo_payment_url}" target="_blank" style="color: #d946ef;">` +
                                `<i class="fas fa-external-link-alt"></i> Mở ứng dụng MoMo để thanh toán</a>`
                            );
                            $('#momo-loading').hide();
                        }
                    } else {
                        $('#momo-status-text').html(`<i class="fas fa-exclamation-triangle"></i> ${response.message}`);
                        $('#momo-loading').hide();
                    }
                },
                error: function() {
                    $('#momo-status-text').html('<i class="fas fa-times-circle"></i> Không thể kết nối đến MoMo');
                    $('#momo-loading').hide();
                }
            });
        }

        function checkMomoPayment() {
            // This would check with backend if MoMo payment was successful
            // For now, just show a message
            alert('Chức năng kiểm tra thanh toán MoMo đang được phát triển');
        }

        function calculateSplitPayment() {
            const cashAmount = parseFloat($('#split-cash-amount').val()) || 0;
            const secondAmount = totalAmount - cashAmount;

            $('#split-second-amount').val(secondAmount);

            const remaining = totalAmount - cashAmount - secondAmount;
            $('#split-remaining').text(formatMoney(Math.max(0, remaining)));

            updateConfirmButton();
        }

        function updateConfirmButton() {
            let isValid = false;

            switch(currentPaymentMethod) {
                case 'cash':
                    const cashReceived = parseFloat($('#cash-received').val()) || 0;
                    isValid = cashReceived >= totalAmount;
                    break;

                case 'banking':
                    isValid = $('#banking-confirmed').is(':checked');
                    break;

                case 'momo':
                    isValid = momoTransactionId !== null;
                    break;

                case 'split':
                    const splitCash = parseFloat($('#split-cash-amount').val()) || 0;
                    const splitSecond = parseFloat($('#split-second-amount').val()) || 0;
                    isValid = Math.abs((splitCash + splitSecond) - totalAmount) < 0.01;
                    break;
            }

            $('#confirm-payment-btn').prop('disabled', !isValid);
        }

        function confirmPayment() {
            const btn = $('#confirm-payment-btn');
            if (btn.prop('disabled')) return;

            $('#loading-overlay').addClass('show');

            let paymentData = {
                action: '',
                order_id: orderId
            };

            switch(currentPaymentMethod) {
                case 'cash':
                    paymentData.action = 'cash_payment';
                    paymentData.cash_received = parseFloat($('#cash-received').val());
                    break;

                case 'banking':
                    paymentData.action = 'banking_payment';
                    paymentData.bank_info = JSON.stringify({
                        confirmed: $('#banking-confirmed').is(':checked')
                    });
                    break;

                case 'momo':
                    // For MoMo, we need to confirm the existing transaction
                    paymentData.action = 'confirm_payment';
                    paymentData.transaction_id = momoTransactionId;
                    break;

                case 'split':
                    paymentData.action = 'split_payment';
                    const splits = [
                        {
                            payment_method: 'cash',
                            amount: parseFloat($('#split-cash-amount').val())
                        },
                        {
                            payment_method: $('#split-second-method').val(),
                            amount: parseFloat($('#split-second-amount').val())
                        }
                    ];
                    paymentData.splits = JSON.stringify(splits);
                    break;
            }

            $.ajax({
                url: '/controllers/POSPaymentController.php',
                method: 'POST',
                data: paymentData,
                dataType: 'json',
                success: function(response) {
                    $('#loading-overlay').removeClass('show');

                    if (response.success) {
                        // Show success message
                        alert('Thanh toán thành công!');

                        // Redirect to invoice page
                        window.location.href = `/pages/invoice.php?order_id=${orderId}`;
                    } else {
                        alert('Lỗi: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#loading-overlay').removeClass('show');
                    alert('Lỗi kết nối: ' + error);
                }
            });
        }

        function formatMoney(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        }
    </script>
</body>
</html>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
