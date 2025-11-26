<?php
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../models/Category.php'; // ✅ nếu dùng model Category
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Nhận tham số tìm kiếm, lọc và phân trang
$search = $_GET['search'] ?? '';
$loai_id = $_GET['loai'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1)); // đảm bảo số trang >= 1
$limit = 15;
$offset = ($page - 1) * $limit;

// Khởi tạo controller
$productController = new ProductController();
$dsLoai = (new Category())->getAll(); // ✅ Lấy danh mục thuốc từ model Category

// Lấy sản phẩm có phân trang
$products = $productController->getAllWithPagination($search, $loai_id, $limit, $offset);
$total = $productController->countFiltered($search, $loai_id);
$totalPages = ceil($total / $limit);

$topExistProducts = $productController->getTopExistProducts(5);
?>


<!-- Hero Banner -->
<section class="products-header">
  <div class="container text-center">
    <h1>Tất cả sản phẩm</h1>
    <p>Khám phá tất cả các sản phẩm đang có tại nhà thuốc MedXtore</p>
  </div>
</section>

<!-- Products Section -->
<section class="container products-section">
  <div class="row">
    <!-- SIDEBAR BỘ LỌC -->
    <div class="col-md-3">
      <div class="filter-card">
        <h5 class="filter-title"><i class="bi bi-funnel me-2"></i>Bộ lọc sản phẩm</h5>
        <form method="GET">
          <div class="mb-3">
            <label for="searchInput" class="form-label">Tìm kiếm:</label>
            <input type="text" name="search" id="searchInput" class="form-control"
                   placeholder="Nhập tên thuốc..." value="<?= htmlspecialchars($search) ?>">
          </div>

          <div class="mb-3">
            <label for="loaiSelect" class="form-label">Loại thuốc:</label>
            <select name="loai" id="loaiSelect" class="form-select">
              <option value="">Tất cả loại</option>
              <?php foreach ($dsLoai as $loai): ?>
                <option value="<?= $loai['id'] ?>" <?= ($loai_id == $loai['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($loai['ten_loai']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn btn-filter w-100">
            <i class="bi bi-search me-1"></i> Áp dụng bộ lọc
          </button>
        </form>
      </div>

      <!-- Featured Products -->
      <div class="featured-card">
        <h5 class="featured-title">Sản phẩm nổi bật</h5>
        <div class="swiper adSwiper">
          <div class="swiper-wrapper">
            <?php foreach ($topExistProducts as $product): ?>
              <div class="swiper-slide text-center">
                <img src="/assets/images/product-images/<?= htmlspecialchars($product['hinhanh']) ?>"
                     class="featured-img" alt="<?= htmlspecialchars($product['ten_thuoc']) ?>">
                <h6 class="featured-product-name"><?= htmlspecialchars($product['ten_thuoc']) ?></h6>
                <p class="featured-stock">Tồn kho: <?= $product['tong_soluong'] ?></p>
                <a href="/pages/products/product-detail.php?id=<?= $product['id'] ?>" class="btn btn-view-featured">
                  Xem ngay
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- DANH SÁCH SẢN PHẨM -->
    <div class="col-md-9">
      <?php if (!empty($products)): ?>
        <div class="row">
          <?php foreach ($products as $sp):
            $donviList = $productController->getDonViTheoThuoc($sp['id']); ?>
            <div class="col-md-4 mb-4">
              <div class="product-card">
                <div class="product-img-wrapper">
                  <img src="/assets/images/product-images/<?= htmlspecialchars($sp['hinhanh'] ?? 'default.png') ?>"
                       alt="<?= htmlspecialchars($sp['ten_thuoc']) ?>">
                </div>

                <div class="product-card-body">
                  <h5 class="product-name"><?= htmlspecialchars($sp['ten_thuoc']) ?></h5>
                  <p class="product-category">Loại: <?= htmlspecialchars($sp['ten_loai']) ?></p>
                  <p class="product-desc">
                    <?= mb_strimwidth(strip_tags($sp['mota']), 0, 80, "...") ?>
                  </p>

                  <?php if (!empty($donviList)): ?>
                    <select class="form-select form-select-sm mb-2 select-donvi"
                            data-product-id="<?= $sp['id'] ?>"
                            onchange="capNhatDonVi(<?= $sp['id'] ?>)">
                      <?php foreach ($donviList as $dv): ?>
                        <option value="<?= $dv['donvi_id'] ?>"
                                data-ten="<?= htmlspecialchars($dv['ten_donvi']) ?>"
                                data-gia="<?= $dv['gia'] ?>">
                          <?= htmlspecialchars($dv['ten_donvi']) ?> - <?= number_format($dv['gia'], 0, ',', '.') ?> VNĐ
                        </option>
                      <?php endforeach; ?>
                    </select>

                    <p class="product-price">
                      <span id="gia-<?= $sp['id'] ?>">
                        <?= number_format($donviList[0]['gia'], 0, ',', '.') ?> VNĐ
                      </span>
                    </p>
                  <?php endif; ?>

                  <div class="product-actions">
                    <a href="product-detail.php?id=<?= $sp['id'] ?>" class="btn btn-view">
                      <i class="bi bi-eye me-1"></i> Xem
                    </a>

                    <form method="POST" action="/controllers/CartController.php" class="flex-grow-1">
                      <input type="hidden" name="thuoc_id" value="<?= $sp['id'] ?>">
                      <input type="hidden" name="ten_thuoc" value="<?= htmlspecialchars($sp['ten_thuoc']) ?>">
                      <input type="hidden" name="hinhanh" value="<?= htmlspecialchars($sp['hinhanh']) ?>">
                      <input type="hidden" name="donvi_id" id="donvi_id_<?= $sp['id'] ?>" value="<?= $donviList[0]['donvi_id'] ?>">
                      <input type="hidden" name="ten_donvi" id="ten_donvi_<?= $sp['id'] ?>" value="<?= htmlspecialchars($donviList[0]['ten_donvi']) ?>">
                      <input type="hidden" name="gia" id="gia_hidden_<?= $sp['id'] ?>" value="<?= $donviList[0]['gia'] ?>">
                      <input type="hidden" name="soluong" value="1">

                      <button type="submit" class="btn btn-add-cart w-100">
                        <i class="bi bi-cart-plus me-1"></i> Thêm giỏ
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="no-products">
          <i class="bi bi-box-seam"></i>
          <h4>Không tìm thấy sản phẩm</h4>
          <p>Vui lòng thử tìm kiếm với từ khóa khác</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
  <nav class="pagination-wrapper">
    <ul class="pagination justify-content-center">
      <?php if ($page > 1): ?>
        <li class="page-item">
          <a class="page-link" href="?search=<?= urlencode($search) ?>&loai=<?= urlencode($loai_id) ?>&page=<?= $page - 1 ?>">
            Trước
          </a>
        </li>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
          <a class="page-link" href="?search=<?= urlencode($search) ?>&loai=<?= urlencode($loai_id) ?>&page=<?= $i ?>">
            <?= $i ?>
          </a>
        </li>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <li class="page-item">
          <a class="page-link" href="?search=<?= urlencode($search) ?>&loai=<?= urlencode($loai_id) ?>&page=<?= $page + 1 ?>">
            Sau
          </a>
        </li>
      <?php endif; ?>
    </ul>
  </nav>
<?php endif; ?>

<style>
/* Header Section */
.products-header {
  background: #1976d2;
  padding: 50px 0;
  color: white;
  margin-bottom: 40px;
}

.products-header h1 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 10px;
}

.products-header p {
  font-size: 1rem;
  opacity: 0.95;
}

/* Products Section */
.products-section {
  padding-bottom: 40px;
}

/* Filter Card */
.filter-card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.filter-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 20px;
}

