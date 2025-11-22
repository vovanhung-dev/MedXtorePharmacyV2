<?php
require_once __DIR__ . '/../includes/config.php';

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    echo "Không tìm thấy đơn hàng.";
    exit();
}

$conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);

// Lấy thông tin đơn hàng + khách hàng
$stmt = $conn->prepare("
    SELECT dh.*, kh.ten_khachhang, kh.diachi, kh.sodienthoai, kh.email 
    FROM donhang dh 
    JOIN khachhang kh ON dh.khachhang_id = kh.id 
    WHERE dh.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Không tìm thấy đơn hàng.";
    exit();
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("
    SELECT cd.*, t.ten_thuoc 
    FROM chitiet_donhang cd 
    JOIN thuoc t ON cd.thuoc_id = t.id 
    WHERE cd.donhang_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format số đơn hàng
$formattedOrderId = str_pad($orderId, 8, '0', STR_PAD_LEFT);

// Sửa phần tính toán tổng tiền
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['dongia'] * $item['soluong'];
}

$shippingFee = $_SESSION['invoice_extra']['shipping_fee'] ?? 0;
$discount = $_SESSION['invoice_extra']['discount'] ?? 0;

// Tổng tiền cuối = subtotal + shipping - discount
$tongThanhToan = $subtotal + $shippingFee - $discount;

// Lấy trạng thái đơn hàng
$orderStatus = $order['trangthai'] ?? 'pending';

// Xác định màu và text theo trạng thái
$statusColors = [
    'pending' => ['color' => '#f0ad4e', 'text' => 'Đang xử lý'],
    'processing' => ['color' => '#5bc0de', 'text' => 'Đang chuẩn bị'],
    'shipping' => ['color' => '#0275d8', 'text' => 'Đang giao hàng'],
    'completed' => ['color' => '#5cb85c', 'text' => 'Hoàn thành'],
    'cancelled' => ['color' => '#d9534f', 'text' => 'Đã hủy']
];

$statusColor = $statusColors[$orderStatus]['color'] ?? '#f0ad4e';
$statusText = $statusColors[$orderStatus]['text'] ?? 'Đang xử lý';

unset($_SESSION['invoice_extra']);

// Format ngày đặt hàng
$orderDate = new DateTime($order['ngay_dat']);
$formattedDate = $orderDate->format('d/m/Y H:i');
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn #<?= $formattedOrderId ?> - MedXtore Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f5f9;
            color: #2c3e50;
            line-height: 1.6;
        }

        /* Page Layout */
        .container {
            max-width: 850px;
            margin: 40px auto;
            background: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .container:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #1a5276 0%, #2874a6 50%, #3498db 100%);
            color: white;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            transform: translate(50%, -50%);
        }

        .brand-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .logo {
            font-size: 32px;
            font-weight: bold;
            position: relative;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 10px;
            color: #a3e4d7;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        .header p {
            opacity: 0.9;
            font-size: 15px;
            margin-bottom: 5px;
        }

        .order-id {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 15px;
            font-weight: 600;
            margin-top: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            position: absolute;
            top: 30px;
            right: 30px;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            color: white;
            background-color: <?= $statusColor ?>;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }

        /* Content Section */
        .content {
            padding: 35px;
        }

        .section {
            margin-bottom: 35px;
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a5276;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ebedef;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 80px;
            height: 2px;
            background: linear-gradient(to right, #1a5276, #3498db);
        }

        /* Customer Info */
        .customer-info {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .info-group {
            flex: 1;
            min-width: 200px;
        }

        .info-label {
            font-weight: 600;
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            position: relative;
            padding-left: 22px;
        }

        .info-value i {
            position: absolute;
            left: 0;
            top: 4px;
            color: #3498db;
        }

        /* Note Box */
        .note-box {
            background-color: #f0f7fc;
            border-left: 4px solid #3498db;
            padding: 18px;
            border-radius: 6px;
            font-size: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        /* Products Table */
        .products-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 25px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        }

        .products-table th {
            background: linear-gradient(to bottom, #f5f7fa, #ebedef);
            color: #2c3e50;
            font-weight: 600;
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid #dce1e4;
            font-size: 14px;
            text-transform: uppercase;
        }

        .products-table td {
            padding: 15px;
            border-bottom: 1px solid #ebedef;
            vertical-align: middle;
        }

        .products-table tr:last-child td {
            border-bottom: none;
        }

        .products-table tr:hover {
            background-color: #f8f9fa;
        }

        .products-table .price {
            text-align: right;
            font-weight: 500;
        }

        .products-table .qty {
            text-align: center;
            font-weight: 500;
        }

        /* Total Section */
        .total-section {
            margin-top: 30px;
            margin-left: auto;
            width: 350px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 10px 0;
            border-bottom: 1px dashed #dce1e4;
            font-size: 15px;
        }

        .text-danger {
            color: #e74c3c;
        }

        .grand-total {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding: 15px 0 5px 0;
            border-top: 2px solid #2c3e50;
            font-size: 20px;
            font-weight: 700;
            color: #1a5276;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 30px;
            background: linear-gradient(to bottom, #f5f7fa, #ebedef);
            color: #2c3e50;
            font-size: 15px;
            border-top: 1px solid #ebedef;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(to right, #1a5276, #3498db, #a3e4d7);
        }

        .footer .thank-you {
            font-size: 20px;
            color: #1a5276;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .pharmacy-details {
            margin-top: 20px;
            font-size: 13px;
            line-height: 1.8;
        }

        .pharmacy-contact {
            display: flex;
            justify-content: center;
            margin-top: 15px;
            gap: 20px;
        }
        
        .contact-item {
            display: inline-flex;
            align-items: center;
        }
        
        .contact-item i {
            margin-right: 8px;
            color: #3498db;
        }

        /* Action Buttons */
        .action-buttons {
            text-align: center;
            margin: 40px 0;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 25px;
            background-color: #3498db;
            color: white;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 15px;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }

        .btn:hover {
            background-color: #1a5276;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }

        .btn i {
            margin-right: 10px;
            font-size: 18px;
        }

        .btn-home {
            background-color: #16a085;
            box-shadow: 0 4px 10px rgba(22, 160, 133, 0.3);
        }

        .btn-home:hover {
            background-color: #0e6655;
            box-shadow: 0 6px 15px rgba(22, 160, 133, 0.4);
        }

        /* QR Code */
        .qr-section {
            position: absolute;
            right: 30px;
            bottom: 30px;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
        }

        .qr-code {
            width: 80px;
            height: 80px;
            background-color: #fff;
            padding: 5px;
            border: 1px solid #dce1e4;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .container {
                margin: 20px 15px;
                border-radius: 8px;
            }

            .header {
                padding: 20px;
            }

            .status-badge {
                position: static;
                display: inline-block;
                margin-top: 10px;
            }

            .content {
                padding: 20px;
            }

            .total-section {
                width: 100%;
            }

            .qr-section {
                position: static;
                margin-top: 20px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 15px;
            }

            .btn {
                width: 100%;
            }
        }

        /* Print Styles */
        @media print {
            body {
                background-color: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .container {
                margin: 0;
                box-shadow: none;
                max-width: 100%;
                transform: none !important;
            }

            .action-buttons,
            .no-print {
                display: none !important;
            }

            .header,
            .products-table th,
            .footer {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="brand-container">
                <div class="logo"><i class="fas fa-pills"></i>MedXtore</div>
            </div>
            <h1>HÓA ĐƠN MUA THUỐC</h1>
            <p class="order-id"><i class="fas fa-receipt"></i> Đơn hàng #<?= $formattedOrderId ?></p>
            <p><i class="far fa-calendar-alt"></i> Ngày đặt: <?= $formattedDate ?></p>
            <div class="status-badge">
                <i class="fas fa-circle-check"></i> <?= $statusText ?>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Customer Information -->
            <div class="section">
                <h2 class="section-title">Thông tin khách hàng</h2>
                <div class="customer-info">
                    <div class="info-group">
                        <div class="info-label">Khách hàng</div>
                        <div class="info-value"><i class="fas fa-user"></i> <?= htmlspecialchars($order['ten_khachhang']) ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Điện thoại</div>
                        <div class="info-value"><i class="fas fa-phone"></i> <?= htmlspecialchars($order['sodienthoai']) ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Email</div>
                        <div class="info-value"><i class="fas fa-envelope"></i> <?= htmlspecialchars($order['email']) ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Địa chỉ</div>
                        <div class="info-value"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($order['diachi']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Order Notes if any -->
            <?php if (!empty($order['ghichu'])): ?>
                <div class="section">
                    <h2 class="section-title">Ghi chú đơn hàng</h2>
                    <div class="note-box">
                        <i class="fas fa-quote-left" style="color: #3498db; margin-right: 10px; opacity: 0.6;"></i>
                        <?= nl2br(htmlspecialchars($order['ghichu'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Order Items -->
            <div class="section">
                <h2 class="section-title">Chi tiết sản phẩm</h2>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th width="45%"><i class="fas fa-capsules"></i> Sản phẩm</th>
                            <th class="qty" width="15%"><i class="fas fa-cubes"></i> Số lượng</th>
                            <th class="price" width="20%"><i class="fas fa-tag"></i> Đơn giá</th>
                            <th class="price" width="20%"><i class="fas fa-calculator"></i> Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item['ten_thuoc']) ?></strong></td>
                                <td class="qty"><?= $item['soluong'] ?></td>
                                <td class="price"><?= number_format($item['dongia'], 0, ',', '.') ?>đ</td>
                                <td class="price"><?= number_format($item['dongia'] * $item['soluong'], 0, ',', '.') ?>đ</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Order Total -->
                <div class="total-section">
                    <div class="total-row">
                        <strong>Tạm tính:</strong> 
                        <span><?= number_format($subtotal, 0, ',', '.') ?>đ</span>
                    </div>

                    <div class="total-row">
                        <strong>Phí vận chuyển:</strong>
                        <?php if ($shippingFee > 0): ?>
                            <span><?= number_format($shippingFee, 0, ',', '.') ?>đ</span>
                        <?php else: ?>
                            <span><i class="fas fa-gift" style="color: #2ecc71;"></i> Miễn phí</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($discount > 0): ?>
                    <div class="total-row text-danger">
                        <strong><i class="fas fa-tags"></i> Giảm giá:</strong>
                        <span>-<?= number_format($discount, 0, ',', '.') ?>đ</span>
                    </div>
                    <?php endif; ?>

                    <div class="grand-total">
                        <strong>Tổng thanh toán:</strong>
                        <span><?= number_format($tongThanhToan, 0, ',', '.') ?>đ</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="thank-you"><i class="fas fa-heart" style="color: #e74c3c;"></i> Cảm ơn quý khách đã mua hàng tại MedXtore Pharmacy!</div>
            <p>Vui lòng kiểm tra kỹ thông tin đơn hàng. Đơn hàng của quý khách sẽ được xử lý trong thời gian sớm nhất.</p>

            <div class="pharmacy-details">
                <strong>MedXtore Pharmacy</strong><br>
                Địa chỉ: 123 Đường Thuốc, Quận Y, Thành phố Z
                
                <div class="pharmacy-contact">
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i> 1900-XXX-XXX
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i> contact@medxtore.com
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-globe"></i> www.medxtore.com
                    </div>
                </div>
            </div>
            
            <!-- QR Code -->
            <div class="qr-section no-print">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAIAQMAAAD+wSzIAAAABlBMVEX///8AAABVwtN+AAAAP0lEQVQI12NgYGD8z8DA9J+BgfU/A8P/Bgam/wwM/0AkA8N/ERJDI4jEwLAYiIGKgLgBKA8S+Q8kGRgYkiQkAIYPCxGJHAJKAAAAAElFTkSuQmCC" alt="QR Code" class="qr-code">
                <div>Quét mã để theo dõi đơn hàng</div>
            </div>
        </div>
    </div>

    <!-- Action Buttons - Won't be printed -->
    <div class="action-buttons no-print">
        <a href="/MedXtorePharmacy/pages/home.php" class="btn btn-home">
            <i class="fas fa-home"></i> Quay lại trang chủ
        </a>
        <button class="btn" onclick="window.print()">
            <i class="fas fa-print"></i> In hóa đơn
        </button>
    </div>

</body>

</html>