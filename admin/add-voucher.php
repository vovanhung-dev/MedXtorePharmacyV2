<?php
require_once('../admin/admin-auth.php');
require_once __DIR__ . '/../config/config.php';

$page_title = "Thêm mã giảm giá mới";

// Hàm tạo mã ngẫu nhiên
function generateRandomCode($length = 6) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

// Xử lý tạo mã mới từ nút
$random_code = generateRandomCode();
if (isset($_POST['generate_code'])) {
    $random_code = generateRandomCode();
    header("Location: add-voucher.php?code=" . $random_code);
    exit();
}

if (isset($_GET['code'])) {
    $random_code = $_GET['code'];
}

// Xử lý form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save'])) {
    $code = $_POST['code'] ?? '';
    $discount_percent = intval($_POST['discount_percent'] ?? 0);
    $expiry_days = intval($_POST['expiry_days'] ?? 0);

    if ($discount_percent < 1 || $discount_percent > 99) {
        $_SESSION['error'] = "Phần trăm giảm giá phải từ 1% đến 99%";
    } elseif ($expiry_days < 1) {
        $_SESSION['error'] = "Số ngày hiệu lực phải lớn hơn 0";
    } elseif (!preg_match('/^[A-Z0-9]{6,10}$/', $code)) {
        $_SESSION['error'] = "Mã giảm giá phải từ 6-10 ký tự và chỉ gồm chữ in hoa và số";
    } else {
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
        $check_sql = "SELECT id FROM vouchers WHERE code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = "Mã giảm giá đã tồn tại, vui lòng tạo mã khác!";
        } else {
            $sql = "INSERT INTO vouchers (code, discount_percent, expires_at) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sis", $code, $discount_percent, $expires_at);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Đã tạo mã giảm giá thành công!";
                header("Location: manage-vouchers.php");
                exit();
            } else {
                $_SESSION['error'] = "Có lỗi xảy ra: " . $conn->error;
            }
        }
    }
}

// Include header và sidebar SAU KHI xử lý form
include_once('../includes/ad-header.php');
include_once('../includes/ad-sidebar.php');
?>

<div class="container-fluid">
    <div class="row min-vh-100">
        <div class="col-md-9 ms-auto p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-plus-circle me-2"></i><?= $page_title ?></h2>
                <a href="manage-vouchers.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Thông tin mã giảm giá</h5>
                        </div>
                        <div class="card-body">
                            <form action="add-voucher.php" method="post">
                                <div class="mb-3">
                                    <label for="code" class="form-label">Mã giảm giá</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control code-field" id="code" name="code"
                                               value="<?= htmlspecialchars($random_code) ?>" required maxlength="10"
                                               oninput="updatePreview(); this.value = this.value.toUpperCase();">
                                        <button class="btn btn-outline-secondary" type="submit" name="generate_code">
                                            <i class="fas fa-sync-alt me-1"></i> Tạo mới
                                        </button>
                                    </div>
                                    <small class="text-muted">Mã gồm chữ in hoa và số, từ 6 đến 10 ký tự</small>
                                </div>

                                <div class="mb-3">
                                    <label for="discount_percent" class="form-label">Phần trăm giảm giá (%)</label>
                                    <input type="number" class="form-control" id="discount_percent" name="discount_percent"
                                           min="1" max="99" value="10" required oninput="updatePreview()">
                                </div>

                                <div class="mb-3">
                                    <label for="expiry_days" class="form-label">Thời hạn sử dụng (ngày)</label>
                                    <input type="number" class="form-control" id="expiry_days" name="expiry_days"
                                           min="1" value="7" required>
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <a href="manage-vouchers.php" class="btn btn-light me-2">Hủy</a>
                                    <button type="submit" name="save" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i> Lưu mã giảm giá
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="discount-preview text-center mt-2 p-3 border rounded bg-light">
                        <h5 class="mb-3">Xem trước mã</h5>
                        <div class="discount-code mb-2" id="preview-code"><?= htmlspecialchars($random_code) ?></div>
                        <div><span class="preview-amount" id="preview-percent">10%</span> GIẢM GIÁ</div>
                        <small class="text-muted d-block mt-2">Áp dụng cho đơn hàng tại MedXtorePharmacy</small>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Lưu ý</h6>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0 small">
                                <li>Mã được xóa sau khi sử dụng hoặc hết hạn</li>
                                <li>Giảm theo phần trăm tổng giá trị đơn</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .code-field {
        font-family: monospace;
        letter-spacing: 2px;
        font-size: 1.2rem;
        text-transform: uppercase;
    }
    .discount-code {
        font-family: monospace;
        font-size: 1.4rem;
        padding: 8px;
        border: 1px solid #ccc;
        display: inline-block;
        background: #fff;
        border-radius: 4px;
        color: #3498db;
    }
    .preview-amount {
        font-size: 2rem;
        color: #e74c3c;
        font-weight: bold;
    }
</style>

<script>
    function updatePreview() {
        const codeInput = document.getElementById('code');
        const percentInput = document.getElementById('discount_percent');
        document.getElementById('preview-code').textContent = codeInput.value.toUpperCase();
        document.getElementById('preview-percent').textContent = percentInput.value + '%';
    }
</script>

<?php include_once('../includes/ad-footer.php'); ?>
