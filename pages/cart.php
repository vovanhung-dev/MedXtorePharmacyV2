<?php
session_start();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

// ✅ Nếu chưa đăng nhập → chuyển sang trang đăng nhập
if (!isset($_SESSION['user_id'])) {
  // Sau khi đăng nhập xong sẽ quay lại giỏ hàng
  $_SESSION['redirect_after_login'] = '/pages/cart.php';
  header("Location: /pages/login.php");
  exit();
}

// ✅ Lấy giỏ hàng riêng cho user hiện tại
$userId = $_SESSION['user_id'];
$cart = $_SESSION['carts'][$userId] ?? [];
$tongTien = array_sum(array_column($cart, 'tongtien'));
$soLuongSP = count($cart);
?>


<style>
  .cart-section {
    min-height: 95vh;
    padding-bottom: 175px;
    background-color: #f8f9fa;
  }

  .cart-header {
    background: linear-gradient(135deg, #13b0c9 0%, #3498db 100%);
    color: white;
    padding: 2rem 0;
    border-radius: 0 0 20px 20px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
  }

  .cart-header::after {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: url("/assets/images/pattern.svg") repeat;
    opacity: 0.1;
  }

  .cart-header h2 {
    position: relative;
    z-index: 2;
  }

  .cart-container {
    background-color: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
  }

  .cart-item {
    border-radius: 10px;
    margin-bottom: 1rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
    border: 1px solid #eaeaea;
  }

  .cart-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
  }

  .product-image {
    transition: transform 0.3s ease;
    width: 80px;
    height: 80px;
    object-fit: cover;
  }

  .cart-item:hover .product-image {
    transform: scale(1.1);
  }

  .btn-quantity {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    border: 1px solid #dee2e6;
    background-color: white;
    transition: all 0.2s ease;
  }

  .btn-quantity:hover {
    background-color: #3498db;
    color: white;
    border-color: #3498db;
  }

  .quantity-input {
    width: 50px;
    text-align: center;
    border: none;
    background: transparent;
    font-weight: bold;
  }

  .cart-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 20px;
  }

  .summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px dashed #dee2e6;
  }

  .total-price {
    font-size: 1.5rem;
    font-weight: bold;
    color: #3498db;
  }

  .btn-action {
    border-radius: 50px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .btn-checkout {
    background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
    border: none;
    color: white;
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
  }

  .btn-checkout:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(39, 174, 96, 0.3);
  }

  .btn-continue {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    border: none;
    color: white;
  }

  .btn-continue:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(52, 152, 219, 0.3);
  }

  .btn-clear {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    border: none;
    color: white;
  }

  .btn-clear:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(231, 76, 60, 0.3);
  }

  .btn-update {
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    transition: all 0.3s ease;
  }

  .btn-update:hover {
    background-color: #2980b9;
    transform: translateY(-2px);
  }

  .btn-delete {
    background-color: #e74c3c;
    color: white;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
  }

  .btn-delete:hover {
    background-color: #c0392b;
    transform: rotate(90deg);
  }

  .empty-cart {
    text-align: center;
    padding: 3rem 0;
  }

  .empty-cart img {
    max-width: 150px;
    opacity: 0.7;
    animation: float 3s ease-in-out infinite;
  }

  @keyframes float {
    0% {
      transform: translateY(0px);
    }

    50% {
      transform: translateY(-15px);
    }

    100% {
      transform: translateY(0px);
    }
  }

  .cart-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0% {
      transform: scale(1);
    }

    50% {
      transform: scale(1.1);
    }

    100% {
      transform: scale(1);
    }
  }

  .cart-title {
    position: relative;
    display: inline-block;
  }

  .cart-title::after {
    content: "";
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 3px;
    background: #3498db;
    border-radius: 3px;
  }

  /* Animations */
  .fade-in {
    animation: fadeIn 0.5s ease-in;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(20px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Custom scrollbar */
  .custom-scrollbar {
    max-height: 500px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #3498db #f1f1f1;
  }

  .custom-scrollbar::-webkit-scrollbar {
    width: 6px;
  }

  .custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }

  .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #3498db;
    border-radius: 10px;
  }

  .stagger-animation {
    opacity: 0;
    animation: staggerFadeIn 0.5s ease forwards;
  }

  @keyframes staggerFadeIn {
    from {
      opacity: 0;
      transform: translateX(-20px);
    }

    to {
      opacity: 1;
      transform: translateX(0);
    }
  }
</style>