.filter-card .form-label {
  font-weight: 600;
  color: #2d3748;
  font-size: 0.9rem;
  margin-bottom: 8px;
}

.filter-card .form-control,
.filter-card .form-select {
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 10px 15px;
  transition: all 0.2s ease;
}

.filter-card .form-control:focus,
.filter-card .form-select:focus {
  border-color: #1976d2;
  box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
}

.btn-filter {
  background: #1976d2;
  color: white;
  border: none;
  padding: 10px 20px;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.btn-filter:hover {
  background: #1565c0;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
}

/* Featured Products Card */
.featured-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.featured-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 20px;
  text-align: center;
}

.featured-img {
  max-height: 130px;
  width: 100%;
  object-fit: contain;
  margin-bottom: 12px;
  border-radius: 8px;
  background: #f8f9fa;
  padding: 10px;
}

.featured-product-name {
  font-size: 0.95rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 8px;
}

.featured-stock {
  font-size: 0.85rem;
  color: #6c757d;
  margin-bottom: 12px;
}

.btn-view-featured {
  padding: 6px 20px;
  border: 1px solid #1976d2;
  color: #1976d2;
  background: white;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
  text-decoration: none;
  display: inline-block;
  transition: all 0.2s ease;
}

.btn-view-featured:hover {
  background: #1976d2;
  color: white;
  text-decoration: none;
}

