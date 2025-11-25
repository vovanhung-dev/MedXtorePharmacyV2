<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../controllers/InventoryAnalyticsController.php';

requireAdmin();

$controller = new InventoryAnalyticsController();

// Lấy dữ liệu
$summary = $controller->getInventorySummary();
$byCategory = $controller->getInventoryByCategory();
$dashboardStats = $controller->getDashboardStats();

// Filters
$filters = [
    'loai_id' => $_GET['loai_id'] ?? '',
    'search' => $_GET['search'] ?? ''
];
$detailedInventory = $controller->getDetailedInventory($filters);

// Phân tích ABC
$abcAnalysis = $controller->getABCAnalysis(90);

// Vòng quay tồn kho
$turnover = $controller->getInventoryTurnover(90);

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

.stats-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.bg-gradient-primary { background: linear-gradient(135deg, #667eea, #764ba2); }
.bg-gradient-success { background: linear-gradient(135deg, #11998e, #38ef7d); }
.bg-gradient-warning { background: linear-gradient(135deg, #f093fb, #f5576c); }
.bg-gradient-danger { background: linear-gradient(135deg, #eb3349, #f45c43); }
.bg-gradient-info { background: linear-gradient(135deg, #4facfe, #00f2fe); }

.table-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table th {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-weight: 600;
    padding: 15px;
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
    padding: 12px 15px;
}

.filter-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.abc-badge {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 16px;
}

.chart-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.nav-pills .nav-link {
    border-radius: 10px;
    padding: 10px 20px;
    margin-right: 10px;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.progress {
    height: 8px;
    border-radius: 4px;
}
</style>

<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-bar text-primary"></i> Báo Cáo Tồn Kho</h2>
        <div>
            <button class="btn btn-success" onclick="exportExcel()">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </button>
            <button class="btn btn-danger" onclick="exportPDF()">
                <i class="fas fa-file-pdf"></i> Xuất PDF
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-gradient-primary me-3">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div>
                        <h4 class="mb-0"><?php echo InventoryAnalyticsController::formatMoney($summary['tong_gia_tri'] ?? 0); ?></h4>
                        <small class="text-muted">Tổng giá trị tồn kho</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-gradient-success me-3">
                        <i class="fas fa-pills"></i>
                    </div>
                    <div>
                        <h4 class="mb-0"><?php echo number_format($summary['tong_loai_thuoc'] ?? 0); ?></h4>
                        <small class="text-muted">Loại thuốc trong kho</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-gradient-warning me-3">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h4 class="mb-0"><?php echo number_format($dashboardStats['thuoc_ton_thap'] ?? 0); ?></h4>
                        <small class="text-muted">Thuốc tồn thấp</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-gradient-danger me-3">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div>
                        <h4 class="mb-0"><?php echo number_format($dashboardStats['thuoc_sap_het_han'] ?? 0); ?></h4>
                        <small class="text-muted">Thuốc sắp hết hạn</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4" id="reportTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="pill" href="#tab-overview">
                <i class="fas fa-th-large"></i> Tổng quan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="pill" href="#tab-detail">
                <i class="fas fa-list"></i> Chi tiết
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="pill" href="#tab-abc">
                <i class="fas fa-sort-amount-down"></i> Phân tích ABC
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="pill" href="#tab-turnover">
                <i class="fas fa-sync-alt"></i> Vòng quay tồn kho
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Tab Tổng quan -->
        <div class="tab-pane fade show active" id="tab-overview">
            <div class="row">
                <!-- Biểu đồ tồn kho theo danh mục -->
                <div class="col-md-6 mb-4">
                    <div class="chart-container">
                        <h5 class="mb-3"><i class="fas fa-chart-pie text-primary"></i> Tồn kho theo danh mục</h5>
                        <canvas id="categoryChart" height="300"></canvas>
                    </div>
                </div>

                <!-- Biểu đồ giá trị -->
                <div class="col-md-6 mb-4">
                    <div class="chart-container">
                        <h5 class="mb-3"><i class="fas fa-chart-bar text-success"></i> Giá trị tồn kho theo danh mục</h5>
                        <canvas id="valueChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bảng tồn kho theo danh mục -->
            <div class="table-container">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Danh mục</th>
                            <th class="text-center">Số loại thuốc</th>
                            <th class="text-center">Tổng số lượng</th>
                            <th class="text-end">Giá trị tồn</th>
                            <th class="text-center">Tỷ lệ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalValue = array_sum(array_column($byCategory, 'gia_tri'));
                        foreach ($byCategory as $cat):
                            $percent = $totalValue > 0 ? ($cat['gia_tri'] / $totalValue) * 100 : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cat['ten_loai']); ?></strong></td>
                            <td class="text-center"><?php echo number_format($cat['so_thuoc']); ?></td>
                            <td class="text-center"><?php echo number_format($cat['tong_so_luong']); ?></td>
                            <td class="text-end"><?php echo InventoryAnalyticsController::formatMoney($cat['gia_tri']); ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2">
                                        <div class="progress-bar bg-primary" style="width: <?php echo $percent; ?>%"></div>
                                    </div>
                                    <span class="text-muted small"><?php echo round($percent, 1); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Chi tiết -->
        <div class="tab-pane fade" id="tab-detail">
            <!-- Filter -->
            <div class="filter-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tìm kiếm</label>
                        <input type="text" class="form-control" name="search" placeholder="Tên thuốc..."
                            value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Danh mục</label>
                        <select class="form-select" name="loai_id">
                            <option value="">Tất cả</option>
                            <?php foreach ($byCategory as $cat): ?>
                                <option value="<?php echo $cat['loai_id']; ?>" <?php echo $filters['loai_id'] == $cat['loai_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['ten_loai']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Lọc
                        </button>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Thuốc</th>
                                <th>Danh mục</th>
                                <th class="text-center">Tồn kho</th>
                                <th class="text-end">Giá nhập TB</th>
                                <th class="text-end">Giá trị tồn</th>
                                <th class="text-center">HSD gần nhất</th>
                                <th class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detailedInventory as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['ten_thuoc']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['ten_donvi']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($item['ten_loai']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $item['tong_ton'] < 20 ? 'danger' : 'success'; ?>">
                                        <?php echo number_format($item['tong_ton']); ?>
                                    </span>
                                </td>
                                <td class="text-end"><?php echo InventoryAnalyticsController::formatMoney($item['gia_nhap_tb']); ?></td>
                                <td class="text-end"><?php echo InventoryAnalyticsController::formatMoney($item['gia_tri_ton']); ?></td>
                                <td class="text-center">
                                    <?php if ($item['hsd_gan_nhat']): ?>
                                        <span class="<?php echo $item['ngay_con_lai'] <= 30 ? 'text-danger' : ''; ?>">
                                            <?php echo date('d/m/Y', strtotime($item['hsd_gan_nhat'])); ?>
                                        </span>
                                        <?php if ($item['ngay_con_lai'] <= 30): ?>
                                            <br><small class="text-danger">Còn <?php echo $item['ngay_con_lai']; ?> ngày</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($item['tong_ton'] < 20): ?>
                                        <span class="badge bg-danger">Tồn thấp</span>
                                    <?php elseif ($item['ngay_con_lai'] <= 30): ?>
                                        <span class="badge bg-warning text-dark">Sắp hết hạn</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Bình thường</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Phân tích ABC -->
        <div class="tab-pane fade" id="tab-abc">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card p-3 border-start border-success border-4">
                        <h6 class="text-muted">Nhóm A - Quan trọng nhất</h6>
                        <h4 class="text-success"><?php echo count(array_filter($abcAnalysis, fn($i) => $i['phan_loai'] === 'A')); ?> sản phẩm</h4>
                        <small>Chiếm 70% doanh thu</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card p-3 border-start border-warning border-4">
                        <h6 class="text-muted">Nhóm B - Quan trọng</h6>
                        <h4 class="text-warning"><?php echo count(array_filter($abcAnalysis, fn($i) => $i['phan_loai'] === 'B')); ?> sản phẩm</h4>
                        <small>Chiếm 20% doanh thu</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card p-3 border-start border-secondary border-4">
                        <h6 class="text-muted">Nhóm C - Ít quan trọng</h6>
                        <h4 class="text-secondary"><?php echo count(array_filter($abcAnalysis, fn($i) => $i['phan_loai'] === 'C')); ?> sản phẩm</h4>
                        <small>Chiếm 10% doanh thu</small>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Phân loại</th>
                                <th>Thuốc</th>
                                <th>Danh mục</th>
                                <th class="text-center">Số lượng bán</th>
                                <th class="text-end">Doanh thu</th>
                                <th class="text-center">% Tích lũy</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($abcAnalysis, 0, 50) as $item): ?>
                            <tr>
                                <td>
                                    <span class="abc-badge bg-<?php echo InventoryAnalyticsController::getABCClass($item['phan_loai']); ?> text-white">
                                        <?php echo $item['phan_loai']; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($item['ten_thuoc']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['ten_loai']); ?></td>
                                <td class="text-center"><?php echo number_format($item['so_luong_ban']); ?></td>
                                <td class="text-end"><?php echo InventoryAnalyticsController::formatMoney($item['doanh_thu']); ?></td>
                                <td class="text-center"><?php echo $item['phan_tram_tich_luy']; ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Vòng quay tồn kho -->
        <div class="tab-pane fade" id="tab-turnover">
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle"></i>
                <strong>Vòng quay tồn kho</strong> cho biết số lần hàng tồn kho được bán và thay thế trong một khoảng thời gian.
                Vòng quay cao = bán chạy, vòng quay thấp = tồn đọng.
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Thuốc</th>
                                <th>Danh mục</th>
                                <th class="text-center">Tồn hiện tại</th>
                                <th class="text-center">Đã bán (90 ngày)</th>
                                <th class="text-center">Vòng quay/năm</th>
                                <th class="text-center">Ngày tồn TB</th>
                                <th class="text-center">Đánh giá</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($turnover as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['ten_thuoc']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['ten_loai']); ?></td>
                                <td class="text-center"><?php echo number_format($item['ton_hien_tai']); ?></td>
                                <td class="text-center"><?php echo number_format($item['da_ban']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $item['vong_quay_nam'] > 4 ? 'success' : ($item['vong_quay_nam'] > 2 ? 'warning' : 'danger'); ?>">
                                        <?php echo $item['vong_quay_nam']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php echo $item['ngay_ton_kho_tb'] < 999 ? number_format($item['ngay_ton_kho_tb']) . ' ngày' : '-'; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($item['vong_quay_nam'] > 6): ?>
                                        <span class="badge bg-success">Bán rất chạy</span>
                                    <?php elseif ($item['vong_quay_nam'] > 4): ?>
                                        <span class="badge bg-primary">Bán chạy</span>
                                    <?php elseif ($item['vong_quay_nam'] > 2): ?>
                                        <span class="badge bg-warning text-dark">Trung bình</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Tồn đọng</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Data từ PHP
const categoryData = <?php echo json_encode($byCategory); ?>;

// Biểu đồ tròn theo danh mục
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: categoryData.map(c => c.ten_loai),
        datasets: [{
            data: categoryData.map(c => c.tong_so_luong),
            backgroundColor: [
                '#667eea', '#764ba2', '#11998e', '#38ef7d',
                '#f093fb', '#f5576c', '#4facfe', '#00f2fe',
                '#eb3349', '#f45c43'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Biểu đồ cột giá trị
new Chart(document.getElementById('valueChart'), {
    type: 'bar',
    data: {
        labels: categoryData.map(c => c.ten_loai),
        datasets: [{
            label: 'Giá trị tồn kho',
            data: categoryData.map(c => c.gia_tri),
            backgroundColor: 'rgba(102, 126, 234, 0.8)',
            borderColor: '#667eea',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN').format(value) + ' đ';
                    }
                }
            }
        }
    }
});

function exportExcel() {
    alert('Chức năng xuất Excel đang phát triển');
}

function exportPDF() {
    alert('Chức năng xuất PDF đang phát triển');
}
</script>
</body>
</html>
