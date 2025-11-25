<?php
require_once __DIR__ . '/../config/database.php';

class ConsultationRequest {
    private $conn;
    private $table = 'yeucau_tuvan';
    private $detail_table = 'chitiet_yeucau_tuvan';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Tạo yêu cầu tư vấn mới
    public function create($data) {
        try {
            $this->conn->beginTransaction();

            // Insert yêu cầu chính
            $query = "INSERT INTO {$this->table} (ho_ten, so_dien_thoai, ghi_chu, hinh_anh_toa, nguoidung_id)
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['ho_ten'],
                $data['so_dien_thoai'],
                $data['ghi_chu'] ?? null,
                $data['hinh_anh_toa'] ?? null,
                $data['nguoidung_id'] ?? null
            ]);

            $yeucau_id = $this->conn->lastInsertId();

            // Insert chi tiết thuốc nếu có
            if (!empty($data['thuoc_list'])) {
                $query_detail = "INSERT INTO {$this->detail_table} (yeucau_id, ten_thuoc, trieu_chung, so_luong, ghi_chu)
                                 VALUES (?, ?, ?, ?, ?)";
                $stmt_detail = $this->conn->prepare($query_detail);

                foreach ($data['thuoc_list'] as $thuoc) {
                    $stmt_detail->execute([
                        $yeucau_id,
                        $thuoc['ten_thuoc'] ?? null,
                        $thuoc['trieu_chung'] ?? null,
                        $thuoc['so_luong'] ?? 1,
                        $thuoc['ghi_chu'] ?? null
                    ]);
                }
            }

            $this->conn->commit();
            return $yeucau_id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // Lấy tất cả yêu cầu (Admin)
    public function getAll($filters = []) {
        $query = "SELECT y.*, n.ten as ten_nguoidung, nv.ten as ten_nhanvien
                  FROM {$this->table} y
                  LEFT JOIN nguoidung n ON y.nguoidung_id = n.id
                  LEFT JOIN nguoidung nv ON y.nhan_vien_xu_ly = nv.id
                  WHERE 1=1";

        $params = [];

        if (!empty($filters['trang_thai'])) {
            $query .= " AND y.trang_thai = ?";
            $params[] = $filters['trang_thai'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (y.ho_ten LIKE ? OR y.so_dien_thoai LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['from_date'])) {
            $query .= " AND DATE(y.ngay_tao) >= ?";
            $params[] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $query .= " AND DATE(y.ngay_tao) <= ?";
            $params[] = $filters['to_date'];
        }

        $query .= " ORDER BY y.ngay_tao DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy yêu cầu theo ID
    public function getById($id) {
        $query = "SELECT y.*, n.ten as ten_nguoidung, nv.ten as ten_nhanvien
                  FROM {$this->table} y
                  LEFT JOIN nguoidung n ON y.nguoidung_id = n.id
                  LEFT JOIN nguoidung nv ON y.nhan_vien_xu_ly = nv.id
                  WHERE y.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy chi tiết thuốc của yêu cầu
    public function getDetails($yeucau_id) {
        $query = "SELECT * FROM {$this->detail_table} WHERE yeucau_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$yeucau_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy yêu cầu theo user ID (cho khách hàng xem lại)
    public function getByUserId($user_id) {
        $query = "SELECT * FROM {$this->table}
                  WHERE nguoidung_id = ?
                  ORDER BY ngay_tao DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy yêu cầu theo số điện thoại (cho khách vãng lai)
    public function getByPhone($phone) {
        $query = "SELECT * FROM {$this->table}
                  WHERE so_dien_thoai = ?
                  ORDER BY ngay_tao DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$phone]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cập nhật trạng thái yêu cầu
    public function updateStatus($id, $status, $nhan_vien_id = null, $ghi_chu = null) {
        $query = "UPDATE {$this->table}
                  SET trang_thai = ?, nhan_vien_xu_ly = ?, ghi_chu_duoc_si = ?
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $nhan_vien_id, $ghi_chu, $id]);
    }

    // Đếm số yêu cầu theo trạng thái
    public function countByStatus($status = null) {
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];

        if ($status) {
            $query .= " WHERE trang_thai = ?";
            $params[] = $status;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Lấy yêu cầu mới nhất (cho dashboard)
    public function getLatest($limit = 5) {
        $query = "SELECT y.*, n.ten as ten_nguoidung
                  FROM {$this->table} y
                  LEFT JOIN nguoidung n ON y.nguoidung_id = n.id
                  ORDER BY y.ngay_tao DESC
                  LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Xóa yêu cầu
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    // Thêm thuốc vào yêu cầu
    public function addDetail($yeucau_id, $data) {
        $query = "INSERT INTO {$this->detail_table} (yeucau_id, ten_thuoc, trieu_chung, so_luong, ghi_chu)
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $yeucau_id,
            $data['ten_thuoc'] ?? null,
            $data['trieu_chung'] ?? null,
            $data['so_luong'] ?? 1,
            $data['ghi_chu'] ?? null
        ]);
    }

    // Xóa chi tiết thuốc
    public function deleteDetail($detail_id) {
        $query = "DELETE FROM {$this->detail_table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$detail_id]);
    }
}
