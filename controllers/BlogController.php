<?php
require_once __DIR__ . '/../models/Blog.php';
$blogCtrl = new BlogController();
class BlogController {
    private $model;

    public function __construct() {
        $this->model = new Blog();
    }

    // Phương thức lấy tất cả bài viết
    public function getAllPosts() {
        return $this->model->getAll();
    }

    // Lấy bài viết theo slug
    public function getPost($slug) {
        return $this->model->getBySlug($slug);
    }

    public function getPostBySlug($slug) {
        return $this->model->getBySlug($slug);
    }

    public function getLatestPosts($limit = 3) {
        return $this->model->getLatest($limit);
    }
    public function filter($search = '', $category = '') {
        return $this->model->filter($search, $category);
    }
    public function filterWithPagination($search = '', $category = '', $limit = 6, $offset = 0) {
        return $this->model->filterWithPagination($search, $category, $limit, $offset);
    }
    
    public function countPosts($search = '', $category = '') {
        return $this->model->countPosts($search, $category);
    }
    // Các phương thức quản lý blog dành cho admin
    
    // Tạo bài viết mới
    public function createPost($data) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin()) {
            return [
                'success' => false,
                'message' => 'Không có quyền thực hiện hành động này'
            ];
        }

        // Validate dữ liệu
        $errors = $this->validatePostData($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Tạo slug từ tiêu đề
        $slug = $this->createSlug($data['tieude']);
        
        // Xử lý upload hình ảnh nếu có
        $hinhanh = '';
        if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
            $hinhanh = $this->uploadImage($_FILES['hinhanh']);
            if (!$hinhanh) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tải lên hình ảnh'
                ];
            }
        }

        // Thêm bài viết
        $user_id = $_SESSION['user_id'];
        $result = $this->model->create([
            'tieude' => $data['tieude'],
            'noidung' => $data['noidung'],
            'mota_ngan' => $data['mota_ngan'],
            'hinhanh' => $hinhanh,
            'slug' => $slug,
            'nguoidung_id' => $user_id
        ]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Tạo bài viết thành công',
                'post_id' => $result
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo bài viết'
            ];
        }
    }

    // Cập nhật bài viết
    public function updatePost($id, $data) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin()) {
            return [
                'success' => false,
                'message' => 'Không có quyền thực hiện hành động này'
            ];
        }

        // Kiểm tra bài viết tồn tại
        $post = $this->model->getById($id);
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Bài viết không tồn tại'
            ];
        }

        // Validate dữ liệu
        $errors = $this->validatePostData($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Cập nhật slug nếu tiêu đề thay đổi
        if ($data['tieude'] !== $post['tieude']) {
            $data['slug'] = $this->createSlug($data['tieude']);
        } else {
            $data['slug'] = $post['slug'];
        }

        // Xử lý upload hình ảnh mới nếu có
        if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
            $hinhanh = $this->uploadImage($_FILES['hinhanh']);
            if (!$hinhanh) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tải lên hình ảnh'
                ];
            }
            
            // Xóa hình ảnh cũ nếu có
            if (!empty($post['hinhanh'])) {
                $this->deleteImage($post['hinhanh']);
            }
            
            $data['hinhanh'] = $hinhanh;
        } else {
            // Giữ nguyên hình ảnh cũ
            $data['hinhanh'] = $post['hinhanh'];
        }

        // Cập nhật bài viết
        $result = $this->model->update($id, $data);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Cập nhật bài viết thành công'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật bài viết'
            ];
        }
    }

    // Xóa bài viết
    public function deletePost($id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin()) {
            return [
                'success' => false,
                'message' => 'Không có quyền thực hiện hành động này'
            ];
        }

        // Kiểm tra bài viết tồn tại
        $post = $this->model->getById($id);
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Bài viết không tồn tại'
            ];
        }

        // Xóa hình ảnh cũ nếu có
        if (!empty($post['hinhanh'])) {
            $this->deleteImage($post['hinhanh']);
        }

        // Xóa bài viết
        $result = $this->model->delete($id);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Xóa bài viết thành công'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa bài viết'
            ];
        }
    }
    public function getRelatedPosts($categoryId, $currentPostId, $limit = 3) {
        $query = "SELECT b.*, l.ten_loai 
                  FROM baiviet b 
                  LEFT JOIN loai_baiviet l ON b.loai_id = l.id 
                  WHERE b.loai_id = :categoryId AND b.id != :currentPostId 
                  ORDER BY b.ngay_dang DESC 
                  LIMIT :limit";
        $stmt = $this->model->getConnection()->prepare($query);
        $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':currentPostId', $currentPostId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCategories() {
        $query = "SELECT * FROM loai_baiviet ORDER BY ten_loai ASC";
        $stmt = $this->model->getConnection()->prepare($query); // Sử dụng phương thức getConnection()
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }  
    
    public function getPostsByCategory($categoryId = '') {
        if (empty($categoryId)) {
            return $this->model->getAll();
        }
    
        $query = "SELECT b.*, l.ten_loai 
                  FROM baiviet b 
                  LEFT JOIN loai_baiviet l ON b.loai_id = l.id 
                  WHERE b.loai_id = :categoryId 
                  ORDER BY b.ngay_dang DESC";
        $stmt = $this->model->getConnection()->prepare($query); // Sử dụng phương thức getConnection()
        $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Kiểm tra quyền admin
    private function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1;
    }

    // Validate dữ liệu bài viết
    private function validatePostData($data) {
        $errors = [];

        if (empty($data['tieude'])) {
            $errors['tieude'] = 'Vui lòng nhập tiêu đề bài viết';
        }

        if (empty($data['noidung'])) {
            $errors['noidung'] = 'Vui lòng nhập nội dung bài viết';
        }

        if (empty($data['mota_ngan'])) {
            $errors['mota_ngan'] = 'Vui lòng nhập mô tả ngắn';
        }

        return $errors;
    }

    // Tạo slug từ tiêu đề
    private function createSlug($title) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->removeAccents($title))));
        
        // Kiểm tra slug đã tồn tại chưa
        $existingSlug = $this->model->getBySlug($slug);
        if ($existingSlug) {
            // Thêm thời gian vào slug để tránh trùng lặp
            $slug .= '-' . time();
        }
        
        return $slug;
    }

    // Bỏ dấu tiếng Việt
    private function removeAccents($str) {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);
        return $str;
    }

    // Upload hình ảnh
    private function uploadImage($file) {
        $upload_dir = __DIR__ . '/../assets/images/blog/';
        
        // Tạo thư mục nếu chưa tồn tại
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($file['name']);
        $target_file = $upload_dir . $file_name;
        
        // Kiểm tra loại file
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'webp');
        if (!in_array($imageFileType, $allowTypes)) {
            return false;
        }
        
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return 'assets/images/blog/' . $file_name;
        } else {
            return false;
        }
    }

    // Xóa hình ảnh
    private function deleteImage($image_path) {
        $full_path = __DIR__ . '/../' . $image_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }
}