/* Product Card */
.product-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.product-img-wrapper {
  height: 200px;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 15px;
  overflow: hidden;
}

.product-img-wrapper img {
  max-height: 170px;
  max-width: 100%;
  object-fit: contain;
  transition: transform 0.3s ease;
}

.product-card:hover .product-img-wrapper img {
  transform: scale(1.05);
}

.product-card-body {
  padding: 20px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

.product-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 8px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-category {
  font-size: 0.85rem;
  color: #6c757d;
  margin-bottom: 8px;
}

.product-desc {
  font-size: 0.9rem;
  color: #6c757d;
  line-height: 1.5;
  margin-bottom: 15px;
  min-height: 40px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-price {
  font-size: 1.25rem;
  font-weight: 700;
  color: #e74c3c;
  margin-bottom: 15px;
}

.product-actions {
  display: flex;
  gap: 10px;
  margin-top: auto;
}

.btn-view {
  padding: 8px 15px;
  border: 1px solid #6c757d;
  color: #6c757d;
  background: white;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-view:hover {
  background: #6c757d;
  color: white;
  text-decoration: none;
}

.btn-add-cart {
  padding: 8px 15px;
  background: #1976d2;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.btn-add-cart:hover {
  background: #1565c0;
  transform: translateY(-2px);
}

/* No Products */
.no-products {
  text-align: center;
  padding: 60px 20px;
  color: #6c757d;
}

.no-products i {
  font-size: 4rem;
  opacity: 0.3;
  margin-bottom: 20px;
}

/* Pagination */
.pagination-wrapper {
  margin: 40px 0;
}

.page-link {
  border-radius: 8px;
  margin: 0 3px;
  color: #1976d2;
  border: 1px solid #dee2e6;
  padding: 8px 15px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.page-link:hover {
  background: #1976d2;
  color: white;
  border-color: #1976d2;
}

.page-item.active .page-link {
  background: #1976d2;
  border-color: #1976d2;
}

/* Responsive */
@media (max-width: 768px) {
  .products-header h1 {
    font-size: 1.75rem;
  }

  .products-header {
    padding: 30px 0;
  }

  .filter-card, .featured-card {
    margin-bottom: 30px;
  }
}
</style>

<script>
function capNhatDonVi(productId) {
  const select = document.querySelector(`.select-donvi[data-product-id='${productId}']`);
  const selected = select.options[select.selectedIndex];

  const donviId = selected.value;
  const ten = selected.getAttribute('data-ten');
  const gia = selected.getAttribute('data-gia');

  // Cập nhật hidden inputs trong form
  document.getElementById('donvi_id_' + productId).value = donviId;
  document.getElementById('ten_donvi_' + productId).value = ten;
  document.getElementById('gia_hidden_' + productId).value = gia;

  // (Tuỳ chọn) cập nhật giá hiển thị
  const spanGia = document.getElementById('gia-' + productId);
  if (spanGia) {
    spanGia.textContent = Number(gia).toLocaleString('vi-VN') + ' VNĐ';
  }

}

// Swiper for featured products
new Swiper(".adSwiper", {
  loop: true,
  autoplay: {
    delay: 3000
  },
  spaceBetween: 20,
  slidesPerView: 1,
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>