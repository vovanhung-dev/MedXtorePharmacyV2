<?php
require_once __DIR__ . '/../config/database.php';

class InventoryAnalytics {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // ==================== BÁO CÁO TỒN KHO ====================

    // Tổng quan tồn kho
    public function getInventorySummary() {
        $query = "SELECT
                    COUNT(DISTINCT k.thuoc_id) as tong_loai_thuoc,
                    SUM(k.soluong) as tong_so_luong,
                    SUM(k.soluong * k.gia) as tong_gia_tri,
                    COUNT(DISTINCT k.nhacungcap_id) as so_nha_cung_cap
                  FROM khohang k
                  WHERE k.soluong > 0";

        $stmt = $this->conn->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Tồn kho theo danh mục
    public function getInventoryByCategory() {
        $query = "SELECT
                    l.id as loai_id,
                    l.ten_loai,
                    COUNT(DISTINCT k.thuoc_id) as so_thuoc,
                    SUM(k.soluong) as tong_so_luong,
                    SUM(k.soluong * k.gia) as gia_tri
                  FROM khohang k
                  JOIN thuoc t ON k.thuoc_id = t.id
                  JOIN loai_thuoc l ON t.loai_id = l.id
                  WHERE k.soluong > 0
                  GROUP BY l.id, l.ten_loai
                  ORDER BY gia_tri DESC";

        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Chi tiết tồn kho theo thuốc
    public function getDetailedInventory($filters = []) {
        $query = "SELECT
                    t.id as thuoc_id,
                    t.ten_thuoc,
                    t.hinhanh,
                    l.ten_loai,
                    dv.ten_donvi,
                    SUM(k.soluong) as tong_ton,
                    AVG(k.gia) as gia_nhap_tb,
                    MIN(k.hansudung) as hsd_gan_nhat,
                    MAX(k.hansudung) as hsd_xa_nhat,
                    SUM(k.soluong * k.gia) as gia_tri_ton,
                    DATEDIFF(MIN(k.hansudung), CURDATE()) as ngay_con_lai
                  FROM khohang k
                  JOIN thuoc t ON k.thuoc_id = t.id
                  JOIN loai_thuoc l ON t.loai_id = l.id
                  JOIN donvi dv ON k.donvi_id = dv.id
                  WHERE k.soluong > 0";

        $params = [];

        if (!empty($filters['loai_id'])) {
            $query .= " AND l.id = ?";
            $params[] = $filters['loai_id'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND t.ten_thuoc LIKE ?";
            $params[] = "%{$filters['search']}%";
        }

        $query .= " GROUP BY t.id, t.ten_thuoc, t.hinhanh, l.ten_loai, dv.ten_donvi
                    ORDER BY tong_ton DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================== GỢI Ý MUA HÀNG ====================

    // Thuốc cần nhập thêm (tồn kho thấp)
    public function getLowStockSuggestions($threshold = 20) {
        $query = "SELECT
                    t.id as thuoc_id,
                    t.ten_thuoc,
                    l.ten_loai,
                    dv.ten_donvi,
                    SUM(k.soluong) as ton_hien_tai,
                    ? as muc_toi_thieu,
                    (? - SUM(k.soluong)) as can_nhap_them,
                    AVG(k.gia) as gia_nhap_tb,
                    GROUP_CONCAT(DISTINCT ncc.ten_ncc) as nha_cung_cap
                  FROM khohang k
                  JOIN thuoc t ON k.thuoc_id = t.id
                  JOIN loai_thuoc l ON t.loai_id = l.id
                  JOIN donvi dv ON k.donvi_id = dv.id
                  JOIN nhacungcap ncc ON k.nhacungcap_id = ncc.id
                  GROUP BY t.id, t.ten_thuoc, l.ten_loai, dv.ten_donvi
                  HAVING SUM(k.soluong) < ?
                  ORDER BY ton_hien_tai ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$threshold, $threshold, $threshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Thuốc sắp hết hạn cần xử lý
    public function getExpiringStockSuggestions($days = 60) {
        $query = "SELECT
                    k.id as kho_id,
                    t.id as thuoc_id,
                    t.ten_thuoc,
                    l.ten_loai,
                    dv.ten_donvi,
                    k.soluong,
                    k.gia as gia_nhap,
                    k.hansudung,
                    DATEDIFF(k.hansudung, CURDATE()) as ngay_con_lai,
                    (k.soluong * k.gia) as gia_tri_ton,
                    ncc.ten_ncc,
                    CASE
                        WHEN DATEDIFF(k.hansudung, CURDATE()) < 0 THEN 'Đã hết hạn'
                        WHEN DATEDIFF(k.hansudung, CURDATE()) <= 7 THEN 'Rất gấp'
                        WHEN DATEDIFF(k.hansudung, CURDATE()) <= 30 THEN 'Gấp'
                        ELSE 'Cần theo dõi'
                    END as muc_do_khan_cap
                  FROM khohang k
                  JOIN thuoc t ON k.thuoc_id = t.id
                  JOIN loai_thuoc l ON t.loai_id = l.id
                  JOIN donvi dv ON k.donvi_id = dv.id
                  JOIN nhacungcap ncc ON k.nhacungcap_id = ncc.id
                  WHERE k.soluong > 0
                  AND DATEDIFF(k.hansudung, CURDATE()) <= ?
                  ORDER BY k.hansudung ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Gợi ý thuốc cần nhập dựa trên tốc độ bán
    public function getRestockSuggestions($days = 30) {
        $query = "SELECT
                    t.id as thuoc_id,
                    t.ten_thuoc,
                    l.ten_loai,
                    COALESCE(SUM(k.soluong), 0) as ton_hien_tai,
                    COALESCE(sold.so_luong_ban, 0) as da_ban_30_ngay,
                    ROUND(COALESCE(sold.so_luong_ban, 0) / ?, 1) as ban_tb_ngay,
                    CASE
                        WHEN COALESCE(sold.so_luong_ban, 0) > 0
                        THEN ROUND(COALESCE(SUM(k.soluong), 0) / (COALESCE(sold.so_luong_ban, 0) / ?), 0)
                        ELSE 999
                    END as ngay_con_du,
                    CASE
                        WHEN COALESCE(SUM(k.soluong), 0) < (COALESCE(sold.so_luong_ban, 0) / ? * 14) THEN 'Cần nhập gấp'
                        WHEN COALESCE(SUM(k.soluong), 0) < (COALESCE(sold.so_luong_ban, 0) / ? * 30) THEN 'Nên nhập sớm'
                        ELSE 'Đủ dùng'
                    END as goi_y
                  FROM thuoc t
                  JOIN loai_thuoc l ON t.loai_id = l.id
                  LEFT JOIN khohang k ON t.id = k.thuoc_id AND k.soluong > 0
                  LEFT JOIN (
                      SELECT ctdh.thuoc_id, SUM(ctdh.soluong) as so_luong_ban
                      FROM chitiet_donhang ctdh
                      JOIN donhang dh ON ctdh.donhang_id = dh.id
                      WHERE dh.trangthai IN ('dagiao', 'dadat', 'danggiao')
                      AND dh.ngay_dat >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                      GROUP BY ctdh.thuoc_id
                  ) sold ON t.id = sold.thuoc_id
                  GROUP BY t.id, t.ten_thuoc, l.ten_loai, sold.so_luong_ban
                  HAVING goi_y != 'Đủ dùng' OR ton_hien_tai = 0
                  ORDER BY ngay_con_du ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$days, $days, $days, $days, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================== DỰ ĐOÁN NHU CẦU ====================

    // Lịch sử bán hàng theo tháng
    public function getSalesHistory($thuoc_id = null, $months = 6) {
        $query = "SELECT
                    DATE_FORMAT(dh.ngay_dat, '%Y-%m') as thang,
                    t.id as thuoc_id,
                    t.ten_thuoc,
                    SUM(ctdh.soluong) as so_luong_ban,
                    SUM(ctdh.soluong * ctdh.dongia) as doanh_thu
                  FROM chitiet_donhang ctdh
                  JOIN donhang dh ON ctdh.donhang_id = dh.id
                  JOIN thuoc t ON ctdh.thuoc_id = t.id
                  WHERE dh.trangthai IN ('dagiao', 'dadat', 'danggiao')
                  AND dh.ngay_dat >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)";

        $params = [$months];

        if ($thuoc_id) {
            $query .= " AND t.id = ?";
            $params[] = $thuoc_id;
        }

        $query .= " GROUP BY DATE_FORMAT(dh.ngay_dat, '%Y-%m'), t.id, t.ten_thuoc
                    ORDER BY thang DESC, so_luong_ban DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Dự đoán nhu cầu tháng tới (dựa trên trung bình động)
    public function getDemandForecast($months = 3) {
        $query = "SELECT
                    t.id as thuoc_id,
                    t.ten_thuoc,
                    l.ten_loai,
                    COALESCE(SUM(k.soluong), 0) as ton_hien_tai,
                    COALESCE(ROUND(AVG(monthly_sales.so_luong_ban), 0), 0) as du_doan_thang_toi,
                    COALESCE(MAX(monthly_sales.so_luong_ban), 0) as ban_cao_nhat,
                    COALESCE(MIN(monthly_sales.so_luong_ban), 0) as ban_thap_nhat,
                    CASE
                        WHEN COALESCE(SUM(k.soluong), 0) < COALESCE(ROUND(AVG(monthly_sales.so_luong_ban), 0), 0)
                        THEN COALESCE(ROUND(AVG(monthly_sales.so_luong_ban), 0), 0) - COALESCE(SUM(k.soluong), 0)
                        ELSE 0
                    END as can_nhap_them
                  FROM thuoc t
                  JOIN loai_thuoc l ON t.loai_id = l.id
                  LEFT JOIN khohang k ON t.id = k.thuoc_id AND k.soluong > 0
                  LEFT JOIN (
                      SELECT
                          ctdh.thuoc_id,
                          DATE_FORMAT(dh.ngay_dat, '%Y-%m') as thang,
                          SUM(ctdh.soluong) as so_luong_ban
                      FROM chitiet_donhang ctdh
                      JOIN donhang dh ON ctdh.donhang_id = dh.id
                      WHERE dh.trangthai IN ('dagiao', 'dadat', 'danggiao')
                      AND dh.ngay_dat >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                      GROUP BY ctdh.thuoc_id, DATE_FORMAT(dh.ngay_dat, '%Y-%m')
                  ) monthly_sales ON t.id = monthly_sales.thuoc_id
                  GROUP BY t.id, t.ten_thuoc, l.ten_loai
                  HAVING du_doan_thang_toi > 0 OR ton_hien_tai > 0
                  ORDER BY du_doan_thang_toi DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Top thuốc bán chạy
    public function getTopSellingProducts($limit = 10, $days = 30) {
        $query = "SELECT
                    t.id as thuoc_id,
                    t.ten_thuoc,
                    t.hinhanh,
                    l.ten_loai,
                    SUM(ctdh.soluong) as tong_ban,
                    SUM(ctdh.soluong * ctdh.dongia) as doanh_thu,
                    COUNT(DISTINCT dh.id) as so_don_hang,
                    COALESCE(inv.ton_kho, 0) as ton_kho
                  FROM chitiet_donhang ctdh
                  JOIN donhang dh ON ctdh.donhang_id = dh.id
                  JOIN thuoc t ON ctdh.thuoc_id = t.id
                  JOIN loai_thuoc l ON t.loai_id = l.id
                  LEFT JOIN (
                      SELECT thuoc_id, SUM(soluong) as ton_kho
                      FROM khohang
                      WHERE soluong > 0
                      GROUP BY thuoc_id
                  ) inv ON t.id = inv.thuoc_id
                  WHERE dh.trangthai IN ('dagiao', 'dadat', 'danggiao')
                  AND dh.ngay_dat >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                  GROUP BY t.id, t.ten_thuoc, t.hinhanh, l.ten_loai, inv.ton_kho
                  ORDER BY tong_ban DESC
                  LIMIT " . intval($limit);

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================== QUẢN TRỊ DỰ TRỮ ====================

    // Lấy cấu hình mức dự trữ
    public function getStockLevels() {
        $query = "SELECT
                    t.id as thuoc_id,
                    t.ten_thuoc,
                    l.ten_loai,
                    COALESCE(sl.muc_toi_thieu, 20) as muc_toi_thieu,
                    COALESCE(sl.muc_toi_da, 200) as muc_toi_da,
                    COALESCE(sl.diem_dat_hang, 30) as diem_dat_hang,
                    COALESCE(SUM(k.soluong), 0) as ton_hien_tai,
                    CASE
                        WHEN COALESCE(SUM(k.soluong), 0) <= COALESCE(sl.muc_toi_thieu, 20) THEN 'Thấp'
                        WHEN COALESCE(SUM(k.soluong), 0) >= COALESCE(sl.muc_toi_da, 200) THEN 'Cao'
                        ELSE 'Bình thường'
                    END as trang_thai_ton
                  FROM thuoc t
                  JOIN loai_thuoc l ON t.loai_id = l.id
                  LEFT JOIN khohang k ON t.id = k.thuoc_id AND k.soluong > 0
                  LEFT JOIN stock_levels sl ON t.id = sl.thuoc_id
                  GROUP BY t.id, t.ten_thuoc, l.ten_loai, sl.muc_toi_thieu, sl.muc_toi_da, sl.diem_dat_hang
                  ORDER BY ton_hien_tai ASC";

        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cập nhật mức dự trữ
    public function updateStockLevel($thuoc_id, $muc_toi_thieu, $muc_toi_da, $diem_dat_hang) {
        // Kiểm tra xem đã có record chưa
        $check = $this->conn->prepare("SELECT id FROM stock_levels WHERE thuoc_id = ?");
        $check->execute([$thuoc_id]);

        if ($check->rowCount() > 0) {
            $query = "UPDATE stock_levels
                      SET muc_toi_thieu = ?, muc_toi_da = ?, diem_dat_hang = ?, ngay_capnhat = NOW()
                      WHERE thuoc_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$muc_toi_thieu, $muc_toi_da, $diem_dat_hang, $thuoc_id]);
        } else {
            $query = "INSERT INTO stock_levels (thuoc_id, muc_toi_thieu, muc_toi_da, diem_dat_hang)
                      VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$thuoc_id, $muc_toi_thieu, $muc_toi_da, $diem_dat_hang]);
        }
    }

    // Phân tích ABC (phân loại thuốc theo giá trị)
    public function getABCAnalysis($days = 90) {
        $query = "SELECT
                    t.id as thuoc_id,
                    t.ten_thuoc,
                    l.ten_loai,
                    COALESCE(SUM(ctdh.soluong * ctdh.dongia), 0) as doanh_thu,
                    COALESCE(SUM(ctdh.soluong), 0) as so_luong_ban
                  FROM thuoc t
                  JOIN loai_thuoc l ON t.loai_id = l.id
                  LEFT JOIN chitiet_donhang ctdh ON t.id = ctdh.thuoc_id
                  LEFT JOIN donhang dh ON ctdh.donhang_id = dh.id
                      AND dh.trangthai IN ('dagiao', 'dadat', 'danggiao')
                      AND dh.ngay_dat >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                  GROUP BY t.id, t.ten_thuoc, l.ten_loai
                  ORDER BY doanh_thu DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$days]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tính tổng doanh thu
        $totalRevenue = array_sum(array_column($products, 'doanh_thu'));

        // Phân loại ABC
        $cumulative = 0;
        foreach ($products as &$product) {
            $cumulative += $product['doanh_thu'];
            $percent = $totalRevenue > 0 ? ($cumulative / $totalRevenue) * 100 : 0;

            if ($percent <= 70) {
                $product['phan_loai'] = 'A';
                $product['mo_ta'] = 'Quan trọng nhất - 70% doanh thu';
            } elseif ($percent <= 90) {
                $product['phan_loai'] = 'B';
                $product['mo_ta'] = 'Quan trọng - 20% doanh thu';
            } else {
                $product['phan_loai'] = 'C';
                $product['mo_ta'] = 'Ít quan trọng - 10% doanh thu';
            }
            $product['phan_tram_tich_luy'] = round($percent, 2);
        }

        return $products;
    }

    // Báo cáo vòng quay tồn kho
    public function getInventoryTurnover($days = 90) {
        $query = "SELECT
                    t.id as thuoc_id,
                    t.ten_thuoc,
                    l.ten_loai,
                    COALESCE(SUM(k.soluong), 0) as ton_hien_tai,
                    COALESCE(SUM(k.soluong * k.gia), 0) as gia_tri_ton,
                    COALESCE(sold.so_luong_ban, 0) as da_ban,
                    COALESCE(sold.gia_von_ban, 0) as gia_von_ban,
                    CASE
                        WHEN COALESCE(SUM(k.soluong * k.gia), 0) > 0
                        THEN ROUND(COALESCE(sold.gia_von_ban, 0) / COALESCE(SUM(k.soluong * k.gia), 0) * (365 / ?), 2)
                        ELSE 0
                    END as vong_quay_nam,
                    CASE
                        WHEN COALESCE(sold.gia_von_ban, 0) > 0 AND COALESCE(SUM(k.soluong * k.gia), 0) > 0
                        THEN ROUND(COALESCE(SUM(k.soluong * k.gia), 0) / (COALESCE(sold.gia_von_ban, 0) / ?) , 0)
                        ELSE 999
                    END as ngay_ton_kho_tb
                  FROM thuoc t
                  JOIN loai_thuoc l ON t.loai_id = l.id
                  LEFT JOIN khohang k ON t.id = k.thuoc_id AND k.soluong > 0
                  LEFT JOIN (
                      SELECT
                          ctdh.thuoc_id,
                          SUM(ctdh.soluong) as so_luong_ban,
                          SUM(ctdh.soluong * COALESCE(k2.gia, ctdh.dongia)) as gia_von_ban
                      FROM chitiet_donhang ctdh
                      JOIN donhang dh ON ctdh.donhang_id = dh.id
                      LEFT JOIN khohang k2 ON ctdh.thuoc_id = k2.thuoc_id
                      WHERE dh.trangthai IN ('dagiao', 'dadat', 'danggiao')
                      AND dh.ngay_dat >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                      GROUP BY ctdh.thuoc_id
                  ) sold ON t.id = sold.thuoc_id
                  GROUP BY t.id, t.ten_thuoc, l.ten_loai, sold.so_luong_ban, sold.gia_von_ban
                  HAVING ton_hien_tai > 0 OR da_ban > 0
                  ORDER BY vong_quay_nam DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$days, $days, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Thống kê tổng hợp cho dashboard
    public function getDashboardStats() {
        $stats = [];

        // Tổng giá trị tồn kho
        $query = "SELECT SUM(soluong * gia) as tong_gia_tri FROM khohang WHERE soluong > 0";
        $stmt = $this->conn->query($query);
        $stats['tong_gia_tri_ton'] = $stmt->fetch(PDO::FETCH_ASSOC)['tong_gia_tri'] ?? 0;

        // Số thuốc tồn thấp
        $query = "SELECT COUNT(DISTINCT thuoc_id) as so_thuoc
                  FROM (
                      SELECT thuoc_id, SUM(soluong) as tong
                      FROM khohang
                      GROUP BY thuoc_id
                      HAVING tong < 20
                  ) sub";
        $stmt = $this->conn->query($query);
        $stats['thuoc_ton_thap'] = $stmt->fetch(PDO::FETCH_ASSOC)['so_thuoc'] ?? 0;

        // Số thuốc sắp hết hạn (30 ngày)
        $query = "SELECT COUNT(*) as so_lo
                  FROM khohang
                  WHERE soluong > 0
                  AND DATEDIFF(hansudung, CURDATE()) <= 30";
        $stmt = $this->conn->query($query);
        $stats['thuoc_sap_het_han'] = $stmt->fetch(PDO::FETCH_ASSOC)['so_lo'] ?? 0;

        // Số thuốc đã hết hạn
        $query = "SELECT COUNT(*) as so_lo, SUM(soluong * gia) as gia_tri
                  FROM khohang
                  WHERE soluong > 0
                  AND hansudung < CURDATE()";
        $stmt = $this->conn->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['thuoc_het_han'] = $result['so_lo'] ?? 0;
        $stats['gia_tri_het_han'] = $result['gia_tri'] ?? 0;

        return $stats;
    }
}
