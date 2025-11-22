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
$ten = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten = trim($_POST['ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matkhau = $_POST['matkhau'] ?? '';
    $xacnhan_matkhau = $_POST['xacnhan_matkhau'] ?? '';

    // Khởi tạo AuthController
    $auth = new AuthController($conn);
    
    // Gọi phương thức register
    $result = $auth->register($ten, $email, $matkhau, $xacnhan_matkhau);
    
    if ($result['success']) {
        // Chuyển hướng đến trang đăng nhập với thông báo
        header('Location: login.php?registered=success');
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
                    <h2 class="text-center mb-4">Đăng Ký Tài Khoản</h2>
                    
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
                    
                    <form method="POST" action="register.php">
                        <div class="mb-3">
                            <label for="ten" class="form-label">Họ và tên</label>
                            <input type="text" class="form-control <?php echo isset($errors['ten']) ? 'is-invalid' : ''; ?>" 
                                id="ten" name="ten" value="<?php echo htmlspecialchars($ten); ?>">
                            <?php if (isset($errors['ten'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['ten']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['email']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="matkhau" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control <?php echo isset($errors['matkhau']) ? 'is-invalid' : ''; ?>" 
                                id="matkhau" name="matkhau">
                            <?php if (isset($errors['matkhau'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['matkhau']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label for="xacnhan_matkhau" class="form-label">Xác nhận mật khẩu</label>
                            <input type="password" class="form-control <?php echo isset($errors['xacnhan_matkhau']) ? 'is-invalid' : ''; ?>" 
                                id="xacnhan_matkhau" name="xacnhan_matkhau">
                            <?php if (isset($errors['xacnhan_matkhau'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['xacnhan_matkhau']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Đăng Ký</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>