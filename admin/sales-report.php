<?php
include_once('../includes/ad-header.php');
include_once('../includes/ad-sidebar.php');
include_once('../config/database.php');

// Kết nối database
$db = new Database();
$conn = $db->getConnection();

// Lấy khoảng thời gian báo cáo
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$period_days = 30; // Mặc định là 30 ngày

if ($period == 'week') {
    $period_days = 7;
} elseif ($period == 'quarter') {
    $period_days = 90;
} elseif ($period == 'year') {
    $period_days = 365;
}

// Lấy dữ liệu bán hàng trong khoảng thời gian
$sql_sales = "SELECT t.id, t.ten_thuoc, t.hinhanh, lt.ten_loai, 
              COUNT(cd.id) AS total_sold,
              SUM(cd.dongia * cd.soluong) AS revenue
              FROM chitiet_donhang cd
              JOIN thuoc t ON cd.thuoc_id = t.id
              JOIN loai_thuoc lt ON t.loai_id = lt.id
              JOIN donhang d ON cd.donhang_id = d.id
              WHERE DATE(d.ngay_dat) BETWEEN DATE_SUB(CURDATE(), INTERVAL :days DAY) AND CURDATE()
              GROUP BY t.id, t.ten_thuoc, t.hinhanh, lt.ten_loai
              ORDER BY total_sold DESC";

$stmt_sales = $conn->prepare($sql_sales);
$stmt_sales->bindParam(':days', $period_days, PDO::PARAM_INT);
$stmt_sales->execute();
$sales_data = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);

// Lấy tổng doanh thu trong khoảng thời gian
$sql_total_revenue = "SELECT SUM(tongtien) AS total 
                     FROM donhang 
                     WHERE DATE(ngay_dat) BETWEEN DATE_SUB(CURDATE(), INTERVAL :days DAY) AND CURDATE()";
$stmt_total_revenue = $conn->prepare($sql_total_revenue);
$stmt_total_revenue->bindParam(':days', $period_days, PDO::PARAM_INT);
$stmt_total_revenue->execute();
$total_revenue = $stmt_total_revenue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Lấy tổng số đơn hàng trong khoảng thời gian
$sql_total_orders = "SELECT COUNT(*) AS total 
                    FROM donhang 
                    WHERE DATE(ngay_dat) BETWEEN DATE_SUB(CURDATE(), INTERVAL :days DAY) AND CURDATE()";
$stmt_total_orders = $conn->prepare($sql_total_orders);
$stmt_total_orders->bindParam(':days', $period_days, PDO::PARAM_INT);
$stmt_total_orders->execute();
$total_orders = $stmt_total_orders->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Lấy dữ liệu theo loại sản phẩm (category)
$sql_category_sales = "SELECT lt.ten_loai, COUNT(cd.id) AS total_sold
                      FROM chitiet_donhang cd
                      JOIN thuoc t ON cd.thuoc_id = t.id
                      JOIN loai_thuoc lt ON t.loai_id = lt.id
                      JOIN donhang d ON cd.donhang_id = d.id
                      WHERE DATE(d.ngay_dat) BETWEEN DATE_SUB(CURDATE(), INTERVAL :days DAY) AND CURDATE()
                      GROUP BY lt.ten_loai
                      ORDER BY total_sold DESC";

$stmt_category_sales = $conn->prepare($sql_category_sales);
$stmt_category_sales->bindParam(':days', $period_days, PDO::PARAM_INT);
$stmt_category_sales->execute();
$category_sales = $stmt_category_sales->fetchAll(PDO::FETCH_ASSOC);

// Lấy dữ liệu doanh thu theo thời gian
$time_labels = [];
$time_data = [];

if ($period == 'week' || $period == 'month') {
    // Doanh thu theo ngày cho tuần hoặc tháng
    for($i = $period_days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $time_labels[] = date('d/m', strtotime($date));
        
        $sql_day_revenue = "SELECT SUM(tongtien) AS total FROM donhang WHERE DATE(ngay_dat) = :date";
        $stmt_day_revenue = $conn->prepare($sql_day_revenue);
        $stmt_day_revenue->bindParam(':date', $date);
        $stmt_day_revenue->execute();
        $day_revenue = $stmt_day_revenue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $time_data[] = $day_revenue;
    }
} else {
    // Doanh thu theo tháng cho quý hoặc năm
    $months = min(12, $period_days / 30);
    for($i = $months - 1; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $time_labels[] = date('m/Y', strtotime($month));
        
        $month_start = date('Y-m-01', strtotime($month));
        $month_end = date('Y-m-t', strtotime($month));
        
        $sql_month_revenue = "SELECT SUM(tongtien) AS total FROM donhang WHERE DATE(ngay_dat) BETWEEN :start AND :end";
        $stmt_month_revenue = $conn->prepare($sql_month_revenue);
        $stmt_month_revenue->bindParam(':start', $month_start);
        $stmt_month_revenue->bindParam(':end', $month_end);
        $stmt_month_revenue->execute();
        $month_revenue = $stmt_month_revenue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $time_data[] = $month_revenue;
    }
}

