<?php
include_once('../includes/ad-header.php');
include_once('../includes/ad-sidebar.php');
include_once('../includes/config.php');

// Cập nhật lại các hàm thống kê
function getRevenueData($period) {
    global $conn;
    
    switch ($period) {
        case '7days':
            $startDate = date('Y-m-d', strtotime('-6 days'));
            $sql = "SELECT 
                        DATE(ngay_dat) as date,
                        COUNT(*) as order_count,
                        SUM(tongtien) as revenue
                    FROM donhang 
                    WHERE trangthai = 'dadat'
                    AND ngay_dat BETWEEN ? AND CURDATE()
                    GROUP BY DATE(ngay_dat)
                    ORDER BY date";
            break;

        case '30days':
            $startDate = date('Y-m-d', strtotime('-29 days')); 
            $sql = "SELECT 
                        DATE(ngay_dat) as date,
                        COUNT(*) as order_count,
                        SUM(tongtien) as revenue  
                    FROM donhang
                    WHERE trangthai = 'dadat'
                    AND ngay_dat BETWEEN ? AND CURDATE()
                    GROUP BY DATE(ngay_dat)
                    ORDER BY date";
            break;

        case 'year':
            $currentYear = date('Y');
            $sql = "SELECT 
                        MONTH(ngay_dat) as month,
                        COUNT(*) as order_count,
                        SUM(tongtien) as revenue
                    FROM donhang
                    WHERE trangthai = 'dadat' 
                    AND YEAR(ngay_dat) = ?
                    GROUP BY MONTH(ngay_dat)
                    ORDER BY month";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $currentYear);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $stmt = $conn->prepare($sql);
    if ($period != 'year') {
        $stmt->bind_param("s", $startDate);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Cập nhật lại hàm thống kê theo trạng thái
function getOrderStats() {
    global $conn;
    $sql = "SELECT 
                trangthai,
                COUNT(*) as so_luong,
                SUM(tongtien) as tong_tien
            FROM donhang 
            GROUP BY trangthai";
            
    $result = $conn->query($sql);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    
    $stats = [];
    while($row = $result->fetch_assoc()) {
        $stats[$row['trangthai']] = $row;
    }
    return $stats;
}

// Cập nhật các truy vấn thống kê doanh thu
// Doanh thu hôm nay 
$sql = "SELECT COALESCE(SUM(tongtien), 0) as total 
        FROM donhang 
        WHERE DATE(ngay_dat) = CURDATE() 
        AND trangthai = 'dadat'";
$result = $conn->query($sql);
$todayRevenue = $result->fetch_assoc()['total'];

// Doanh thu tháng này
$sql = "SELECT COALESCE(SUM(tongtien), 0) as total 
        FROM donhang 
        WHERE MONTH(ngay_dat) = MONTH(CURDATE())
        AND YEAR(ngay_dat) = YEAR(CURDATE())
        AND trangthai = 'dadat'";
$result = $conn->query($sql);
$monthRevenue = $result->fetch_assoc()['total'];

// Doanh thu năm nay
$sql = "SELECT COALESCE(SUM(tongtien), 0) as total 
        FROM donhang 
        WHERE YEAR(ngay_dat) = YEAR(CURDATE())
        AND trangthai = 'dadat'";
$result = $conn->query($sql);  
$yearRevenue = $result->fetch_assoc()['total'];

// Lấy số lượng đơn hàng theo trạng thái
$orderStats = getOrderStats();

// Lấy tổng số đơn đã đặt
$sql = "SELECT COUNT(*) as total FROM donhang WHERE trangthai = 'dadat'";
$result = $conn->query($sql);
$totalOrders = $result->fetch_assoc()['total'];

// Lấy dữ liệu doanh thu theo năm cho biểu đồ
$yearlyRevenueData = getRevenueData('year');

// Xử lý dữ liệu cho biểu đồ
$monthLabels = [];
$revenueValues = [];
$orderCounts = [];

// Khởi tạo mảng cho 12 tháng
for ($i = 1; $i <= 12; $i++) {
    $monthLabels[] = 'T' . $i;
    $revenueValues[$i] = 0;
    $orderCounts[$i] = 0;
}

// Điền dữ liệu thu được vào mảng
foreach ($yearlyRevenueData as $data) {
    $month = $data['month'];
    $revenueValues[$month] = $data['revenue'];
    $orderCounts[$month] = $data['order_count'];
}

// Chuyển đổi mảng kết hợp thành mảng tuần tự để sử dụng trong biểu đồ
$finalRevenueData = array_values($revenueValues);
$finalOrderCountData = array_values($orderCounts);
?>

<style>
/* Dashboard Layout */
.content-wrapper {
    margin-left: 250px;
    padding: 20px;
    min-height: 100vh;
    background: #f4f6f9;
}

/* Stats Cards */
.small-box {
    position: relative;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.small-box:hover {
    transform: translateY(-5px);
}

.small-box .inner {
    padding: 20px;
}

.small-box .inner h3 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    white-space: nowrap;
    padding: 0;
}

.small-box .inner p {
    font-size: 1rem;
    margin-bottom: 0;
}

.small-box .icon {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 50px;
    opacity: 0.3;
    transition: all 0.3s ease;
}

.small-box:hover .icon {
    font-size: 60px;
    opacity: 0.4;
}

/* Chart Cards */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header {
    background: linear-gradient(to right, #1a237e, #0d47a1);
    color: white;
    border-radius: 15px 15px 0 0 !important;
    padding: 15px 20px;
}

.card-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
}

.card-body {
    padding: 20px;
}

/* Chart Container */
#revenueChart, #orderPieChart {
    min-height: 300px;
}

/* Grid Layout */
.row {
    margin-left: -10px;
    margin-right: -10px;
}

.col-lg-3, .col-md-8, .col-md-4 {
    padding: 10px;
}

/* Color Scheme */
.bg-info {
    background: linear-gradient(135deg, #2196F3, #4CAF50) !important;
}

.bg-success {
    background: linear-gradient(135deg, #4CAF50, #8BC34A) !important;
}

.bg-warning {
    background: linear-gradient(135deg, #FFC107, #FF9800) !important;
}

.bg-danger {
    background: linear-gradient(135deg, #f44336, #E91E63) !important;
}

.bg-primary {
    background: linear-gradient(135deg, #3F51B5, #2196F3) !important;
}

.bg-secondary {
    background: linear-gradient(135deg, #9E9E9E, #607D8B) !important;
}

/* Period Selection */
.period-select {
    margin-bottom: 15px;
    display: flex;
    justify-content: flex-end;
}

.period-select .btn {
    border-radius: 20px;
    padding: 5px 15px;
    margin-left: 10px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.period-select .btn.active {
    background-color: #1a237e;
    color: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .col-lg-3 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (max-width: 768px) {
    .content-wrapper {
        margin-left: 0;
        padding: 15px;
    }
    
    .col-lg-3 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .col-md-8, .col-md-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .small-box {
        margin-bottom: 15px;
    }
}
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tổng Quan</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Thống kê doanh thu -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= number_format($todayRevenue, 0, ',', '.') ?>đ</h3>
                            <p>Doanh Thu Hôm Nay</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= number_format($monthRevenue, 0, ',', '.') ?>đ</h3>
                            <p>Doanh Thu Tháng Này</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= number_format($yearRevenue, 0, ',', '.') ?>đ</h3>
                            <p>Doanh Thu Năm <?= date('Y') ?></p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $orderStats['choxacnhan']['so_luong'] ?? 0 ?></h3>
                            <p>Đơn Hàng Chờ Xác Nhận</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thống kê đơn hàng -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?= $orderStats['daxacnhan']['so_luong'] ?? 0 ?></h3>
                            <p>Đã Xác Nhận</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $orderStats['danggiao']['so_luong'] ?? 0 ?></h3>
                            <p>Đang Giao</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-truck"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $orderStats['dadat']['so_luong'] ?? 0 ?></h3>
                            <p>Đã Đặt</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-double"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3><?= $orderStats['dahuy']['so_luong'] ?? 0 ?></h3>
                            <p>Đã Hủy</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Biểu đồ thống kê -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-2"></i>
                                Thống Kê Doanh Thu
                            </h3>
                            <div class="period-select float-right">
                                <button class="btn btn-sm btn-light period-btn active" data-period="year">Năm</button>
                                <button class="btn btn-sm btn-light period-btn" data-period="30days">30 Ngày</button>
                                <button class="btn btn-sm btn-light period-btn" data-period="7days">7 Ngày</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-2"></i>
                                Tỷ Lệ Đơn Hàng
                            </h3>
                        </div>
                        <div class="card-body">
                            <canvas id="orderPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Biểu đồ doanh thu
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    let revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthLabels) ?>,
            datasets: [{
                label: 'Doanh thu (đ)',
                data: <?= json_encode($finalRevenueData) ?>,
                borderColor: '#2196F3',
                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Số đơn hàng',
                data: <?= json_encode($finalOrderCountData) ?>,
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                tension: 0.4,
                fill: true,
                hidden: true,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.datasetIndex === 0) {
                                    label += new Intl.NumberFormat('vi-VN').format(context.parsed.y) + 'đ';
                                } else {
                                    label += context.parsed.y;
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                        }
                    }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                }
            }
        }
    });

    // Biểu đồ tỷ lệ đơn hàng
    const orderCtx = document.getElementById('orderPieChart').getContext('2d');
    new Chart(orderCtx, {
        type: 'doughnut',
        data: {
            labels: ['Chờ xác nhận', 'Đã xác nhận', 'Đang giao', 'Đã đặt', 'Đã hủy'],
            datasets: [{
                data: [
                    <?= $orderStats['choxacnhan']['so_luong'] ?? 0 ?>,
                    <?= $orderStats['daxacnhan']['so_luong'] ?? 0 ?>,
                    <?= $orderStats['danggiao']['so_luong'] ?? 0 ?>,
                    <?= $orderStats['dadat']['so_luong'] ?? 0 ?>,
                    <?= $orderStats['dahuy']['so_luong'] ?? 0 ?>
                ],
                backgroundColor: [
                    '#FFC107',
                    '#2196F3',
                    '#03A9F4',
                    '#4CAF50',
                    '#9E9E9E'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '60%',
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });

    // Xử lý chuyển đổi thời gian cho biểu đồ doanh thu
    const periodButtons = document.querySelectorAll('.period-btn');
    
    periodButtons.forEach(button => {
        button.addEventListener('click', function() {
            const period = this.getAttribute('data-period');
            
            // Đánh dấu nút đang active
            periodButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Gọi AJAX để lấy dữ liệu theo kỳ hạn
            fetch(`get-revenue-data.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    updateRevenueChart(revenueChart, data, period);
                })
                .catch(error => console.error('Error fetching data:', error));
        });
    });
});

// Hàm cập nhật biểu đồ doanh thu
function updateRevenueChart(chart, data, period) {
    let labels = [];
    let revenues = [];
    let orders = [];
    
    if (period === 'year') {
        // Dữ liệu theo năm (tháng)
        for (let i = 1; i <= 12; i++) {
            labels.push('T' + i);
            
            // Tìm dữ liệu cho tháng này
            const monthData = data.find(item => parseInt(item.month) === i);
            
            revenues.push(monthData ? parseInt(monthData.revenue) : 0);
            orders.push(monthData ? parseInt(monthData.order_count) : 0);
        }
    } else {
        // Dữ liệu theo ngày
        data.forEach(item => {
            // Định dạng ngày theo dd/MM
            const date = new Date(item.date);
            const formattedDate = `${date.getDate()}/${date.getMonth() + 1}`;
            
            labels.push(formattedDate);
            revenues.push(parseInt(item.revenue));
            orders.push(parseInt(item.order_count));
        });
    }
    
    chart.data.labels = labels;
    chart.data.datasets[0].data = revenues;
    chart.data.datasets[1].data = orders;
    chart.update();
}
</script>

<?php
// Tạo file get-revenue-data.php để xử lý AJAX
file_put_contents('../admin/get-revenue-data.php', '<?php
include_once("../includes/config.php");

// Hàm lấy dữ liệu doanh thu
function getRevenueData($period) {
    global $conn;
    
    switch ($period) {
        case "7days":
            $startDate = date("Y-m-d", strtotime("-6 days"));
            $sql = "SELECT 
                        DATE(ngay_dat) as date,
                        COUNT(*) as order_count,
                        SUM(tongtien) as revenue
                    FROM donhang 
                    WHERE trangthai = \'dadat\'
                    AND ngay_dat BETWEEN ? AND CURDATE()
                    GROUP BY DATE(ngay_dat)
                    ORDER BY date";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $startDate);
            break;

        case "30days":
            $startDate = date("Y-m-d", strtotime("-29 days")); 
            $sql = "SELECT 
                        DATE(ngay_dat) as date,
                        COUNT(*) as order_count,
                        SUM(tongtien) as revenue  
                    FROM donhang
                    WHERE trangthai = \'dadat\'
                    AND ngay_dat BETWEEN ? AND CURDATE()
                    GROUP BY DATE(ngay_dat)
                    ORDER BY date";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $startDate);
            break;

        case "year":
        default:
            $currentYear = date("Y");
            $sql = "SELECT 
                        MONTH(ngay_dat) as month,
                        COUNT(*) as order_count,
                        SUM(tongtien) as revenue
                    FROM donhang
                    WHERE trangthai = \'dadat\' 
                    AND YEAR(ngay_dat) = ?
                    GROUP BY MONTH(ngay_dat)
                    ORDER BY month";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $currentYear);
            break;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Lấy tham số period từ request
$period = isset($_GET["period"]) ? $_GET["period"] : "year";

// Lấy dữ liệu và trả về dạng JSON
$data = getRevenueData($period);
header("Content-Type: application/json");
echo json_encode($data);
?>');

include('../includes/ad-footer.php'); 
?>