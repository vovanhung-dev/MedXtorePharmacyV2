<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../controllers/InventoryAnalyticsController.php';

requireAdmin();

$controller = new InventoryAnalyticsController();

// Lấy dữ liệu
$forecast = $controller->getDemandForecast(3);
$topSelling = $controller->getTopSellingProducts(10, 30);
$chartData = $controller->getForecastChartData();
$salesHistory = $controller->getSalesHistory(null, 6);

// Nhóm lịch sử bán hàng theo tháng
$monthlyData = [];
foreach ($salesHistory as $item) {
    if (!isset($monthlyData[$item['thang']])) {
        $monthlyData[$item['thang']] = ['so_luong' => 0, 'doanh_thu' => 0];
    }
    $monthlyData[$item['thang']]['so_luong'] += $item['so_luong_ban'];
    $monthlyData[$item['thang']]['doanh_thu'] += $item['doanh_thu'];
}
ksort($monthlyData);

include('../includes/ad-header.php');
include('../includes/ad-sidebar.php');
?>

<style>
.content-wrapper {
    margin-left: 250px;
    padding: 20px;
    background: #f4f6f9;
    min-height: 100vh;
}

.forecast-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 20px;
}

.forecast-header {
    padding: 20px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.chart-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.stats-box {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.stats-box h2 {
    color: #667eea;
    margin-bottom: 5px;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
    padding: 12px 15px;
}

.table td {
    vertical-align: middle;
    padding: 12px 15px;
}

.product-img {
    width: 45px;
    height: 45px;
    object-fit: cover;
    border-radius: 8px;
}

.rank-badge {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.rank-1 { background: linear-gradient(135deg, #ffd700, #ffb347); color: white; }
.rank-2 { background: linear-gradient(135deg, #c0c0c0, #a8a8a8); color: white; }
.rank-3 { background: linear-gradient(135deg, #cd7f32, #b87333); color: white; }

.forecast-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
}

.nav-pills .nav-link {
    border-radius: 10px;
    padding: 12px 25px;
    margin-right: 10px;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.monthly-card {
    background: white;
    border-radius: 12px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.monthly-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
}

.monthly-card.forecast {
    border: 2px dashed #667eea;
    background: #f8f9ff;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 20px;
}
</style>

<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-line text-primary"></i> Dự Đoán Nhu Cầu</h2>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="pill" href="#tab-forecast">
                <i class="fas fa-chart-area"></i> Dự đoán nhu cầu
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="pill" href="#tab-top-selling">
                <i class="fas fa-fire"></i> Top bán chạy
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="pill" href="#tab-history">
                <i class="fas fa-history"></i> Lịch sử bán hàng
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Tab Dự đoán -->
        <div class="tab-pane fade show active" id="tab-forecast">
            <?php if (empty($forecast)): ?>
                <div class="forecast-card">
                    <div class="empty-state">
                        <i class="fas fa-chart-line"></i>
                        <h5 class="text-muted">Chưa có dữ liệu dự đoán</h5>
                        <p class="text-muted">Hệ thống cần có lịch sử bán hàng để dự đoán nhu cầu.</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Biểu đồ xu hướng -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-chart-area text-primary"></i> Xu hướng bán hàng & Dự đoán</h5>
                            <canvas id="forecastChart" height="120"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container h-100">
                            <h5 class="mb-3"><i class="fas fa-bullseye text-success"></i> Dự đoán tháng tới</h5>
                            <div class="stats-box mb-3">
                                <small class="text-muted">Số lượng dự đoán</small>
                                <h2><?php echo number_format($chartData['forecast_value']); ?></h2>
                                <small>sản phẩm</small>
                            </div>
                            <p class="text-muted small">
                                <i class="fas fa-info-circle"></i>
                                Dựa trên trung bình động 6 tháng gần nhất
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bảng dự đoán chi tiết -->
                <div class="forecast-card">
                    <div class="forecast-header">
                        <h5 class="mb-0"><i class="fas fa-table"></i> Chi tiết dự đoán theo sản phẩm</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th class="text-center">Tồn hiện tại</th>
                                    <th class="text-center">Dự đoán tháng tới</th>
                                    <th class="text-center">Cao nhất</th>
                                    <th class="text-center">Thấp nhất</th>
                                    <th class="text-center">Cần nhập thêm</th>
                                    <th class="text-center">Đánh giá</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($forecast, 0, 30) as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['ten_thuoc']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['ten_loai']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $item['ton_hien_tai'] < $item['du_doan_thang_toi'] ? 'warning' : 'success'; ?>">
                                            <?php echo number_format($item['ton_hien_tai']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-primary"><?php echo number_format($item['du_doan_thang_toi']); ?></strong>
                                    </td>
                                    <td class="text-center text-success"><?php echo number_format($item['ban_cao_nhat']); ?></td>
                                    <td class="text-center text-danger"><?php echo number_format($item['ban_thap_nhat']); ?></td>
                                    <td class="text-center">
                                        <?php if ($item['can_nhap_them'] > 0): ?>
                                            <span class="badge bg-danger"><?php echo number_format($item['can_nhap_them']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Đủ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item['can_nhap_them'] > 0): ?>
                                            <span class="forecast-badge bg-danger text-white">Thiếu hàng</span>
                                        <?php elseif ($item['ton_hien_tai'] > $item['du_doan_thang_toi'] * 3): ?>
                                            <span class="forecast-badge bg-warning text-dark">Tồn cao</span>
                                        <?php else: ?>
                                            <span class="forecast-badge bg-success text-white">Ổn định</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab Top bán chạy -->
        <div class="tab-pane fade" id="tab-top-selling">
            <?php if (empty($topSelling)): ?>
                <div class="forecast-card">
                    <div class="empty-state">
                        <i class="fas fa-fire"></i>
                        <h5 class="text-muted">Chưa có dữ liệu bán hàng</h5>
                        <p class="text-muted">Khi có đơn hàng hoàn thành, danh sách top bán chạy sẽ hiển thị ở đây.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="forecast-card">
                    <div class="forecast-header">
                        <h5 class="mb-0"><i class="fas fa-fire"></i> Top 10 sản phẩm bán chạy nhất (30 ngày)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th class="text-center">Đã bán</th>
                                    <th class="text-center">Số đơn</th>
                                    <th class="text-end">Doanh thu</th>
                                    <th class="text-center">Tồn kho</th>
                                    <th class="text-center">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topSelling as $index => $item): ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if ($index < 3): ?>
                                            <span class="rank-badge rank-<?php echo $index + 1; ?>"><?php echo $index + 1; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo $index + 1; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($item['hinhanh'])): ?>
                                                <img src="/assets/images/product-images/<?php echo $item['hinhanh']; ?>" class="product-img me-2">
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($item['ten_thuoc']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['ten_loai']); ?></td>
                                    <td class="text-center"><strong><?php echo number_format($item['tong_ban']); ?></strong></td>
                                    <td class="text-center"><?php echo number_format($item['so_don_hang']); ?></td>
                                    <td class="text-end text-success">
                                        <strong><?php echo InventoryAnalyticsController::formatMoney($item['doanh_thu']); ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $item['ton_kho'] < 20 ? 'danger' : 'success'; ?>">
                                            <?php echo number_format($item['ton_kho']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item['ton_kho'] < 10): ?>
                                            <span class="badge bg-danger">Cần nhập gấp</span>
                                        <?php elseif ($item['ton_kho'] < 20): ?>
                                            <span class="badge bg-warning text-dark">Tồn thấp</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Đủ hàng</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab Lịch sử -->
        <div class="tab-pane fade" id="tab-history">
            <?php if (empty($monthlyData)): ?>
                <div class="forecast-card">
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h5 class="text-muted">Chưa có lịch sử bán hàng</h5>
                        <p class="text-muted">Khi có đơn hàng, lịch sử bán hàng theo tháng sẽ hiển thị ở đây.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row mb-4">
                    <?php
                    $nextMonth = date('Y-m', strtotime('+1 month'));
                    $avgQty = count($monthlyData) > 0 ? array_sum(array_column($monthlyData, 'so_luong')) / count($monthlyData) : 0;
                    ?>

                    <?php foreach ($monthlyData as $month => $data): ?>
                    <div class="col-md-2 mb-3">
                        <div class="monthly-card">
                            <small class="text-muted"><?php echo date('m/Y', strtotime($month . '-01')); ?></small>
                            <h4 class="my-2"><?php echo number_format($data['so_luong']); ?></h4>
                            <small class="text-success"><?php echo InventoryAnalyticsController::formatMoney($data['doanh_thu']); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="col-md-2 mb-3">
                        <div class="monthly-card forecast">
                            <small class="text-primary"><i class="fas fa-magic"></i> Dự đoán</small>
                            <h4 class="my-2 text-primary"><?php echo number_format(round($avgQty)); ?></h4>
                            <small class="text-primary"><?php echo date('m/Y', strtotime($nextMonth . '-01')); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ lịch sử -->
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-bar text-primary"></i> Biểu đồ bán hàng theo tháng</h5>
                    <canvas id="historyChart" height="100"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($chartData['labels'])): ?>
// Data từ PHP
const chartLabels = <?php echo json_encode($chartData['labels']); ?>;
const chartValues = <?php echo json_encode($chartData['data']); ?>;
const forecastMonth = '<?php echo $chartData['forecast_month']; ?>';

// Biểu đồ dự đoán
if (document.getElementById('forecastChart')) {
    new Chart(document.getElementById('forecastChart'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Số lượng bán',
                data: chartValues,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: chartLabels.map(l => l === forecastMonth ? '#ff6b6b' : '#667eea'),
                pointRadius: chartLabels.map(l => l === forecastMonth ? 8 : 4),
                pointBorderWidth: chartLabels.map(l => l === forecastMonth ? 3 : 1)
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label === forecastMonth ? 'Dự đoán: ' : 'Đã bán: ';
                            return label + context.formattedValue + ' sản phẩm';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
<?php endif; ?>

<?php if (!empty($monthlyData)): ?>
// Biểu đồ lịch sử
const historyLabels = <?php echo json_encode(array_keys($monthlyData)); ?>;
const historyQty = <?php echo json_encode(array_column($monthlyData, 'so_luong')); ?>;
const historyRevenue = <?php echo json_encode(array_column($monthlyData, 'doanh_thu')); ?>;

if (document.getElementById('historyChart')) {
    new Chart(document.getElementById('historyChart'), {
        type: 'bar',
        data: {
            labels: historyLabels.map(m => {
                const d = new Date(m + '-01');
                return d.toLocaleDateString('vi-VN', { month: 'short', year: 'numeric' });
            }),
            datasets: [{
                label: 'Số lượng bán',
                data: historyQty,
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderRadius: 8,
                yAxisID: 'y'
            }, {
                label: 'Doanh thu',
                data: historyRevenue,
                type: 'line',
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: false,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Số lượng'
                    }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Doanh thu (đ)'
                    },
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN').format(value);
                        }
                    }
                }
            }
        }
    });
}
<?php endif; ?>
</script>
</body>
</html>
