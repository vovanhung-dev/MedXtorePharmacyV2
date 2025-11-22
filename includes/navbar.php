<?php
require_once __DIR__ . '/../includes/config.php';

if (file_exists(__DIR__ . '/functions.php')) {
  include_once(__DIR__ . '/functions.php');
}

$currentPage = basename($_SERVER['SCRIPT_NAME']);

// ✅ Đếm số mặt hàng trong giỏ hàng theo user đăng nhập
$cartCount = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['carts'][$_SESSION['user_id']])) {
  $cartCount = count($_SESSION['carts'][$_SESSION['user_id']]);
}
?>

<style>
  /* Reset tất cả hiệu ứng chuyển động có thể gây giật */
  .custom-navbar * {
    transform: none !important;
  }
  
  /* Base navbar styles */
  .custom-navbar {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  }

  /* Brand styling */
  .custom-navbar .navbar-brand {
    font-size: 1.5rem;
    font-weight: 700;
  }

  /* Navigation links - chỉ sử dụng hiệu ứng màu sắc */
  .custom-navbar .nav-link {
    font-weight: 500;
    color: #333;
    padding: 0.5rem 0.75rem;
    margin: 0 0.25rem;
    border-radius: 4px;
    transition: color 0.2s ease, background-color 0.2s ease;
  }

  .custom-navbar .nav-link:hover,
  .custom-navbar .nav-link.active {
    color: #FBAE3C;
    background-color: rgba(251, 174, 60, 0.08);
  }

  /* Cart badge - vị trí cố định không di chuyển */
  .cart-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    font-size: 0.65rem;
    padding: 2px 5px;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #dc3545;
    color: white;
    border: 2px solid white;
  }

  /* Button styles - không dùng transform hay shadow */
  .custom-navbar .btn {
    font-weight: 500;
    padding: 0.375rem 1rem;
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    position: relative;
  }

  /* User dropdown */
  #userDropdown {
    min-width: 120px;
    text-align: left;
  }

  .dropdown-menu {
    border-radius: 0.5rem;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0,0,0,0.08);
    padding: 0.5rem 0;
    margin-top: 0.5rem !important;
  }

  .dropdown-item {
    padding: 0.5rem 1.5rem;
    transition: background-color 0.2s ease;
  }

  .dropdown-item:hover {
    background-color: rgba(251, 174, 60, 0.08);
  }

  /* Fix vị trí cố định cho nút giỏ hàng */
  .cart-btn-container {
    position: relative;
    height: 38px; /* Chiều cao cố định bằng với nút */
    display: inline-block;
  }

  /* Mobile responsiveness */
  @media (max-width: 991.98px) {
    .custom-navbar .nav-link {
      padding: 0.5rem 1rem;
      margin: 0.25rem 0;
    }
  }
</style>

<nav class="navbar navbar-expand-lg navbar-light bg-white py-3 sticky-top custom-navbar">
  <div class="container">
    <a class="navbar-brand" href="<?= page_url('home.php'); ?>">
      <span class="text-dark">MED</span><span style="color:#FBAE3C">XTORE</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto ms-lg-4">
        <li class="nav-item">
          <a class="nav-link fw-semibold <?= $currentPage == 'home.php' ? 'active' : '' ?>" href="<?= page_url('home.php'); ?>">Trang Chủ</a>
        </li>
        <li class="nav-item">
          <a class="nav-link fw-semibold <?= $currentPage == 'products.php' ? 'active' : '' ?>" href="<?= page_url('products/products.php'); ?>">Sản Phẩm</a>
        </li>
        <li class="nav-item">
          <a class="nav-link fw-semibold <?= $currentPage == 'about.php' ? 'active' : '' ?>" href="<?= page_url('about.php'); ?>">Về Chúng Tôi</a>
        </li>
        <li class="nav-item">
          <a class="nav-link fw-semibold <?= $currentPage == 'blog.php' ? 'active' : '' ?>" href="<?= page_url('blog.php'); ?>">Bài Viết</a>
        </li>
      </ul>

      <div class="d-flex align-items-center gap-3">
        <?php if (isset($_SESSION['user_id'])): ?>
          <!-- Dropdown người dùng -->
          <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle rounded-pill" 
                    type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="<?= page_url('profile.php'); ?>"><i class="bi bi-person me-2"></i>Hồ sơ cá nhân</a></li>
              <li><a class="dropdown-item" href="<?= page_url('order-history.php'); ?>"><i class="bi bi-clipboard-check me-2"></i>Lịch sử đơn hàng</a></li>
              <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] == 1): ?>
                <li><a class="dropdown-item" href="<?= url('/admin'); ?>"><i class="bi bi-gear me-2"></i>Quản trị viên</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= $base_url; ?>/pages/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
            </ul>
          </div>

          <!-- Nút giỏ hàng có container cố định -->
          <div class="cart-btn-container">
            <a href="<?= page_url('cart.php'); ?>" class="btn btn-outline-dark rounded-pill cart-btn">
              <i class="bi bi-cart me-1"></i> Giỏ hàng
              <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
              <?php endif; ?>
            </a>
          </div>

        <?php else: ?>
          <a href="<?= page_url('login.php'); ?>" class="btn btn-outline-primary rounded-pill me-2">
            <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
          </a>
          <a href="<?= page_url('register.php'); ?>" class="btn btn-primary rounded-pill">
            <i class="bi bi-person-plus me-1"></i> Đăng ký
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>