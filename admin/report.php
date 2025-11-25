<?php
include_once('../includes/ad-header.php');
include_once('../includes/ad-sidebar.php');
include_once('../config/database.php');

// Kết nối database
$db = new Database();
$conn = $db->getConnection();

// Lấy tổng số sản phẩm (thay vì tổng số thuốc)
$sql_total_products = "SELECT COUNT(*) AS total FROM thuoc";
$stmt_total_products = $conn->prepare($sql_total_products);
$stmt_total_products->execute();
$total_products = $stmt_total_products->fetch(PDO::FETCH_ASSOC)['total'];

// Lấy thông tin đơn hàng hôm nay từ cart.php
$today = date('Y-m-d');
$sql_orders_today = "SELECT COUNT(*) AS total FROM donhang WHERE DATE(ngay_dat) = :today";
$stmt_orders_today = $conn->prepare($sql_orders_today);
$stmt_orders_today->bindParam(':today', $today);
$stmt_orders_today->execute();
$orders_today = $stmt_orders_today->fetch(PDO::FETCH_ASSOC)['total'];

// Lấy thông tin khách hàng mới trong hôm nay
$sql_new_customers = "SELECT COUNT(*) AS total FROM khachhang WHERE DATE(ngay_tao) = :today";
$stmt_new_customers = $conn->prepare($sql_new_customers);
$stmt_new_customers->bindParam(':today', $today);
$stmt_new_customers->execute();
$new_customers = $stmt_new_customers->fetch(PDO::FETCH_ASSOC)['total'];

// Lấy tổng thu (tổng tiền từ tất cả đơn hàng)
$sql_total_revenue = "SELECT SUM(tongtien) AS total FROM donhang";
$stmt_total_revenue = $conn->prepare($sql_total_revenue);
$stmt_total_revenue->execute();
$total_revenue = $stmt_total_revenue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Lấy tổng chi (tổng tiền từ nhập kho)
$sql_total_expenses = "SELECT SUM(soluong * gia) AS total FROM khohang";
$stmt_total_expenses = $conn->prepare($sql_total_expenses);
$stmt_total_expenses->execute();
$total_expenses = $stmt_total_expenses->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Ngưỡng cảnh báo tồn kho
$alert_threshold = 10;

// Lấy danh sách sản phẩm có số lượng dưới ngưỡng cảnh báo (10)
$sql_low_inventory = "SELECT t.id, t.ten_thuoc, t.hinhanh, lt.ten_loai, 
                    IFNULL(SUM(kh.soluong), 0) as soluong, MAX(kh.hansudung) as hansudung
                    FROM thuoc t
                    JOIN loai_thuoc lt ON t.loai_id = lt.id
                    LEFT JOIN khohang kh ON t.id = kh.thuoc_id
                    GROUP BY t.id, t.ten_thuoc, t.hinhanh, lt.ten_loai
                    HAVING IFNULL(SUM(kh.soluong), 0) <= :threshold
                    ORDER BY soluong ASC";
$stmt_low_inventory = $conn->prepare($sql_low_inventory);
$stmt_low_inventory->bindParam(':threshold', $alert_threshold, PDO::PARAM_INT);
$stmt_low_inventory->execute();
$low_inventory_items = $stmt_low_inventory->fetchAll(PDO::FETCH_ASSOC);
$low_inventory_count = count($low_inventory_items);

// Adjust the SQL query to fetch revenue data based on the selected period
$selected_period = isset($_GET['period']) && $_GET['period'] == 'month' ? 30 : 7;

$chart_labels = [];
$chart_data = [];
for($i = $selected_period - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($date));
    
    $sql_revenue = "SELECT SUM(tongtien) AS total FROM donhang WHERE DATE(ngay_dat) = :date";
    $stmt_revenue = $conn->prepare($sql_revenue);
    $stmt_revenue->bindParam(':date', $date);
    $stmt_revenue->execute();
    $revenue = $stmt_revenue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $chart_data[] = $revenue;
}

// Lấy thông tin top 5 sản phẩm bán chạy
$sql_top_products = "SELECT t.ten_thuoc, COUNT(cd.id) AS total_sold
                    FROM chitiet_donhang cd
                    JOIN thuoc t ON cd.thuoc_id = t.id
                    JOIN donhang d ON cd.donhang_id = d.id
                    WHERE DATE(d.ngay_dat) BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
                    GROUP BY cd.thuoc_id, t.ten_thuoc
                    ORDER BY total_sold DESC
                    LIMIT 5";
