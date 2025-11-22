<?php
require_once __DIR__ . '/../config/database.php';

class Inventory {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // ✅ 1. Lấy toàn bộ dữ liệu kho (gồm tên thuốc, đơn vị, NCC)
    public function getAllInventory() {
        $query = "SELECT k.*, t.ten_thuoc, dv.ten_donvi, ncc.ten_ncc
                  FROM khohang k
                  JOIN thuoc t ON k.thuoc_id = t.id
                  JOIN donvi dv ON k.donvi_id = dv.id
                  JOIN nhacungcap ncc ON k.nhacungcap_id = ncc.id
                  ORDER BY k.capnhat DESC";
    
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ 2. Danh sách thuốc (dùng cho form)
    public function getThuocList() {
        $stmt = $this->conn->query("SELECT id, ten_thuoc FROM thuoc ORDER BY ten_thuoc ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ 3. Danh sách đơn vị tính
    public function getDonViList() {
        $stmt = $this->conn->query("SELECT id, ten_donvi FROM donvi ORDER BY ten_donvi ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ 4. Danh sách nhà cung cấp
    public function getNhaCungCapList() {
        $stmt = $this->conn->query("SELECT id, ten_ncc FROM nhacungcap ORDER BY ten_ncc ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ 5. Thêm dữ liệu nhập kho mới
    public function insertInventory($thuoc_id, $donvi_id, $nhacungcap_id, $gia, $soluong, $hansudung, $capnhat) {
        $query = "INSERT INTO khohang (thuoc_id, donvi_id, nhacungcap_id, gia, soluong, hansudung, capnhat)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $thuoc_id,
            $donvi_id,
            $nhacungcap_id,
            $gia,
            $soluong,
            $hansudung,
            $capnhat
        ]);
    }

    // ✅ 6. Cập nhật hoặc thêm mới giá bán trong bảng thuoc_donvi
    public function updateOrInsertGiaBan($thuoc_id, $donvi_id, $gia_ban) {
        $check = $this->conn->prepare("SELECT id FROM thuoc_donvi WHERE thuoc_id = ? AND donvi_id = ?");
        $check->execute([$thuoc_id, $donvi_id]);

        if ($check->rowCount() > 0) {
            // Đã tồn tại => cập nhật
            $update = $this->conn->prepare("UPDATE thuoc_donvi SET gia = ? WHERE thuoc_id = ? AND donvi_id = ?");
            $update->execute([$gia_ban, $thuoc_id, $donvi_id]);
        } else {
            // Chưa có => thêm mới
            $insert = $this->conn->prepare("INSERT INTO thuoc_donvi (thuoc_id, donvi_id, gia) VALUES (?, ?, ?)");
            $insert->execute([$thuoc_id, $donvi_id, $gia_ban]);
        }
    }

    // ✅ 7. Lấy danh sách thuốc gần hết (số lượng dưới ngưỡng)
    public function getLowStockItems($threshold = 20) {
        $query = "SELECT
                    k.thuoc_id,
                    t.ten_thuoc,
                    dv.ten_donvi,
                    SUM(k.soluong) as tong_soluong,
                    MIN(k.hansudung) as hansudung_ganhat
                  FROM khohang k
                  JOIN thuoc t ON k.thuoc_id = t.id
                  JOIN donvi dv ON k.donvi_id = dv.id
                  GROUP BY k.thuoc_id, k.donvi_id, t.ten_thuoc, dv.ten_donvi
                  HAVING SUM(k.soluong) < ?
                  ORDER BY tong_soluong ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$threshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ 8. Lấy danh sách thuốc gần hết hạn hoặc đã hết hạn
    public function getExpiringItems($daysThreshold = 30) {
        $query = "SELECT
                    k.id,
                    k.thuoc_id,
                    k.donvi_id,
                    t.ten_thuoc,
                    dv.ten_donvi,
                    k.soluong,
                    k.hansudung,
                    ncc.ten_ncc,
                    DATEDIFF(k.hansudung, CURDATE()) as ngay_conlai
                  FROM khohang k
                  JOIN thuoc t ON k.thuoc_id = t.id
                  JOIN donvi dv ON k.donvi_id = dv.id
                  JOIN nhacungcap ncc ON k.nhacungcap_id = ncc.id
                  WHERE k.soluong > 0
                  AND DATEDIFF(k.hansudung, CURDATE()) <= ?
                  ORDER BY k.hansudung ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$daysThreshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
