<?php
require_once('../admin/admin-auth.php');
require_once __DIR__ . '/../config/config.php';
include_once('../includes/ad-header.php');
include_once('../includes/ad-sidebar.php');

$page_title = "Quản lý mã giảm giá";

// Xóa mã hết hạn hoặc đã dùng
$sql_cleanup = "DELETE FROM vouchers WHERE expires_at < NOW() OR is_used = 1";
$conn->query($sql_cleanup);

// Lấy các voucher còn hiệu lực
$sql = "SELECT * FROM vouchers WHERE is_used = 0 ORDER BY created_at DESC";
$result = $conn->query($sql);

// Xử lý xóa mã
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $conn->real_escape_string($_GET['delete']);
    $sql_delete = "DELETE FROM vouchers WHERE id = '$id'";
    if ($conn->query($sql_delete)) {
        $_SESSION['success'] = "Đã xóa mã giảm giá thành công!";
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra: " . $conn->error;
    }
    header("Location: manage-vouchers.php");
    exit();
}
?>

            
     <div class="col-md-9 ms-auto p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-percent me-2"></i><?= $page_title ?></h2>
                    <a href="add-voucher.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Tạo mã giảm giá mới
                    </a>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Mã giảm giá</th>
                                        <th>Giảm giá</th>
                                        <th>Ngày tạo</th>
                                        <th>Hết hạn</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?= htmlspecialchars($row['code']) ?>
                                                    </span>
                                                </td>
                                                <td><?= $row['discount_percent'] ?>%</td>
                                                <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($row['expires_at'])) ?></td>
                                                <td>
                                                    <a href="edit-voucher.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="manage-vouchers.php?delete=<?= $row['id'] ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>Chưa có mã giảm giá nào khả dụng. Hãy tạo mã giảm giá mới!
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5>Mã giảm giá đã sử dụng</h5>
                            <p class="text-muted">Mã giảm giá đã sử dụng và hết hạn sẽ tự động được xóa khỏi hệ thống.</p>
                            <a href="voucher-history.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-history me-1"></i> Xem lịch sử sử dụng
                            </a>
                        </div>
                    </div>
                </div>
     </div>
    

    
