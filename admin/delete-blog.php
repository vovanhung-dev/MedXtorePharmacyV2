<?php
session_start();
require_once('../controllers/BlogController.php');

// Kiểm tra quyền admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: manage-blogs.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blogCtrl = new BlogController();
    $id = $_POST['blog_id'] ?? null;

    if ($id) {
        $result = $blogCtrl->deletePost($id);

        if ($result['success']) {
            header('Location: manage-blogs.php?success=Xóa bài viết thành công');
        } else {
            header('Location: manage-blogs.php?error=' . urlencode($result['message']));
        }
    }
    exit;
}