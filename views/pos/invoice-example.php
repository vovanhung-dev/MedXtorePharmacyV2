<?php
/**
 * Example usage of POSInvoiceController
 * File: views/pos/invoice-example.php
 * VOVANHUNG-DEV
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/POSInvoiceController.php';

// Khởi tạo controller
$invoiceController = new POSInvoiceController();

// Lấy order_id từ URL hoặc POST
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
$action = isset($_GET['invoice_action']) ? $_GET['invoice_action'] : 'view';

if (!$orderId) {
    die("Vui lòng cung cấp order_id");
}

// Lấy dữ liệu hóa đơn
$invoiceData = $invoiceController->getInvoiceData($orderId);

if (!$invoiceData) {
    die("Không tìm thấy đơn hàng");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn #<?php echo $orderId; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-primary {
            background-color: #4285f4;
            color: white;
        }
        .btn-primary:hover {
            background-color: #357abd;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background-color: #138496;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .invoice-preview {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            background: white;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #dee2e6;
        }
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #6c757d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab.active {
            color: #4285f4;
            border-bottom-color: #4285f4;
        }
        .tab:hover {
            color: #4285f4;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .email-form {
            max-width: 500px;
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-size: 14px;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @media print {
            .container {
                box-shadow: none;
                padding: 0;
            }
            .action-buttons, .tabs {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="printInvoice('standard')">
                🖨️ In hóa đơn A4
            </button>
            <button class="btn btn-primary" onclick="printInvoice('thermal')">
                🎫 In bill nhiệt
            </button>
            <button class="btn btn-success" onclick="showEmailForm()">
                📧 Gửi Email
            </button>
            <button class="btn btn-info" onclick="exportPDF()">
                📄 Export PDF
            </button>
            <a href="../../index.php" class="btn btn-secondary">
                ← Quay lại
            </a>
        </div>

        <div id="emailAlert" style="display: none;"></div>

        <div class="tabs">
            <button class="tab active" onclick="switchTab('standard')">
                Hóa đơn A4
            </button>
            <button class="tab" onclick="switchTab('thermal')">
                Bill nhiệt
            </button>
            <button class="tab" onclick="switchTab('email')">
                Gửi Email
            </button>
        </div>

        <!-- Tab: Hóa đơn chuẩn -->
        <div id="tab-standard" class="tab-content active">
            <div class="invoice-preview">
                <?php echo $invoiceController->generateInvoiceHTML($orderId, false); ?>
            </div>
        </div>

        <!-- Tab: Bill nhiệt -->
        <div id="tab-thermal" class="tab-content">
            <div class="invoice-preview" style="max-width: 58mm; margin: 0 auto;">
                <?php echo $invoiceController->generateInvoiceHTML($orderId, true); ?>
            </div>
        </div>

        <!-- Tab: Gửi Email -->
        <div id="tab-email" class="tab-content">
            <div class="email-form">
                <h3>Gửi hóa đơn qua Email</h3>
                <form id="emailInvoiceForm">
                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">

                    <div class="form-group">
                        <label for="customer_email">Email khách hàng *</label>
                        <input
                            type="email"
                            id="customer_email"
                            name="email"
                            value="<?php echo $invoiceData['order']['email'] ?? ''; ?>"
                            placeholder="example@email.com"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            📧 Gửi Email
                        </button>
                    </div>
                </form>

                <div style="margin-top: 30px; padding: 15px; background-color: #e7f3ff; border-radius: 5px;">
                    <h4>Thông tin đơn hàng:</h4>
                    <p><strong>Mã đơn:</strong> <?php echo str_pad($orderId, 8, '0', STR_PAD_LEFT); ?></p>
                    <p><strong>Khách hàng:</strong> <?php echo $invoiceData['order']['ten_khachhang'] ?? 'Khách vãng lai'; ?></p>
                    <p><strong>Tổng tiền:</strong> <?php echo number_format($invoiceData['order']['tongtien'], 0, ',', '.'); ?>đ</p>
                    <p><strong>Ngày:</strong> <?php echo date('d/m/Y H:i', strtotime($invoiceData['order']['ngay_dat'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Chuyển đổi tabs
        function switchTab(tabName) {
            // Ẩn tất cả tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Hiển thị tab được chọn
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // In hóa đơn
        function printInvoice(type) {
            const thermal = type === 'thermal' ? '1' : '0';
            const url = '../../controllers/POSInvoiceController.php?action=print&order_id=<?php echo $orderId; ?>&thermal=' + thermal;
            window.open(url, '_blank');
        }

        // Export PDF
        function exportPDF() {
            const url = '../../controllers/POSInvoiceController.php?action=export_pdf&order_id=<?php echo $orderId; ?>';
            window.open(url, '_blank');
        }

        // Hiển thị form email
        function showEmailForm() {
            switchTab('email');
            document.querySelector('.tab[onclick*="email"]').click();
        }

        // Hiển thị alert
        function showAlert(message, type) {
            const alertDiv = document.getElementById('emailAlert');
            alertDiv.className = 'alert alert-' + type;
            alertDiv.textContent = message;
            alertDiv.style.display = 'block';

            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 5000);
        }

        // Xử lý form gửi email
        $('#emailInvoiceForm').submit(function(e) {
            e.preventDefault();

            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('⏳ Đang gửi...').prop('disabled', true);

            $.ajax({
                url: '../../controllers/POSInvoiceController.php',
                method: 'POST',
                data: $(this).serialize() + '&action=send_email',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('Email đã được gửi thành công!', 'success');
                    } else {
                        showAlert('Gửi email thất bại. Vui lòng thử lại.', 'danger');
                    }
                },
                error: function() {
                    showAlert('Có lỗi xảy ra. Vui lòng thử lại.', 'danger');
                },
                complete: function() {
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });

        // Phím tắt
        document.addEventListener('keydown', function(e) {
            // Ctrl + P: In
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                printInvoice('standard');
            }
            // Ctrl + E: Email
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                showEmailForm();
            }
        });
    </script>
</body>
</html>
