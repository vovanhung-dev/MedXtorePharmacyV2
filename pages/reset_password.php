<?php
session_start();
include('../includes/config.php');
require_once('../controllers/AuthController.php');


// Nếu người dùng đã đăng nhập, chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

// Khởi tạo AuthController
$auth = new AuthController($conn);

// Kiểm tra token
$token = $_GET['token'] ?? '';
$email = $auth->checkResetToken($token);

if (!$email) {
    $_SESSION['reset_error'] = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';
    header('Location: forgot_password.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matkhau = $_POST['matkhau'] ?? '';
    $xacnhan_matkhau = $_POST['xacnhan_matkhau'] ?? '';

    // Gọi phương thức resetPassword
    $result = $auth->resetPassword($token, $matkhau, $xacnhan_matkhau);
    
    if ($result['success']) {
        // Chuyển hướng đến trang đăng nhập với thông báo thành công
        header('Location: login.php?reset=success');
        exit;
    } else {
        $errors = $result['errors'];
    }
}

// Include header và navbar
include('../includes/header.php');
include('../includes/navbar.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Đặt Lại Mật Khẩu</h2>
                    
                    <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors['db'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $errors['db']; ?>
                    </div>
                    <?php endif; ?>
                    
                    <p class="mb-4">Vui lòng nhập mật khẩu mới cho tài khoản <strong><?php echo htmlspecialchars($email); ?></strong></p>
                    
                    <form method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="mb-3">
                            <label for="matkhau" class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control <?php echo isset($errors['matkhau']) ? 'is-invalid' : ''; ?>" 
                                id="matkhau" name="matkhau">
                            <?php if (isset($errors['matkhau'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['matkhau']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label for="xacnhan_matkhau" class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control <?php echo isset($errors['xacnhan_matkhau']) ? 'is-invalid' : ''; ?>" 
                                id="xacnhan_matkhau" name="xacnhan_matkhau">
                            <?php if (isset($errors['xacnhan_matkhau'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['xacnhan_matkhau']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Đặt Lại Mật Khẩu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>