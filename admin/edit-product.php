<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = $_POST['thuoc_id'];
    $tenThuoc   = $_POST['ten_thuoc'];
    $loaiId     = $_POST['loai_id'];
    $mota       = $_POST['mota'];
    $hinhanhCu  = $_POST['hinhanh_cu'];  // ảnh cũ

    $hinhanhMoi = $hinhanhCu; // mặc định là ảnh cũ

    // ✅ Nếu người dùng chọn ảnh mới
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/images/product-images/';
        $fileName = basename($_FILES['hinhanh']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['hinhanh']['tmp_name'], $targetPath)) {
            $hinhanhMoi = $fileName;
        }
    }

    try {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("UPDATE thuoc SET ten_thuoc = ?, loai_id = ?, mota = ?, hinhanh = ? WHERE id = ?");
        $stmt->execute([$tenThuoc, $loaiId, $mota, $hinhanhMoi, $id]);

        header("Location: manage-products.php?success=Cập nhật thuốc thành công");
        exit;
    } catch (PDOException $e) {
        die("Lỗi cập nhật: " . $e->getMessage());
    }
} else {
    header("Location: manage-products.php");
    exit;
}
