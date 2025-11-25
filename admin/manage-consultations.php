<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../controllers/ConsultationController.php';

// Kiểm tra quyền admin
requireAdmin();

$controller = new ConsultationController();

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $result = $controller->updateStatus(
            $_POST['id'],
            $_POST['trang_thai'],
            $_SESSION['user_id'],
            $_POST['ghi_chu_duoc_si'] ?? null
        );
        if ($result['success']) {
            header("Location: manage-consultations.php?success=" . urlencode($result['message']));
        } else {
            header("Location: manage-consultations.php?error=" . urlencode($result['error']));
        }
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $controller->delete($_POST['id']);
        header("Location: manage-consultations.php?success=" . urlencode('Đã xóa yêu cầu'));
        exit;
    }
}

// Lấy filters
$filters = [
    'trang_thai' => $_GET['trang_thai'] ?? '',
    'search' => $_GET['search'] ?? '',
    'from_date' => $_GET['from_date'] ?? '',
    'to_date' => $_GET['to_date'] ?? ''
];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

$allRequests = $controller->getAll($filters);
$totalItems = count($allRequests);
$totalPages = ceil($totalItems / $limit);
$requests = array_slice($allRequests, $offset, $limit);

// Đếm số lượng theo trạng thái
$countAll = $controller->countByStatus();
$countPending = $controller->countByStatus('cho_xu_ly');
$countProcessing = $controller->countByStatus('dang_xu_ly');
$countCompleted = $controller->countByStatus('da_hoan_thanh');

include('../includes/ad-header.php');
include('../includes/ad-sidebar.php');
?>

<style>
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
    color: white;
}

.status-waiting {
    --color-start: #ffd700;
    --color-end: #ffa500;
}

.status-processing {
    --color-start: #3498db;
    --color-end: #2980b9;
}

.status-completed {
    --color-start: #2ecc71;
    --color-end: #27ae60;
}

.status-all {
    --color-start: #9b59b6;
    --color-end: #8e44ad;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.8rem;
}

