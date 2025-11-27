<?php
/**
 * API xuất báo cáo bán hàng ra Excel
 */

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../../config/database.php';

// Lấy khoảng thời gian báo cáo
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$period_days = 30;

if ($period == 'week') {
    $period_days = 7;
} elseif ($period == 'quarter') {
    $period_days = 90;
} elseif ($period == 'year') {
    $period_days = 365;
}

$period_label = [
    'week' => '7 ngày',
    'month' => '30 ngày',
    'quarter' => 'Quý',
    'year' => 'Năm'
][$period] ?? '30 ngày';

// Kết nối database
$db = new Database();
$conn = $db->getConnection();

// Lấy dữ liệu bán hàng
$sql_sales = "SELECT t.id, t.ten_thuoc, lt.ten_loai,
              SUM(cd.soluong) AS total_sold,
              SUM(cd.dongia * cd.soluong) AS revenue
              FROM chitiet_donhang cd
              JOIN thuoc t ON cd.thuoc_id = t.id
              JOIN loai_thuoc lt ON t.loai_id = lt.id
              JOIN donhang d ON cd.donhang_id = d.id
              WHERE DATE(d.ngay_dat) BETWEEN DATE_SUB(CURDATE(), INTERVAL :days DAY) AND CURDATE()
              AND d.trangthai IN ('dadat', 'dathanhtoan')
              GROUP BY t.id, t.ten_thuoc, lt.ten_loai
              ORDER BY total_sold DESC";

$stmt_sales = $conn->prepare($sql_sales);
$stmt_sales->bindParam(':days', $period_days, PDO::PARAM_INT);
$stmt_sales->execute();
$sales_data = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);

// Lấy tổng doanh thu
$sql_total_revenue = "SELECT SUM(tongtien) AS total
                     FROM donhang
                     WHERE DATE(ngay_dat) BETWEEN DATE_SUB(CURDATE(), INTERVAL :days DAY) AND CURDATE()
                     AND trangthai IN ('dadat', 'dathanhtoan')";
$stmt_total_revenue = $conn->prepare($sql_total_revenue);
$stmt_total_revenue->bindParam(':days', $period_days, PDO::PARAM_INT);
$stmt_total_revenue->execute();
$total_revenue = $stmt_total_revenue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Lấy tổng số đơn hàng
$sql_total_orders = "SELECT COUNT(*) AS total
                    FROM donhang
                    WHERE DATE(ngay_dat) BETWEEN DATE_SUB(CURDATE(), INTERVAL :days DAY) AND CURDATE()
                    AND trangthai IN ('dadat', 'dathanhtoan')";
$stmt_total_orders = $conn->prepare($sql_total_orders);
$stmt_total_orders->bindParam(':days', $period_days, PDO::PARAM_INT);
$stmt_total_orders->execute();
$total_orders = $stmt_total_orders->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Tổng sản phẩm đã bán
$total_products_sold = array_sum(array_column($sales_data, 'total_sold'));

// Tạo file Excel (CSV với BOM cho Excel đọc được tiếng Việt)
$filename = 'bao-cao-ban-hang-' . $period . '-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output BOM for UTF-8
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Tiêu đề báo cáo
fputcsv($output, ['BÁO CÁO SẢN PHẨM BÁN CHẠY']);
fputcsv($output, ['Khoảng thời gian: ' . $period_label]);
fputcsv($output, ['Ngày xuất: ' . date('d/m/Y H:i:s')]);
fputcsv($output, []);

// Thống kê tổng quan
fputcsv($output, ['THỐNG KÊ TỔNG QUAN']);
fputcsv($output, ['Tổng sản phẩm đã bán', $total_products_sold]);
fputcsv($output, ['Tổng đơn hàng', $total_orders]);
fputcsv($output, ['Tổng doanh thu', number_format($total_revenue, 0, ',', '.') . ' đ']);
fputcsv($output, []);

// Header bảng chi tiết
fputcsv($output, ['CHI TIẾT SẢN PHẨM BÁN CHẠY']);
fputcsv($output, ['STT', 'Mã SP', 'Tên sản phẩm', 'Loại sản phẩm', 'Số lượng bán', 'Doanh thu']);

// Data rows
$stt = 1;
foreach ($sales_data as $product) {
    fputcsv($output, [
        $stt++,
        $product['id'],
        $product['ten_thuoc'],
        $product['ten_loai'],
        $product['total_sold'],
        number_format($product['revenue'], 0, ',', '.') . ' đ'
    ]);
}

// Dòng tổng cộng
fputcsv($output, []);
fputcsv($output, ['', '', '', 'TỔNG CỘNG', $total_products_sold, number_format($total_revenue, 0, ',', '.') . ' đ']);

fclose($output);
exit;
