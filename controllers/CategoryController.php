<?php
require_once __DIR__ . '/../models/Category.php';

class CategoryController {
    private $model;

    public function __construct() {
        $this->model = new Category();
    }

    // Lấy danh sách tất cả loại thuốc
    public function index() {
        return $this->model->getAll();
    }

    // Lấy chi tiết 1 loại thuốc theo ID
    public function getById($id) {
        return $this->model->getById($id);
    }

    // Thêm loại thuốc mới
    public function create($data) {
        $ten_loai = trim($data['ten_loai']);
        
        // Validate dữ liệu
        if (empty($ten_loai)) {
            return [
                'success' => false,
                'message' => 'Tên loại thuốc không được để trống'
            ];
        }
        
        return $this->model->create($ten_loai);
    }

    // Cập nhật loại thuốc
    public function update($id, $data) {
        $ten_loai = trim($data['ten_loai']);
        
        // Validate dữ liệu
        if (empty($ten_loai)) {
            return [
                'success' => false,
                'message' => 'Tên loại thuốc không được để trống'
            ];
        }
        
        return $this->model->update($id, $ten_loai);
    }

    // Xóa loại thuốc
    public function delete($id) {
        return $this->model->delete($id);
    }

    // Lấy tất cả loại thuốc (alias cho index)
    public function getAllCategories() {
        return $this->model->getAll();
    }
}