// Chuyển đổi dữ liệu cho biểu đồ (JSON)
$category_labels = array_column($category_sales, 'ten_loai');
$category_data = array_column($category_sales, 'total_sold');

// Lấy màu cho từng loại sản phẩm
$category_colors = [
    'Thuốc cảm' => '#4e73df',
    'Thuốc kháng sinh' => '#1cc88a',
    'Vitamin' => '#f6c23e',
    'Kẹo' => '#e74a3b',
    'Khẩu Trang' => '#36b9cc'
];

// Tạo màu mặc định cho các loại không có trong danh sách
$default_colors = ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc', '#6f42c1', '#fd7e14', '#20c9a6'];
?>

<style>
    /* Main Content Layout */
    .main-content {
        margin-left: 250px;
        padding: 1.5rem;
        min-height: 100vh;
        background-color: #f8f9fc;
    }

    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
        }
    }

    /* Sales Report Styling */
    .report-card {
        transition: all 0.3s ease;
        border-radius: 0.5rem;
        background-color: white;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.15rem 1.75rem rgba(58, 59, 69, 0.1);
    }
    
    .report-card .card-header {
        padding: 1rem 1.25rem;
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    
    .report-card .card-body {
        padding: 1.25rem;
    }
    
    .report-metric {
        padding: 1.5rem;
        border-radius: 0.5rem;
        background-color: white;
        text-align: center;
        box-shadow: 0 0.15rem 1.75rem rgba(58, 59, 69, 0.1);
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .report-metric:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 2rem rgba(58, 59, 69, 0.15);
    }
    
    .report-metric .metric-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 60px;
        width: 60px;
        border-radius: 50%;
        margin: 0 auto 1rem;
        font-size: 1.5rem;
        color: white;
    }
    
    .report-metric .metric-title {
        text-transform: uppercase;
        font-size: 0.8rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: #5a5c69;
    }
    
    .report-metric .metric-value {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0;
    }

    .product-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-radius: 0.5rem;
        background-color: white;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 0.1rem 0.5rem rgba(58, 59, 69, 0.1);
    }
    
    .product-item:hover {
        transform: translateX(5px);
        box-shadow: 0 0.25rem 1rem rgba(58, 59, 69, 0.15);
    }
    
    .product-rank {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        width: 40px;
        border-radius: 50%;
        margin-right: 1rem;
        font-size: 1rem;
        color: white;
        background-color: #4e73df;
        font-weight: 700;
    }
    
    .product-rank.top-1 { background-color: #f6c23e; }
    .product-rank.top-2 { background-color: #aaaaaa; }
    .product-rank.top-3 { background-color: #cd7f32; }
    
    .product-image {
        height: 50px;
        width: 50px;
        border-radius: 0.25rem;
        margin-right: 1rem;
        object-fit: contain;
        background-color: #f8f9fc;
    }
    
    .product-info {
        flex: 1;
    }
    
    .product-name {
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .product-category {
        font-size: 0.8rem;
        color: #858796;
    }
    
    .product-stats {
        text-align: right;
        min-width: 120px;
    }
    
    .product-sold {
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 0.25rem;
    }
    
    .product-revenue {
        font-size: 0.9rem;
        color: #1cc88a;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
        margin-bottom: 1.5rem;
    }
    
    .time-filter {
        display: flex;
        justify-content: center;
        margin-bottom: 1.5rem;
    }
    
    .time-filter .btn {
        margin: 0 0.25rem;
    }
    
    @media (max-width: 767px) {
        .report-metric {
            margin-bottom: 1rem;
        }
        
        .product-stats {
            min-width: 80px;
        }
    }
</style>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-bar me-2"></i>Báo Cáo Sản Phẩm Bán Chạy
            </h1>
            <div>
                <button id="exportBtn" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel me-1"></i> Xuất Excel
                </button>
                <button id="refreshBtn" class="btn btn-sm btn-primary ms-2">
                    <i class="fas fa-sync-alt me-1"></i> Làm mới
                </button>
            </div>
        </div>
        
        <!-- Time Filter -->
        <div class="time-filter">
            <a href="?period=week" class="btn btn-sm <?= $period == 'week' ? 'btn-primary' : 'btn-outline-primary' ?>">7 Ngày</a>
            <a href="?period=month" class="btn btn-sm <?= $period == 'month' ? 'btn-primary' : 'btn-outline-primary' ?>">30 Ngày</a>
            <a href="?period=quarter" class="btn btn-sm <?= $period == 'quarter' ? 'btn-primary' : 'btn-outline-primary' ?>">Quý</a>
            <a href="?period=year" class="btn btn-sm <?= $period == 'year' ? 'btn-primary' : 'btn-outline-primary' ?>">Năm</a>
        </div>
        
        <!-- Summary Metrics -->
        <div class="row">
            <div class="col-md-4">
                <div class="report-metric">
                    <div class="metric-icon" style="background-color: #4e73df;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="metric-title">Tổng sản phẩm đã bán</div>
                    <div class="metric-value"><?= count($sales_data) > 0 ? array_sum(array_column($sales_data, 'total_sold')) : 0 ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="report-metric">
                    <div class="metric-icon" style="background-color: #1cc88a;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="metric-title">Tổng đơn hàng</div>
                    <div class="metric-value"><?= $total_orders ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="report-metric">
                    <div class="metric-icon" style="background-color: #f6c23e;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="metric-title">Tổng doanh thu</div>
                    <div class="metric-value"><?= number_format($total_revenue, 0, ',', '.') ?> đ</div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row">
            <!-- Sales by Time Chart -->
            <div class="col-lg-8 mb-4">
                <div class="report-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Doanh Thu Theo Thời Gian</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesTimeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sales by Category Chart -->
            <div class="col-lg-4 mb-4">
                <div class="report-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Thống Kê Theo Loại Sản Phẩm</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesCategoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Products List -->
        <div class="report-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Top Sản Phẩm Bán Chạy</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">Tùy chọn:</div>
                        <a class="dropdown-item" href="#" id="sortBySold">Sắp xếp theo Số lượng</a>
                        <a class="dropdown-item" href="#" id="sortByRevenue">Sắp xếp theo Doanh thu</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($sales_data) > 0): ?>
                    <div id="productsList">
                        <?php foreach ($sales_data as $index => $product): ?>
                            <div class="product-item" data-sold="<?= $product['total_sold'] ?>" data-revenue="<?= $product['revenue'] ?>">
                                <div class="product-rank <?= $index < 3 ? 'top-'.($index + 1) : '' ?>"><?= $index + 1 ?></div>
                                <img class="product-image" src="/assets/images/product-images/<?= htmlspecialchars($product['hinhanh']) ?>" alt="<?= htmlspecialchars($product['ten_thuoc']) ?>">
                                <div class="product-info">
                                    <div class="product-name"><?= htmlspecialchars($product['ten_thuoc']) ?></div>
                                    <div class="product-category"><?= htmlspecialchars($product['ten_loai']) ?></div>
                                </div>
                                <div class="product-stats">
                                    <div class="product-sold"><?= $product['total_sold'] ?> đã bán</div>
                                    <div class="product-revenue"><?= number_format($product['revenue'], 0, ',', '.') ?> đ</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open text-gray-300 fa-3x mb-3"></i>
                        <p class="text-gray-500 mb-0">Chưa có dữ liệu bán hàng trong khoảng thời gian này.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Doanh thu theo thời gian Chart
        const timeCtx = document.getElementById('salesTimeChart').getContext('2d');
        new Chart(timeCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($time_labels) ?>,
                datasets: [{
                    label: 'Doanh Thu',
                    data: <?= json_encode($time_data) ?>,
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

        // Sales by Category Chart
        const categoryCtx = document.getElementById('salesCategoryChart').getContext('2d');
        
        // Prepare colors for the categories
        const categoryLabels = <?= json_encode($category_labels) ?>;
        const categoryColors = categoryLabels.map((label, index) => {
            return <?= json_encode($category_colors) ?>[label] || <?= json_encode($default_colors) ?>[index % <?= json_encode($default_colors) ?>.length];
        });
        
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: <?= json_encode($category_data) ?>,
                    backgroundColor: categoryColors,
                    hoverBackgroundColor: categoryColors.map(color => {
                        // Create a slightly darker version for hover
                        const r = parseInt(color.slice(1, 3), 16);
                        const g = parseInt(color.slice(3, 5), 16);
                        const b = parseInt(color.slice(5, 7), 16);
                        return `rgba(${r}, ${g}, ${b}, 0.8)`;
                    }),
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%',
            },
        });

        // Sorting products
        document.getElementById('sortBySold').addEventListener('click', function(e) {
            e.preventDefault();
            sortProducts('sold', 'desc');
        });

        document.getElementById('sortByRevenue').addEventListener('click', function(e) {
            e.preventDefault();
            sortProducts('revenue', 'desc');
        });

        function sortProducts(field, direction = 'desc') {
            const productsList = document.getElementById('productsList');
            const products = Array.from(productsList.children);
            
            products.sort((a, b) => {
                const aValue = parseFloat(a.getAttribute(`data-${field}`));
                const bValue = parseFloat(b.getAttribute(`data-${field}`));
                
                return direction === 'desc' ? bValue - aValue : aValue - bValue;
            });
            
            // Update ranks after sorting
            products.forEach((product, index) => {
                const rankElement = product.querySelector('.product-rank');
                rankElement.textContent = index + 1;
                rankElement.classList.remove('top-1', 'top-2', 'top-3');
                if (index < 3) {
                    rankElement.classList.add(`top-${index + 1}`);
                }
            });
            
            // Rearrange the DOM
            productsList.innerHTML = '';
            products.forEach(product => productsList.appendChild(product));
        }

        // Nút làm mới dữ liệu
        const refreshBtn = document.getElementById('refreshBtn');
        refreshBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang cập nhật...';
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });
        
        // Export to Excel button - placeholder functionality
        document.getElementById('exportBtn').addEventListener('click', function() {
            alert('Tính năng xuất Excel đang được phát triển.');
        });
    });
</script>

<?php
include_once('../includes/ad-footer.php');
?>