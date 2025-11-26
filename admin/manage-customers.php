<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Kiểm tra quyền admin
requireAdmin();

// Search và filter
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

// Lấy danh sách khách hàng
$sql = "SELECT * FROM khachhang WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (ten_khachhang LIKE ? OR email LIKE ? OR sodienthoai LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam];
}

$sql .= " ORDER BY ngay_tao DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$allCustomers = $result->fetch_all(MYSQLI_ASSOC);

$totalItems = count($allCustomers);
$totalPages = ceil($totalItems / $limit);
$customers = array_slice($allCustomers, $offset, $limit);

// Lấy thống kê tổng số đơn hàng cho mỗi khách hàng
foreach ($customers as &$customer) {
    $sql_orders = "SELECT COUNT(*) as total_orders, SUM(tongtien) as total_spent
                   FROM donhang
                   WHERE khachhang_id = ? AND trangthai IN ('dadat', 'dathanhtoan', 'dagiao')";
    $stmt_orders = $conn->prepare($sql_orders);
    $stmt_orders->bind_param("i", $customer['id']);
    $stmt_orders->execute();
    $order_stats = $stmt_orders->get_result()->fetch_assoc();
    $customer['total_orders'] = $order_stats['total_orders'] ?? 0;
    $customer['total_spent'] = $order_stats['total_spent'] ?? 0;
}

include('../includes/ad-header.php');
include('../includes/ad-sidebar.php');
?>

<style>
.stats-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(0,0,0,0.05);
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(102, 126, 234, 0.2);
    border-color: rgba(102, 126, 234, 0.3);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.table-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.05);
}

.table th {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-weight: 600;
    padding: 15px;
    white-space: nowrap;
    border: none;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
    padding: 12px 15px;
    border-color: rgba(0,0,0,0.05);
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
    transform: scale(1.01);
}

.customer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
}

