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
<section class="py-5" style="background-color: #e6f9fd;">
  <div class="container py-5 text-center">
    <h1 class="fw-bold text-dark">Tất cả sản phẩm</h1>
    <p class="text-muted col-md-6 mx-auto">Khám phá tất cả các sản phẩm đang có tại nhà thuốc MedXtore.</p>
  </div>
</section>

<!-- Giao diện với Sidebar lọc + Danh sách sản phẩm -->
<div class="row">
  <!-- SIDEBAR BỘ LỌC -->
  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
      <h5 class="fw-bold mb-3"><i class="bi bi-funnel-fill me-1 text-warning"></i> Bộ lọc sản phẩm</h5>
      <form method="GET">
        <div class="mb-3">
          <label for="searchInput" class="form-label fw-semibold">Tìm kiếm:</label>
          <input type="text" name="search" id="searchInput" class="form-control" placeholder="Nhập tên thuốc..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="mb-3">
          <label for="loaiSelect" class="form-label fw-semibold">Loại thuốc:</label>
          <select name="loai" id="loaiSelect" class="form-select">
            <option value="">Tất cả loại</option>
            <?php foreach ($dsLoai as $loai): ?>
              <option value="<?= $loai['id'] ?>" <?= ($loai_id == $loai['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($loai['ten_loai']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn btn-warning w-100 rounded-pill fw-semibold mt-2">
          <i class="bi bi-search me-1"></i> Áp dụng bộ lọc
        </button>
      </form>
    </div>
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden p-3">
      <!-- Tiêu đề chạy -->
      <div class="bg-warning text-white py-1 px-3 mb-3 rounded-3 marquee-container">
        <marquee behavior="scroll" direction="left" scrollamount="5" class="fw-bold">
          🎉 Quảng cáo sản phẩm nổi bật! Ưu đãi mỗi ngày - Mua ngay kẻo lỡ! 🎉
        </marquee>
      </div>

      <!-- Swiper Slide (phải đã import SwiperJS ở layout chính) -->
      <div class="card border-0 shadow-sm p-3 rounded-4">
        <h5 class="fw-bold mb-3 text-center text-primary">
          <span class="text-danger">Quảng Cáo Sản Phẩm Hot</span>
        </h5>
        <div class="swiper adSwiper">
          <div class="swiper-wrapper">
            <?php foreach ($topExistProducts as $product): ?>
              <div class="swiper-slide text-center px-2">
                <img src="/assets/images/product-images/<?= htmlspecialchars($product['hinhanh']) ?>"
                  class="img-fluid rounded mb-2" style="max-height: 130px; object-fit: contain;" alt="<?= htmlspecialchars($product['ten_thuoc']) ?>">
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($product['ten_thuoc']) ?></h6>
                <p class="small text-muted">Tồn kho: <?= $product['tong_soluong'] ?> sản phẩm</p>
                <a href="/pages/products/product-detail.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                  Xem ngay
                </a>
              </div>
            <?php endforeach; ?>
          </div>
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
            <div class="card h-100 shadow-sm border-0 rounded-4 product-card">
              <div class="card-img-top-wrapper position-relative overflow-hidden" style="height: 230px;">
                <img src="/assets/images/product-images/<?= htmlspecialchars($sp['hinhanh'] ?? 'default.png') ?>"
                  class="card-img-top w-100 h-100 object-fit-contain p-2"
                  alt="<?= htmlspecialchars($sp['ten_thuoc']) ?>">
              </div>

              <div class="card-body text-center d-flex flex-column">
                <h5 class="card-title fw-semibold mb-1"><?= htmlspecialchars($sp['ten_thuoc']) ?></h5>
                <p class="text-muted small mb-1">Loại: <?= htmlspecialchars($sp['ten_loai']) ?></p>
                <p class="text-muted mb-2" style="min-height: 40px;">
                  <?= mb_strimwidth(strip_tags($sp['mota']), 0, 80, "...") ?>
                </p>

                <?php if (!empty($donviList)): ?>
                  <select class="form-select form-select-sm mb-2 select-donvi"
        data-product-id="<?= $sp['id'] ?>"
        onchange="capNhatDonVi(<?= $sp['id'] ?>)">
  <?php foreach ($donviList as $dv): ?>
    <option 
      value="<?= $dv['donvi_id'] ?>" 
      data-ten="<?= htmlspecialchars($dv['ten_donvi']) ?>" 
      data-gia="<?= $dv['gia'] ?>">
      <?= htmlspecialchars($dv['ten_donvi']) ?> - <?= number_format($dv['gia'], 0, ',', '.') ?> VNĐ
    </option>
  <?php endforeach; ?>
</select>

                  <p class="fw-bold text-danger fs-6 mb-3">
                    <span class="gia-hienthi" id="gia-<?= $sp['id'] ?>">
                      <?= number_format($donviList[0]['gia'], 0, ',', '.') ?> VNĐ
                    </span>
                  </p>
                <?php endif; ?>

                <div class="mt-auto d-flex justify-content-between gap-2">
                  <a href="product-detail.php?id=<?= $sp['id'] ?>"
                    class="btn btn-outline-secondary px-3 rounded-pill fw-semibold flex-grow-1 text-nowrap">
                    <i class="bi bi-eye me-1"></i> Xem chi tiết
                  </a>

                  <form method="POST" action="/controllers/CartController.php" class="flex-grow-1">
  <input type="hidden" name="thuoc_id" value="<?= $sp['id'] ?>">
  <input type="hidden" name="ten_thuoc" value="<?= htmlspecialchars($sp['ten_thuoc']) ?>">
  <input type="hidden" name="hinhanh" value="<?= htmlspecialchars($sp['hinhanh']) ?>">

  <!-- Hidden này được cập nhật động -->
  <input type="hidden" name="donvi_id" id="donvi_id_<?= $sp['id'] ?>" value="<?= $donviList[0]['donvi_id'] ?>">
  <input type="hidden" name="ten_donvi" id="ten_donvi_<?= $sp['id'] ?>" value="<?= htmlspecialchars($donviList[0]['ten_donvi']) ?>">
  <input type="hidden" name="gia" id="gia_hidden_<?= $sp['id'] ?>" value="<?= $donviList[0]['gia'] ?>">
  <input type="hidden" name="soluong" value="1">

  <button type="submit" class="btn btn-primary px-3 rounded-pill fw-semibold shadow w-100 text-nowrap">
    <i class="bi bi-cart-plus me-1"></i> Thêm vào giỏ
  </button>
</form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="text-center my-5">
        <img src="/assets/images/empty.png" alt="empty" style="width: 100px;">
        <p class="text-muted">Hiện tại chưa có sản phẩm nào phù hợp.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!--Phân trang-->

<?php if ($totalPages > 1): ?>
  <nav aria-label="Pagination">
    <ul class="pagination justify-content-center mt-4">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
          <a class="page-link rounded-pill px-3"
            href="?search=<?= urlencode($search) ?>&loai=<?= urlencode($loai_id) ?>&page=<?= $i ?>">
            <?= $i ?>
          </a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<!-- Giỏ hàng mini -->
<div id="cart-mini" class="position-fixed bottom-0 end-0 m-4 bg-white shadow-lg p-3 rounded-4" style="width: 280px; z-index: 999; display: none;">
  <h6 class="fw-bold mb-3">🛒 Giỏ hàng</h6>
  <ul id="cart-items" class="list-group mb-3"></ul>
  <button class="btn btn-sm btn-outline-danger w-100" onclick="clearCart()">Xóa tất cả</button>
</div>

<style>
  h5 span.text-danger {
    display: inline-block;
    animation: marquee 6s linear infinite;
    white-space: nowrap;
  }

  @keyframes marquee {
    0% {
      transform: translateX(30%);
    }

    100% {
      transform: translateX(-30%);
    }
  }

  /* Thanh tìm kiếm nhỏ gọn */
  .search-box {
    max-width: 550px;
    margin: 0 auto;
    transition: all 0.3s ease;
  }

  .search-box input {
    font-size: 0.95rem;
    padding: 0.6rem 0.8rem;
  }

  .input-group input::placeholder {
    color: #aaa;
    font-style: italic;
  }

  .form-select:focus,
  .form-control:focus {
    box-shadow: none;
    border-color: #fbae3c;
  }

  .suggestion-box {
    border: 1px solid #ddd;
    border-top: none;
    max-height: 250px;
    overflow-y: auto;
    font-size: 0.95rem;
  }

  .suggestion-item:hover {
    background-color: #f1f1f1;
  }

  /* Add these styles to your existing <style> block */

  /* Product card hover effects */
  .product-card {
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275),
      box-shadow 0.4s ease,
      opacity 0.5s ease;
  }

  .product-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
  }

  /* Button click effect */
  .add-to-cart-btn {
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275),
      background-color 0.3s ease,
      color 0.3s ease,
      box-shadow 0.3s ease;
  }

  .add-to-cart-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 5px 15px rgba(251, 174, 60, 0.4);
  }

  .btn-clicked {
    animation: btnClick 0.5s ease;
  }

  @keyframes btnClick {
    0% {
      transform: scale(1);
    }

    50% {
      transform: scale(0.95);
    }

    100% {
      transform: scale(1);
    }
  }

  /* Floating item animation */
  .floating-item {
    position: fixed;
    z-index: 9999;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    transition: all 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  }

  .floating-item img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  /* Cart highlight effect */
  .cart-highlight {
    animation: pulse 0.7s ease;
  }

  @keyframes pulse {
    0% {
      transform: scale(1);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    50% {
      transform: scale(1.05);
      box-shadow: 0 8px 25px rgba(251, 174, 60, 0.4);
    }

    100% {
      transform: scale(1);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
  }

  /* Cart mini show animation */
  #cart-mini.show-cart {
    animation: slideUp 0.5s ease forwards;
  }

  @keyframes slideUp {
    from {
      transform: translateY(20px);
      opacity: 0;
    }

    to {
      transform: translateY(0);
      opacity: 1;
    }
  }

  /* New item in cart animation */
  .new-item-pulse {
    animation: itemPulse 1s ease;
    background-color: rgba(251, 174, 60, 0.1);
  }

  @keyframes itemPulse {
    0% {
      background-color: rgba(251, 174, 60, 0.3);
    }

    100% {
      background-color: transparent;
    }
  }

  /* Filter transition */
  .filter-transition {
    animation: filterFade 0.5s ease;
  }

  @keyframes filterFade {
    0% {
      opacity: 1;
    }

    50% {
      opacity: 0.5;
      transform: scale(0.98);
    }

    100% {
      opacity: 1;
      transform: scale(1);
    }
  }

  /* Pagination hover effect */
  .page-link {
    transition: all 0.3s ease;
  }

  .page-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
  }

  /* Search suggestion transitions */
  .suggestion-box {
    transition: opacity 0.3s ease, transform 0.3s ease;
    transform-origin: top center;
  }

  .list-group-item-action {
    transition: background-color 0.2s ease, transform 0.2s ease;
  }

  .list-group-item-action:hover {
    transform: translateX(3px);
  }

  /* Page load transition */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .product-card {
    animation: fadeInUp 0.6s ease forwards;
    animation-delay: calc(0.1s * var(--index, 0));
  }

  .py-5[style*="background-color: #e6f9fd"] {
    position: relative;
    overflow: hidden;
  }

  .py-5[style*="background-color: #e6f9fd"] .container {
    animation: fadeInUp 0.8s ease-out forwards;
  }

  .py-5[style*="background-color: #e6f9fd"] h1 {
    animation: slideInRight 1s ease-out forwards;
    opacity: 0;
  }

  .py-5[style*="background-color: #e6f9fd"] p {
    animation: slideInLeft 1s ease-out forwards;
    animation-delay: 0.3s;
    opacity: 0;
  }

  .py-5[style*="background-color: #e6f9fd"]::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shimmer 3s infinite;
  }

  /* Banner Animation Keyframes */
  @keyframes slideInRight {
    from {
      transform: translateX(-50px);
      opacity: 0;
    }

    to {
      transform: translateX(0);
      opacity: 1;
    }
  }

  @keyframes slideInLeft {
    from {
      transform: translateX(50px);
      opacity: 0;
    }

    to {
      transform: translateX(0);
      opacity: 1;
    }
  }

  @keyframes shimmer {
    0% {
      left: -100%;
    }

    100% {
      left: 200%;
    }
  }

  /* Floating shapes in banner */
  .banner-shape {
    position: absolute;
    opacity: 0.2;
    pointer-events: none;
    animation: float 15s infinite ease-in-out;
    z-index: 0;
  }

  .banner-shape.circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
  }

  .banner-shape.square {
    width: 40px;
    height: 40px;
    transform: rotate(45deg);
  }

  .banner-shape.triangle {
    width: 0;
    height: 0;
    border-left: 25px solid transparent;
    border-right: 25px solid transparent;
    border-bottom: 50px solid #cff5fd;
    background-color: transparent !important;
  }

  /* Wave effect at the bottom of banner */
  .banner-wave {
    position: absolute;
    bottom: -10px;
    left: 0;
    width: 100%;
    height: 20px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z' fill='%23ffffff' opacity='.25'%3E%3C/path%3E%3Cpath d='M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z' fill='%23ffffff' opacity='.5'%3E%3C/path%3E%3Cpath d='M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z' fill='%23ffffff'%3E%3C/path%3E%3C/svg%3E") no-repeat;
    background-size: cover;
    animation: wave 10s linear infinite;
  }

  /* Animation keyframes */
  @keyframes float {

    0%,
    100% {
      transform: translateY(0) rotate(0);
    }

    50% {
      transform: translateY(-20px) rotate(5deg);
    }
  }

  @keyframes wave {
    0% {
      background-position-x: 0;
    }

    100% {
      background-position-x: 1000px;
    }
  }

  /* Make the banner more interactive */
  .py-5[style*="background-color: #e6f9fd"] h1,
  .py-5[style*="background-color: #e6f9fd"] p {
    position: relative;
    z-index: 2;
    transition: transform 0.2s ease-out;
  }

  /* Banner entrance animation */
  .py-5[style*="background-color: #e6f9fd"] {
    animation: bannerEnter 0.8s ease-out forwards;
    transform-origin: center bottom;
  }

  @keyframes bannerEnter {
    from {
      transform: translateY(-20px);
      opacity: 0;
    }

    to {
      transform: translateY(0);
      opacity: 1;
    }
  }

  /* Enhanced Search Form Styles with Transitions */

  /* Form container styling */
  form.row {
    background-color: #ffffff;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-top: -30px;
    position: relative;
    z-index: 10;
  }

  form.row:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  }

  /* Search input group styling */
  .input-group {
    transition: all 0.3s ease;
    border: none !important;
  }

  .input-group:focus-within {
    box-shadow: 0 0 0 3px rgba(251, 174, 60, 0.25) !important;
    transform: translateY(-2px);
  }

  /* Search icon styling */
  .input-group-text {
    transition: all 0.3s ease;
  }

  .input-group:focus-within .input-group-text {
    color: #fbae3c !important;
  }

  .input-group:focus-within .bi-search {
    transform: scale(1.2);
    transition: transform 0.3s ease;
  }

  .bi-search,
  .bi-filter-circle,
  .bi-funnel-fill {
    transition: transform 0.3s ease;
  }

  /* Input field styling */
  .form-control,
  .form-select {
    transition: all 0.3s ease;
    padding: 12px 15px;
    font-size: 0.95rem;
  }

  .form-control:focus,
  .form-select:focus {
    box-shadow: none;
    border-color: transparent;
  }

  .form-control::placeholder {
    color: #aaa;
    font-style: italic;
    transition: opacity 0.3s ease;
  }

  .form-control:focus::placeholder {
    opacity: 0.5;
  }

  /* Select dropdown styling */
  .form-select {
    cursor: pointer;
    background-image: linear-gradient(45deg, transparent 50%, #fbae3c 50%),
      linear-gradient(135deg, #fbae3c 50%, transparent 50%);
    background-position: calc(100% - 20px) calc(1em + 2px),
      calc(100% - 15px) calc(1em + 2px);
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
    padding-right: 35px !important;
  }

  .form-select:hover {
    background-color: #f9f9f9;
  }

  /* Filter icon styling */
  .bi-filter-circle {
    color: #fbae3c !important;
  }

  .input-group:hover .bi-filter-circle {
    transform: rotate(15deg);
  }

  /* Button styling */
  .btn-warning {
    background-color: #fbae3c;
    border: none;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    padding: 10px 20px;
    box-shadow: 0 4px 10px rgba(251, 174, 60, 0.3);
  }

  .btn-warning:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(251, 174, 60, 0.4);
    background-color: #f9a826;
  }

  .btn-warning:active {
    transform: translateY(0);
    box-shadow: 0 2px 5px rgba(251, 174, 60, 0.4);
  }

  .btn-warning:hover .bi-funnel-fill {
    transform: rotate(15deg);
  }

  /* Suggestion box styling */
  #suggestionBox {
    border-radius: 10px;
    transition: opacity 0.3s ease, transform 0.3s ease;
    transform-origin: top center;
    padding: 5px 0;
    margin-top: 5px;
    background-color: white;
  }

  #suggestionBox.show {
    transform: scaleY(1);
    opacity: 1;
  }

  #suggestionBox.hide {
    transform: scaleY(0.8);
    opacity: 0;
  }

  #suggestionBox .list-group-item {
    border-left: none;
    border-right: none;
    padding: 10px 15px;
    transition: all 0.2s ease;
  }

  #suggestionBox .list-group-item:first-child {
    border-top: none;
  }

  #suggestionBox .list-group-item:last-child {
    border-bottom: none;
  }

  #suggestionBox .list-group-item:hover {
    background-color: #f5f5f5;
    padding-left: 20px;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    form.row {
      margin-top: -20px;
      padding: 15px;
    }

    .input-group,
    .btn-warning {
      margin-bottom: 10px;
    }
  }

  /* Form elements entrance animation */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .position-relative,
  .col-md-4,
  .col-md-3 {
    animation: fadeInUp 0.6s ease-out forwards;
  }

  .position-relative {
    animation-delay: 0.1s;
  }

  .col-md-4 {
    animation-delay: 0.2s;
  }

  .col-md-3 {
    animation-delay: 0.3s;
  }

  /* Additional Animation Styles */

  /* Typing indicator effect for search input */
  .active-input~.input-group-text .bi-search {
    animation: pulse 1.5s infinite;
  }

  @keyframes pulse {
    0% {
      transform: scale(1);
    }

    50% {
      transform: scale(1.2);
    }

    100% {
      transform: scale(1);
    }
  }

  /* Filter changed animation */
  .filter-changed {
    animation: filterPulse 1s ease;
  }

  @keyframes filterPulse {
    0% {
      transform: scale(1);
    }

    50% {
      transform: scale(1.05);
      background-color: #f9a826;
    }

    100% {
      transform: scale(1);
    }
  }

  /* Button click animation */
  .btn-clicked {
    animation: btnClick 0.3s ease;
  }

  @keyframes btnClick {
    0% {
      transform: scale(1);
    }

    50% {
      transform: scale(0.95);
    }

    100% {
      transform: scale(1);
    }
  }

  /* Form appearance animation */
  .form-appear {
    animation: formAppear 0.8s ease-out forwards;
  }

  @keyframes formAppear {
    from {
      opacity: 0;
      transform: translateY(20px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Add a subtle glow effect to the entire form on hover */
  form.row:hover {
    box-shadow: 0 10px 30px rgba(251, 174, 60, 0.1), 0 0 0 1px rgba(251, 174, 60, 0.05);
  }

  /* Add styles for the search input suggestions */
  #suggestionBox {
    max-height: 250px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #fbae3c #f9f9f9;
  }

  #suggestionBox::-webkit-scrollbar {
    width: 6px;
  }

  #suggestionBox::-webkit-scrollbar-track {
    background: #f9f9f9;
    border-radius: 10px;
  }

  #suggestionBox::-webkit-scrollbar-thumb {
    background-color: #fbae3c;
    border-radius: 10px;
  }

  /* Empty suggestion message */
  .empty-suggestion {
    padding: 15px;
    text-align: center;
    color: #888;
    font-style: italic;
  }

  /* Add a highlight effect to input icons */
  .input-group:focus-within .input-group-text i {
    color: #fbae3c;
    animation: iconPop 0.3s ease;
  }

  @keyframes iconPop {
    0% {
      transform: scale(1);
    }

    50% {
      transform: scale(1.2);
    }

    100% {
      transform: scale(1);
    }
  }

  /* Add floating animation to the form */
  form.row {
    animation: float 6s ease-in-out infinite;
  }

  @keyframes float {

    0%,
    100% {
      transform: translateY(0);
    }

    50% {
      transform: translateY(-5px);
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

  document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.querySelector('input[name="search"]');

    if (!searchInput) return;

    // Tạo hộp gợi ý
    const wrapper = searchInput.closest('.position-relative') || searchInput.parentElement;
    const suggestionBox = document.createElement("div");
    suggestionBox.className = "list-group position-absolute shadow-sm suggestion-box";
    suggestionBox.style.top = "100%";
    suggestionBox.style.left = 0;
    suggestionBox.style.right = 0;
    suggestionBox.style.zIndex = "1050";
    suggestionBox.style.display = "none";
    suggestionBox.style.maxHeight = "300px";
    suggestionBox.style.overflowY = "auto";

    wrapper.appendChild(suggestionBox);

    // Xử lý khi gõ phím
    searchInput.addEventListener("input", function() {
      const keyword = this.value.trim();
      if (keyword.length < 1) {
        suggestionBox.style.display = "none";
        return;
      }

      fetch(`/controllers/SearchController.php?term=${encodeURIComponent(keyword)}`)
        .then(res => res.json())
        .then(data => {
          suggestionBox.innerHTML = '';
          if (!data.length) {
            suggestionBox.style.display = "none";
            return;
          }

          data.forEach(item => {
            const div = document.createElement("div");
            div.className = "list-group-item list-group-item-action";
            div.textContent = item;
            div.addEventListener("click", () => {
              searchInput.value = item;
              suggestionBox.style.display = "none";
            });
            suggestionBox.appendChild(div);
          });

          suggestionBox.style.display = "block";
        })
        .catch(err => {
          console.error("Lỗi gợi ý:", err);
          suggestionBox.style.display = "none";
        });
    });

    // Ẩn suggestion khi click ra ngoài
    document.addEventListener("click", function(e) {
      if (!suggestionBox.contains(e.target) && e.target !== searchInput) {
        suggestionBox.style.display = "none";
      }
    });
  });

  document.addEventListener("DOMContentLoaded", function() {

    const productCards = document.querySelectorAll('.product-card');

    // Apply staggered animation to product cards
    productCards.forEach((card, index) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(20px)';
      card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

      // Staggered delay based on card index
      setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, 100 * index);
    });

    // Add smooth transition when adding to cart
    const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
    addToCartBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        // Visual feedback on button click
        this.classList.add('btn-clicked');

        // Create a floating item that moves to cart
        const productCard = this.closest('.card');
        const productImage = productCard.querySelector('img').cloneNode(true);

        const floatingItem = document.createElement('div');
        floatingItem.className = 'floating-item';
        floatingItem.appendChild(productImage);
        document.body.appendChild(floatingItem);

        // Position at the button's location
        const rect = this.getBoundingClientRect();
        floatingItem.style.top = rect.top + 'px';
        floatingItem.style.left = rect.left + 'px';

        // Get cart position
        const cart = document.getElementById('cart-mini');
        const cartRect = cart.getBoundingClientRect();

        // Animate to cart
        setTimeout(() => {
          floatingItem.style.top = cartRect.top + 'px';
          floatingItem.style.left = cartRect.left + 'px';
          floatingItem.style.transform = 'scale(0.2)';
          floatingItem.style.opacity = '0.5';
        }, 10);

        // Remove the element after animation completes
        setTimeout(() => {
          document.body.removeChild(floatingItem);
          this.classList.remove('btn-clicked');

          // Highlight cart
          cart.classList.add('cart-highlight');
          setTimeout(() => {
            cart.classList.remove('cart-highlight');
          }, 700);
        }, 800);
      });
    });

    // Smooth transition for cart mini
    const cartMini = document.getElementById('cart-mini');
    cartMini.style.transform = 'translateY(20px)';
    cartMini.style.opacity = '0';
    cartMini.style.transition = 'transform 0.4s ease-out, opacity 0.4s ease-out';

    // Override the updateCart function to add animations
    const originalUpdateCart = window.updateCart;
    window.updateCart = function() {
      originalUpdateCart();
      cartMini.style.transform = 'translateY(0)';
      cartMini.style.opacity = '1';

      // Pulse animation for newly added items
      const items = document.getElementById('cart-items').children;
      if (items.length > 0) {
        const lastItem = items[items.length - 1];
        lastItem.classList.add('new-item-pulse');
        setTimeout(() => {
          lastItem.classList.remove('new-item-pulse');
        }, 1000);
      }
    };

    // Transition for category/filter changes
    const filterSelect = document.querySelector('select[name="loai"]');
    filterSelect.addEventListener('change', function() {
      document.querySelectorAll('.product-card').forEach(card => {
        card.classList.add('filter-transition');
      });
    });

    // Page transition for pagination
    const paginationLinks = document.querySelectorAll('.pagination .page-link');
    paginationLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        // Only if it's not the current page
        if (!this.parentElement.classList.contains('active')) {
          e.preventDefault();

          // Start fade out transition
          document.querySelectorAll('.product-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
          });

          // Navigate after transition
          setTimeout(() => {
            window.location.href = this.href;
          }, 300);
        }
      });
    });

    // Smooth transition for search suggestions
    const searchInput = document.querySelector('input[name="search"]');
    const suggestionBox = document.querySelector('.suggestion-box');

    if (searchInput && suggestionBox) {
      searchInput.addEventListener('input', function() {
        if (this.value.trim().length > 0) {
          suggestionBox.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
          suggestionBox.style.transform = 'translateY(5px)';
          suggestionBox.style.opacity = '0';

          setTimeout(() => {
            suggestionBox.style.transform = 'translateY(0)';
            suggestionBox.style.opacity = '1';
          }, 100);
        }
      });
    }
  });

  document.addEventListener("DOMContentLoaded", function() {
    // Banner entrance animation
    const banner = document.querySelector('.py-5[style*="background-color: #e6f9fd"]');

    if (banner) {
      // Create subtle floating shapes in the banner background
      const shapes = ['circle', 'square', 'triangle'];
      const colors = ['#cff5fd', '#b3eafb', '#d7f7fd'];

      for (let i = 0; i < 15; i++) {
        const shape = document.createElement('div');
        const randomShape = shapes[Math.floor(Math.random() * shapes.length)];
        const randomColor = colors[Math.floor(Math.random() * colors.length)];

        shape.className = 'banner-shape ' + randomShape;
        shape.style.backgroundColor = randomColor;
        shape.style.left = Math.random() * 100 + '%';
        shape.style.top = Math.random() * 100 + '%';
        shape.style.animationDelay = (Math.random() * 5) + 's';
        shape.style.animationDuration = (Math.random() * 10 + 10) + 's';

        banner.appendChild(shape);
      }

      // Add interactive parallax effect
      banner.addEventListener('mousemove', function(e) {
        const moveX = (e.clientX / window.innerWidth) - 0.5;
        const moveY = (e.clientY / window.innerHeight) - 0.5;

        const title = banner.querySelector('h1');
        const text = banner.querySelector('p');

        title.style.transform = `translateX(${moveX * 15}px) translateY(${moveY * 15}px)`;
        text.style.transform = `translateX(${moveX * 10}px) translateY(${moveY * 10}px)`;

        const shapes = banner.querySelectorAll('.banner-shape');
        shapes.forEach(shape => {
          const speed = parseFloat(shape.style.animationDuration) / 20;
          shape.style.marginLeft = `${moveX * 30 * speed}px`;
          shape.style.marginTop = `${moveY * 30 * speed}px`;
        });
      });
    }

    // Add wave effect at the bottom of the banner
    const wave = document.createElement('div');
    wave.className = 'banner-wave';
    if (banner) {
      banner.appendChild(wave);
    }
  });

  // Add this to your existing JavaScript code

  document.addEventListener("DOMContentLoaded", function() {
    // Enhanced input field animations
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.querySelector('form.row');
    const suggestionBox = document.getElementById('suggestionBox');
    const filterSelect = document.querySelector('select[name="loai"]');
    const filterBtn = document.querySelector('.btn-warning');

    // Input field focus effects
    if (searchInput) {
      searchInput.addEventListener('focus', function() {
        this.closest('.input-group').style.boxShadow = '0 0 0 3px rgba(251, 174, 60, 0.25)';
        this.closest('.input-group').style.transform = 'translateY(-2px)';
      });

      searchInput.addEventListener('blur', function() {
        this.closest('.input-group').style.boxShadow = '';
        this.closest('.input-group').style.transform = '';
      });

      // Enhanced typing effect
      searchInput.addEventListener('input', function() {
        if (this.value.trim().length > 0) {
          this.classList.add('active-input');

          // Show suggestion box with animation
          if (suggestionBox) {
            suggestionBox.classList.remove('d-none');
            suggestionBox.classList.remove('hide');
            suggestionBox.classList.add('show');
          }
        } else {
          this.classList.remove('active-input');

          // Hide suggestion box with animation
          if (suggestionBox) {
            suggestionBox.classList.add('hide');
            setTimeout(() => {
              suggestionBox.classList.add('d-none');
            }, 300);
          }
        }
      });
    }

    // Filter select animation
    if (filterSelect) {
      filterSelect.addEventListener('change', function() {
        // Add pulse animation to filter button
        if (filterBtn) {
          filterBtn.classList.add('filter-changed');
          setTimeout(() => {
            filterBtn.classList.remove('filter-changed');
          }, 1000);
        }
      });

      filterSelect.addEventListener('focus', function() {
        this.closest('.input-group').style.boxShadow = '0 0 0 3px rgba(251, 174, 60, 0.25)';
        this.closest('.input-group').style.transform = 'translateY(-2px)';
      });

      filterSelect.addEventListener('blur', function() {
        this.closest('.input-group').style.boxShadow = '';
        this.closest('.input-group').style.transform = '';
      });
    }

    // Button hover effect with icon rotation
    if (filterBtn) {
      filterBtn.addEventListener('mouseover', function() {
        const icon = this.querySelector('.bi-funnel-fill');
        if (icon) {
          icon.style.transform = 'rotate(15deg)';
        }
      });

      filterBtn.addEventListener('mouseout', function() {
        const icon = this.querySelector('.bi-funnel-fill');
        if (icon) {
          icon.style.transform = '';
        }
      });

      // Button click animation
      filterBtn.addEventListener('click', function() {
        this.classList.add('btn-clicked');
        setTimeout(() => {
          this.classList.remove('btn-clicked');
        }, 300);
      });
    }

    // Add animation when search form appears
    if (searchForm) {
      searchForm.classList.add('form-appear');
    }

    // Improved suggestion item interaction
    if (suggestionBox) {
      // Create a function to handle suggestion item creation
      window.createSuggestionItem = function(text) {
        const item = document.createElement('div');
        item.className = 'list-group-item list-group-item-action';
        item.textContent = text;

        // Add hover highlight effect
        item.addEventListener('mouseover', function() {
          this.style.borderLeft = '3px solid #fbae3c';
        });

        item.addEventListener('mouseout', function() {
          this.style.borderLeft = '';
        });

        // Add click effect
        item.addEventListener('click', function() {
          if (searchInput) {
            searchInput.value = text;

            // Hide suggestion box with animation
            suggestionBox.classList.add('hide');
            setTimeout(() => {
              suggestionBox.classList.add('d-none');
            }, 300);

            // Add "selected" visual feedback
            this.style.backgroundColor = 'rgba(251, 174, 60, 0.1)';
            setTimeout(() => {
              this.style.backgroundColor = '';
            }, 500);
          }
        });

        return item;
      };
    }
  });

  new Swiper(".adSwiper", {
    loop: true,
    autoplay: {
      delay: 3000
    },
    spaceBetween: 20, // 👈 khoảng cách giữa slide
    slidesPerView: 1,
  });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>