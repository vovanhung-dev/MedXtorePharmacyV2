<?php
session_start();
require_once __DIR__ . '/../controllers/ConsultationController.php';
require_once __DIR__ . '/../config/database.php';

$controller = new ConsultationController();
$requests = [];
$searchPhone = '';

// Nếu đã đăng nhập thì lấy theo user_id
if (isset($_SESSION['user_id'])) {
    $requests = $controller->getByUserId($_SESSION['user_id']);
}

// Nếu tìm kiếm theo số điện thoại
if (isset($_GET['phone']) && !empty($_GET['phone'])) {
    $searchPhone = trim($_GET['phone']);
    $requests = $controller->getByPhone($searchPhone);
}

// Xem chi tiết yêu cầu
$selectedRequest = null;
if (isset($_GET['id'])) {
    $selectedRequest = $controller->getById($_GET['id']);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
    .requests-section {
        min-height: 100vh;
        padding-bottom: 80px;
        background-color: #f8f9fa;
    }

    .requests-header {
        background: linear-gradient(135deg, #13b0c9 0%, #3498db 100%);
        color: white;
        padding: 2rem 0;
        border-radius: 0 0 20px 20px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .requests-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 1rem;
    }

    .request-item {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        border-left: 4px solid #13b0c9;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .request-item:hover {
        transform: translateX(5px);
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    .request-item.active {
        border-left-color: #28a745;
        background: #e8f5e9;
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
    }

    .detail-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        position: sticky;
        top: 20px;
    }

    .detail-header {
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }

    .medicine-list-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
    }

    .search-box {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 1.5rem;
    }

    .prescription-image {
        max-width: 100%;
        border-radius: 10px;
        border: 1px solid #e9ecef;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
    }

    .empty-state i {
        font-size: 4rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }
</style>

<section class="requests-section">
    <!-- Header -->
    <div class="requests-header text-center">
        <div class="container">
            <h2 class="mb-2"><i class="bi bi-list-check"></i> Đơn yêu cầu của tôi</h2>
            <p class="mb-0 opacity-75">Xem lại các yêu cầu tư vấn thuốc đã gửi</p>
            <a href="/pages/consultation-request.php" class="btn btn-outline-light btn-sm mt-2">
                <i class="bi bi-plus-circle"></i> Gửi yêu cầu mới
            </a>
        </div>
    </div>

    <div class="container">
        <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Form tìm kiếm cho khách vãng lai -->
        <div class="search-box">
            <h5 class="mb-3"><i class="bi bi-search"></i> Tra cứu đơn theo số điện thoại</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <input type="tel" class="form-control" name="phone" placeholder="Nhập số điện thoại đã đăng ký"
                        value="<?php echo htmlspecialchars($searchPhone); ?>" pattern="[0-9]{10,11}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Tra cứu
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Danh sách yêu cầu -->
            <div class="col-lg-5">
                <div class="requests-card">
                    <h5 class="mb-3">
                        <i class="bi bi-clock-history text-primary"></i>
                        Lịch sử yêu cầu
                        <span class="badge bg-secondary"><?php echo count($requests); ?></span>
                    </h5>

                    <?php if (empty($requests)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p class="text-muted">Chưa có yêu cầu nào</p>
                            <a href="/pages/consultation-request.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Gửi yêu cầu đầu tiên
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <div class="request-item <?php echo ($selectedRequest && $selectedRequest['id'] == $request['id']) ? 'active' : ''; ?>"
                                onclick="window.location.href='?id=<?php echo $request['id']; ?><?php echo $searchPhone ? '&phone=' . $searchPhone : ''; ?>'">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>#<?php echo $request['id']; ?></strong>
                                        <span class="badge status-badge <?php echo ConsultationController::getStatusBadgeClass($request['trang_thai']); ?>">
                                            <?php echo ConsultationController::getStatusName($request['trang_thai']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($request['ngay_tao'])); ?>
                                    </small>
                                </div>
                                <div class="mt-2 text-muted small">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($request['ho_ten']); ?>
                                </div>
                                <?php if ($request['ghi_chu']): ?>
                                    <div class="mt-1 text-truncate small">
                                        <i class="bi bi-chat-left-text"></i>
                                        <?php echo htmlspecialchars(substr($request['ghi_chu'], 0, 50)); ?>...
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chi tiết yêu cầu -->
            <div class="col-lg-7">
                <?php if ($selectedRequest): ?>
                    <div class="detail-card">
                        <div class="detail-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-file-text text-primary"></i>
                                    Chi tiết yêu cầu #<?php echo $selectedRequest['id']; ?>
                                </h5>
                                <span class="badge status-badge <?php echo ConsultationController::getStatusBadgeClass($selectedRequest['trang_thai']); ?>">
                                    <?php echo ConsultationController::getStatusName($selectedRequest['trang_thai']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Thông tin liên hệ -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-person-lines-fill"></i> Thông tin liên hệ</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Họ và tên</small>
                                    <p class="mb-2 fw-bold"><?php echo htmlspecialchars($selectedRequest['ho_ten']); ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Số điện thoại</small>
                                    <p class="mb-2 fw-bold"><?php echo htmlspecialchars($selectedRequest['so_dien_thoai']); ?></p>
                                </div>
                            </div>
                            <?php if ($selectedRequest['ghi_chu']): ?>
                                <small class="text-muted">Ghi chú</small>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($selectedRequest['ghi_chu'])); ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Hình ảnh toa thuốc -->
                        <?php if ($selectedRequest['hinh_anh_toa']): ?>
                            <div class="mb-4">
                                <h6 class="text-muted mb-3"><i class="bi bi-image"></i> Hình ảnh toa thuốc</h6>
                                <img src="/assets/images/consultation/<?php echo $selectedRequest['hinh_anh_toa']; ?>"
                                    class="prescription-image" alt="Toa thuốc">
                            </div>
                        <?php endif; ?>

                        <!-- Danh sách thuốc -->
                        <?php if (!empty($selectedRequest['chi_tiet'])): ?>
                            <div class="mb-4">
                                <h6 class="text-muted mb-3"><i class="bi bi-capsule"></i> Thuốc cần tư vấn</h6>
                                <?php foreach ($selectedRequest['chi_tiet'] as $item): ?>
                                    <div class="medicine-list-item">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo htmlspecialchars($item['ten_thuoc'] ?: 'Chưa xác định'); ?></strong>
                                            <span class="badge bg-light text-dark">x<?php echo $item['so_luong']; ?></span>
                                        </div>
                                        <?php if ($item['trieu_chung']): ?>
                                            <small class="text-muted">
                                                <i class="bi bi-activity"></i> <?php echo htmlspecialchars($item['trieu_chung']); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($item['ghi_chu']): ?>
                                            <small class="d-block text-muted">
                                                <i class="bi bi-chat-left-text"></i> <?php echo htmlspecialchars($item['ghi_chu']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Phản hồi dược sĩ -->
                        <?php if ($selectedRequest['ghi_chu_duoc_si']): ?>
                            <div class="mb-4">
                                <h6 class="text-muted mb-3"><i class="bi bi-chat-square-text"></i> Phản hồi từ dược sĩ</h6>
                                <div class="alert alert-success">
                                    <?php echo nl2br(htmlspecialchars($selectedRequest['ghi_chu_duoc_si'])); ?>
                                    <?php if ($selectedRequest['ten_nhanvien']): ?>
                                        <hr>
                                        <small class="text-muted">
                                            <i class="bi bi-person-badge"></i> Dược sĩ: <?php echo htmlspecialchars($selectedRequest['ten_nhanvien']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Thời gian -->
                        <div class="text-muted small">
                            <i class="bi bi-clock"></i> Ngày tạo: <?php echo date('d/m/Y H:i', strtotime($selectedRequest['ngay_tao'])); ?>
                            <?php if ($selectedRequest['ngay_cap_nhat'] != $selectedRequest['ngay_tao']): ?>
                                <br>
                                <i class="bi bi-arrow-clockwise"></i> Cập nhật: <?php echo date('d/m/Y H:i', strtotime($selectedRequest['ngay_cap_nhat'])); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="detail-card text-center">
                        <div class="empty-state">
                            <i class="bi bi-hand-index"></i>
                            <p class="text-muted">Chọn một yêu cầu để xem chi tiết</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