.table-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table th {
    background: linear-gradient(135deg, #13b0c9, #3498db);
    color: white;
    font-weight: 600;
    padding: 15px;
}

.table td {
    vertical-align: middle;
    padding: 12px 15px;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.filter-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.prescription-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
}

.modal-img {
    max-width: 100%;
    border-radius: 10px;
}

.action-btn {
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin: 2px;
}

.page-header {
    margin-bottom: 25px;
}

.page-header h2 {
    color: #333;
    font-weight: 700;
}
</style>

<div class="content-wrapper">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-clipboard-list text-primary"></i> Quản Lý Yêu Cầu Tư Vấn</h2>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon status-all"><i class="fas fa-list"></i></div>
                    <div>
                        <h3 class="mb-0"><?php echo $countAll; ?></h3>
                        <small class="text-muted">Tổng yêu cầu</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon status-waiting"><i class="fas fa-clock"></i></div>
                    <div>
                        <h3 class="mb-0"><?php echo $countPending; ?></h3>
                        <small class="text-muted">Chờ xử lý</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon status-processing"><i class="fas fa-spinner"></i></div>
                    <div>
                        <h3 class="mb-0"><?php echo $countProcessing; ?></h3>
                        <small class="text-muted">Đang xử lý</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon status-completed"><i class="fas fa-check"></i></div>
                    <div>
                        <h3 class="mb-0"><?php echo $countCompleted; ?></h3>
                        <small class="text-muted">Hoàn thành</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" class="form-control" name="search" placeholder="Tên, SĐT..."
                    value="<?php echo htmlspecialchars($filters['search']); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Trạng thái</label>
                <select class="form-select" name="trang_thai">
                    <option value="">Tất cả</option>
                    <option value="cho_xu_ly" <?php echo $filters['trang_thai'] === 'cho_xu_ly' ? 'selected' : ''; ?>>Chờ xử lý</option>
                    <option value="dang_xu_ly" <?php echo $filters['trang_thai'] === 'dang_xu_ly' ? 'selected' : ''; ?>>Đang xử lý</option>
                    <option value="da_hoan_thanh" <?php echo $filters['trang_thai'] === 'da_hoan_thanh' ? 'selected' : ''; ?>>Hoàn thành</option>
                    <option value="da_huy" <?php echo $filters['trang_thai'] === 'da_huy' ? 'selected' : ''; ?>>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Từ ngày</label>
                <input type="date" class="form-control" name="from_date"
                    value="<?php echo htmlspecialchars($filters['from_date']); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Đến ngày</label>
                <input type="date" class="form-control" name="to_date"
                    value="<?php echo htmlspecialchars($filters['to_date']); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Lọc
                </button>
                <a href="manage-consultations.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Khách hàng</th>
                    <th>SĐT</th>
                    <th>Ghi chú</th>
                    <th>Toa thuốc</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <span class="text-muted">Không có yêu cầu nào</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><strong>#<?php echo $request['id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($request['ho_ten']); ?></strong>
                                <?php if ($request['ten_nguoidung']): ?>
                                    <br><small class="text-muted">User: <?php echo htmlspecialchars($request['ten_nguoidung']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="tel:<?php echo $request['so_dien_thoai']; ?>">
                                    <?php echo htmlspecialchars($request['so_dien_thoai']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($request['ghi_chu']): ?>
                                    <span title="<?php echo htmlspecialchars($request['ghi_chu']); ?>">
                                        <?php echo htmlspecialchars(substr($request['ghi_chu'], 0, 30)); ?>
                                        <?php if (strlen($request['ghi_chu']) > 30) echo '...'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($request['hinh_anh_toa']): ?>
                                    <img src="/assets/images/consultation/<?php echo $request['hinh_anh_toa']; ?>"
                                        class="prescription-thumb"
                                        onclick="showImage('/assets/images/consultation/<?php echo $request['hinh_anh_toa']; ?>')"
                                        alt="Toa thuốc">
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge status-badge <?php echo ConsultationController::getStatusBadgeClass($request['trang_thai']); ?>">
                                    <?php echo ConsultationController::getStatusName($request['trang_thai']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($request['ngay_tao'])); ?>
                                <br>
                                <small class="text-muted"><?php echo date('H:i', strtotime($request['ngay_tao'])); ?></small>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm action-btn" onclick="viewDetail(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-success btn-sm action-btn" onclick="updateStatus(<?php echo $request['id']; ?>, '<?php echo $request['trang_thai']; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm action-btn" onclick="deleteRequest(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalItems > 0): ?>
    <div class="p-3">
      <?php if ($totalPages > 1): ?>
      <nav>
        <ul class="pagination justify-content-center mb-0">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($filters['search']) ?>&trang_thai=<?= urlencode($filters['trang_thai']) ?>&from_date=<?= urlencode($filters['from_date']) ?>&to_date=<?= urlencode($filters['to_date']) ?>">Trước</a>
          </li>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&trang_thai=<?= urlencode($filters['trang_thai']) ?>&from_date=<?= urlencode($filters['from_date']) ?>&to_date=<?= urlencode($filters['to_date']) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($filters['search']) ?>&trang_thai=<?= urlencode($filters['trang_thai']) ?>&from_date=<?= urlencode($filters['from_date']) ?>&to_date=<?= urlencode($filters['to_date']) ?>">Sau</a>
          </li>
        </ul>
      </nav>
      <?php endif; ?>
      <div class="text-center <?= $totalPages > 1 ? 'mt-2' : '' ?>">
        <small class="text-muted">Trang <?= $page ?> / <?= $totalPages ?> (Tổng <?= $totalItems ?> mục)</small>
      </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal xem chi tiết -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-alt"></i> Chi tiết yêu cầu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Modal cập nhật trạng thái -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="statusRequestId">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Cập nhật trạng thái</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="trang_thai" id="statusSelect" required>
                            <option value="cho_xu_ly">Chờ xử lý</option>
                            <option value="dang_xu_ly">Đang xử lý</option>
                            <option value="da_hoan_thanh">Hoàn thành</option>
                            <option value="da_huy">Đã hủy</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú dược sĩ</label>
                        <textarea class="form-control" name="ghi_chu_duoc_si" rows="3"
                            placeholder="Ghi chú phản hồi cho khách hàng..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xem ảnh -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hình ảnh toa thuốc</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" class="modal-img" src="" alt="Toa thuốc">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewDetail(id) {
    fetch('get-consultation-detail.php?id=' + id)
        .then(response => response.text())
        .then(html => {
            document.getElementById('detailContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        });
}

function updateStatus(id, currentStatus) {
    document.getElementById('statusRequestId').value = id;
    document.getElementById('statusSelect').value = currentStatus;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function deleteRequest(id) {
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: 'Bạn có chắc muốn xóa yêu cầu này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function showImage(src) {
    document.getElementById('modalImage').src = src;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}
</script>
</body>
</html>
