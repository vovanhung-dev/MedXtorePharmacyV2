<?php
require_once __DIR__ . '/../config/database.php';

class Category {
    private $conn;
    private $table = 'loai_thuoc';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Lấy tất cả loại thuốc
    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy loại thuốc theo ID
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Thêm loại thuốc mới
    public function create($ten_loai) {
        // Kiểm tra loại thuốc đã tồn tại chưa
        if ($this->checkExists($ten_loai)) {
            return [
                'success' => false,
                'message' => 'Loại thuốc này đã tồn tại'
            ];
        }

        $query = "INSERT INTO {$this->table} (ten_loai) VALUES (?)";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([$ten_loai])) {
            return [
                'success' => true,
                'id' => $this->conn->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể thêm loại thuốc'
            ];
        }
    }

    // Cập nhật loại thuốc
    public function update($id, $ten_loai) {
        // Kiểm tra loại thuốc đã tồn tại chưa (ngoại trừ chính nó)
        if ($this->checkExistsExcept($ten_loai, $id)) {
            return [
                'success' => false,
                'message' => 'Loại thuốc này đã tồn tại'
            ];
        }

        $query = "UPDATE {$this->table} SET ten_loai = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([$ten_loai, $id])) {
            return [
                'success' => true
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể cập nhật loại thuốc'
            ];
        }
    }

    // Xóa loại thuốc
    public function delete($id) {
        // Kiểm tra xem loại thuốc này có đang được sử dụng không
        if ($this->isInUse($id)) {
            return [
                'success' => false,
                'message' => 'Không thể xóa loại thuốc này vì đang được sử dụng'
            ];
        }

        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([$id])) {
            return [
                'success' => true
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể xóa loại thuốc'
            ];
        }
    }

    // Kiểm tra loại thuốc đã tồn tại chưa
    private function checkExists($ten_loai) {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE ten_loai = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$ten_loai]);
        return $stmt->fetchColumn() > 0;
    }

    // Kiểm tra loại thuốc đã tồn tại chưa (ngoại trừ chính nó)
    private function checkExistsExcept($ten_loai, $id) {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE ten_loai = ? AND id != ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$ten_loai, $id]);
        return $stmt->fetchColumn() > 0;
    }

    // Kiểm tra xem loại thuốc này có đang được sử dụng không
    private function isInUse($id) {
        $query = "SELECT COUNT(*) FROM thuoc WHERE loai_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }
}
