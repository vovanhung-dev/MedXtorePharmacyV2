<?php
require_once '../controllers/InventoryController.php';

$ctrl = new InventoryController();

// Lấy danh sách thuốc, đơn vị tính, nhà cung cấp
$dsThuoc = $ctrl->getThuocList();
$dsDonVi = $ctrl->getDonViList();
$dsNCC   = $ctrl->getNhaCungCapList();

// Xử lý khi submit form nhập kho
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctrl->importInventory($_POST);
    header('Location: manage-inventory.php?success=1');
    exit;
}

require_once '../includes/ad-header.php';
require_once '../includes/ad-sidebar.php';
?>

<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Nhập kho mới</h3>
    <a href="manage-inventory.php" class="btn btn-outline-secondary">
      <i class="fas fa-chevron-left me-1"></i> Quay lại danh sách
    </a>
  </div>

  <form method="POST" class="card p-4 shadow-sm border-0 bg-white rounded-4">
    <div class="row g-4">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Thuốc</label>
        <select name="thuoc_id" class="form-select" required>
          <option value="">-- Chọn thuốc --</option>
          <?php foreach ($dsThuoc as $thuoc): ?>
            <option value="<?= $thuoc['id'] ?>"><?= htmlspecialchars($thuoc['ten_thuoc']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold">Đơn vị</label>
        <select name="donvi_id" class="form-select" required>
          <option value="">-- Chọn đơn vị --</option>
          <?php foreach ($dsDonVi as $dv): ?>
            <option value="<?= $dv['id'] ?>"><?= htmlspecialchars($dv['ten_donvi']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold">Nhà cung cấp</label>
        <select name="nhacungcap_id" class="form-select" required>
          <option value="">-- Chọn NCC --</option>
          <?php foreach ($dsNCC as $ncc): ?>
            <option value="<?= $ncc['id'] ?>"><?= htmlspecialchars($ncc['ten_ncc']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold">Giá nhập (VNĐ)</label>
        <input type="number" name="gia" class="form-control" placeholder="Nhập giá" required>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold">Số lượng</label>
        <input type="number" name="soluong" class="form-control" placeholder="Nhập số lượng" required>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold">Hạn sử dụng</label>
        <input type="date" name="hansudung" class="form-control" required>
      </div>

      <div class="col-12 text-end mt-4">
        <button type="submit" class="btn btn-success px-5">
          <i class="fas fa-save me-1"></i> Lưu nhập kho
        </button>
      </div>
    </div>
  </form>
</div>

<?php require_once '../includes/ad-footer.php'; ?>
