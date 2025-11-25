<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../controllers/InventoryAnalyticsController.php';

requireAdmin();

$controller = new InventoryAnalyticsController();

// Lấy tất cả gợi ý
$suggestions = $controller->getAllPurchaseSuggestions();
$lowStock = $suggestions['low_stock'];
$expiring = $suggestions['expiring'];
$restock = $suggestions['restock'];
$counts = $suggestions['counts'];

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

.alert-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 20px;
}

.alert-card-header {
    padding: 15px 20px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.alert-card-header.bg-danger { background: linear-gradient(135deg, #eb3349, #f45c43); }
.alert-card-header.bg-warning { background: linear-gradient(135deg, #f093fb, #f5576c); }
.alert-card-header.bg-info { background: linear-gradient(135deg, #4facfe, #00f2fe); }

.alert-card-body {
    padding: 0;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
    padding: 12px 15px;
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
    padding: 10px 15px;
}

.urgency-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.summary-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    text-align: center;
    transition: all 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
}

.summary-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin: 0 auto 15px;
    color: white;
}

.supplier-tag {
    background: #e9ecef;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    margin-right: 5px;
    margin-bottom: 5px;
    display: inline-block;
}

.action-btn {
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
}

.nav-pills .nav-link {
    border-radius: 10px;
    padding: 12px 25px;
    margin-right: 10px;
    font-weight: 500;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.badge-count {
    background: rgba(255,255,255,0.3);
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: 8px;
}
</style>

<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-shopping-cart text-primary"></i> Gợi Ý Mua Hàng</h2>
        <div>
            <a href="import-inventory.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Nhập kho mới
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="summary-card">
                <div class="summary-icon bg-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="mb-1"><?php echo $counts['low_stock']; ?></h3>
                <p class="text-muted mb-0">Thuốc tồn thấp</p>
                <small class="text-danger">Cần nhập thêm ngay</small>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="summary-card">
                <div class="summary-icon bg-warning">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3 class="mb-1"><?php echo count($expiring); ?></h3>
                <p class="text-muted mb-0">Lô sắp hết hạn</p>
                <small class="text-warning">Trong 60 ngày tới</small>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="summary-card">
                <div class="summary-icon bg-info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="mb-1"><?php echo $counts['restock']; ?></h3>
                <p class="text-muted mb-0">Cần nhập gấp</p>
                <small class="text-info">Dựa trên tốc độ bán</small>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="pill" href="#tab-low-stock">
                <i class="fas fa-battery-quarter"></i> Tồn thấp
                <span class="badge-count"><?php echo $counts['low_stock']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="pill" href="#tab-expiring">
                <i class="fas fa-hourglass-half"></i> Sắp hết hạn
                <span class="badge-count"><?php echo count($expiring); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="pill" href="#tab-restock">
                <i class="fas fa-truck-loading"></i> Dựa trên bán hàng
                <span class="badge-count"><?php echo count($restock); ?></span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Tab Tồn thấp -->
        <div class="tab-pane fade show active" id="tab-low-stock">
            <div class="alert-card">
                <div class="alert-card-header bg-danger">
                    <div>
                        <i class="fas fa-exclamation-circle fa-lg me-2"></i>
                        <strong>Thuốc tồn kho thấp (dưới 20 đơn vị)</strong>
                    </div>
                    <span class="badge bg-light text-danger"><?php echo count($lowStock); ?> sản phẩm</span>
                </div>
                <div class="alert-card-body">
                    <?php if (empty($lowStock)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">Không có thuốc nào tồn kho thấp</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Tên thuốc</th>
                                        <th>Danh mục</th>
                                        <th class="text-center">Tồn hiện tại</th>
                                        <th class="text-center">Mức tối thiểu</th>
                                        <th class="text-center">Cần nhập thêm</th>
                                        <th class="text-end">Giá nhập TB</th>
                                        <th>Nhà cung cấp</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStock as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['ten_thuoc']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($item['ten_donvi']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['ten_loai']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?php echo number_format($item['ton_hien_tai']); ?></span>
                                        </td>
                                        <td class="text-center"><?php echo number_format($item['muc_toi_thieu']); ?></td>
                                        <td class="text-center">
                                            <strong class="text-primary"><?php echo number_format($item['can_nhap_them']); ?></strong>
                                        </td>
                                        <td class="text-end"><?php echo InventoryAnalyticsController::formatMoney($item['gia_nhap_tb']); ?></td>
                                        <td>
                                            <?php
                                            $suppliers = explode(',', $item['nha_cung_cap']);
                                            foreach ($suppliers as $sup):
                                            ?>
                                                <span class="supplier-tag"><?php echo htmlspecialchars(trim($sup)); ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="import-inventory.php?thuoc_id=<?php echo $item['thuoc_id']; ?>" class="btn btn-sm btn-primary action-btn">
                                                <i class="fas fa-plus"></i> Nhập
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab Sắp hết hạn -->
        <div class="tab-pane fade" id="tab-expiring">
            <div class="alert-card">
                <div class="alert-card-header bg-warning">
                    <div>
                        <i class="fas fa-calendar-times fa-lg me-2"></i>
                        <strong>Thuốc sắp hết hạn (trong 60 ngày)</strong>
                    </div>
                    <span class="badge bg-light text-warning"><?php echo count($expiring); ?> lô hàng</span>
                </div>
                <div class="alert-card-body">
                    <?php if (empty($expiring)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">Không có thuốc nào sắp hết hạn</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Tên thuốc</th>
                                        <th>Danh mục</th>
                                        <th class="text-center">Số lượng</th>
                                        <th class="text-center">Hạn sử dụng</th>
                                        <th class="text-center">Còn lại</th>
                                        <th class="text-end">Giá trị tồn</th>
                                        <th>Nhà cung cấp</th>
                                        <th class="text-center">Mức độ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expiring as $item): ?>
                                    <tr class="<?php echo $item['ngay_con_lai'] < 0 ? 'table-danger' : ($item['ngay_con_lai'] <= 7 ? 'table-warning' : ''); ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['ten_thuoc']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($item['ten_donvi']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['ten_loai']); ?></td>
                                        <td class="text-center"><?php echo number_format($item['soluong']); ?></td>
                                        <td class="text-center">
                                            <strong><?php echo date('d/m/Y', strtotime($item['hansudung'])); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($item['ngay_con_lai'] < 0): ?>
                                                <span class="badge bg-dark">Đã hết hạn <?php echo abs($item['ngay_con_lai']); ?> ngày</span>
                                            <?php elseif ($item['ngay_con_lai'] == 0): ?>
                                                <span class="badge bg-danger">Hết hạn hôm nay</span>
                                            <?php else: ?>
                                                <span class="badge bg-<?php echo $item['ngay_con_lai'] <= 7 ? 'danger' : 'warning'; ?>">
                                                    Còn <?php echo $item['ngay_con_lai']; ?> ngày
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?php echo InventoryAnalyticsController::formatMoney($item['gia_tri_ton']); ?></td>
                                        <td><span class="supplier-tag"><?php echo htmlspecialchars($item['ten_ncc']); ?></span></td>
                                        <td class="text-center">
                                            <span class="urgency-badge bg-<?php
                                                echo $item['muc_do_khan_cap'] === 'Đã hết hạn' ? 'dark' :
                                                    ($item['muc_do_khan_cap'] === 'Rất gấp' ? 'danger' :
                                                    ($item['muc_do_khan_cap'] === 'Gấp' ? 'warning text-dark' : 'info'));
                                            ?>">
                                                <?php echo $item['muc_do_khan_cap']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="p-3 bg-light">
                            <strong>Tổng giá trị thuốc sắp hết hạn:</strong>
                            <span class="text-danger fs-5 ms-2">
                                <?php echo InventoryAnalyticsController::formatMoney(array_sum(array_column($expiring, 'gia_tri_ton'))); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <i class="fas fa-lightbulb"></i>
                <strong>Gợi ý:</strong> Với thuốc sắp hết hạn, bạn có thể:
                <ul class="mb-0 mt-2">
                    <li>Áp dụng khuyến mãi để đẩy nhanh tiêu thụ</li>
                    <li>Liên hệ nhà cung cấp để đổi trả (nếu có chính sách)</li>
                    <li>Chuyển sang kênh bán hàng có vòng quay nhanh hơn</li>
                </ul>
            </div>
        </div>

        <!-- Tab Dựa trên bán hàng -->
        <div class="tab-pane fade" id="tab-restock">
            <div class="alert-card">
                <div class="alert-card-header bg-info">
                    <div>
                        <i class="fas fa-chart-line fa-lg me-2"></i>
                        <strong>Gợi ý nhập hàng dựa trên tốc độ bán (30 ngày)</strong>
                    </div>
                    <span class="badge bg-light text-info"><?php echo count($restock); ?> sản phẩm</span>
                </div>
                <div class="alert-card-body">
                    <?php if (empty($restock)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">Tất cả sản phẩm đều đủ hàng</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Tên thuốc</th>
                                        <th>Danh mục</th>
                                        <th class="text-center">Tồn hiện tại</th>
                                        <th class="text-center">Đã bán (30 ngày)</th>
                                        <th class="text-center">Bán TB/ngày</th>
                                        <th class="text-center">Còn đủ dùng</th>
                                        <th class="text-center">Gợi ý</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($restock as $item): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['ten_thuoc']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['ten_loai']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo $item['ton_hien_tai'] == 0 ? 'dark' : 'secondary'; ?>">
                                                <?php echo number_format($item['ton_hien_tai']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?php echo number_format($item['da_ban_30_ngay']); ?></td>
                                        <td class="text-center"><?php echo $item['ban_tb_ngay']; ?></td>
                                        <td class="text-center">
                                            <?php if ($item['ngay_con_du'] < 999): ?>
                                                <span class="badge bg-<?php echo $item['ngay_con_du'] < 7 ? 'danger' : ($item['ngay_con_du'] < 14 ? 'warning' : 'info'); ?>">
                                                    <?php echo $item['ngay_con_du']; ?> ngày
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo InventoryAnalyticsController::getStockStatusClass($item['goi_y']); ?>">
                                                <?php echo $item['goi_y']; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="import-inventory.php?thuoc_id=<?php echo $item['thuoc_id']; ?>" class="btn btn-sm btn-primary action-btn">
                                                <i class="fas fa-plus"></i> Nhập
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="alert alert-light border mt-3">
                <i class="fas fa-info-circle text-primary"></i>
                <strong>Giải thích:</strong>
                <ul class="mb-0 mt-2">
                    <li><span class="badge bg-danger">Cần nhập gấp</span> - Tồn kho chỉ đủ dùng dưới 14 ngày</li>
                    <li><span class="badge bg-warning text-dark">Nên nhập sớm</span> - Tồn kho đủ dùng 14-30 ngày</li>
                    <li><span class="badge bg-success">Đủ dùng</span> - Tồn kho đủ dùng trên 30 ngày</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
