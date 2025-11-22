<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $thuoc_id = $_POST['thuoc_id'] ?? null;

    if ($thuoc_id) {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Xoá liên kết phụ thuộc trước (vd: khohang, thuoc_donvi)
            $conn->prepare("DELETE FROM khohang WHERE thuoc_id = ?")->execute([$thuoc_id]);
            $conn->prepare("DELETE FROM thuoc_donvi WHERE thuoc_id = ?")->execute([$thuoc_id]);

            // Xoá thuốc
            $stmt = $conn->prepare("DELETE FROM thuoc WHERE id = ?");
            $stmt->execute([$thuoc_id]);

            header("Location: manage-products.php?success=Đã xóa thuốc thành công!");
            exit;
        } catch (PDOException $e) {
            die("Lỗi khi xoá thuốc: " . $e->getMessage());
        }
    }
}

// Nếu không đúng phương thức POST, quay lại
header("Location: manage-products.php");
exit;
