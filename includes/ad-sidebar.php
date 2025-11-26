<?php require_once('../admin/admin-auth.php');
require_once __DIR__ . '/../config/config.php';
?>
<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-logo text-center py-3">
    <h4 class="fw-bold mb-0">MedxtorePhamarcy</h4>
  </div>

  <div class="sidebar-menu">
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
          <i class="fas fa-chart-pie"></i> Tổng Quan
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage-products.php' ? 'active' : '' ?>" href="manage-products.php">
          <i class="fas fa-pills"></i> Quản Lý Thuốc
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage-categories.php' ? 'active' : '' ?>" href="manage-categories.php">
          <i class="fas fa-tags"></i> Loại Thuốc
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage-orders.php' ? 'active' : '' ?>" href="manage-orders.php">
          <i class="fas fa-shopping-cart"></i> Đơn Hàng</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage-consultations.php' ? 'active' : '' ?>" href="manage-consultations.php">
          <i class="fas fa-clipboard-list"></i> Yêu Cầu Tư Vấn</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../pages/pos-dashboard.php" target="_blank">
          <i class="fas fa-cash-register"></i> Bán Hàng POS
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage-customers.php' ? 'active' : '' ?>" href="manage-customers.php">
          <i class="fas fa-users"></i> Khách Hàng
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage-vouchers.php' ? 'active' : '' ?>" href="manage-vouchers.php">
          <i class="fas fa-percent"></i> Khuyến Mãi
        </a>
      </li>
      <li class="nav-item">
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage-blogs.php' ? 'active' : '' ?>" href="manage-blogs.php">
        <i class="fas fa-newspaper"></i> Quản Lý Bài Viết
    </a>
    <li class="nav-item">
  <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage-blog-categories.php' ? 'active' : '' ?>" href="manage-blog-categories.php">
    <i class="fas fa-tags"></i> Loại Bài Viết
  </a>
</li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'import-inventory.php' ? 'active' : '' ?>" href="import-inventory.php">
          <i class="fas fa-truck-loading"></i> Nhập Kho
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage-inventory.php' ? 'active' : '' ?>" href="manage-inventory.php">
          <i class="fas fa-warehouse"></i> Kho Thuốc
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'inventory-report.php' ? 'active' : '' ?>" href="inventory-report.php">
          <i class="fas fa-chart-bar"></i> Báo Cáo Tồn Kho
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'purchase-suggestions.php' ? 'active' : '' ?>" href="purchase-suggestions.php">
          <i class="fas fa-shopping-basket"></i> Gợi Ý Mua Hàng
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'demand-forecast.php' ? 'active' : '' ?>" href="demand-forecast.php">
          <i class="fas fa-chart-line"></i> Dự Đoán Nhu Cầu
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/pages/home.php" target="_blank">
          <i class="fas fa-globe"></i> Xem Trang Người Dùng
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : '' ?>" href="report.php">
          <i class="fas fa-chart-bar"></i> Báo Cáo</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'sales-report.php' ? 'active' : '' ?>" href="sales-report.php">
          <i class="fas fa-star"></i> Top Sản Phẩm Bán Chạy
        </a>
      </li>
    </ul>
  </div>
</div>