<div class="cart-section">
  <div class="cart-header position-relative">
    <div class="container">
      <h2 class="text-center cart-title fw-bold">
        <i class="bi bi-cart-fill me-2"></i>Giỏ hàng của bạn
      </h2>
      <?php if (!empty($cart)): ?>
        <div class="cart-badge"><?= count($cart) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="container fade-in">
    <?php if (empty($cart)): ?>
      <div class="empty-cart">
        <img src="/assets/images/empty.png" alt="Giỏ hàng trống">
        <h3 class="mt-4 text-muted">Giỏ hàng của bạn đang trống</h3>
        <p class="text-muted mb-4">Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm</p>
        <a href="/pages/products/products.php" class="btn btn-action btn-continue">
          <i class="bi bi-arrow-left"></i> Bắt đầu mua sắm
        </a>
      </div>
    <?php else: ?>
      <div class="row">
        <!-- Phần danh sách sản phẩm -->
        <div class="col-lg-8 mb-4">
          <div class="cart-container">
            <h4 class="mb-4"><i class="bi bi-bag-check me-2"></i>Sản phẩm (<?= $soLuongSP ?>)</h4>

            <div class="custom-scrollbar">
              <?php $i = 0;
              foreach ($cart as $key => $item): $i++; ?>
                <div class="cart-item p-3 mb-3 stagger-animation" style="animation-delay: <?= $i * 0.1 ?>s">
                  <div class="row align-items-center">
                    <div class="col-md-2 col-3 text-center">
                      <div class="overflow-hidden rounded">
                        <img src="/assets/images/product-images/<?= htmlspecialchars($item['hinhanh']) ?>"
                          alt="<?= htmlspecialchars($item['ten_thuoc']) ?>"
                          class="product-image rounded">
                      </div>
                    </div>

                    <div class="col-md-3 col-9">
                      <h5 class="mb-1"><?= htmlspecialchars($item['ten_thuoc']) ?></h5>
                      <span class="badge bg-light text-dark">Thuốc</span>
                    </div>

                    <div class="col-md-3 col-6 mt-3 mt-md-0">
                      <form action="/controllers/CartController.php" method="POST" class="d-flex align-items-center">
                        <input type="hidden" name="key" value="<?= $key ?>">

                        <div class="input-group">
                          <input type="number" name="soluong" value="<?= $item['soluong'] ?>" min="1"
                            class="form-control form-control-sm text-center"
                            style="width: 60px">
                          <button type="submit" name="update_qty" class="btn btn-update btn-sm">
                            <i class="bi bi-arrow-repeat"></i>
                          </button>
                        </div>
                      </form>
                    </div>

                    <div class="col-md-2 col-6 text-md-center mt-3 mt-md-0">
                      <div class="price">
                        <span class="fs-6 text-muted"><?= number_format($item['gia'], 0, ',', '.') ?>đ</span>
                      </div>
                    </div>

                    <div class="col-md-1 col-6 text-danger text-md-center fw-bold mt-3 mt-md-0">
                      <?= number_format($item['tongtien'], 0, ',', '.') ?>đ
                    </div>

                    <div class="col-md-1 col-6 text-end mt-3 mt-md-0">
                      <a href="/controllers/CartController.php?delete=<?= $key ?>"
                        class="btn btn-delete"
                        onclick="return confirm('Bạn có chắc muốn xoá sản phẩm này khỏi giỏ hàng?');">
                        <i class="bi bi-trash"></i>
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-between mt-4">
              <a href="/controllers/CartController.php?clear=all"
                class="btn btn-action btn-clear"
                onclick="return confirm('Bạn có chắc chắn muốn xoá toàn bộ giỏ hàng?');">
                <i class="bi bi-trash me-1"></i> Xoá tất cả
              </a>

              <a href="/pages/products/products.php"
                class="btn btn-action btn-continue">
                <i class="bi bi-arrow-left me-1"></i> Tiếp tục mua hàng
              </a>
            </div>
          </div>
        </div>

        <!-- Phần tổng tiền và thanh toán -->
        <div class="col-lg-4">
          <div class="cart-summary">
            <h4 class="mb-4"><i class="bi bi-receipt me-2"></i>Tổng đơn hàng</h4>

            <div class="summary-item">
              <span>Tổng sản phẩm:</span>
              <span><?= $soLuongSP ?></span>
            </div>

            <div class="summary-item">
              <span>Tạm tính:</span>
              <span><?= number_format($tongTien, 0, ',', '.') ?>đ</span>
            </div>

            <div class="summary-item">
              <span>Phí vận chuyển:</span>
              <span>0đ</span>
            </div>

            <div class="mt-4 pt-2">
              <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold">Thành tiền:</span>
                <span class="total-price"><?= number_format($tongTien, 0, ',', '.') ?>đ</span>
              </div>
              <small class="text-muted">(Đã bao gồm VAT nếu có)</small>
            </div>

            <form method="POST" action="/pages/checkout.php">
              <button type="submit" class="btn btn-checkout w-100">
                <i class="bi bi-credit-card me-2"></i> Tiến hành thanh toán
              </button>
            </form>

            <div class="mt-4 text-center">
              <div class="d-flex justify-content-center gap-2">
                <i class="bi bi-shield-check text-success fs-4"></i>
                <i class="bi bi-truck text-primary fs-4"></i>
                <i class="bi bi-currency-exchange text-warning fs-4"></i>
              </div>
              <small class="text-muted">Thanh toán an toàn & bảo mật</small>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  // Animation khi scroll
  document.addEventListener('DOMContentLoaded', function() {
    const items = document.querySelectorAll('.stagger-animation');

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateX(0)';
        }
      });
    }, {
      threshold: 0.1
    });

    items.forEach(item => {
      observer.observe(item);
    });

    // Hiệu ứng khi update số lượng
    const quantityInputs = document.querySelectorAll('input[name="soluong"]');
    quantityInputs.forEach(input => {
      const originalValue = input.value;

      input.addEventListener('change', function() {
        if (this.value !== originalValue) {
          this.closest('.input-group').classList.add('animate__animated', 'animate__pulse');

          setTimeout(() => {
            this.closest('.input-group').classList.remove('animate__animated', 'animate__pulse');
          }, 1000);
        }
      });
    });
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>