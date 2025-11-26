<?php
require_once('../admin/admin-auth.php');
require_once __DIR__ . '/../config/config.php';

$page_title = "Chỉnh sửa mã giảm giá";

// Kiểm tra ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID mã giảm giá không hợp lệ!";
    header("Location: manage-vouchers.php");
    exit();
}

$id = $_GET['id'];

// Lấy dữ liệu
$sql = "SELECT * FROM vouchers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Không tìm thấy mã giảm giá!";
    header("Location: manage-vouchers.php");
    exit();
}

$voucher = $result->fetch_assoc();

// Tính số ngày còn lại
$now = new DateTime();
$expires = new DateTime($voucher['expires_at']);
$interval = $now->diff($expires);
$remaining_days = $interval->days;

// Xử lý cập nhật
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save'])) {
    $discount_percent = intval($_POST['discount_percent'] ?? 0);
    $expiry_days = intval($_POST['expiry_days'] ?? 0);

    if ($discount_percent < 1 || $discount_percent > 99) {
        $_SESSION['error'] = "Phần trăm giảm giá phải từ 1% đến 99%";
    } elseif ($expiry_days < 1) {
        $_SESSION['error'] = "Số ngày hiệu lực phải lớn hơn 0";
    } else {
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
        $update_sql = "UPDATE vouchers SET discount_percent = ?, expires_at = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("isi", $discount_percent, $expires_at, $id);
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Cập nhật mã giảm giá thành công!";
            header("Location: manage-vouchers.php");
            exit();
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra: " . $conn->error;
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
                <h2><i class="fas fa-edit me-2"></i><?= $page_title ?></h2>
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
                            <form action="edit-voucher.php?id=<?= $id ?>" method="post">
                                <div class="mb-3">
                                    <label for="code" class="form-label">Mã giảm giá</label>
                                    <input type="text" class="form-control code-field bg-light" id="code"
                                           value="<?= htmlspecialchars($voucher['code']) ?>" readonly>
                                    <small class="text-muted">Không thể thay đổi mã sau khi tạo</small>
                                </div>

                                <div class="mb-3">
                                    <label for="discount_percent" class="form-label">Phần trăm giảm giá (%)</label>
                                    <input type="number" class="form-control" id="discount_percent" name="discount_percent"
                                           min="1" max="99" value="<?= $voucher['discount_percent'] ?>" required oninput="updatePreview()">
                                </div>

                                <div class="mb-3">
                                    <label for="expiry_days" class="form-label">Gia hạn thêm (ngày)</label>
                                    <input type="number" class="form-control" id="expiry_days" name="expiry_days"
                                           min="1" value="<?= $remaining_days > 0 ? $remaining_days : 7 ?>" required>
                                    <small class="text-muted">Số ngày hiệu lực tính từ hôm nay</small>
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <a href="manage-vouchers.php" class="btn btn-light me-2">Hủy</a>
                                    <button type="submit" name="save" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i> Cập nhật mã giảm giá
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="discount-preview text-center p-3 mt-2 border rounded bg-light">
                        <h5 class="mb-3">Xem trước mã</h5>
                        <div class="discount-code mb-2" id="preview-code"><?= htmlspecialchars($voucher['code']) ?></div>
                        <div><span class="preview-amount" id="preview-percent"><?= $voucher['discount_percent'] ?>%</span> GIẢM GIÁ</div>
                        <small class="text-muted d-block mt-2">Hiện tại hết hạn: <?= date('d/m/Y H:i', strtotime($voucher['expires_at'])) ?></small>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Lưu ý</h6>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0 small">
                                <li>Thời hạn mới sẽ tính lại từ hôm nay</li>
                                <li>Mã đã sử dụng hoặc hết hạn sẽ bị xóa tự động</li>
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
        const percentInput = document.getElementById('discount_percent');
        document.getElementById('preview-percent').textContent = percentInput.value + '%';
    }
</script>

<?php include_once('../includes/ad-footer.php'); ?>
