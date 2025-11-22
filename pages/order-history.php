<?php
session_start();

// Xử lý AJAX request trước
if (isset($_GET['action']) && $_GET['action'] === 'get_details' && isset($_GET['order_id'])) {
    // Tắt error reporting cho AJAX request
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Đảm bảo không có output nào trước khi gửi JSON
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Phiên đăng nhập đã hết hạn'
        ]);
        exit();
    }
    
    try {
        // Khởi tạo kết nối database mới cho AJAX request
        require_once __DIR__ . '/../config/database.php';
        
        // Kiểm tra kết nối
        if (!$conn || $conn->connect_error) {
            throw new Exception("Lỗi kết nối database: " . ($conn ? $conn->connect_error : "Không thể khởi tạo kết nối"));
        }

        $orderId = $_GET['order_id'];
        $userId = $_SESSION['user_id'];
        
        // Debug vào file log thay vì hiển thị
        error_log("OrderID: " . $orderId);
        error_log("UserID: " . $userId);
        
        // Lấy thông tin đơn hàng và khách hàng
        $sql = "SELECT dh.*, kh.ten_khachhang, kh.sodienthoai, kh.diachi 
                FROM donhang dh 
                JOIN khachhang kh ON dh.khachhang_id = kh.id 
                WHERE dh.id = ? AND dh.nguoidung_id = ?";
                
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Lỗi prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("ii", $orderId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if (!$order) {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng'
            ]);
            exit();
        }

        // Lấy chi tiết đơn hàng và thông tin thuốc
        $sql = "SELECT ctdh.*, t.ten_thuoc, t.hinhanh,
                       ctdh.dongia as gia,
                       (ctdh.soluong * ctdh.dongia) as thanh_tien
                FROM chitiet_donhang ctdh 
                JOIN thuoc t ON ctdh.thuoc_id = t.id 
                WHERE ctdh.donhang_id = ?";
                
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Lỗi prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'order' => $order,
                'items' => $items
            ]
        ]);
        exit();
    } catch(Exception $e) {
        error_log("Error in order details: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi truy vấn: ' . $e->getMessage()
        ]);
        exit();
    }
}

