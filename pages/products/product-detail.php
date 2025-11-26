<?php
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$productController = new ProductController();
$product_id = $_GET['id'] ?? 0;
$product = $productController->getById($product_id);

if (!$product) {
  header("Location: /pages/products/products.php");
  exit();
}

$donviList = $productController->getDonViTheoThuoc($product['id']);
$relatedProducts = $productController->getRelated($product['id'], $product['loai_id']);
?>

<style>
.product-detail-section {
  padding: 40px 0;
  background: #f8f9fa;
}

.product-image-card {
  background: white;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: box-shadow 0.3s ease;
}

.product-image-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.product-image {
  max-height: 350px;
  width: 100%;
  object-fit: contain;
}

.product-info-card {
  background: white;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.product-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 15px;
}

.product-category-badge {
  display: inline-block;
  padding: 6px 16px;
  background: #e3f2fd;
  color: #1976d2;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 15px;
}

.product-description {
  color: #6c757d;
  line-height: 1.7;
  margin-bottom: 25px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 8px;
}

.form-section {
  margin-top: 25px;
}

.form-label {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 8px;
  font-size: 0.95rem;
}

.form-select, .form-control {
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 10px 15px;
  transition: all 0.2s ease;
}

.form-select:focus, .form-control:focus {
  border-color: #1976d2;
  box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
}

.price-box {
  background: #1976d2;
  color: white;
  padding: 20px;
  border-radius: 10px;
  margin: 20px 0;
}

.price-label {
  font-size: 0.875rem;
  opacity: 0.9;
  margin-bottom: 5px;
}

.price-value {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
}

.quantity-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.quantity-input {
  width: 100px;
  text-align: center;
  font-weight: 600;
}

.btn-add-to-cart {
  background: #1976d2;
  color: white;
  border: none;
  padding: 12px 35px;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.3s ease;
  width: 100%;
  font-size: 1rem;
}

.btn-add-to-cart:hover {
  background: #1565c0;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
}

