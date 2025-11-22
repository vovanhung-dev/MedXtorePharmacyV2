<?php
require_once __DIR__ . '/../config/database.php';

class Blog {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }
       // Thêm phương thức getter
       public function getConnection() {
        return $this->conn;
    }


    // Lấy tất cả bài viết
    public function getAll() {
        $stmt = $this->conn->query("SELECT b.*, n.ten as tac_gia 
                                    FROM baiviet b 
                                    LEFT JOIN nguoidung n ON b.nguoidung_id = n.id 
                                    ORDER BY b.ngay_dang DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy chi tiết bài viết theo ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT b.*, n.ten as tac_gia 
                                      FROM baiviet b 
                                      LEFT JOIN nguoidung n ON b.nguoidung_id = n.id 
                                      WHERE b.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy bài viết theo slug
    
    public function getBySlug($slug) {
        $query = "SELECT b.*, l.ten_loai, n.ten as tac_gia 
                  FROM baiviet b 
                  LEFT JOIN loai_baiviet l ON b.loai_id = l.id 
                  LEFT JOIN nguoidung n ON b.nguoidung_id = n.id 
                  WHERE b.slug = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Lấy bài viết mới nhất
    public function getLatest($limit = 3) {
        $stmt = $this->conn->prepare("SELECT b.tieude, b.slug, b.hinhanh, b.tomtat, b.ngay_dang, n.ten as tac_gia 
                                      FROM baiviet b 
                                      LEFT JOIN nguoidung n ON b.nguoidung_id = n.id 
                                      ORDER BY b.ngay_dang DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tạo bài viết mới
    public function create($data) {
        $query = "INSERT INTO baiviet (tieude, noidung, tomtat, hinhanh, slug, nguoidung_id) 
                  VALUES (:tieude, :noidung, :tomtat, :hinhanh, :slug, :nguoidung_id)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':tieude', $data['tieude']);
        $stmt->bindParam(':noidung', $data['noidung']); 
        $stmt->bindParam(':tomtat', $data['tomtat']);
        $stmt->bindParam(':hinhanh', $data['hinhanh']);
        $stmt->bindParam(':slug', $data['slug']);
        $stmt->bindParam(':nguoidung_id', $data['nguoidung_id']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    // Cập nhật bài viết
    public function update($id, $data) {
        $query = "UPDATE baiviet SET 
                  tieude = :tieude, 
                  noidung = :noidung, 
                  tomtat = :tomtat, 
                  hinhanh = :hinhanh, 
                  slug = :slug,
                  ngay_capnhat = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':tieude', $data['tieude']);
        $stmt->bindParam(':noidung', $data['noidung']);
        $stmt->bindParam(':tomtat', $data['tomtat']);
        $stmt->bindParam(':hinhanh', $data['hinhanh']);
        $stmt->bindParam(':slug', $data['slug']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    // Xóa bài viết
    public function delete($id) {
        $query = "DELETE FROM baiviet WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    public function filter($search = '', $category = '') {
        $query = "SELECT b.*, l.ten_loai 
                  FROM baiviet b 
                  LEFT JOIN loai_baiviet l ON b.loai_id = l.id 
                  WHERE b.tieude LIKE :search";
    
        if (!empty($category)) {
            $query .= " AND b.loai_id = :category";
        }
    
        $query .= " ORDER BY b.ngay_dang DESC";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    
        if (!empty($category)) {
            $stmt->bindValue(':category', $category, PDO::PARAM_INT);
        }
    
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
public function getCategories() {
    $query = "SELECT * FROM loai_baiviet ORDER BY ten_loai ASC";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getPostsByCategory($categoryId) {
    $query = "SELECT b.*, l.ten_loai 
              FROM baiviet b 
              LEFT JOIN loai_baiviet l ON b.loai_id = l.id 
              WHERE b.loai_id = :categoryId 
              ORDER BY b.ngay_dang DESC";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function filterWithPagination($search = '', $category = '', $limit = 6, $offset = 0) {
    $query = "SELECT b.*, l.ten_loai 
              FROM baiviet b 
              LEFT JOIN loai_baiviet l ON b.loai_id = l.id 
              WHERE b.tieude LIKE :search";

    if (!empty($category)) {
        $query .= " AND b.loai_id = :category";
    }

    $query .= " ORDER BY b.ngay_dang DESC LIMIT :limit OFFSET :offset";

    $stmt = $this->conn->prepare($query);
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    if (!empty($category)) {
        $stmt->bindValue(':category', $category, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function countPosts($search = '', $category = '') {
    $query = "SELECT COUNT(*) as total 
              FROM baiviet b 
              LEFT JOIN loai_baiviet l ON b.loai_id = l.id 
              WHERE b.tieude LIKE :search";

    if (!empty($category)) {
        $query .= " AND b.loai_id = :category";
    }

    $stmt = $this->conn->prepare($query);
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);

    if (!empty($category)) {
        $stmt->bindValue(':category', $category, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}
}