.badge {
    padding: 6px 12px;
    font-weight: 500;
    border-radius: 6px;
}
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-users text-primary"></i> Quản lý khách hàng</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Thống kê cards -->
            <div class="row mb-4">
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="stats-card p-3">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon me-3" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Tổng khách hàng</div>
                                <h3 class="mb-0"><?= number_format($totalItems) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4">
                    <div class="stats-card p-3">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon me-3" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Khách hàng mới (30 ngày)</div>
                                <h3 class="mb-0">
                                    <?php
                                    $new_customers = array_filter($allCustomers, function($c) {
                                        return strtotime($c['ngay_tao']) > strtotime('-30 days');
                                    });
                                    echo count($new_customers);
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4">
                    <div class="stats-card p-3">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon me-3" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Khách hàng có đơn</div>
                                <h3 class="mb-0">
                                    <?php
                                    $active_customers = array_filter($allCustomers, function($c) {
                                        global $conn;
                                        $sql = "SELECT COUNT(*) as total FROM donhang WHERE khachhang_id = ?";
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param("i", $c['id']);
                                        $stmt->execute();
                                        return $stmt->get_result()->fetch_assoc()['total'] > 0;
                                    });
                                    echo count($active_customers);
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="card p-3 shadow-sm mb-3">
                <form method="GET" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control"
                               placeholder="Tìm theo tên, email, số điện thoại..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Tìm
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bảng khách hàng -->
            <div class="table-container card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Khách hàng</th>
                                <th>Email</th>
                                <th>Số điện thoại</th>
                                <th>Địa chỉ</th>
                                <th class="text-center">Số đơn</th>
                                <th class="text-end">Tổng chi tiêu</th>
                                <th class="text-center">Ngày tạo</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($customers) > 0): ?>
                            <?php foreach($customers as $index => $customer): ?>
                            <tr>
                                <td><?= $offset + $index + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="customer-avatar me-2">
                                            <?= strtoupper(substr($customer['ten_khachhang'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($customer['ten_khachhang']) ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($customer['email']) ?></td>
                                <td><?= htmlspecialchars($customer['sodienthoai']) ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?= htmlspecialchars(substr($customer['diachi'], 0, 50)) ?>
                                        <?= strlen($customer['diachi']) > 50 ? '...' : '' ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?= $customer['total_orders'] ?></span>
                                </td>
                                <td class="text-end">
                                    <strong><?= number_format($customer['total_spent'], 0, ',', '.') ?>đ</strong>
                                </td>
                                <td class="text-center">
                                    <small><?= date('d/m/Y', strtotime($customer['ngay_tao'])) ?></small>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-info view-customer"
                                            data-customer-id="<?= $customer['id'] ?>"
                                            title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Không tìm thấy khách hàng nào</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalItems > 0): ?>
                <div class="card-footer">
                    <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Trước</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Sau</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    <div class="text-center <?= $totalPages > 1 ? 'mt-2' : '' ?>">
                        <small class="text-muted">Trang <?= $page ?> / <?= $totalPages ?> (Tổng <?= $totalItems ?> khách hàng)</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- Modal chi tiết khách hàng -->
<div class="modal fade" id="customerModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                <h5 class="modal-title">Chi tiết khách hàng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="customerDetails">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Đang tải thông tin...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/ad-footer.php'); ?>

<script>
$(document).ready(function() {
    $('.view-customer').click(function() {
        const customerId = $(this).data('customer-id');
        const $modal = $('#customerModal');
        const $modalBody = $('#customerDetails');

        $modalBody.html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Đang tải thông tin khách hàng...</p>
            </div>
        `);

        $modal.modal('show');

        $.get(`get-customer-details.php?customer_id=${customerId}`)
            .done(function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        renderCustomerDetails(data.customer, data.orders);
                    } else {
                        $modalBody.html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${data.message || 'Có lỗi xảy ra khi tải dữ liệu'}
                            </div>
                        `);
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    $modalBody.html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Có lỗi xảy ra khi tải dữ liệu
                        </div>
                    `);
                }
            })
            .fail(function() {
                $modalBody.html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Có lỗi xảy ra khi tải dữ liệu
                    </div>
                `);
            });
    });

    function renderCustomerDetails(customer, orders) {
        let ordersHtml = '';
        if (orders && orders.length > 0) {
            orders.forEach((order, index) => {
                const statusMap = {
                    'choxacnhan': { text: 'Chờ xác nhận', class: 'warning' },
                    'dadat': { text: 'Đã đặt', class: 'success' },
                    'dathanhtoan': { text: 'Đã thanh toán', class: 'primary' },
                    'danggiao': { text: 'Đang giao', class: 'info' },
                    'dagiao': { text: 'Đã giao', class: 'success' },
                    'dahuy': { text: 'Đã hủy', class: 'danger' }
                };
                const status = statusMap[order.trangthai] || { text: order.trangthai, class: 'secondary' };

                ordersHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>#${String(order.id).padStart(5, '0')}</td>
                        <td>${new Date(order.ngay_dat).toLocaleDateString('vi-VN')}</td>
                        <td><span class="badge bg-${status.class}">${status.text}</span></td>
                        <td class="text-end">${new Intl.NumberFormat('vi-VN').format(order.tongtien)}đ</td>
                    </tr>
                `;
            });
        } else {
            ordersHtml = '<tr><td colspan="5" class="text-center py-3">Chưa có đơn hàng nào</td></tr>';
        }

        $('#customerDetails').html(`
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Thông tin khách hàng</h6>
                    <p class="mb-2"><strong>Họ tên:</strong> ${customer.ten_khachhang}</p>
                    <p class="mb-2"><strong>Email:</strong> ${customer.email}</p>
                    <p class="mb-2"><strong>Số điện thoại:</strong> ${customer.sodienthoai}</p>
                    <p class="mb-2"><strong>Địa chỉ:</strong> ${customer.diachi}</p>
                    <p class="mb-2"><strong>Ngày tạo:</strong> ${new Date(customer.ngay_tao).toLocaleString('vi-VN')}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Thống kê</h6>
                    <p class="mb-2"><strong>Tổng đơn hàng:</strong> ${orders.length}</p>
                    <p class="mb-2"><strong>Tổng chi tiêu:</strong> ${new Intl.NumberFormat('vi-VN').format(
                        orders.reduce((sum, o) => sum + parseFloat(o.tongtien), 0)
                    )}đ</p>
                </div>
            </div>

            <h6 class="text-muted mb-3">Lịch sử đơn hàng</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>STT</th>
                            <th>Mã đơn</th>
                            <th>Ngày đặt</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Tổng tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${ordersHtml}
                    </tbody>
                </table>
            </div>
        `);
    }
});
</script>
