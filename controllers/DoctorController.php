<?php
require_once __DIR__ . '/../models/Doctor.php';

class DoctorController {
    private $model;

    public function __construct() {
        $this->model = new Doctor();
    }

    // Lấy danh sách bác sĩ nổi bật
    public function getFeaturedDoctors($limit = 3) {
        return $this->model->getFeaturedDoctors($limit);
    }

    // Lấy tất cả bác sĩ
    public function getAllDoctors() {
        return $this->model->getAllDoctors();
    }

    // Lấy chi tiết bác sĩ theo ID
    public function getDoctorById($id) {
        return $this->model->getDoctorById($id);
    }

    // Thêm bác sĩ mới (nếu cần)
    public function addDoctor($data) {
        // Validate dữ liệu
        $errors = $this->validateDoctorData($data);
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Gọi phương thức từ model để thêm bác sĩ
        return $this->model->create($data);
    }

    // Cập nhật thông tin bác sĩ (nếu cần)
    public function updateDoctor($id, $data) {
        // Validate dữ liệu
        $errors = $this->validateDoctorData($data);
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Gọi phương thức từ model để cập nhật bác sĩ
        return $this->model->update($id, $data);
    }

    // Xóa bác sĩ (nếu cần)
    public function deleteDoctor($id) {
        return $this->model->delete($id);
    }

    // Validate dữ liệu bác sĩ
    private function validateDoctorData($data) {
        $errors = [];

        // Kiểm tra tên
        if (empty($data['name'])) {
            $errors['name'] = 'Vui lòng nhập tên bác sĩ.';
        }

        // Kiểm tra chuyên khoa
        if (empty($data['specialization'])) {
            $errors['specialization'] = 'Vui lòng nhập chuyên khoa.';
        }

        // Kiểm tra bằng cấp
        if (empty($data['qualification'])) {
            $errors['qualification'] = 'Vui lòng nhập bằng cấp.';
        }

        // Kiểm tra kinh nghiệm
        if (!isset($data['experience']) || $data['experience'] < 0) {
            $errors['experience'] = 'Kinh nghiệm không hợp lệ.';
        }

        return $errors;
    }

    // Tìm kiếm bác sĩ theo tên hoặc chuyên khoa
    public function searchDoctors($keyword) {
        return $this->model->search($keyword);
    }
}