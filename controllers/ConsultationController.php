<?php
require_once __DIR__ . '/../models/ConsultationRequest.php';

class ConsultationController {
    private $model;

    public function __construct() {
        $this->model = new ConsultationRequest();
    }

    // Tạo yêu cầu mới
    public function create($data, $files = null) {
        $errors = [];

        // Validate required fields
        if (empty($data['ho_ten'])) {
            $errors[] = 'Họ và tên là bắt buộc';
        }

        if (empty($data['so_dien_thoai'])) {
            $errors[] = 'Số điện thoại là bắt buộc';
        } elseif (!preg_match('/^[0-9]{10,11}$/', $data['so_dien_thoai'])) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Xử lý upload hình ảnh
        $hinh_anh = null;
        if (!empty($files['hinh_anh_toa']['name'])) {
            $upload_result = $this->uploadImage($files['hinh_anh_toa']);
            if ($upload_result['success']) {
                $hinh_anh = $upload_result['filename'];
            } else {
                return ['success' => false, 'errors' => [$upload_result['error']]];
            }
        }

        // Chuẩn bị dữ liệu
        $request_data = [
            'ho_ten' => trim($data['ho_ten']),
            'so_dien_thoai' => trim($data['so_dien_thoai']),
            'ghi_chu' => trim($data['ghi_chu'] ?? ''),
            'hinh_anh_toa' => $hinh_anh,
            'nguoidung_id' => $data['nguoidung_id'] ?? null,
            'thuoc_list' => []
        ];

        // Xử lý danh sách thuốc
        if (!empty($data['thuoc'])) {
            foreach ($data['thuoc'] as $thuoc) {
                if (!empty($thuoc['ten_thuoc']) || !empty($thuoc['trieu_chung'])) {
                    $request_data['thuoc_list'][] = [
                        'ten_thuoc' => trim($thuoc['ten_thuoc'] ?? ''),
                        'trieu_chung' => trim($thuoc['trieu_chung'] ?? ''),
                        'so_luong' => intval($thuoc['so_luong'] ?? 1),
                        'ghi_chu' => trim($thuoc['ghi_chu'] ?? '')
                    ];
                }
            }
        }

        try {
            $id = $this->model->create($request_data);
            return ['success' => true, 'id' => $id, 'message' => 'Gửi yêu cầu tư vấn thành công!'];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['Đã có lỗi xảy ra: ' . $e->getMessage()]];
        }
    }

    // Upload hình ảnh
    private function uploadImage($file) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            return ['success' => false, 'error' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)'];
        }

        if ($file['size'] > $max_size) {
            return ['success' => false, 'error' => 'Kích thước file không được vượt quá 5MB'];
        }

        $upload_dir = __DIR__ . '/../assets/images/consultation/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'toa_' . time() . '_' . uniqid() . '.' . $ext;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'filename' => $filename];
        }

        return ['success' => false, 'error' => 'Không thể upload file'];
    }

    // Lấy tất cả yêu cầu (Admin)
    public function getAll($filters = []) {
        return $this->model->getAll($filters);
    }

    // Lấy yêu cầu theo ID
    public function getById($id) {
        $request = $this->model->getById($id);
        if ($request) {
            $request['chi_tiet'] = $this->model->getDetails($id);
        }
        return $request;
    }

    // Lấy yêu cầu của user
    public function getByUserId($user_id) {
        return $this->model->getByUserId($user_id);
    }

    // Lấy yêu cầu theo số điện thoại
    public function getByPhone($phone) {
        return $this->model->getByPhone($phone);
    }

    // Cập nhật trạng thái
    public function updateStatus($id, $status, $nhan_vien_id = null, $ghi_chu = null) {
        $valid_statuses = ['cho_xu_ly', 'dang_xu_ly', 'da_hoan_thanh', 'da_huy'];
        if (!in_array($status, $valid_statuses)) {
            return ['success' => false, 'error' => 'Trạng thái không hợp lệ'];
        }

        if ($this->model->updateStatus($id, $status, $nhan_vien_id, $ghi_chu)) {
            return ['success' => true, 'message' => 'Cập nhật trạng thái thành công'];
        }

        return ['success' => false, 'error' => 'Không thể cập nhật trạng thái'];
    }

    // Đếm số yêu cầu theo trạng thái
    public function countByStatus($status = null) {
        return $this->model->countByStatus($status);
    }

    // Lấy yêu cầu mới nhất
    public function getLatest($limit = 5) {
        return $this->model->getLatest($limit);
    }

    // Xóa yêu cầu
    public function delete($id) {
        return $this->model->delete($id);
    }

    // Lấy tên trạng thái
    public static function getStatusName($status) {
        $statuses = [
            'cho_xu_ly' => 'Chờ xử lý',
            'dang_xu_ly' => 'Đang xử lý',
            'da_hoan_thanh' => 'Đã hoàn thành',
            'da_huy' => 'Đã hủy'
        ];
        return $statuses[$status] ?? $status;
    }

    // Lấy class CSS cho badge trạng thái
    public static function getStatusBadgeClass($status) {
        $classes = [
            'cho_xu_ly' => 'bg-warning text-dark',
            'dang_xu_ly' => 'bg-info',
            'da_hoan_thanh' => 'bg-success',
            'da_huy' => 'bg-secondary'
        ];
        return $classes[$status] ?? 'bg-secondary';
    }
}
