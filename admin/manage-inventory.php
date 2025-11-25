<?php
require_once '../controllers/InventoryController.php';
$inventoryCtrl = new InventoryController();

// Pagination and search parameters
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

$allKhohang = $inventoryCtrl->getAll();

// Filter by search
if (!empty($search)) {
    $khohang = array_filter($allKhohang, function($item) use ($search) {
        return stripos($item['ten_thuoc'], $search) !== false ||
               stripos($item['ten_ncc'], $search) !== false;
    });
} else {
    $khohang = $allKhohang;
}

// Calculate pagination
$totalItems = count($khohang);
$totalPages = ceil($totalItems / $limit);
$khohang = array_slice($khohang, $offset, $limit);

$lowStockItems = $inventoryCtrl->getLowStockItems(20); // Ngưỡng: 20 đơn vị
$expiringItems = $inventoryCtrl->getExpiringItems(30); // Ngưỡng: 30 ngày

require_once '../includes/ad-header.php';
require_once '../includes/ad-sidebar.php';
?>

<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Quản lý kho hàng</h3>
    <a href="import-inventory.php" class="btn btn-primary">
      <i class="fas fa-plus me-1"></i> Nhập kho mới
    </a>
  </div>

  <?php if (!empty($expiringItems)): ?>
  <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3" role="alert">
    <div class="d-flex align-items-start">
      <div class="me-3">
        <i class="fas fa-calendar-times fa-2x text-danger"></i>
      </div>
      <div class="flex-grow-1">
        <h5 class="alert-heading mb-2">
          <i class="fas fa-exclamation-circle me-2"></i>Cảnh báo: Thuốc hết hạn hoặc gần hết hạn
        </h5>
        <p class="mb-2">Có <strong><?= count($expiringItems) ?></strong> lô thuốc sắp hết hạn hoặc đã hết hạn (trong vòng 30 ngày):</p>
        <ul class="mb-0 ps-3">
          <?php foreach ($expiringItems as $item): ?>
          <li class="mb-1">
            <strong><?= htmlspecialchars($item['ten_thuoc']) ?></strong>
            (<?= htmlspecialchars($item['ten_donvi']) ?>) -
            <?php if ($item['ngay_conlai'] < 0): ?>
              <span class="badge bg-dark">Đã hết hạn <?= abs($item['ngay_conlai']) ?> ngày</span>
            <?php elseif ($item['ngay_conlai'] == 0): ?>
              <span class="badge bg-danger">Hết hạn hôm nay</span>
            <?php elseif ($item['ngay_conlai'] <= 7): ?>
              <span class="badge bg-danger">Còn <?= $item['ngay_conlai'] ?> ngày</span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">Còn <?= $item['ngay_conlai'] ?> ngày</span>
            <?php endif; ?>
            <span class="text-muted ms-2">
              <i class="fas fa-calendar-alt"></i> HSD: <?= date('d/m/Y', strtotime($item['hansudung'])) ?>
            </span>
            <span class="text-muted ms-2">
              <i class="fas fa-box"></i> SL: <?= number_format($item['soluong']) ?>
            </span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php endif; ?>

  <?php if (!empty($lowStockItems)): ?>
  <div class="alert alert-warning alert-dismissible fade show shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-start">
      <div class="me-3">
        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
      </div>
      <div class="flex-grow-1">
        <h5 class="alert-heading mb-2">
          <i class="fas fa-bell me-2"></i>Cảnh báo: Thuốc sắp hết hàng
        </h5>
        <p class="mb-2">Có <strong><?= count($lowStockItems) ?></strong> loại thuốc có số lượng tồn kho thấp (dưới 20 đơn vị):</p>
        <ul class="mb-0 ps-3">
          <?php foreach ($lowStockItems as $item): ?>
          <li class="mb-1">
            <strong><?= htmlspecialchars($item['ten_thuoc']) ?></strong>
            (<?= htmlspecialchars($item['ten_donvi']) ?>) -
            Còn lại: <span class="badge bg-danger"><?= number_format($item['tong_soluong']) ?></span>
            <?php if ($item['hansudung_ganhat']): ?>
              <span class="text-muted ms-2">
                <i class="fas fa-calendar-alt"></i> HSD gần nhất: <?= date('d/m/Y', strtotime($item['hansudung_ganhat'])) ?>
              </span>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php endif; ?>

  <!-- Search -->
  <div class="card p-3 shadow-sm mb-3">
    <form method="GET" class="row g-2">
      <div class="col-md-10">
        <input type="text" name="search" class="form-control" placeholder="Tìm kiếm thuốc hoặc nhà cung cấp..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Tìm</button>
      </div>
    </form>
  </div>

  <div class="card shadow-sm fade-in">
    <div class="table-responsive">
      <table class="table table-bordered align-middle table-hover mb-0">
        <thead class="table-light text-center">
          <tr>
            <th>#</th>
            <th>Tên thuốc</th>
            <th>Đơn vị</th>
            <th>Nhà cung cấp</th>
            <th>Số lượng</th>
            <th>Giá nhập</th>
            <th>Hạn sử dụng</th>
            <th>Ngày cập nhật</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($khohang)): ?>
            <?php foreach ($khohang as $i => $item): ?>
              <?php
                // Kiểm tra số lượng tồn kho
                $rowClass = '';
                $stockBadge = '';
                if ($item['soluong'] == 0) {
                  $rowClass = 'table-danger';
                  $stockBadge = '<span class="badge bg-danger ms-2">Hết hàng</span>';
                } elseif ($item['soluong'] < 10) {
                  $rowClass = 'table-warning';
                  $stockBadge = '<span class="badge bg-warning text-dark ms-2">Rất thấp</span>';
                } elseif ($item['soluong'] < 20) {
                  $rowClass = 'table-light border-warning';
                  $stockBadge = '<span class="badge bg-warning text-dark ms-2">Gần hết</span>';
                }

                // Kiểm tra hạn sử dụng
                $expiryBadge = '';
                $today = new DateTime();
                $expiryDate = new DateTime($item['hansudung']);
                $daysUntilExpiry = $today->diff($expiryDate)->days;
                $isExpired = $expiryDate < $today;

                if ($isExpired) {
                  if ($rowClass == '') $rowClass = 'table-danger';
                  $expiryBadge = '<span class="badge bg-dark ms-2"><i class="fas fa-ban"></i> Đã hết hạn</span>';
                } elseif ($daysUntilExpiry == 0) {
                  if ($rowClass == '') $rowClass = 'table-danger';
                  $expiryBadge = '<span class="badge bg-danger ms-2"><i class="fas fa-exclamation-circle"></i> Hết hạn hôm nay</span>';
                } elseif ($daysUntilExpiry <= 7) {
                  if ($rowClass == '') $rowClass = 'table-danger';
                  $expiryBadge = '<span class="badge bg-danger ms-2"><i class="fas fa-clock"></i> Còn ' . $daysUntilExpiry . ' ngày</span>';
                } elseif ($daysUntilExpiry <= 30) {
                  if ($rowClass == '' || $rowClass == 'table-light border-warning') $rowClass = 'table-warning';
                  $expiryBadge = '<span class="badge bg-warning text-dark ms-2"><i class="fas fa-calendar-alt"></i> Còn ' . $daysUntilExpiry . ' ngày</span>';
                }
              ?>
              <tr class="<?= $rowClass ?>">
                <td class="text-center"><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($item['ten_thuoc']) ?></td>
                <td><?= htmlspecialchars($item['ten_donvi']) ?></td>
                <td><?= htmlspecialchars($item['ten_ncc']) ?></td>
                <td class="text-end">
                  <?= number_format($item['soluong']) ?>
                  <?= $stockBadge ?>
                </td>
                <td class="text-end text-danger fw-bold"><?= number_format($item['gia'], 0, ',', '.') ?> đ</td>
                <td class="text-center">
                  <?= date('d/m/Y', strtotime($item['hansudung'])) ?>
                  <?= $expiryBadge ?>
                </td>
                <td class="text-center"><?= date('d/m/Y H:i', strtotime($item['capnhat'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-4">Không có dữ liệu kho hàng.</td>
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
        <small class="text-muted">Trang <?= $page ?> / <?= $totalPages ?> (Tổng <?= $totalItems ?> mục)</small>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once '../includes/ad-footer.php'; ?>
