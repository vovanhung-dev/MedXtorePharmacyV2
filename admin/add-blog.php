<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tieude    = $_POST['tieude'];
    $tomtat    = $_POST['tomtat'];
    $noidung   = $_POST['noidung'];
    $loaiId    = $_POST['loai_id']; // Lấy thể loại từ form
    $ngayTao   = date('Y-m-d H:i:s');

    // ✅ Lấy ID người dùng từ session
    if (!isset($_SESSION['user_id'])) {
        die("Lỗi: Người dùng chưa đăng nhập.");
    }
    $nguoidung_id = $_SESSION['user_id'];

    // ✅ Xử lý hình ảnh
    $hinhanhName = '';
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../assets/images/blog/';
        $fileName = time() . '_' . basename($_FILES['hinhanh']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['hinhanh']['tmp_name'], $targetPath)) {
            $hinhanhName = $fileName;
        }
    }

    try {
        $db = new Database();
        $conn = $db->getConnection();

        // ✅ Thêm bài viết vào cơ sở dữ liệu
        $stmt = $conn->prepare("INSERT INTO baiviet (tieude, tomtat, noidung, hinhanh, ngay_dang, nguoidung_id, loai_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tieude, $tomtat, $noidung, $hinhanhName, $ngayTao, $nguoidung_id, $loaiId]);

        header("Location: manage-blogs.php?success=" . urlencode("Thêm bài viết thành công!"));
        exit;
    } catch (PDOException $e) {
        die("Lỗi khi thêm bài viết: " . $e->getMessage());
    }
} else {
    header("Location: manage-blogs.php");
    exit;
}