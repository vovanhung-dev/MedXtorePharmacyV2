<?php
require_once '../controllers/InventoryController.php';
$inventoryCtrl = new InventoryController();
$khohang = $inventoryCtrl->getAll();

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
              <tr>
                <td class="text-center"><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($item['ten_thuoc']) ?></td>
                <td><?= htmlspecialchars($item['ten_donvi']) ?></td>
                <td><?= htmlspecialchars($item['ten_ncc']) ?></td>
                <td class="text-end"><?= number_format($item['soluong']) ?></td>
                <td class="text-end text-danger fw-bold"><?= number_format($item['gia'], 0, ',', '.') ?> đ</td>
                <td class="text-center"><?= date('d/m/Y', strtotime($item['hansudung'])) ?></td>
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
  </div>
</div>

<?php require_once '../includes/ad-footer.php'; ?>
