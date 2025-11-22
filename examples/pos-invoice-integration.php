<?php
/**
 * Example: POS Invoice Integration
 * Ví dụ tích hợp in hóa đơn với quy trình thanh toán POS
 *
 * VOVANHUNG-DEV
 * File: examples/pos-invoice-integration.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/POSInvoiceController.php';

/**
 * Example 1: In hóa đơn sau khi thanh toán thành công
 */
function example1_printAfterPayment($orderId, $thermalPrint = true) {
    try {
        $invoiceController = new POSInvoiceController();

        // In hóa đơn
        $invoiceController->printInvoice($orderId, $thermalPrint);

        return [
            'success' => true,
            'message' => 'Hóa đơn đã được in thành công'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Lỗi in hóa đơn: ' . $e->getMessage()
        ];
    }
}

/**
 * Example 2: Gửi email hóa đơn tự động nếu có email khách hàng
 */
function example2_autoSendEmail($orderId) {
    try {
        $invoiceController = new POSInvoiceController();

        // Lấy thông tin đơn hàng
        $invoiceData = $invoiceController->getInvoiceData($orderId);

        if (!$invoiceData) {
            throw new Exception("Không tìm thấy đơn hàng");
        }

        // Kiểm tra có email khách hàng không
        $customerEmail = $invoiceData['order']['email'] ?? null;

        if ($customerEmail) {
            // Gửi email
            $result = $invoiceController->sendInvoiceEmail($orderId, $customerEmail);

            return [
                'success' => $result,
                'message' => $result ? 'Email đã được gửi tới ' . $customerEmail : 'Gửi email thất bại'
            ];
        }

        return [
            'success' => false,
            'message' => 'Khách hàng chưa có email'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ];
    }
}

/**
 * Example 3: Workflow hoàn chỉnh - Thanh toán + In + Email
 */
function example3_completeWorkflow($cartItems, $customerId, $paymentData, $employeeId) {
    global $conn;

    try {
        $conn->begin_transaction();

        // Bước 1: Tính tổng tiền
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            $totalAmount += $item['quantity'] * $item['price'];
        }

        // Áp dụng giảm giá nếu có
        $discount = $paymentData['discount'] ?? 0;
        $finalAmount = $totalAmount - $discount;

        // Bước 2: Tạo đơn hàng
        $sql = "INSERT INTO donhang (khachhang_id, nguoidung_id, tongtien, phuongthuc_thanhtoan, trangthai)
                VALUES (?, ?, ?, ?, 'dathanhtoan')";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iids", $customerId, $employeeId, $finalAmount, $paymentData['method']);
        $stmt->execute();
        $orderId = $conn->insert_id;

        // Bước 3: Thêm chi tiết đơn hàng
        $sql = "INSERT INTO chitiet_donhang (donhang_id, thuoc_id, donvi_id, soluong, dongia)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        foreach ($cartItems as $item) {
            $stmt->bind_param("iiiid",
                $orderId,
                $item['product_id'],
                $item['unit_id'],
                $item['quantity'],
                $item['price']
            );
            $stmt->execute();
        }

        // Bước 4: Lưu thông tin giao dịch thanh toán
        $sql = "INSERT INTO pos_transactions
                (order_id, session_id, payment_method, amount, cash_received, change_given, status)
                VALUES (?, ?, ?, ?, ?, ?, 'completed')";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisddd",
            $orderId,
            $paymentData['session_id'] ?? 0,
            $paymentData['method'],
            $finalAmount,
            $paymentData['cash_received'] ?? null,
            $paymentData['change_given'] ?? null
        );
        $stmt->execute();

        // Bước 5: Cập nhật kho
        foreach ($cartItems as $item) {
            $sql = "UPDATE khohang
                    SET soluong = soluong - ?
                    WHERE thuoc_id = ? AND donvi_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $item['quantity'], $item['product_id'], $item['unit_id']);
            $stmt->execute();
        }

        $conn->commit();

        // Bước 6: In hóa đơn
        $invoiceController = new POSInvoiceController();
        $invoiceController->printInvoice($orderId, true); // In nhiệt

        // Bước 7: Gửi email (nếu có)
        $invoiceData = $invoiceController->getInvoiceData($orderId);
        if (!empty($invoiceData['order']['email'])) {
            $invoiceController->sendInvoiceEmail($orderId, $invoiceData['order']['email']);
        }

        return [
            'success' => true,
            'order_id' => $orderId,
            'message' => 'Thanh toán thành công'
        ];

    } catch (Exception $e) {
        $conn->rollback();

        return [
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ];
    }
}

