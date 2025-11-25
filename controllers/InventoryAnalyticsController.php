<?php
require_once __DIR__ . '/../models/InventoryAnalytics.php';

class InventoryAnalyticsController {
    private $model;

    public function __construct() {
        $this->model = new InventoryAnalytics();
    }

    // ==================== BÁO CÁO TỒN KHO ====================

    public function getInventorySummary() {
        return $this->model->getInventorySummary();
    }

    public function getInventoryByCategory() {
        return $this->model->getInventoryByCategory();
    }

    public function getDetailedInventory($filters = []) {
        return $this->model->getDetailedInventory($filters);
    }

    // ==================== GỢI Ý MUA HÀNG ====================

    public function getLowStockSuggestions($threshold = 20) {
        return $this->model->getLowStockSuggestions($threshold);
    }

    public function getExpiringStockSuggestions($days = 60) {
        return $this->model->getExpiringStockSuggestions($days);
    }

    public function getRestockSuggestions($days = 30) {
        return $this->model->getRestockSuggestions($days);
    }

    // Tổng hợp tất cả gợi ý mua hàng
    public function getAllPurchaseSuggestions() {
        $suggestions = [
            'low_stock' => $this->getLowStockSuggestions(20),
            'expiring' => $this->getExpiringStockSuggestions(60),
            'restock' => $this->getRestockSuggestions(30)
        ];

        // Đếm số lượng cảnh báo
        $suggestions['counts'] = [
            'low_stock' => count($suggestions['low_stock']),
            'expiring' => count($suggestions['expiring']),
            'restock' => count(array_filter($suggestions['restock'], function($item) {
                return $item['goi_y'] === 'Cần nhập gấp';
            }))
        ];

        return $suggestions;
    }

    // ==================== DỰ ĐOÁN NHU CẦU ====================

    public function getSalesHistory($thuoc_id = null, $months = 6) {
        return $this->model->getSalesHistory($thuoc_id, $months);
    }

    public function getDemandForecast($months = 3) {
        return $this->model->getDemandForecast($months);
    }

    public function getTopSellingProducts($limit = 10, $days = 30) {
        return $this->model->getTopSellingProducts($limit, $days);
    }

    // Lấy dữ liệu biểu đồ dự đoán
    public function getForecastChartData($thuoc_id = null) {
        $history = $this->getSalesHistory($thuoc_id, 6);

        // Nhóm theo tháng
        $chartData = [];
        foreach ($history as $item) {
            if (!isset($chartData[$item['thang']])) {
                $chartData[$item['thang']] = 0;
            }
            $chartData[$item['thang']] += $item['so_luong_ban'];
        }

        // Sắp xếp theo tháng
        ksort($chartData);

        // Dự đoán tháng tiếp theo (trung bình động đơn giản)
        $values = array_values($chartData);
        $avgSales = count($values) > 0 ? array_sum($values) / count($values) : 0;

        // Thêm dự đoán
        $nextMonth = date('Y-m', strtotime('+1 month'));
        $chartData[$nextMonth] = round($avgSales);

        return [
            'labels' => array_keys($chartData),
            'data' => array_values($chartData),
            'forecast_month' => $nextMonth,
            'forecast_value' => round($avgSales)
        ];
    }

    // ==================== QUẢN TRỊ DỰ TRỮ ====================

    public function getStockLevels() {
        return $this->model->getStockLevels();
    }

    public function updateStockLevel($thuoc_id, $muc_toi_thieu, $muc_toi_da, $diem_dat_hang) {
        // Validate
        if ($muc_toi_thieu < 0 || $muc_toi_da < 0 || $diem_dat_hang < 0) {
            return ['success' => false, 'error' => 'Giá trị không được âm'];
        }

        if ($muc_toi_thieu >= $muc_toi_da) {
            return ['success' => false, 'error' => 'Mức tối thiểu phải nhỏ hơn mức tối đa'];
        }

        if ($this->model->updateStockLevel($thuoc_id, $muc_toi_thieu, $muc_toi_da, $diem_dat_hang)) {
            return ['success' => true, 'message' => 'Cập nhật thành công'];
        }

        return ['success' => false, 'error' => 'Không thể cập nhật'];
    }

    public function getABCAnalysis($days = 90) {
        return $this->model->getABCAnalysis($days);
    }

    public function getInventoryTurnover($days = 90) {
        return $this->model->getInventoryTurnover($days);
    }

    // ==================== DASHBOARD ====================

    public function getDashboardStats() {
        return $this->model->getDashboardStats();
    }

    // Tổng hợp dữ liệu cho dashboard
    public function getDashboardData() {
        return [
            'stats' => $this->getDashboardStats(),
            'low_stock' => $this->getLowStockSuggestions(20),
            'expiring' => $this->getExpiringStockSuggestions(30),
            'top_selling' => $this->getTopSellingProducts(5, 30),
            'by_category' => $this->getInventoryByCategory()
        ];
    }

    // ==================== HELPER ====================

    // Format số tiền
    public static function formatMoney($amount) {
        return number_format($amount, 0, ',', '.') . ' đ';
    }

    // Format số lượng
    public static function formatNumber($number) {
        return number_format($number, 0, ',', '.');
    }

    // Lấy class CSS cho trạng thái tồn kho
    public static function getStockStatusClass($status) {
        $classes = [
            'Thấp' => 'danger',
            'Cao' => 'warning',
            'Bình thường' => 'success',
            'Cần nhập gấp' => 'danger',
            'Nên nhập sớm' => 'warning',
            'Đủ dùng' => 'success'
        ];
        return $classes[$status] ?? 'secondary';
    }

    // Lấy class CSS cho phân loại ABC
    public static function getABCClass($class) {
        $classes = [
            'A' => 'success',
            'B' => 'warning',
            'C' => 'secondary'
        ];
        return $classes[$class] ?? 'secondary';
    }
}
