<?php
require_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: /MedXtorePharmacy/pages/login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$cart = $_SESSION['carts'][$userId] ?? [];

if (empty($cart)) {
    header("Location: /MedXtorePharmacy/pages/cart.php");       
    exit();
}       

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kiểm tra user tồn tại
    $stmt = $conn->prepare("SELECT id FROM nguoidung WHERE id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception("Người dùng không tồn tại");
    }

    $conn->beginTransaction();
    $transactionStarted = true;

    try {
        // === Lưu thông tin khách hàng ===
        $stmt = $conn->prepare("INSERT INTO khachhang (ten_khachhang, sodienthoai, diachi, email, ngay_tao, nguoidung_id) 
                                VALUES (?, ?, ?, ?, NOW(), ?)");
        $diachi = $_POST['address'];
        $stmt->execute([
            $_POST['fullName'],
            $_POST['phone'],
            $diachi,
            $_POST['email'],
            $userId
        ]);
        $khachhang_id = $conn->lastInsertId();

        // === Tính toán tổng tiền, phí ship, giảm giá ===
        $tongTien = $_POST['total_amount'];
        $voucher_id = $_POST['voucher_id'] ?? null;
        $shipping_fee = $_POST['shipping_fee'] ?? 0;
        $discount = $_POST['discount'] ?? 0;

        // Xác định trạng thái đơn hàng dựa trên phương thức thanh toán
        $payment_method = $_POST['payment_method'] ?? 'cod';
        $trangthai = 'dadat'; // Mặc định là đã đặt

        if ($payment_method === 'momo') {
            $trangthai = 'choxacnhan';
        } elseif ($payment_method === 'banking') {
            $trangthai = 'chochuyenkhoan';
        }

        // === Lưu đơn hàng ===
        $stmt = $conn->prepare("INSERT INTO donhang (khachhang_id, nguoidung_id, ngay_dat, tongtien, ghichu, trangthai, phuongthuc_thanhtoan) 
                                VALUES (?, ?, NOW(), ?, ?, ?, ?)");
        $ghichu = !empty($_POST['notes']) ? $_POST['notes'] : null;
        $stmt->execute([
            $khachhang_id,
            $userId,
            $tongTien,
            $ghichu,
            $trangthai,
            $payment_method
        ]);
        $donhang_id = $conn->lastInsertId();

        // === Lưu chi tiết đơn hàng ===
        $stmt = $conn->prepare("INSERT INTO chitiet_donhang (donhang_id, thuoc_id, soluong, dongia) 
                                VALUES (?, ?, ?, ?)");
        foreach ($cart as $item) {
            $stmt->execute([
                $donhang_id,
                $item['thuoc_id'],
                $item['soluong'],
                $item['gia']
            ]);
        }

        // === Cập nhật trạng thái voucher nếu có ===
        if ($voucher_id) {
            $stmt = $conn->prepare("UPDATE vouchers SET is_used = 1, used_by = ?, used_at = NOW() WHERE id = ? AND is_used = 0");
            $stmt->execute([$userId, $voucher_id]);
        }

        $conn->commit();
        $transactionStarted = false;

        // Cập nhật số lượng trong kho nếu là thanh toán COD
        if ($payment_method === 'cod') {
            require_once __DIR__ . '/OrderController.php';
            $orderController = new OrderController();
            if (!$orderController->updateInventoryForNewOrder($donhang_id)) {
                throw new Exception("Không thể cập nhật số lượng trong kho");
            }
        }

        // === Lưu thông tin ship/giảm giá vào session để show hóa đơn
        $_SESSION['invoice_extra'] = [
            'shipping_fee' => (int)$shipping_fee,
            'discount' => (int)$discount
        ];

        // Xử lý chuyển hướng dựa trên phương thức thanh toán
        if ($payment_method === 'momo') {
            // Lưu thông tin đơn hàng vào session để xử lý sau thanh toán
            $_SESSION['pending_order'] = [
                'order_id' => $donhang_id,
                'amount' => $tongTien,
                'order_info' => "Thanh toan don hang #" . $donhang_id
            ];
            $_SESSION['pending_cart'] = $cart;
            header("Location: /MedXtorePharmacy/controllers/PaymentController.php?action=momo");
        } else {
            // Xóa giỏ hàng và chuyển đến trang hóa đơn
            unset($_SESSION['carts'][$userId]);
            unset($_SESSION['applied_voucher']);
            header("Location: /MedXtorePharmacy/pages/invoice.php?order_id=" . $donhang_id);
        }
        exit();

    } catch (Exception $e) {
        if ($transactionStarted) {
            $conn->rollBack();
        }
        throw $e;
    }

} catch (Exception $e) {
    $_SESSION['error'] = "🛑 Lỗi xử lý đơn hàng: " . $e->getMessage();
    header("Location: /MedXtorePharmacy/pages/checkout.php");
    exit();
}
