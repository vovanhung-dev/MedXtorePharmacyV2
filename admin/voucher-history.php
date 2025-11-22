<?php
require_once('../admin/admin-auth.php');
require_once __DIR__ . '/../config/config.php';
include_once('../includes/ad-header.php');
include_once('../includes/ad-sidebar.php');

$page_title = "Lịch sử sử dụng mã giảm giá";

// Truy vấn mã đã sử dụng
$sql = "SELECT v.*, n.ten AS used_by_name, v.used_at
        FROM vouchers v
        LEFT JOIN nguoidung n ON v.used_by = n.id
        WHERE v.is_used = 1
        ORDER BY v.used_at DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid">
    <div class="row min-vh-100">
        <div class="col-md-9 ms-auto p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-history me-2"></i><?= $page_title ?></h2>
                <a href="manage-vouchers.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại quản lý mã giảm giá
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã giảm giá</th>
                                        <th>Giảm giá</th>
                                        <th>Người sử dụng</th>
                                        <th>Thời gian sử dụng</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary code-badge">
                                                    <?= htmlspecialchars($row['code']) ?>
                                                </span>
                                            </td>
                                            <td><?= $row['discount_percent'] ?>%</td>
                                            <td><?= htmlspecialchars($row['used_by_name'] ?? 'Không xác định') ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($row['used_at'])) ?></td>
                                            <td><span class="badge bg-success">Đã sử dụng</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Chưa có mã giảm giá nào được sử dụng.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Ghi chú</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">Lịch sử giúp bạn theo dõi các mã đã được áp dụng. Mã giảm giá sẽ được lưu lại trước khi bị xóa.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .code-badge {
        font-family: monospace;
        letter-spacing: 1px;
    }
</style>

<?php include_once('../includes/ad-footer.php'); ?>