/**
 * Example 4: Tạo modal in lại hóa đơn với tìm kiếm
 */
function example4_reprintModal() {
    ?>
    <div id="reprintModal" style="display: none;">
        <h3>Tìm kiếm đơn hàng để in lại</h3>

        <input
            type="text"
            id="searchOrderInput"
            placeholder="Nhập mã đơn, SĐT hoặc tên khách hàng"
            style="width: 100%; padding: 10px; margin: 10px 0;"
        >

        <div id="searchResults"></div>
    </div>

    <script>
        let searchTimeout;

        $('#searchOrderInput').on('input', function() {
            const searchTerm = $(this).val().trim();

            if (searchTerm.length < 2) {
                $('#searchResults').html('');
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                // Gọi API tìm kiếm
                $.ajax({
                    url: '../controllers/POSInvoiceController.php',
                    method: 'POST',
                    data: {
                        action: 'search_order',
                        search_term: searchTerm
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.orders.length > 0) {
                            let html = '<table style="width: 100%; border-collapse: collapse;">';
                            html += '<thead><tr>';
                            html += '<th>Mã đơn</th>';
                            html += '<th>Khách hàng</th>';
                            html += '<th>Tổng tiền</th>';
                            html += '<th>Ngày</th>';
                            html += '<th>Thao tác</th>';
                            html += '</tr></thead><tbody>';

                            response.orders.forEach(function(order) {
                                html += '<tr>';
                                html += '<td>' + order.id + '</td>';
                                html += '<td>' + (order.ten_khachhang || 'Khách vãng lai') + '</td>';
                                html += '<td>' + formatMoney(order.tongtien) + '</td>';
                                html += '<td>' + formatDate(order.ngay_dat) + '</td>';
                                html += '<td>';
                                html += '<button onclick="reprintInvoice(' + order.id + ', true)">In bill</button> ';
                                html += '<button onclick="reprintInvoice(' + order.id + ', false)">In A4</button>';
                                html += '</td>';
                                html += '</tr>';
                            });

                            html += '</tbody></table>';
                            $('#searchResults').html(html);
                        } else {
                            $('#searchResults').html('<p>Không tìm thấy đơn hàng</p>');
                        }
                    }
                });
            }, 500); // Debounce 500ms
        });

        function reprintInvoice(orderId, thermal) {
            const thermalParam = thermal ? '&thermal=1' : '';
            window.open('../controllers/POSInvoiceController.php?action=print&order_id=' + orderId + thermalParam, '_blank');
        }

        function formatMoney(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN');
        }
    </script>
    <?php
}

/**
 * Example 5: AJAX handler cho POS payment
 */
function example5_ajaxHandler() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pos_action'])) {
        header('Content-Type: application/json');

        $action = $_POST['pos_action'];

        try {
            switch ($action) {
                case 'complete_payment':
                    // Lấy dữ liệu từ POST
                    $cartItems = json_decode($_POST['cart_items'], true);
                    $customerId = intval($_POST['customer_id']);
                    $paymentData = json_decode($_POST['payment_data'], true);
                    $employeeId = intval($_POST['employee_id']);

                    // Xử lý thanh toán
                    $result = example3_completeWorkflow($cartItems, $customerId, $paymentData, $employeeId);

                    echo json_encode($result);
                    break;

                case 'print_invoice':
                    $orderId = intval($_POST['order_id']);
                    $thermal = isset($_POST['thermal']) && $_POST['thermal'] === '1';

                    $result = example1_printAfterPayment($orderId, $thermal);
                    echo json_encode($result);
                    break;

                case 'send_invoice_email':
                    $orderId = intval($_POST['order_id']);

                    $result = example2_autoSendEmail($orderId);
                    echo json_encode($result);
                    break;

                default:
                    echo json_encode([
                        'success' => false,
                        'message' => 'Action không hợp lệ'
                    ]);
            }

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
        }

        exit;
    }
}

/**
 * Example 6: Tích hợp với nút thanh toán POS
 */