.tabs-section {
  background: white;
  border-radius: 12px;
  padding: 30px;
  margin-top: 40px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.nav-tabs {
  border-bottom: 2px solid #e9ecef;
}

.nav-tabs .nav-link {
  color: #6c757d;
  border: none;
  padding: 12px 24px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.nav-tabs .nav-link:hover {
  color: #1976d2;
  border-bottom: 2px solid #1976d2;
}

.nav-tabs .nav-link.active {
  color: #1976d2;
  border-bottom: 2px solid #1976d2;
  background: none;
}

.tab-content {
  padding-top: 25px;
}

.related-product-card {
  background: white;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  transition: all 0.3s ease;
  height: 100%;
}

.related-product-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
  transform: translateY(-4px);
}

.related-img-wrapper {
  height: 160px;
  margin-bottom: 12px;
  border-radius: 8px;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.related-img-wrapper img {
  max-height: 140px;
  max-width: 100%;
  object-fit: contain;
}

.related-product-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 8px;
  line-height: 1.4;
}

.related-product-category {
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 12px;
}

.btn-view-product {
  width: 100%;
  padding: 8px;
  border: 1px solid #1976d2;
  color: #1976d2;
  background: white;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.btn-view-product:hover {
  background: #1976d2;
  color: white;
}

@media (max-width: 768px) {
  .product-title {
    font-size: 1.5rem;
  }

  .price-value {
    font-size: 1.5rem;
  }

  .product-detail-section {
    padding: 20px 0;
  }
}
</style>

<section class="product-detail-section">
  <div class="container">
    <div class="row g-4">
      <!-- Product Image -->
      <div class="col-lg-5">
        <div class="product-image-card">
          <img src="/assets/images/product-images/<?= htmlspecialchars($product['hinhanh']) ?>"
               alt="<?= htmlspecialchars($product['ten_thuoc']) ?>"
               class="product-image">
        </div>
      </div>

      <!-- Product Info -->
      <div class="col-lg-7">
        <div class="product-info-card">
          <h1 class="product-title"><?= htmlspecialchars($product['ten_thuoc']) ?></h1>

          <span class="product-category-badge">
            <?= htmlspecialchars($product['ten_loai']) ?>
          </span>

          <p class="product-description">
            <?= htmlspecialchars($product['mota']) ?>
          </p>

          <?php if (!empty($donviList)): ?>
            <form method="POST" action="/controllers/CartController.php" class="form-section">
              <input type="hidden" name="thuoc_id" value="<?= $product['id'] ?>">
              <input type="hidden" name="ten_thuoc" value="<?= htmlspecialchars($product['ten_thuoc']) ?>">
              <input type="hidden" name="hinhanh" value="<?= htmlspecialchars($product['hinhanh']) ?>">
              <input type="hidden" name="ten_donvi" id="ten_donvi">
              <input type="hidden" name="gia" id="gia">

              <div class="mb-3">
                <label class="form-label">Đơn vị:</label>
                <select class="form-select" name="donvi_id" id="donviSelect" onchange="capNhatGiaDonVi()">
                  <?php foreach ($donviList as $dv): ?>
                    <option value="<?= $dv['donvi_id'] ?>"
                            data-ten="<?= htmlspecialchars($dv['ten_donvi']) ?>"
                            data-gia="<?= $dv['gia'] ?>">
                      <?= htmlspecialchars($dv['ten_donvi']) ?> - <?= number_format($dv['gia'], 0, ',', '.') ?>đ
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="price-box">
                <div class="price-label">Giá bán:</div>
                <h3 class="price-value">
                  <span id="gia-hienthi"><?= number_format($donviList[0]['gia'], 0, ',', '.') ?></span>đ
                </h3>
              </div>

              <div class="quantity-wrapper">
                <label class="form-label mb-0">Số lượng:</label>
                <input type="number" name="soluong" value="1" min="1" class="form-control quantity-input" required>
              </div>

              <button type="submit" class="btn btn-add-to-cart">
                <i class="bi bi-cart-plus me-2"></i>Thêm vào giỏ hàng
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-section">
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#description">
            Mô tả chi tiết
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#related">
            Sản phẩm liên quan
          </button>
        </li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane fade show active" id="description">
          <?= !empty($product['mota_chitiet'])
              ? $product['mota_chitiet']
              : '<p class="text-muted">Chưa có mô tả chi tiết.</p>' ?>
        </div>

        <div class="tab-pane fade" id="related">
          <div class="row g-3">
            <?php if (!empty($relatedProducts)): ?>
              <?php foreach ($relatedProducts as $sp): ?>
                <div class="col-md-6 col-lg-3">
                  <div class="related-product-card">
                    <div class="related-img-wrapper">
                      <img src="/assets/images/product-images/<?= htmlspecialchars($sp['hinhanh']) ?>"
                           alt="<?= htmlspecialchars($sp['ten_thuoc']) ?>">
                    </div>
                    <h6 class="related-product-title"><?= htmlspecialchars($sp['ten_thuoc']) ?></h6>
                    <p class="related-product-category"><?= htmlspecialchars($sp['ten_loai']) ?></p>
                    <a href="product-detail.php?id=<?= $sp['id'] ?>" class="btn btn-view-product">
                      Xem chi tiết
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="col-12 text-center text-muted py-4">
                Không có sản phẩm liên quan
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
function capNhatGiaDonVi() {
  const select = document.getElementById('donviSelect');
  const option = select.options[select.selectedIndex];
  const gia = option.getAttribute('data-gia');
  const ten = option.getAttribute('data-ten');

  document.getElementById('gia').value = gia;
  document.getElementById('ten_donvi').value = ten;
  document.getElementById('gia-hienthi').textContent = Number(gia).toLocaleString('vi-VN');
}

document.addEventListener('DOMContentLoaded', capNhatGiaDonVi);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
