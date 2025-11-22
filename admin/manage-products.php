<?php include_once('../includes/ad-header.php'); ?>
<?php include_once('../includes/ad-sidebar.php'); ?>
<?php
require_once '../controllers/ProductController.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Lấy danh sách loại thuốc
$loaiStmt = $conn->query("SELECT id, ten_loai FROM loai_thuoc");
$dsLoaiThuoc = $loaiStmt->fetchAll(PDO::FETCH_ASSOC);

$productController = new ProductController();

// Xử lý lọc
$search = $_GET['search'] ?? '';
$loai = $_GET['loai'] ?? '';
$hsd = $_GET['hsd'] ?? '';
$products = $productController->filter($search, $loai, $hsd);
?>

<!-- Main Content -->
<div class="main-content">

<?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($_GET['success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
  </div>
<?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Quản Lý Thuốc</h3>
    <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
      <i class="fas fa-plus me-2"></i>Thêm Thuốc
    </button>
  </div>

  <!-- Search and Filter -->
  <div class="card p-4 shadow-sm mb-4">
    <form class="row g-3" method="GET">
      <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Tìm theo tên thuốc..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-3">
        <select class="form-select" name="loai">
          <option value="">Tất cả loại</option>
          <?php foreach ($dsLoaiThuoc as $loaiRow): ?>
            <option value="<?= htmlspecialchars($loaiRow['ten_loai']) ?>" <?= $loai == $loaiRow['ten_loai'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($loaiRow['ten_loai']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <input type="date" name="hsd" class="form-control" value="<?= htmlspecialchars($hsd) ?>">
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-primary w-100">Lọc</button>
      </div>
    </form>
  </div>

  <!-- Medicine Table -->
  <div class="card shadow-sm fade-in">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Hình ảnh</th>
            <th>Tên thuốc</th>
            <th>Loại</th>
            <th>Đơn vị</th>
            <th>Giá</th>
            <th>Mô tả</th>
            <th>Hạn sử dụng</th>
            <th>Tồn kho</th>
            <th class="text-end">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $index => $p): ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td>
                <img src="/MedXtorePharmacy/assets/images/product-images/<?= htmlspecialchars($p['hinhanh']) ?>"
                     alt="<?= htmlspecialchars($p['ten_thuoc']) ?>"
                     style="width: 60px; height: 60px; object-fit: contain; border-radius: 6px; background: #f8f8f8;">
              </td>
              <td><?= htmlspecialchars($p['ten_thuoc']) ?></td>
              <td><?= htmlspecialchars($p['ten_loai']) ?></td>
              <td><?= htmlspecialchars($p['ten_donvi']) ?></td>
              <td><?= number_format($p['gia'], 0, ',', '.') ?>đ</td>
              <td><?= htmlspecialchars(mb_strimwidth(strip_tags($p['mota'] ?? ''), 0, 60, '...')) ?></td> <!-- ✅ CỘT MÔ TẢ -->
              <td><?= htmlspecialchars($p['hansudung']) ?></td>
              <td><?= htmlspecialchars($p['soluong']) ?></td>
              <td class="text-end">
              <button 
  class="btn btn-sm btn-outline-secondary"
  data-bs-toggle="modal"
  data-bs-target="#editMedicineModal"
  data-id="<?= $p['id'] ?>"
  data-ten="<?= htmlspecialchars($p['ten_thuoc']) ?>"
  data-loai="<?= htmlspecialchars($p['ten_loai']) ?>"
  data-mota="<?= htmlspecialchars($p['mota']) ?>"
  data-hinhanh="<?= htmlspecialchars($p['hinhanh']) ?>"
>
  <i class="fas fa-edit"></i> <!-- ✅ Icon đã quay lại -->
</button>

<!-- Nút xoá -->
<form action="delete-product.php" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa thuốc này không?');">
    <input type="hidden" name="thuoc_id" value="<?= $p['id'] ?>">
    <button type="submit" class="btn btn-sm btn-outline-danger">
      <i class="fas fa-trash-alt"></i>
    </button>
  </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add Medicine Modal -->
  <div class="modal fade" id="addMedicineModal" tabindex="-1" aria-labelledby="addMedicineLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" action="add-product.php" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title" id="addMedicineLabel">Thêm Thuốc Mới</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Tên thuốc</label>
                <input type="text" name="ten_thuoc" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Loại thuốc</label>
                <select name="loai_id" class="form-select" required>
                  <option value="">-- Chọn loại thuốc --</option>
                  <?php foreach ($dsLoaiThuoc as $loai): ?>
                    <option value="<?= $loai['id'] ?>"><?= $loai['ten_loai'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-12">
                <label class="form-label">Mô tả</label>
                <textarea name="mota" class="form-control" rows="3"></textarea>
              </div>
              <div class="col-md-12">
                <label class="form-label">Hình ảnh sản phẩm</label>
                <input type="file" name="hinhanh" class="form-control" accept="image/*" required>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="submit" class="btn btn-primary">Lưu thuốc</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editMedicineModal" tabindex="-1" aria-labelledby="editMedicineLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="edit-product.php" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editMedicineLabel">Cập Nhật Thông Tin Thuốc</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="thuoc_id" id="edit-id">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tên thuốc</label>
              <input type="text" name="ten_thuoc" id="edit-ten" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Loại thuốc</label>
              <select name="loai_id" id="edit-loai" class="form-select" required>
                <?php foreach ($dsLoaiThuoc as $loai): ?>
                  <option value="<?= $loai['id'] ?>"><?= $loai['ten_loai'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Mô tả</label>
              <textarea name="mota" id="edit-mota" class="form-control" rows="3"></textarea>
            </div>

            <div class="col-md-12">
              <label class="form-label">Hình ảnh (nếu muốn thay)</label>
              <input type="file" name="hinhanh" class="form-control" accept="image/*">
              <input type="hidden" name="hinhanh_cu" id="edit-hinhanh-cu"> 
              <div class="mt-2">
                  <label class="form-label">Ảnh hiện tại</label><br>
                  <img id="current-image-preview" src="" class="img-thumbnail" style="max-height: 120px;">
                </div><!-- Lưu ảnh cũ nếu không thay -->
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Lưu thay đổi</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
        </div>
      </div>
    </form>
  </div>
</div>



<?php include_once('../includes/ad-footer.php'); ?>

<script>
  const editModal = document.getElementById('editMedicineModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;

    const id = button.getAttribute('data-id');
    const ten = button.getAttribute('data-ten');
    const mota = button.getAttribute('data-mota');
    const loai = button.getAttribute('data-loai');
    const hinhanh = button.getAttribute('data-hinhanh'); // hình ảnh cũ

    // Gán dữ liệu vào form
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-ten').value = ten;
    document.getElementById('edit-mota').value = mota;
    document.getElementById('edit-hinhanh-cu').value = hinhanh;

    const selectLoai = document.getElementById('edit-loai');
    for (let i = 0; i < selectLoai.options.length; i++) {
      if (selectLoai.options[i].text === loai) {
        selectLoai.selectedIndex = i;
        break;
      }
    }

    // ✅ Hiển thị ảnh hiện tại (nếu bạn có khu vực hiển thị ảnh)
    const preview = document.getElementById('current-image-preview');
if (preview && hinhanh) {
  preview.src = `/MedXtorePharmacy/assets/images/product-images/${hinhanh}`;
}
  });
</script>