function example6_paymentButtonHTML() {
    ?>
    <div class="payment-section">
        <button id="completePaymentBtn" class="btn btn-success btn-lg">
            Hoàn tất thanh toán
        </button>
    </div>

    <script>
        $('#completePaymentBtn').click(function() {
            const cartItems = getCartItems(); // Function lấy giỏ hàng
            const customerId = getCustomerId(); // Function lấy ID khách hàng
            const paymentData = getPaymentData(); // Function lấy thông tin thanh toán
            const employeeId = getCurrentEmployeeId(); // Function lấy ID nhân viên

            // Hiển thị loading
            $(this).html('⏳ Đang xử lý...').prop('disabled', true);

            $.ajax({
                url: 'examples/pos-invoice-integration.php',
                method: 'POST',
                data: {
                    pos_action: 'complete_payment',
                    cart_items: JSON.stringify(cartItems),
                    customer_id: customerId,
                    payment_data: JSON.stringify(paymentData),
                    employee_id: employeeId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Thanh toán thành công! Mã đơn: ' + response.order_id);

                        // Tự động mở cửa sổ in hóa đơn
                        window.open(
                            'controllers/POSInvoiceController.php?action=print&order_id=' +
                            response.order_id + '&thermal=1',
                            '_blank'
                        );

                        // Reset giỏ hàng
                        clearCart();
                    } else {
                        alert('Lỗi: ' + response.message);
                    }
                },
                error: function() {
                    alert('Có lỗi xảy ra trong quá trình thanh toán');
                },
                complete: function() {
                    $('#completePaymentBtn').html('Hoàn tất thanh toán').prop('disabled', false);
                }
            });
        });

        // Helper functions (cần implement dựa trên hệ thống thực tế)
        function getCartItems() {
            // Lấy dữ liệu giỏ hàng từ DOM hoặc state
            return [];
        }

        function getCustomerId() {
            // Lấy ID khách hàng đã chọn
            return 0;
        }

        function getPaymentData() {
            // Lấy thông tin thanh toán
            return {
                method: 'cash',
                session_id: 1,
                cash_received: 0,
                change_given: 0,
                discount: 0
            };
        }

        function getCurrentEmployeeId() {
            // Lấy ID nhân viên đang đăng nhập
            return 1;
        }

        function clearCart() {
            // Xóa giỏ hàng
        }
    </script>
    <?php
}

/**
 * Example 7: Cấu hình máy in nhiệt ESC/POS (nếu có thư viện)
 */
function example7_escposConfig() {
    /*
    // Cần cài đặt: composer require mike42/escpos-php

    use Mike42\Escpos\Printer;
    use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
    use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

    // Option 1: Windows printer
    $connector = new WindowsPrintConnector("POS-58");

    // Option 2: Network printer
    // $connector = new NetworkPrintConnector("192.168.1.100", 9100);

    $printer = new Printer($connector);

    // Lấy dữ liệu hóa đơn
    $invoiceController = new POSInvoiceController();
    $data = $invoiceController->getInvoiceData($orderId);

    // In header
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setTextSize(2, 2);
    $printer->text("MedXtore Pharmacy\n");
    $printer->setTextSize(1, 1);
    $printer->text("Chi nhánh Quận 1\n");
    $printer->text("123 Nguyễn Huệ, Q1, TPHCM\n");
    $printer->text("ĐT: 028 1234 5678\n");
    $printer->feed();

    // In nội dung đơn hàng
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("Hóa đơn #" . $orderId . "\n");
    $printer->text("Ngày: " . date('d/m/Y H:i') . "\n");
    $printer->text(str_repeat("-", 32) . "\n");

    // In sản phẩm
    foreach ($data['items'] as $item) {
        $printer->text($item['ten_thuoc'] . "\n");
        $printer->text(sprintf("  %d x %s = %s\n",
            $item['soluong'],
            number_format($item['dongia']),
            number_format($item['thanhtien'])
        ));
    }

    $printer->text(str_repeat("-", 32) . "\n");
    $printer->setTextSize(2, 2);
    $printer->text("Tổng: " . number_format($data['summary']['final_total']) . "đ\n");
    $printer->setTextSize(1, 1);

    // In footer
    $printer->feed(2);
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("Cảm ơn quý khách!\n");
    $printer->text("Hẹn gặp lại!\n");

    // Cắt giấy
    $printer->cut();
    $printer->close();
    */

    echo "Cần cài đặt thư viện ESC/POS: composer require mike42/escpos-php\n";
}

// Gọi AJAX handler nếu có request
example5_ajaxHandler();
