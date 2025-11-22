<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../controllers/OrderController.php';

// Kiểm tra quyền admin
requireAdmin();

// Khởi tạo controller và lấy dữ liệu
$orderController = new OrderController();
$orders = $orderController->getAllOrders();
$orderStats = $orderController->getOrderStats();

include('../includes/ad-header.php');
include('../includes/ad-sidebar.php');
?>

<style>
.content-wrapper {
    margin-left: 250px;
    padding: 20px;
    background: #f4f6f9;
    min-height: 100vh;
}

.stats-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    overflow: hidden;
    position: relative;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
}

.stats-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
    opacity: 0;
    transition: opacity 0.3s;
}

.stats-card:hover::after {
    opacity: 1;
}

.stats-icon {
    width: 52px;
    height: 52px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-right: 15px;
    background: linear-gradient(135deg, var(--color-start), var(--color-end));
}

/* Custom gradient colors for each status */
.status-waiting {
    --color-start: #ffd700;
    --color-end: #ffa500;
}

.status-processing {
    --color-start: #3498db;
    --color-end: #2980b9;
}

.status-shipping {
    --color-start: #2ecc71;
    --color-end: #27ae60;
}

.status-cancelled {
    --color-start: #e74c3c;
    --color-end: #c0392b;
}

.status-badge {
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Table Styling */
.table-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 20px;
    margin-top: 20px;
}

.orders-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.orders-table th {
    background: #f8f9fa;
    font-weight: 600;
    padding: 15px;
    border-bottom: 2px solid #dee2e6;
    text-transform: uppercase;
    font-size: 0.85rem;
}

.orders-table td {
    padding: 15px;
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
}

.orders-table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Action Buttons */
.action-btns .btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    margin: 0 3px;
    transition: all 0.3s ease;
}

.action-btns .btn:hover {
    transform: translateY(-2px);
}

/* Status Select Styling */
.status-select {
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 5px 10px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.status-select:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    outline: none;
}

/* Modal Styling */
.modal-content {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.2);
}

.modal-header {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border-radius: 15px 15px 0 0;
    padding: 15px 20px;
}

.modal-body {
    padding: 20px;
}

/* Responsive Design */
@media (max-width: 992px) {
    .content-wrapper {
        margin-left: 0;
    }
    
    .stats-card {
        margin-bottom: 15px;
    }
}

/* Thêm styles mới */
.stats-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
}

.stats-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-right: 15px;
    color: white;
}

