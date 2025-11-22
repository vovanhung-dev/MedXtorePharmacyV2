<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenThuoc   = $_POST['ten_thuoc'];
    $loaiId     = $_POST['loai_id'];
    $mota       = $_POST['mota'];
    $ngayTao    = date('Y-m-d H:i:s');

    // ✅ Xử lý hình ảnh
    $hinhanhName = '';
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../assets/images/product-images/';
        $fileName = basename($_FILES['hinhanh']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['hinhanh']['tmp_name'], $targetPath)) {
            $hinhanhName = $fileName;
        }
    }

    try {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("INSERT INTO thuoc (ten_thuoc, loai_id, mota, hinhanh, ngay_tao) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tenThuoc, $loaiId, $mota, $hinhanhName, $ngayTao]);

        header("Location: manage-products.php?success=" . urlencode("Thêm thuốc thành công! Vui lòng nhập kho để sản phẩm hiển thị."));
        exit;
    } catch (PDOException $e) {
        die("Lỗi khi thêm thuốc: " . $e->getMessage());
    }
} else {
    header("Location: manage-products.php");
    exit;
}
