<?php
// Hàm kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hàm kiểm tra quyền admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1;
}

// Hàm yêu cầu đăng nhập, nếu chưa đăng nhập sẽ chuyển hướng
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Hàm yêu cầu quyền admin, nếu không phải admin sẽ chuyển hướng
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

// Hàm lấy thông tin người dùng hiện tại
function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM nguoidung WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Hàm đăng xuất
function logout() {
    // Xóa tất cả biến session
    $_SESSION = array();
    
    // Xóa cookie session nếu có
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Hủy session
    session_destroy();
}
?>