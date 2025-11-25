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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $matkhau = $_POST['matkhau'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;

    // Khởi tạo AuthController
    $auth = new AuthController($conn);
    
    // Gọi phương thức login
    $result = $auth->login($email, $matkhau, $remember);
    
    if ($result['success']) {
        // Chuyển hướng tới trang chủ hoặc dashboard tùy theo vai trò
        if ($result['user']['vaitro_id'] == 1) {
            header('Location: ../admin/index.php');
        } else {
            header('Location: home.php');
        }
        exit;
    } else {
        $errors = $result['errors'];
    }
}

// Kiểm tra cookie remember_token
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Kiểm tra token trong database và đăng nhập tự động
    $stmt = $conn->prepare("SELECT u.id, u.ten, u.vaitro_id FROM nguoidung u 
                          JOIN remember_tokens r ON u.id = r.user_id 
                          WHERE r.token = ? AND r.expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['ten'];
        $_SESSION['user_role'] = $user['vaitro_id'];
        
        // Chuyển hướng tới trang chủ hoặc dashboard
        if ($user['vaitro_id'] == 1) {
            header('Location: ../admin/index.php');
        } else {
            header('Location: home.php');
        }
        exit;
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
          <h2 class="text-center mb-4">Đăng Nhập</h2>

          <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
            <div class="alert alert-success">
              Đăng ký thành công! Vui lòng đăng nhập.
            </div>
          <?php endif; ?>

          <?php if (isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
            <div class="alert alert-success">
              Mật khẩu đã được đặt lại thành công! Vui lòng đăng nhập với mật khẩu mới.
            </div>
          <?php endif; ?>

          <?php if (!empty($errors['login'])): ?>
            <div class="alert alert-danger">
              <?= $errors['login'] ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="login.php">
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input 
                type="email" 
                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                id="email" 
                name="email" 
                value="<?= htmlspecialchars($email ?? '') ?>"
                required
              >
              <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback">
                  <?= $errors['email'] ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="mb-3">
              <label for="matkhau" class="form-label">Mật khẩu</label>
              <input 
                type="password" 
                class="form-control <?= isset($errors['matkhau']) ? 'is-invalid' : '' ?>" 
                id="matkhau" 
                name="matkhau"
                required
              >
              <?php if (isset($errors['matkhau'])): ?>
                <div class="invalid-feedback">
                  <?= $errors['matkhau'] ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="remember" name="remember">
              <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
            </div>

            <div class="mb-4 text-end">
              <a href="forgot_password.php" class="text-decoration-none">Quên mật khẩu?</a>
            </div>

            <div class="d-grid">
              <button type="submit" class="btn btn-primary">Đăng Nhập</button>
            </div>

            <div class="text-center my-3">
              <span class="text-muted">Hoặc</span>
            </div>

            <!-- Nút đăng nhập bằng Google -->
            <div class="d-grid">
            <a href="/pages/google-login.php" class="btn btn-outline-dark py-2">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20" class="me-2" alt="Google Logo">
                Đăng nhập bằng Google
              </a>
            </div>

            <div class="text-center mt-3">
              <p>Chưa có tài khoản? <a href="register.php" class="text-decoration-none">Đăng ký ngay</a></p>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>


<?php include('../includes/footer.php'); ?>