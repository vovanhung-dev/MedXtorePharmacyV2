<?php
require_once __DIR__ . '/../models/Product.php';

class ProductController {
    private $model;

    public function __construct() {
        $this->model = new Product();
    }

    // Lấy danh sách tất cả thuốc
    public function index() {
        return $this->model->getAll();
    }

    // Lấy chi tiết 1 thuốc theo ID
    public function getById($id) {
        return $this->model->getById($id);
    }

    // Lấy danh sách đơn vị + giá theo thuốc
    public function getDonViTheoThuoc($thuoc_id) {
        return $this->model->getDonViByThuoc($thuoc_id);
    }

    // Lọc thuốc theo tên, loại, hạn sử dụng
    public function filter($search = '', $loai = '', $hsd = '') {
        return $this->model->filterProducts($search, $loai, $hsd);
    }

    // Lấy sản phẩm có phân trang
    public function getAllWithPagination($search = '', $loai_id = '', $limit = 15, $offset = 0) {
        return $this->model->getAllPaginated($search, $loai_id, $limit, $offset);
    }

    // Đếm số lượng kết quả lọc
    public function countFiltered($search = '', $loai_id = '') {
        return $this->model->countFilteredProducts($search, $loai_id);
    }

    // Sản phẩm mới nhất
    public function getLatestProducts($limit = 6) {
        return $this->model->getLatestProducts($limit);
    }

    public function getRelated($productId, $loaiId, $limit = 4) {
        $productModel = new Product();
        return $productModel->getRelatedProducts($productId, $loaiId, $limit);
    }
    
    public function getTopExistProducts($limit = 5) {
        return $this->model->getTopExistProducts($limit);
    }

}
