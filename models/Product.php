<?php
require_once __DIR__ . '/../config/database.php';

class Product {
    private $conn;
    private $table = 'thuoc';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Lấy tất cả sản phẩm + loại
    public function getAll() {
        $query = "SELECT t.*, l.ten_loai 
                  FROM {$this->table} t
                  LEFT JOIN loai_thuoc l ON t.loai_id = l.id
                  ORDER BY t.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy chi tiết thuốc theo ID
    public function getById($id) {
        $query = "SELECT t.*, l.ten_loai 
                  FROM {$this->table} t
                  LEFT JOIN loai_thuoc l ON t.loai_id = l.id
                  WHERE t.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy đơn vị tính + giá của thuốc
    public function getDonViByThuoc($thuoc_id) {
        $query = "SELECT dv.id AS donvi_id, dv.ten_donvi, td.gia
                  FROM thuoc_donvi td
                  JOIN donvi dv ON td.donvi_id = dv.id
                  WHERE td.thuoc_id = ?
                  ORDER BY dv.id ASC";
    
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$thuoc_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lọc thuốc theo tên, loại, hạn sử dụng
    public function filterProducts($search = '', $loai = '', $hsd = '') {
        $query = "SELECT 
                    t.id, 
                    t.ten_thuoc, 
                    t.mota,                       -- ✅ Thêm dòng này
                    t.hinhanh, 
                    l.ten_loai, 
                    dv.ten_donvi, 
                    td.gia, 
                    COALESCE(SUM(k.soluong), 0) AS soluong,
                    MAX(k.hansudung) AS hansudung
                  FROM thuoc t
                  LEFT JOIN loai_thuoc l ON t.loai_id = l.id
                  JOIN thuoc_donvi td ON t.id = td.thuoc_id
                  JOIN donvi dv ON td.donvi_id = dv.id
                  LEFT JOIN khohang k 
                    ON t.id = k.thuoc_id AND td.donvi_id = k.donvi_id";
    
        $params = [];
        $conditions = [];
    
        if (!empty($search)) {
            $conditions[] = "t.ten_thuoc LIKE ?";
            $params[] = "%$search%";
        }
    
        if (!empty($loai)) {
            $conditions[] = "l.ten_loai = ?";
            $params[] = $loai;
        }
    
        if (!empty($hsd)) {
            $conditions[] = "k.hansudung <= ?";
            $params[] = $hsd;
        }
    
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
    
        $query .= " GROUP BY t.id, t.ten_thuoc, t.mota, t.hinhanh, l.ten_loai, dv.ten_donvi, td.gia, td.donvi_id ORDER BY t.id DESC";
    
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestProducts($limit = 6) {
        $query = "
            SELECT 
                t.*, 
                k.gia, 
                k.soluong, 
                d.ten_donvi
            FROM thuoc t
            INNER JOIN khohang k ON t.id = k.thuoc_id
            INNER JOIN donvi d ON k.donvi_id = d.id
            WHERE k.soluong > 0 AND k.gia > 0
            ORDER BY t.ngay_tao DESC
            LIMIT ?
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }  

    // Lấy sản phẩm phân trang có thể tìm kiếm và lọc
public function getPagedProducts($limit = 15, $offset = 0, $search = '', $loai_id = null) {
    $sql = "SELECT t.*, l.ten_loai 
            FROM {$this->table} t
            LEFT JOIN loai_thuoc l ON t.loai_id = l.id
            WHERE 1 ";

    $params = [];

    if (!empty($search)) {
        $sql .= " AND t.ten_thuoc LIKE ? ";
        $params[] = "%$search%";
    }

    if (!empty($loai_id)) {
        $sql .= " AND t.loai_id = ? ";
        $params[] = $loai_id;
    }

    $sql .= " ORDER BY t.id DESC LIMIT ? OFFSET ?";
    $params[] = (int)$limit;
    $params[] = (int)$offset;

    $stmt = $this->conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Đếm tổng số sản phẩm phục vụ phân trang
public function countFilteredProducts($search = '', $loai_id = null) {
    $sql = "SELECT COUNT(*) FROM {$this->table} WHERE 1 ";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND ten_thuoc LIKE ? ";
        $params[] = "%$search%";
    }

    if (!empty($loai_id)) {
        $sql .= " AND loai_id = ? ";
        $params[] = $loai_id;
    }

    $stmt = $this->conn->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

public function getAllPaginated($search = '', $loai_id = '', $limit = 15, $offset = 0) {
    $query = "SELECT t.*, l.ten_loai 
              FROM thuoc t 
              LEFT JOIN loai_thuoc l ON t.loai_id = l.id
              WHERE EXISTS (
                  SELECT 1 FROM khohang k 
                  WHERE k.thuoc_id = t.id 
                    AND k.gia > 0 
                    AND k.soluong > 0
              )";
    $params = [];

    if (!empty($search)) {
        $query .= " AND t.ten_thuoc LIKE ?";
        $params[] = "%$search%";
    }

    if (!empty($loai_id)) {
        $query .= " AND t.loai_id = ?";
        $params[] = $loai_id;
    }

    $query .= " ORDER BY t.id DESC LIMIT ? OFFSET ?";

    $stmt = $this->conn->prepare($query);

    $i = 1;
    foreach ($params as $param) {
        $stmt->bindValue($i++, $param);
    }
    $stmt->bindValue($i++, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue($i, (int)$offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function searchSuggestions($keyword) {
    $query = "SELECT DISTINCT t.ten_thuoc
              FROM thuoc t
              JOIN khohang k ON t.id = k.thuoc_id
              WHERE t.ten_thuoc LIKE ?
                AND k.gia > 0
                AND k.soluong > 0
              LIMIT 10";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute(["%" . $keyword . "%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getRelatedProducts($productId, $loaiId, $limit = 4) {
    $sql = "SELECT DISTINCT t.*, l.ten_loai
            FROM thuoc t
            JOIN loai_thuoc l ON t.loai_id = l.id
            JOIN khohang k ON t.id = k.thuoc_id
            WHERE t.loai_id = :loai_id
              AND t.id != :product_id
              AND k.soluong > 0
            GROUP BY t.id
            ORDER BY RAND()
            LIMIT :limit";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':loai_id', $loaiId, PDO::PARAM_INT);
    $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Lấy top sản phẩm tồn kho nhiều nhất
public function getTopExistProducts($limit = 5) {
    $sql = "
        SELECT t.*, l.ten_loai, SUM(k.soluong) AS tong_soluong
        FROM thuoc t
        JOIN loai_thuoc l ON t.loai_id = l.id
        JOIN khohang k ON k.thuoc_id = t.id
        GROUP BY t.id
        ORDER BY tong_soluong DESC
        LIMIT :limit
    ";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
 
}
