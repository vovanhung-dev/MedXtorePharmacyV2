<?php
session_start();
include('../includes/config.php');
require_once('../controllers/AuthController.php');

// Nếu người dùng đã đăng nhập, chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    // Khởi tạo AuthController
    $auth = new AuthController($conn);
    
    // Gọi phương thức forgotPassword
    $result = $auth->forgotPassword($email);
    
    if ($result['success']) {
        $success = $result['message'];
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
                    <h2 class="text-center mb-4">Quên Mật Khẩu</h2>
                    
                    <?php if (isset($_SESSION['reset_error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            echo $_SESSION['reset_error']; 
                            unset($_SESSION['reset_error']);
                        ?>
                    </div>
                    <?php endif; ?>
                    
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
                    
                    <p class="mb-4">Nhập địa chỉ email của bạn và chúng tôi sẽ gửi cho bạn hướng dẫn để đặt lại mật khẩu.</p>
                    
                    <form method="POST" action="forgot_password.php">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                            <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['email']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Gửi Hướng Dẫn Đặt Lại Mật Khẩu</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p><a href="login.php">Quay lại đăng nhập</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>