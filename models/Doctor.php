<?php
require_once __DIR__ . '/../config/database.php';

class Doctor {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Lấy danh sách bác sĩ nổi bật
    public function getFeaturedDoctors($limit = 3) {
        $stmt = $this->conn->prepare("SELECT * FROM doctors WHERE is_featured = 1 ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tất cả bác sĩ
    public function getAllDoctors() {
        $stmt = $this->conn->query("SELECT * FROM doctors ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy chi tiết bác sĩ theo ID
    public function getDoctorById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM doctors WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Thêm bác sĩ mới
    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO doctors (name, specialization, qualification, experience, image, description, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        try {
            $result = $stmt->execute([
                $data['name'], 
                $data['specialization'], 
                $data['qualification'], 
                $data['experience'], 
                $data['image'] ?? '', 
                $data['description'] ?? '', 
                $data['is_featured'] ?? 0
            ]);

            return [
                'success' => $result,
                'id' => $this->conn->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi thêm bác sĩ: ' . $e->getMessage()
            ];
        }
    }

    // Cập nhật thông tin bác sĩ
    public function update($id, $data) {
        $stmt = $this->conn->prepare("UPDATE doctors SET name = ?, specialization = ?, qualification = ?, experience = ?, image = ?, description = ?, is_featured = ? WHERE id = ?");
        
        try {
            $result = $stmt->execute([
                $data['name'], 
                $data['specialization'], 
                $data['qualification'], 
                $data['experience'], 
                $data['image'] ?? '', 
                $data['description'] ?? '', 
                $data['is_featured'] ?? 0,
                $id
            ]);

            return [
                'success' => $result
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi cập nhật bác sĩ: ' . $e->getMessage()
            ];
        }
    }

    // Xóa bác sĩ
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM doctors WHERE id = ?");
        
        try {
            $result = $stmt->execute([$id]);
            return [
                'success' => $result
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi xóa bác sĩ: ' . $e->getMessage()
            ];
        }
    }

    // Tìm kiếm bác sĩ
    public function search($keyword) {
        $stmt = $this->conn->prepare("SELECT * FROM doctors WHERE name LIKE ? OR specialization LIKE ?");
        $stmt->execute(["%$keyword%", "%$keyword%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}