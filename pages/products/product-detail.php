<?php
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$productController = new ProductController();
$product_id = $_GET['id'] ?? 0;
$product = $productController->getById($product_id);

// Nếu không có sản phẩm
if (!$product) {
  header("Location: /MedXtorePharmacy/pages/products/products.php");
  exit();
}

$donviList = $productController->getDonViTheoThuoc($product['id']);
$relatedProducts = $productController->getRelated($product['id'], $product['loai_id']);
?>

<!-- Chi tiết sản phẩm -->
<section class="container py-5">
  <div class="row align-items-start">
    <!-- Hình ảnh -->
    <div class="col-md-5 mb-4">
      <div class="card shadow-sm border-0 rounded-4">
        <div class="card-img-top-wrapper text-center p-4">
          <img src="/MedXtorePharmacy/assets/images/product-images/<?= htmlspecialchars($product['hinhanh']) ?>" 
               alt="<?= htmlspecialchars($product['ten_thuoc']) ?>" 
               class="img-fluid" style="max-height: 300px; object-fit: contain;">
        </div>
      </div>
    </div>

    <!-- Thông tin sản phẩm -->
    <div class="col-md-7">
      <h2 class="fw-bold mb-2"><?= htmlspecialchars($product['ten_thuoc']) ?></h2>
      <p class="text-muted mb-2">Loại: <?= htmlspecialchars($product['ten_loai']) ?></p>
      <p class="text-muted"><?= htmlspecialchars($product['mota']) ?></p>

      <?php if (!empty($donviList)): ?>
        <form method="POST" action="/MedXtorePharmacy/controllers/CartController.php" class="mt-4">
          <input type="hidden" name="thuoc_id" value="<?= $product['id'] ?>">
          <input type="hidden" name="ten_thuoc" value="<?= htmlspecialchars($product['ten_thuoc']) ?>">
          <input type="hidden" name="hinhanh" value="<?= htmlspecialchars($product['hinhanh']) ?>">
          <input type="hidden" name="ten_donvi" id="ten_donvi">
          <input type="hidden" name="gia" id="gia">

          <label class="form-label fw-semibold">Chọn đơn vị:</label>
          <select class="form-select w-75 mb-3" name="donvi_id" id="donviSelect" onchange="capNhatGiaDonVi()">
  <?php foreach ($donviList as $dv): ?>
    <option 
  value="<?= htmlspecialchars($dv['donvi_id']) ?>" 
  data-ten="<?= htmlspecialchars($dv['ten_donvi']) ?>" 
  data-gia="<?= htmlspecialchars($dv['gia']) ?>">
  <?= htmlspecialchars($dv['ten_donvi']) ?> - <?= number_format($dv['gia'], 0, ',', '.') ?> VNĐ
</option>
  <?php endforeach; ?>
</select>

          <p class="fs-5 fw-bold text-danger mb-3">
            Giá: <span id="gia-hienthi"><?= number_format($donviList[0]['gia'], 0, ',', '.') ?> VNĐ</span>
          </p>

          <div class="mb-3 d-flex align-items-center">
            <label for="soluong" class="form-label me-2 mb-0">Số lượng:</label>
            <input type="number" name="soluong" value="1" min="1" id="soluong" class="form-control w-25" required>
          </div>

          <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
            <i class="bi bi-cart-plus me-1"></i> Thêm vào giỏ hàng
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Tabs: Mô tả chi tiết & Sản phẩm liên quan -->
<section class="container py-5">
  <ul class="nav nav-tabs" id="detailTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="mota-tab" data-bs-toggle="tab" data-bs-target="#mota" type="button" role="tab">Mô tả</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="lienquan-tab" data-bs-toggle="tab" data-bs-target="#lienquan" type="button" role="tab">Sản phẩm liên quan</button>
    </li>
  </ul>

  <div class="tab-content border border-top-0 shadow-sm p-4 rounded-bottom" id="detailTabsContent">
  <div class="tab-pane fade show active" id="mota" role="tabpanel">
  <?= !empty($product['mota_chitiet']) 
        ? $product['mota_chitiet'] 
        : '<p>Không có mô tả chi tiết.</p>'; ?>
</div>


    <div class="tab-pane fade" id="lienquan" role="tabpanel">
      <div class="row mt-4">
        <?php foreach ($relatedProducts as $sp): ?>
          <div class="col-md-3 mb-4">
            <div class="card product-card h-100 shadow-sm border-0 rounded-4">
              <div class="card-img-top-wrapper text-center p-3" style="height: 200px;">
                <img src="/MedXtorePharmacy/assets/images/product-images/<?= htmlspecialchars($sp['hinhanh']) ?>" 
                     class="w-100 h-100 object-fit-contain" 
                     alt="<?= htmlspecialchars($sp['ten_thuoc']) ?>">
              </div>
              <div class="card-body text-center">
                <h6 class="fw-bold mb-2"><?= htmlspecialchars($sp['ten_thuoc']) ?></h6>
                <p class="text-muted small">Loại: <?= htmlspecialchars($sp['ten_loai']) ?></p>
                <a href="/MedXtorePharmacy/pages/products/product-detail.php?id=<?= $sp['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill mt-2">
                  <i class="bi bi-eye"></i> Xem chi tiết
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($relatedProducts)): ?>
          <div class="col-12 text-center text-muted py-3">Không có sản phẩm liên quan.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Script cập nhật giá -->
<script>
function capNhatGiaDonVi() {
  const select = document.getElementById('donviSelect');
  const option = select.options[select.selectedIndex];

  const gia = option.getAttribute('data-gia');
  const ten = option.getAttribute('data-ten');

  document.getElementById('gia').value = gia;
  document.getElementById('ten_donvi').value = ten;
  document.getElementById('gia-hienthi').innerText = Number(gia).toLocaleString('vi-VN') + ' VNĐ';
}

document.addEventListener('DOMContentLoaded', capNhatGiaDonVi);
</script>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