// Bật lại error reporting cho phần hiển thị thông thường
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tiếp tục với phần hiển thị HTML
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: /MedXtorePharmacy/pages/login.php");
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // Lấy danh sách đơn hàng của người dùng
    $sql = "SELECT dh.*, kh.ten_khachhang, kh.sodienthoai, kh.diachi 
            FROM donhang dh 
            JOIN khachhang kh ON dh.khachhang_id = kh.id 
            WHERE dh.nguoidung_id = ? 
            ORDER BY dh.ngay_dat DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

} catch(Exception $e) {
    $_SESSION['error'] = "Lỗi truy vấn: " . $e->getMessage();
}
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<style>
.modal-lg {
    max-width: 900px;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.card-title {
    color: #0d6efd;
}

.text-primary {
    color: #0d6efd !important;
}

.table > :not(caption) > * > * {
    padding: 0.75rem;
}

.fs-6 {
    font-size: 1rem !important;
}

.me-2 {
    margin-right: 0.5rem !important;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

.pb-3 {
    padding-bottom: 1rem !important;
}

.border-bottom {
    border-bottom: 1px solid #dee2e6 !important;
}
</style>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Lịch Sử Mua Hàng</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error'] ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info">
                            Bạn chưa có đơn hàng nào.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã Đơn</th>
                                        <th>Ngày Đặt</th>
                                        <th>Người Nhận</th>
                                        <th>Tổng Tiền</th>
                                        <th>Trạng Thái</th>
                                        <th>Thanh Toán</th>
                                        <th>Chi Tiết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?= $order['id'] ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($order['ngay_dat'])) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($order['ten_khachhang']) ?></strong><br>
                                                <small><?= htmlspecialchars($order['sodienthoai']) ?></small>
                                            </td>
                                            <td><?= number_format($order['tongtien'], 0, ',', '.') ?> đ</td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
                                                switch ($order['trangthai']) {
                                                    case 'choxacnhan':
                                                        $statusClass = 'warning';
                                                        $statusText = 'Chờ xác nhận';
                                                        break;
                                                    case 'dathanhtoan':
                                                        $statusClass = 'success';
                                                        $statusText = 'Đã thanh toán';
                                                        break;
                                                    case 'dahuy':
                                                        $statusClass = 'danger';
                                                        $statusText = 'Đã hủy';
                                                        break;
                                                    case 'dadat':
                                                        $statusClass = 'info';
                                                        $statusText = 'Đã đặt';
                                                        break;
                                                    default:
                                                        $statusClass = 'secondary';
                                                        $statusText = $order['trangthai'];
                                                }
                                                ?>
                                                <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $order['phuongthuc_thanhtoan'] === 'cod' ? 'secondary' : 'primary' ?>">
                                                    <?= strtoupper($order['phuongthuc_thanhtoan']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm btn-info text-white view-details" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#orderModal" 
                                                        data-order-id="<?= $order['id'] ?>">
                                                    Chi tiết
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chi tiết đơn hàng -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi Tiết Đơn Hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="orderDetails">
                    <!-- Nội dung chi tiết đơn hàng sẽ được load bằng AJAX -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý sự kiện click vào nút chi tiết
    const viewButtons = document.querySelectorAll('.view-details');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            loadOrderDetails(orderId);
        });
    });

    // Thay thế hàm loadOrderDetails cũ bằng hàm mới này
    function loadOrderDetails(orderId) {
        fetch(`/MedXtorePharmacy/pages/order-history.php?action=get_details&order_id=${orderId}`)
            .then(response => {
                console.log('Response status:', response.status);
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Parse error:', e);
                        console.log('Raw response:', text);
                        throw new Error('Invalid JSON response');
                    }
                });
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    const details = data.data;
                    let html = `
                        <div class="order-info mb-4">
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="text-primary mb-0">Đơn hàng #${details.order.id}</h5>
                                    <span class="badge bg-${getStatusClass(details.order.trangthai)} fs-6">
                                        ${getStatusText(details.order.trangthai)}
                                    </span>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3">
                                                <i class="fas fa-user-circle me-2"></i>Thông tin người nhận
                                            </h6>
                                            <p class="mb-2">
                                                <i class="fas fa-user me-2 text-primary"></i>
                                                ${details.order.ten_khachhang}
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-phone me-2 text-primary"></i>
                                                ${details.order.sodienthoai}
                                            </p>
                                            <p class="mb-0">
                                                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                                ${details.order.diachi}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3">
                                                <i class="fas fa-info-circle me-2"></i>Thông tin đơn hàng
                                            </h6>
                                            <p class="mb-2">
                                                <i class="fas fa-calendar me-2 text-primary"></i>
                                                Ngày đặt: ${formatDate(details.order.ngay_dat)}
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-money-bill-wave me-2 text-primary"></i>
                                                Phương thức: ${formatPaymentMethod(details.order.phuongthuc_thanhtoan)}
                                            </p>
                                            <p class="mb-0">
                                                <i class="fas fa-shipping-fast me-2 text-primary"></i>
                                                Phí vận chuyển: ${formatCurrency(0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="order-items mt-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-shopping-basket me-2"></i>Chi tiết sản phẩm
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th class="text-center">Số lượng</th>
                                            <th class="text-end">Đơn giá</th>
                                            <th class="text-end">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;

                    details.items.forEach(item => {
                        html += `
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        ${item.hinhanh ? `
                                            <img src="/MedXtorePharmacy/assets/images/product-images/${item.hinhanh}" 
                                                 alt="${item.ten_thuoc}" 
                                                 class="me-2" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        ` : ''}
                                        <span>${item.ten_thuoc}</span>
                                    </div>
                                </td>
                                <td class="text-center">${item.soluong}</td>
                                <td class="text-end">${formatCurrency(item.gia)}</td>
                                <td class="text-end">${formatCurrency(item.thanh_tien)}</td>
                            </tr>`;
                    });

                    html += `
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="3" class="text-end">
                                                <strong>Tổng thanh toán:</strong>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-primary fs-5">
                                                    ${formatCurrency(details.order.tongtien)}
                                                </strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>`;

                    document.getElementById('orderDetails').innerHTML = html;
                } else {
                    document.getElementById('orderDetails').innerHTML = `
                        <div class="alert alert-danger">
                            ${data.message || 'Có lỗi xảy ra khi tải chi tiết đơn hàng'}
                        </div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('orderDetails').innerHTML = `
                    <div class="alert alert-danger">
                        Có lỗi xảy ra khi tải chi tiết đơn hàng: ${error.message}
                    </div>`;
            });
    }

    // Giữ nguyên các hàm helper khác
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatPaymentMethod(method) {
        switch (method.toLowerCase()) {
            case 'cod':
                return 'Thanh toán khi nhận hàng (COD)';
            case 'momo':
                return 'Ví MoMo';
            case 'banking':
                return 'Chuyển khoản ngân hàng';
            default:
                return method;
        }
    }

    function getStatusClass(status) {
        switch (status) {
            case 'choxacnhan':
                return 'warning';
            case 'dathanhtoan':
                return 'success';
            case 'dahuy':
                return 'danger';
            case 'dadat':
                return 'info';
            default:
                return 'secondary';
        }
    }

    function getStatusText(status) {
        switch (status) {
            case 'choxacnhan':
                return 'Chờ xác nhận';
            case 'dathanhtoan':
                return 'Đã thanh toán';
            case 'dahuy':
                return 'Đã hủy';
            case 'dadat':
                return 'Đã đặt';
            default:
                return status;
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>