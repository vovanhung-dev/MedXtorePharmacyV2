<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = $_POST['blog_id'];
    $tieude     = trim($_POST['tieude']);
    $tomtat     = trim($_POST['tomtat']);
    $noidung    = trim($_POST['noidung']);
    $hinhanhCu  = $_POST['hinhanh_cu']; // Hình ảnh cũ

    $hinhanhMoi = $hinhanhCu; // Mặc định giữ nguyên hình ảnh cũ

    // ✅ Kiểm tra dữ liệu đầu vào
    if (empty($tieude) || empty($tomtat) || empty($noidung)) {
        header("Location: manage-blogs.php?error=Vui lòng điền đầy đủ thông tin bài viết.");
        exit;
    }

    // ✅ Nếu người dùng chọn hình ảnh mới
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/images/blog/';
        $fileName = time() . '_' . basename($_FILES['hinhanh']['name']);
        $targetPath = $uploadDir . $fileName;

        // Kiểm tra loại file
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        if (!in_array($fileType, $allowedTypes)) {
            header("Location: manage-blogs.php?error=Chỉ cho phép các định dạng hình ảnh: jpg, jpeg, png, gif.");
            exit;
        }

        // Di chuyển file
        if (move_uploaded_file($_FILES['hinhanh']['tmp_name'], $targetPath)) {
            $hinhanhMoi = $fileName;

            // ✅ Xóa hình ảnh cũ nếu có
            if (!empty($hinhanhCu) && file_exists($uploadDir . $hinhanhCu)) {
                unlink($uploadDir . $hinhanhCu);
            }
        } else {
            header("Location: manage-blogs.php?error=Lỗi khi tải lên hình ảnh.");
            exit;
        }
    }

    try {
        $db = new Database();
        $conn = $db->getConnection();

        // ✅ Cập nhật bài viết
        $stmt = $conn->prepare("UPDATE baiviet SET tieude = ?, tomtat = ?, noidung = ?, hinhanh = ?, loai_id = ?, ngay_capnhat = NOW() WHERE id = ?");
        $stmt->execute([$tieude, $tomtat, $noidung, $hinhanhMoi, $loaiId, $id]);
        header("Location: manage-blogs.php?success=Cập nhật bài viết thành công");
        exit;
    } catch (PDOException $e) {
        header("Location: manage-blogs.php?error=Lỗi khi cập nhật bài viết: " . $e->getMessage());
        exit;
    }
} else {
    header("Location: manage-blogs.php");
    exit;
}