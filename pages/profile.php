<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

// Kiểm tra đăng nhập
requireLogin();

// Kết nối database
try {
    // Kết nối database
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset("utf8");

    // Khởi tạo các biến thông báo
    $error = $success = $passwordError = $passwordSuccess = '';

    // Lấy thông tin user từ hàm getCurrentUser()
    $user = getCurrentUser($conn);
    
    if (!$user) {
        $error = "Không tìm thấy thông tin người dùng.";
        exit;
    }

    // Xử lý cập nhật thông tin cá nhân
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        if ($_POST['action'] == 'update_profile') {
            $ten = $_POST['ten'];
            $email = $_POST['email'];
            $dienthoai = $_POST['dienthoai'] ?? '';
            $diachi = $_POST['diachi'] ?? '';
            
            // Xử lý upload avatar nếu có
            $avatar = $user['avatar']; // Giữ avatar cũ
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $target_dir = __DIR__ . "/../assets/images/avatars/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $imageFileType = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
                $new_file_name = uniqid() . "." . $imageFileType;
                $target_file = $target_dir . $new_file_name;

                if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                    $avatar = '/MedXtorePharmacy/assets/images/avatars/' . $new_file_name;
                }
            }

            // Cập nhật thông tin
            $stmt = $conn->prepare("UPDATE nguoidung SET ten = ?, email = ?, dienthoai = ?, diachi = ?, avatar = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $ten, $email, $dienthoai, $diachi, $avatar, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = "Cập nhật thông tin thành công!";
                // Refresh user data
                $user = getCurrentUser($conn);
            } else {
                $error = "Lỗi cập nhật thông tin: " . $conn->error;
            }
        } 
        // Xử lý đổi mật khẩu
        elseif ($_POST['action'] == 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (password_verify($current_password, $user['matkhau'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE nguoidung SET matkhau = ? WHERE id = ?");
                    $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $passwordSuccess = "Đổi mật khẩu thành công!";
                    } else {
                        $passwordError = "Lỗi đổi mật khẩu: " . $conn->error;
                    }
                } else {
                    $passwordError = "Mật khẩu mới không khớp!";
                }
            } else {
                $passwordError = "Mật khẩu hiện tại không đúng!";
            }
        }
        
        // Redirect để tránh gửi lại form
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

} catch (Exception $e) {
    $error = "Lỗi: " . $e->getMessage();
}
?>

<div class="container py-5">
    <div class="row">
        <!-- Thêm nút quay lại đầu trang -->
        <div class="col-12 mb-4">
            <a href="/MedXtorePharmacy/pages/home.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Quay lại trang chủ
            </a>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="avatar" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <img src="/MedXtorePharmacy/assets/images/default-avatar.png" alt="avatar" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php endif; ?>
                    <h5 class="my-3"><?php echo htmlspecialchars($user['ten']); ?></h5>
                    <p class="text-muted mb-1"><?php echo $user['vaitro_id'] == 1 ? 'Quản trị viên' : ($user['vaitro_id'] == 2 ? 'Nhân viên' : 'Khách hàng'); ?></p>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-body">
                    <ul class="nav nav-pills flex-column" id="profileTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="profile-tab" data-bs-toggle="pill" href="#profile" role="tab" aria-controls="profile" aria-selected="true">
                                <i class="fas fa-user me-2"></i>Thông tin cá nhân
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="password-tab" data-bs-toggle="pill" href="#password" role="tab" aria-controls="password" aria-selected="false">
                                <i class="fas fa-key me-2"></i>Đổi mật khẩu
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="tab-content" id="profileTabsContent">
                <!-- Tab Thông tin cá nhân -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Cập nhật thông tin cá nhân</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>
                            
                            <form action="" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="mb-3">
                                    <label for="avatar" class="form-label">Ảnh đại diện</label>
                                    <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                                    <small class="form-text text-muted">Chọn ảnh định dạng JPG, PNG, JPEG hoặc GIF (tối đa 2MB)</small>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="ten" class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" id="ten" name="ten" value="<?php echo htmlspecialchars($user['ten']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="dienthoai" class="form-label">Số điện thoại</label>
                                        <input type="text" class="form-control" id="dienthoai" name="dienthoai" value="<?php echo htmlspecialchars($user['dienthoai'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="diachi" class="form-label">Địa chỉ</label>
                                        <textarea class="form-control" id="diachi" name="diachi" rows="3"><?php echo htmlspecialchars($user['diachi'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Đổi mật khẩu -->
                <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-key me-2"></i>Đổi mật khẩu</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($passwordError): ?>
                                <div class="alert alert-danger"><?php echo $passwordError; ?></div>
                            <?php endif; ?>
                            <?php if ($passwordSuccess): ?>
                                <div class="alert alert-success"><?php echo $passwordSuccess; ?></div>
                            <?php endif; ?>
                            
                            <form action="" method="post">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Mật khẩu mới</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <small class="form-text text-muted">Mật khẩu phải có ít nhất 6 ký tự</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script để xử lý tab -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Kiểm tra xem có hash trong URL không để mở tab tương ứng
    let hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`a[href="${hash}"]`);
        if (tab) {
            tab.click();
        }
    }
    
    // Hiển thị ảnh xem trước khi chọn file
    const avatarInput = document.getElementById('avatar');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatarImg = document.querySelector('.card-body img.rounded-circle');
                    avatarImg.src = e.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>