/* Gradient colors cho từng trạng thái */
.status-total .stats-icon {
    background: linear-gradient(135deg, #3498db, #2980b9);
}

.status-ordered .stats-icon {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.status-waiting .stats-icon {
    background: linear-gradient(135deg, #f1c40f, #f39c12);
}

.status-shipping .stats-icon {
    background: linear-gradient(135deg, #3498db, #2980b9);
}

.status-delivered .stats-icon {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.status-cancelled .stats-icon {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.stats-card h3 {
    font-size: 1.8rem;
    font-weight: 600;
    margin: 0;
    color: #2c3e50;
}

.stats-card .text-muted {
    font-size: 0.9rem;
    color: #7f8c8d !important;
}

/* Hiệu ứng hover cho icon */
.stats-card:hover .stats-icon {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Quản lý đơn hàng</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Thống kê cards -->
            <div class="row mb-4">
                <!-- Tổng đơn hàng -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="stats-card p-3 status-total">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div>
                                <div class="text-muted">Tổng đơn hàng</div>
                                <h3 class="mb-0">
                                    <?= ($orderStats['choxacnhan']['so_luong'] ?? 0) + 
                                        ($orderStats['dadat']['so_luong'] ?? 0) +
                                        ($orderStats['danggiao']['so_luong'] ?? 0) + 
                                        ($orderStats['dagiao']['so_luong'] ?? 0) + 
                                        ($orderStats['dahuy']['so_luong'] ?? 0) ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đơn đã đặt -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="stats-card p-3 status-ordered">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div>
                                <div class="text-muted">Đơn đã đặt</div>
                                <h3 class="mb-0"><?= $orderStats['dadat']['so_luong'] ?? 0 ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đơn chờ xác nhận -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="stats-card p-3 status-waiting">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="text-muted">Chờ xác nhận</div>
                                <h3 class="mb-0"><?= $orderStats['choxacnhan']['so_luong'] ?? 0 ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đơn đang giao -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="stats-card p-3 status-shipping">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div>
                                <div class="text-muted">Đang giao</div>
                                <h3 class="mb-0"><?= $orderStats['danggiao']['so_luong'] ?? 0 ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đơn đã giao -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="stats-card p-3 status-delivered">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <div class="text-muted">Đã giao</div>
                                <h3 class="mb-0"><?= $orderStats['dagiao']['so_luong'] ?? 0 ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đơn đã hủy -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="stats-card p-3 status-cancelled">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div>
                                <div class="text-muted">Đã hủy</div>
                                <h3 class="mb-0"><?= $orderStats['dahuy']['so_luong'] ?? 0 ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bảng đơn hàng -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover orders-table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Mã ĐH</th>
                                <th>Khách hàng</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>  
                                <th>Trạng thái</th>
                                <th>Thanh toán</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($orders as $order): ?>
                            <?php
                            // Xử lý hiển thị trạng thái đơn hàng
                            switch ($order['trangthai']) {
                                case 'choxacnhan':
                                    $statusClass = 'warning';
                                    $statusText = 'Chờ xác nhận';
                                    break;
                                case 'dadat':
                                    $statusClass = 'success'; 
                                    $statusText = 'Đã đặt';
                                    break;
                                case 'danggiao':
                                    $statusClass = 'info';
                                    $statusText = 'Đang giao'; 
                                    break;
                                case 'dagiao':
                                    $statusClass = 'primary';
                                    $statusText = 'Đã giao';
                                    break;
                                case 'dahuy':
                                    $statusClass = 'danger';
                                    $statusText = 'Đã hủy';
                                    break;
                                default:
                                    $statusClass = 'secondary';
                                    $statusText = $order['trangthai'];
                            }
                            ?>
                            <tr>
                                <td>#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($order['ten_khachhang']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($order['sodienthoai']) ?></small>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['ngay_dat'])) ?></td>
                                <td><?= number_format($order['tongtien'], 0, ',', '.') ?>đ</td>
                                <td>
                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $order['phuongthuc_thanhtoan'] === 'cod' ? 'secondary' : 'primary' ?>">
                                        <?= strtoupper($order['phuongthuc_thanhtoan']) ?>
                                    </span>
                                </td>
                                <td class="action-btns">
                                    <button type="button" class="btn btn-info view-order" 
                                            data-order-id="<?= $order['id'] ?>"
                                            title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="../pages/invoice.php?order_id=<?= $order['id'] ?>" 
                                       class="btn btn-primary"
                                       target="_blank"
                                       title="In hóa đơn">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal chi tiết đơn hàng -->
<div class="modal fade" id="orderModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết đơn hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetails">
            </div>
        </div>
    </div>
</div>

<?php include('../includes/ad-footer.php'); ?>

<script>
$(document).ready(function() {
    // Initialize DataTable with enhanced features
    $('#ordersTable').DataTable({
        "responsive": true,
        "order": [[2, "desc"]],
        "pageLength": 25,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json"
        },
        "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rtip',
        "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
        "columnDefs": [
            { "orderable": false, "targets": [6] },
            { "searchable": false, "targets": [6] }
        ],
        "drawCallback": function() {
            // Reinitialize tooltips after table redraw
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Enhanced status update with animation
    $('.status-select').change(function() {
        const $this = $(this);
        const orderId = $this.data('order-id');
        const newStatus = $this.val();
        const oldStatus = $this.data('old-status');
        
        if (confirm('Bạn có chắc muốn cập nhật trạng thái đơn hàng?')) {
            // Add loading state
            $this.prop('disabled', true);
            
            $.post('../controllers/OrderController.php', {
                action: 'update_status',
                order_id: orderId,
                status: newStatus
            })
            .done(function(response) {
                if (response.success) {
                    toastr.success('Cập nhật trạng thái thành công');
                    // Animate stats update
                    updateStatsWithAnimation();
                    // Update row color based on new status
                    updateRowStatus($this.closest('tr'), newStatus);
                } else {
                    toastr.error('Có lỗi xảy ra');
                    $this.val(oldStatus);
                }
            })
            .fail(function() {
                toastr.error('Có lỗi xảy ra khi cập nhật trạng thái');
                $this.val(oldStatus);
            })
            .always(function() {
                $this.prop('disabled', false);
            });
        } else {
            $this.val(oldStatus);
        }
    }).each(function() {
        $(this).data('old-status', $(this).val());
    });

    // Enhanced order details modal
    $('.view-order').click(function() {
        const orderId = $(this).data('order-id');
        const $modal = $('#orderModal');
        const $modalBody = $('#orderDetails');
        
        $modalBody.html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Đang tải thông tin đơn hàng...</p>
            </div>
        `);
        
        $modal.modal('show');
        
        $.get(`../controllers/OrderController.php?action=get_details&order_id=${orderId}`)
            .done(function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        renderOrderDetails(data.data);
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
                    $modalBody.html(response);
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

    // Helper functions
    function updateStatsWithAnimation() {
        $('.stats-card h3').each(function() {
            $(this).addClass('updating');
            setTimeout(() => $(this).removeClass('updating'), 1000);
        });
        setTimeout(() => location.reload(), 1000);
    }

    function updateRowStatus($row, status) {
        const statusColors = {
            'choxacnhan': '#ffd700',
            'daxacnhan': '#3498db',
            'danggiao': '#2ecc71',
            'dagiao': '#27ae60',
            'dahuy': '#e74c3c'
        };
        
        $row.css('background-color', `${statusColors[status]}15`);
        setTimeout(() => $row.css('background-color', ''), 1000);
    }

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>