<?php
// Prevent direct access
if (!defined('ADMIN_DASHBOARD')) {
    exit('Direct access not permitted');
}

// Count active vouchers
$activeVouchersSql = "SELECT COUNT(*) as count FROM vouchers WHERE is_used = 0 AND expires_at > NOW()";
$activeVouchersCount = $conn->query($activeVouchersSql)->fetch_assoc()['count'];

// Count used vouchers
$usedVouchersSql = "SELECT COUNT(*) as count FROM vouchers WHERE is_used = 1";
$usedVouchersCount = $conn->query($usedVouchersSql)->fetch_assoc()['count'];

// Count expired vouchers
$expiredVouchersSql = "SELECT COUNT(*) as count FROM vouchers WHERE expires_at < NOW() AND is_used = 0";
$expiredVouchersCount = $conn->query($expiredVouchersSql)->fetch_assoc()['count'];

// Get soon-to-expire vouchers (next 3 days)
$soon3days = date('Y-m-d H:i:s', strtotime('+3 days'));
$soonExpiringSql = "SELECT COUNT(*) as count FROM vouchers 
                   WHERE is_used = 0 
                   AND expires_at > NOW() 
                   AND expires_at < '$soon3days'";
$soonExpiringCount = $conn->query($soonExpiringSql)->fetch_assoc()['count'];
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-percent me-2"></i>Trạng Thái Mã Giảm Giá</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 col-6 text-center mb-3">
                <div class="p-3 rounded bg-primary bg-opacity-10">
                    <h3><?= $activeVouchersCount ?></h3>
                    <p class="mb-0 small">Mã còn hiệu lực</p>
                </div>
            </div>
            <div class="col-md-3 col-6 text-center mb-3">
                <div class="p-3 rounded bg-success bg-opacity-10">
                    <h3><?= $usedVouchersCount ?></h3>
                    <p class="mb-0 small">Đã sử dụng</p>
                </div>
            </div>
            <div class="col-md-3 col-6 text-center mb-3">
                <div class="p-3 rounded bg-danger bg-opacity-10">
                    <h3><?= $expiredVouchersCount ?></h3>
                    <p class="mb-0 small">Đã hết hạn</p>
                </div>
            </div>
            <div class="col-md-3 col-6 text-center mb-3">
                <div class="p-3 rounded bg-warning bg-opacity-10">
                    <h3><?= $soonExpiringCount ?></h3>
                    <p class="mb-0 small">Sắp hết hạn</p>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-2">
            <a href="manage-vouchers.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-list me-1"></i> Xem tất cả
            </a>
            <a href="add-voucher.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Tạo mã mới
            </a>
        </div>
    </div>
</div>