$stmt_top_products = $conn->prepare($sql_top_products);
$stmt_top_products->execute();
$top_products = $stmt_top_products->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Global Styling Enhancements */
    :root {
        --primary-color: #4e73df;
        --secondary-color: #36b9cc;
        --success-color: #1cc88a;
        --warning-color: #f6c23e;
        --danger-color: #e74a3b;
        --info-color: #858796;
        --soft-background: #f8f9fc;
    }

    body {
        font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
        background-color: var(--soft-background);
        color: #2e2e2e;
    }

    .main-content {
        padding: 1.5rem;
        background-color: transparent;
    }

    /* Enhanced Dashboard Cards */
    .dashboard-card {
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        border: none;
        box-shadow: 0 4px 6px rgba(50, 50, 93, 0.05), 0 1px 3px rgba(0, 0, 0, 0.08);
        width: 100%;
    }

    .dashboard-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(50, 50, 93, 0.1), 0 4px 6px rgba(0, 0, 0, 0.08);
    }

    .card-icon {
        background: linear-gradient(135deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 80%, white));
        transition: all 0.3s ease;
    }

    .dashboard-card:hover .card-icon {
        transform: rotate(15deg) scale(1.1);
    }

    /* Refined Color Scheme */
    .icon-medication { background: linear-gradient(135deg, #4e73df, #224abe); }
    .icon-order { background: linear-gradient(135deg, #36b9cc, #1a8997); }
    .icon-customer { background: linear-gradient(135deg, #f6c23e, #dda20a); }
    .icon-revenue { background: linear-gradient(135deg, #1cc88a, #13855c); }

    /* Subtle Animations */
    @keyframes subtle-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }

    .pulse-hover:hover {
        animation: subtle-pulse 0.5s ease;
    }

    /* Enhanced Responsive Typography */
    .text-dashboard-title {
        font-weight: 600;
        letter-spacing: -0.5px;
        color: var(--primary-color);
    }

    .text-dashboard-value {
        font-weight: 700;
        letter-spacing: -0.3px;
    }

    /* Alert Styling */
    .alert-inventory {
        border-left: 4px solid var(--danger-color);
        background-color: color-mix(in srgb, var(--danger-color) 10%, transparent);
        transition: all 0.3s ease;
    }

    .alert-inventory:hover {
        transform: translateX(5px);
    }

    /* Refined Chart Styling */
    .chart-area {
        position: relative;
        height: 350px !important;
    }

    @media (min-width: 992px) {
        .chart-area {
            height: 400px !important;
        }
    }

    @media (min-width: 1200px) {
        .chart-area {
            height: 450px !important;
        }
    }

    /* Responsive Tweaks */
    @media (max-width: 768px) {
        .main-content {
            padding: 0.5rem;
        }
        
        .dashboard-card {
            margin-bottom: 1rem;
        }
    }

    /* New CSS for single row layout */
    .row.mb-4 {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
    }

    .col-md-3 {
        flex: 0 0 auto;
        width: 20%;
    }

    /* CSS for single row stats */
    @media (min-width: 768px) {
        .row.mb-4 {
            display: flex;
            flex-wrap: wrap;
        }
        
        .dashboard-card {
            height: 100%;
        }
    }

    @media (max-width: 767px) {
        .dashboard-card {
            width: 100%;
        }
    }

    /* Adjusted card size for smaller screens */
    @media (max-width: 1199px) {
        .col-md-3 {
            flex: 0 0 auto;
            width: 33.33%;
        }
    }

    @media (max-width: 991px) {
        .col-md-3 {
            width: 50%;
        }
    }

    @media (max-width: 575px) {
        .col-md-3 {
            width: 100%;
        }
    }

    /* Đảm bảo hai bảng có cùng chiều cao */
    @media (min-width: 992px) {
        .row .col-lg-6 .card {
            height: calc(100% - 1.5rem); /* Trừ đi margin-bottom */
        }
        
        .card-body[style*="max-height"] {
            max-height: 280px !important; /* Điều chỉnh chiều cao phần body của cảnh báo tồn kho */
        }
    }

    /* Làm nổi bật dropdown chọn thời gian */
    .dropdown-item.active {
        background-color: var(--primary-color);
        color: white;
    }

    /* Đảm bảo hai bảng ở hàng thứ ba có chiều cao bằng nhau */
    .card.h-100 {
        height: 100% !important;
        display: flex;
        flex-direction: column;
    }

    .card.h-100 .card-body {
        flex: 1 1 auto;
        overflow: auto;
    }

    /* Làm cho danh sách sản phẩm bán chạy hiển thị đẹp hơn */
    .badge.rounded-circle {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
    }

    /* Đảm bảo nút trong footer có kích thước đồng nhất */
    .card-footer .btn {
        min-width: 120px;
    }

    /* Bổ sung hiệu ứng hover cho các dòng trong bảng */
    .table-hover tr:hover {
        background-color: rgba(78, 115, 223, 0.05);
    }

    /* Hiển thị badge tồn kho */
    .badge.bg-danger {
        background-color: #e74a3b !important;
    }

    .badge.bg-warning {
        background-color: #f6c23e !important;
    }

    .badge.bg-success {
        background-color: #1cc88a !important;
    }

    /* Làm cho button "Nhập thêm" nổi bật hơn */
    .btn-danger {
        background-color: #e74a3b;
        border-color: #e74a3b;
    }

    .btn-danger:hover {
        background-color: #c13b2d;
        border-color: #c13b2d;
    }
</style>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Thông báo cảnh báo tồn kho nếu có -->
        <?php if($low_inventory_count > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-4 pulse-animation" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Cảnh báo tồn kho!</strong> Có <?= $low_inventory_count ?> sản phẩm có số lượng tồn kho dưới ngưỡng an toàn<?php if(count(array_filter($low_inventory_items, function($item) { return $item['soluong'] == 0; })) > 0): ?>, trong đó có <strong class="text-danger"><?= count(array_filter($low_inventory_items, function($item) { return $item['soluong'] == 0; })) ?> sản phẩm đã hết hàng</strong><?php endif; ?>.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
    
        <!-- Page Heading -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tachometer-alt me-2"></i>Báo Cáo
            </h1>
            <div>
                <button id="refreshBtn" class="btn btn-sm btn-primary">
                    <i class="fas fa-sync-alt me-1"></i> Làm mới dữ liệu
                </button>
            </div>
        </div>

        <!-- Thống kê nhanh - Tất cả trong một hàng -->
        <div class="row mb-4">
            <!-- Tổng số sản phẩm -->
            <div class="col-md-3 col-lg-2 mb-4">
                <div class="dashboard-card bg-white p-3 fade-in" style="animation-delay: 0s">
                    <div class="d-flex align-items-center">
                        <div class="card-icon icon-medication me-3">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng Số Sản Phẩm
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_products) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Đơn hàng hôm nay -->
            <div class="col-md-3 col-lg-2 mb-4">
                <div class="dashboard-card bg-white p-3 fade-in" style="animation-delay: 0.1s">
                    <div class="d-flex align-items-center">
                        <div class="card-icon icon-order me-3">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Đơn Hàng Hôm Nay
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $orders_today ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Khách hàng mới -->
            <div class="col-md-3 col-lg-2 mb-4">
                <div class="dashboard-card bg-white p-3 fade-in" style="animation-delay: 0.2s">
                    <div class="d-flex align-items-center">
                        <div class="card-icon icon-customer me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Khách Hàng Mới
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $new_customers ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tổng Thu -->
            <div class="col-md-3 col-lg-2 mb-4">
                <div class="dashboard-card bg-white p-3 fade-in" style="animation-delay: 0.3s">
                    <div class="d-flex align-items-center">
                        <div class="card-icon icon-revenue me-3">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tổng Thu
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_revenue, 0, ',', '.') ?> đ</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tổng Chi -->
            <div class="col-md-3 col-lg-2 mb-4">
                <div class="dashboard-card bg-white p-3 fade-in" style="animation-delay: 0.4s">
                    <div class="d-flex align-items-center">
                        <div class="card-icon me-3" style="background: linear-gradient(135deg, #e74a3b, #c0392b);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Tổng Chi
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_expenses, 0, ',', '.') ?> đ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Biểu đồ doanh thu - chiếm toàn bộ hàng -->
            <div class="col-lg-12 mb-4">
                <div class="card shadow fade-in" style="animation-delay: 0.5s">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Doanh Thu <?= $selected_period == 30 ? '30' : '7' ?> Ngày Gần Đây
                        </h6>
                        <!-- Dropdown chọn khoảng thời gian -->
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle d-flex align-items-center" href="#" role="button" id="dropdownMenuLink" 
                               data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-calendar-alt fa-sm text-gray-400 me-1"></i>
                                <span><?= $selected_period == 30 ? '30 ngày' : '7 ngày' ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" 
                                 aria-labelledby="dropdownMenuLink">
                                <div class="dropdown-header">Chọn thời gian:</div>
                                <a class="dropdown-item <?= $selected_period == 7 ? 'active' : '' ?>" href="?period=week">7 ngày</a>
                                <a class="dropdown-item <?= $selected_period == 30 ? 'active' : '' ?>" href="?period=month">30 ngày</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 350px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Hàng thứ ba: Top sản phẩm và danh sách thuốc -->
        <div class="row">
            <!-- Top sản phẩm bán chạy - với thứ hạng đẹp hơn -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow fade-in h-100" style="animation-delay: 0.7s">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-trophy me-2"></i>Top Sản Phẩm Bán Chạy
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if(count($top_products) > 0): ?>
                            <?php foreach($top_products as $index => $product): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <!-- Cải thiện hiển thị thứ hạng (từ thấp đến cao) -->
                                        <div class="me-3">
                                            <span class="badge <?= $index < 3 ? 'bg-primary' : 'bg-secondary' ?> rounded-circle" 
                                                  style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                                                <?= $index + 1 ?>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($product['ten_thuoc']) ?></h6>
                                            <small class="text-muted"><?= $product['total_sold'] ?> đã bán</small>
                                        </div>
                                    </div>
                                    <div class="progress-bar" style="width: 100px;">
                                        <?php $percentage = ($product['total_sold'] / ($top_products[0]['total_sold'] > 0 ? $top_products[0]['total_sold'] : 1)) * 100; ?>
                                        <div class="fill" style="width: <?= $percentage ?>%; background-color: #4e73df;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p>Chưa có dữ liệu bán hàng.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="sales-report.php" class="btn btn-outline-primary btn-sm">Xem Top sản phẩm</a>
                    </div>
                </div>
            </div>

            <!-- Danh sách sản phẩm mới cập nhật - loại bỏ cảnh báo dưới 10 -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow fade-in h-100" style="animation-delay: 0.8s">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>Danh Sách Thuốc Mới Nhất
                        </h6>
                        <a href="manage-products.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>Thêm thuốc
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Hình ảnh</th>
                                        <th>Tên thuốc</th>
                                        <th>Loại</th>
                                        <th>Tồn kho</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Lấy danh sách thuốc mới nhất với tổng số lượng tồn kho
                                    $sql_recent_products = "SELECT t.id, t.ten_thuoc, t.hinhanh, lt.ten_loai, 
                                                           IFNULL(SUM(kh.soluong), 0) as soluong
                                                           FROM thuoc t
                                                           LEFT JOIN loai_thuoc lt ON t.loai_id = lt.id
                                                           LEFT JOIN khohang kh ON t.id = kh.thuoc_id
                                                           GROUP BY t.id, t.ten_thuoc, t.hinhanh, lt.ten_loai
                                                           ORDER BY t.ngay_tao DESC
                                                           LIMIT 5";
                                    $stmt_recent_products = $conn->prepare($sql_recent_products);
                                    $stmt_recent_products->execute();
                                    $recent_products = $stmt_recent_products->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach($recent_products as $index => $product):
                                        // Bỏ cảnh báo dưới 10
                                        $product_units = [];
                                        $sql_product_units = "SELECT d.ten_donvi, kh.soluong 
                                                             FROM khohang kh 
                                                             JOIN donvi d ON kh.donvi_id = d.id 
                                                             WHERE kh.thuoc_id = :thuoc_id";
                                        $stmt_product_units = $conn->prepare($sql_product_units);
                                        $stmt_product_units->bindParam(':thuoc_id', $product['id']);
                                        $stmt_product_units->execute();
                                        $product_units = $stmt_product_units->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <img src="/assets/images/product-images/<?= htmlspecialchars($product['hinhanh']) ?>"
                                                alt="<?= htmlspecialchars($product['ten_thuoc']) ?>"
                                                style="width: 50px; height: 50px; object-fit: contain; border-radius: 6px; background: #f8f8f8;">
                                        </td>
                                        <td><?= htmlspecialchars($product['ten_thuoc']) ?></td>
                                        <td><?= htmlspecialchars($product['ten_loai']) ?></td>
                                        <td>
                                            <?php if(count($product_units) > 0):
                                                foreach($product_units as $unit):
                                                    // Sử dụng màu đỏ cho sản phẩm hết hàng, màu xanh cho sản phẩm đủ hàng
                                                    $unit_color = $unit['soluong'] == 0 ? 'danger' : ($unit['soluong'] <= $alert_threshold ? 'warning' : 'success');
                                                    $text_color = $unit['soluong'] == 0 ? 'text-white' : '';
                                            ?>
                                                <span class="badge bg-<?= $unit_color ?> me-1 <?= $text_color ?>"><?= $unit['soluong'] ?> <?= $unit['ten_donvi'] ?></span>
                                            <?php endforeach;
                                            else: ?>
                                                <span class="badge bg-danger text-white">0</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="manage-products.php" class="btn btn-outline-primary btn-sm">Xem tất cả sản phẩm</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cập nhật Cảnh Báo Tồn Kho  -->
        <div class="row">
            <div class="col-lg-12 mb-4">
                <div class="card shadow fade-in h-100" style="animation-delay: 0.9s">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Cảnh Báo Tồn Kho
                            <?php if($low_inventory_count > 0): ?>
                            <span class="badge bg-danger ms-2"><?= $low_inventory_count ?></span>
                            <?php endif; ?>
                        </h6>
                    
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Hình ảnh</th>
                                        <th>Tên thuốc</th>
                                        <th>Loại</th>
                                        <th>Tồn kho</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($low_inventory_count > 0): ?>
                                        <?php foreach($low_inventory_items as $index => $item): ?>
                                            <tr class="<?= $item['soluong'] == 0 ? 'table-danger' : 'table-warning' ?>">
                                                <td><?= $index + 1 ?></td>
                                                <td>
                                                    <img src="/assets/images/product-images/<?= htmlspecialchars($item['hinhanh']) ?>"
                                                        alt="<?= htmlspecialchars($item['ten_thuoc']) ?>"
                                                        style="width: 50px; height: 50px; object-fit: contain; border-radius: 6px; background: #f8f8f8;">
                                                </td>
                                                <td><?= htmlspecialchars($item['ten_thuoc']) ?></td>
                                                <td><?= htmlspecialchars($item['ten_loai']) ?></td>
                                                <td>
                                                    <?php 
                                                    // Lấy thông tin đơn vị cho sản phẩm này
                                                    $sql_units = "SELECT d.ten_donvi, kh.soluong 
                                                                  FROM khohang kh 
                                                                  JOIN donvi d ON kh.donvi_id = d.id 
                                                                  WHERE kh.thuoc_id = :thuoc_id";
                                                    $stmt_units = $conn->prepare($sql_units);
                                                    $stmt_units->bindParam(':thuoc_id', $item['id']);
                                                    $stmt_units->execute();
                                                    $units = $stmt_units->fetchAll(PDO::FETCH_ASSOC);
                                                    ?>
                                                    <?php if(count($units) > 0): ?>
                                                        <?php foreach($units as $unit): ?>
                                                            <span class="badge bg-<?= $unit['soluong'] == 0 ? 'danger' : ($unit['soluong'] <= $alert_threshold ? 'warning' : 'success') ?> me-1">
                                                                <?= $unit['soluong'] ?> <?= $unit['ten_donvi'] ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="import-inventory.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-plus me-1"></i>Nhập thêm
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-check-circle text-success fa-2x mb-3"></i>
                                                <p class="mb-0">Không có sản phẩm nào cần nhập thêm.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="manage-inventory.php" class="btn btn-danger btn-sm">
                            <i class="fas fa-boxes me-1"></i>Quản lý tồn kho
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Biểu đồ doanh thu
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Doanh Thu',
                    data: <?= json_encode($chart_data) ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND'
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'tr';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'k';
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        });
        
        // Nút làm mới dữ liệu
        const refreshBtn = document.getElementById('refreshBtn');
        refreshBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang cập nhật...';
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });
        
        // Hiệu ứng hiển thị khi cuộn
        const fadeElements = document.querySelectorAll('.fade-in');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });
        
        fadeElements.forEach(element => {
            observer.observe(element);
        });
    });
</script>

<?php
include_once('../includes/ad-footer.php');
?>