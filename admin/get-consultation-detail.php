<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../controllers/ConsultationController.php';

requireAdmin();

if (!isset($_GET['id'])) {
    echo '<p class="text-danger">ID không hợp lệ</p>';
    exit;
}

$controller = new ConsultationController();
$request = $controller->getById($_GET['id']);

if (!$request) {
    echo '<p class="text-danger">Không tìm thấy yêu cầu</p>';
    exit;
}
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-muted mb-3"><i class="fas fa-user"></i> Thông tin liên hệ</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Họ tên:</th>
                <td><?php echo htmlspecialchars($request['ho_ten']); ?></td>
            </tr>
            <tr>
                <th>Số điện thoại:</th>
                <td>
                    <a href="tel:<?php echo $request['so_dien_thoai']; ?>" class="text-primary">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['so_dien_thoai']); ?>
                    </a>
                </td>
            </tr>
            <?php if ($request['ten_nguoidung']): ?>
            <tr>
                <th>Tài khoản:</th>
                <td><?php echo htmlspecialchars($request['ten_nguoidung']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Trạng thái:</th>
                <td>
                    <span class="badge <?php echo ConsultationController::getStatusBadgeClass($request['trang_thai']); ?>">
                        <?php echo ConsultationController::getStatusName($request['trang_thai']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Ngày gửi:</th>
                <td><?php echo date('d/m/Y H:i', strtotime($request['ngay_tao'])); ?></td>
            </tr>
        </table>

        <?php if ($request['ghi_chu']): ?>
        <h6 class="text-muted mb-2 mt-4"><i class="fas fa-comment"></i> Ghi chú khách hàng</h6>
        <div class="alert alert-light">
            <?php echo nl2br(htmlspecialchars($request['ghi_chu'])); ?>
        </div>
        <?php endif; ?>

        <?php if ($request['ghi_chu_duoc_si']): ?>
        <h6 class="text-muted mb-2 mt-4"><i class="fas fa-user-md"></i> Phản hồi dược sĩ</h6>
        <div class="alert alert-success">
            <?php echo nl2br(htmlspecialchars($request['ghi_chu_duoc_si'])); ?>
            <?php if ($request['ten_nhanvien']): ?>
            <hr>
            <small>Dược sĩ: <?php echo htmlspecialchars($request['ten_nhanvien']); ?></small>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-6">
        <?php if ($request['hinh_anh_toa']): ?>
        <h6 class="text-muted mb-3"><i class="fas fa-image"></i> Hình ảnh toa thuốc</h6>
        <img src="/assets/images/consultation/<?php echo $request['hinh_anh_toa']; ?>"
            class="img-fluid rounded mb-3" style="max-height: 250px" alt="Toa thuốc">
        <?php endif; ?>

        <?php if (!empty($request['chi_tiet'])): ?>
        <h6 class="text-muted mb-3"><i class="fas fa-pills"></i> Danh sách thuốc cần tư vấn</h6>
        <div class="list-group">
            <?php foreach ($request['chi_tiet'] as $item): ?>
            <div class="list-group-item">
                <div class="d-flex justify-content-between">
                    <strong><?php echo htmlspecialchars($item['ten_thuoc'] ?: 'Chưa xác định'); ?></strong>
                    <span class="badge bg-primary">x<?php echo $item['so_luong']; ?></span>
                </div>
                <?php if ($item['trieu_chung']): ?>
                <small class="text-info">
                    <i class="fas fa-heartbeat"></i> <?php echo htmlspecialchars($item['trieu_chung']); ?>
                </small>
                <?php endif; ?>
                <?php if ($item['ghi_chu']): ?>
                <small class="d-block text-muted">
                    <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($item['ghi_chu']); ?>
                </small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-light text-center">
            <i class="fas fa-info-circle"></i> Không có danh sách thuốc cụ thể
        </div>
        <?php endif; ?>
    </div>
</div>

<hr>

<div class="d-flex justify-content-end gap-2">
    <a href="tel:<?php echo $request['so_dien_thoai']; ?>" class="btn btn-success">
        <i class="fas fa-phone"></i> Gọi điện
    </a>
    <button type="button" class="btn btn-primary" onclick="updateStatus(<?php echo $request['id']; ?>, '<?php echo $request['trang_thai']; ?>')" data-bs-dismiss="modal">
        <i class="fas fa-edit"></i> Cập nhật trạng thái
    </button>
</div>
