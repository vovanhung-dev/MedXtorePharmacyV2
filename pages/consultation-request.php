<?php
session_start();
require_once __DIR__ . '/../controllers/ConsultationController.php';
require_once __DIR__ . '/../config/database.php';

$controller = new ConsultationController();
$message = '';
$messageType = '';

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'ho_ten' => $_POST['ho_ten'] ?? '',
        'so_dien_thoai' => $_POST['so_dien_thoai'] ?? '',
        'ghi_chu' => $_POST['ghi_chu'] ?? '',
        'nguoidung_id' => $_SESSION['user_id'] ?? null,
        'thuoc' => $_POST['thuoc'] ?? []
    ];

    $result = $controller->create($data, $_FILES);

    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
        // Reset form data
        $_POST = [];
    } else {
        $message = implode('<br>', $result['errors']);
        $messageType = 'danger';
    }
}

// Lấy thông tin user nếu đã đăng nhập
$userData = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM nguoidung WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
    .consultation-section {
        min-height: 100vh;
        padding-bottom: 80px;
        background-color: #f8f9fa;
    }

    .consultation-header {
        background: linear-gradient(135deg, #13b0c9 0%, #3498db 100%);
        color: white;
        padding: 2rem 0;
        border-radius: 0 0 20px 20px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .consultation-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 1.5rem;
    }

    .card-title {
        color: #13b0c9;
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e9ecef;
    }

    .card-title i {
        margin-right: 10px;
    }

    .form-label {
        font-weight: 500;
        color: #333;
    }

    .form-control:focus {
        border-color: #13b0c9;
        box-shadow: 0 0 0 0.2rem rgba(19, 176, 201, 0.15);
    }

    .btn-primary {
        background: linear-gradient(135deg, #13b0c9 0%, #3498db 100%);
        border: none;
        padding: 12px 30px;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(19, 176, 201, 0.3);
    }

    .medicine-item {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid #e9ecef;
        position: relative;
    }

    .btn-remove-medicine {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #dc3545;
        color: white;
        border: none;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        font-size: 14px;
        cursor: pointer;
    }

    .btn-add-medicine {
        background: #28a745;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-add-medicine:hover {
        background: #218838;
    }

    .upload-area {
        border: 2px dashed #13b0c9;
        border-radius: 10px;
        padding: 2rem;
        text-align: center;
        background: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .upload-area:hover {
        background: #e3f8fb;
    }

    .upload-area i {
        font-size: 3rem;
        color: #13b0c9;
        margin-bottom: 1rem;
    }

    .upload-preview {
        max-width: 200px;
        max-height: 200px;
        margin-top: 1rem;
        border-radius: 10px;
        display: none;
    }

    .info-box {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .info-box i {
        color: #856404;
    }

    .quick-link {
        color: #13b0c9;
        text-decoration: none;
        font-weight: 500;
    }

    .quick-link:hover {
        text-decoration: underline;
    }
</style>

<section class="consultation-section">
    <!-- Header -->
    <div class="consultation-header text-center">
        <div class="container">
            <h2 class="mb-2"><i class="bi bi-capsule"></i> Cần Mua Thuốc</h2>
            <p class="mb-0 opacity-75">Gửi yêu cầu tư vấn - Dược sĩ sẽ liên hệ hỗ trợ bạn</p>
            <a href="/pages/my-requests.php" class="btn btn-outline-light btn-sm mt-2">
                <i class="bi bi-list-check"></i> Xem lại đơn của tôi
            </a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="consultationForm">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Thông tin liên hệ -->
                    <div class="consultation-card">
                        <h5 class="card-title"><i class="bi bi-person-lines-fill"></i> Thông tin liên hệ</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ho_ten" required
                                    value="<?php echo htmlspecialchars($userData['ten'] ?? $_POST['ho_ten'] ?? ''); ?>"
                                    placeholder="Nhập họ và tên">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="so_dien_thoai" required
                                    value="<?php echo htmlspecialchars($userData['sodienthoai'] ?? $_POST['so_dien_thoai'] ?? ''); ?>"
                                    placeholder="Nhập số điện thoại" pattern="[0-9]{10,11}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chú / Mô tả tình trạng</label>
                            <textarea class="form-control" name="ghi_chu" rows="3"
                                placeholder="VD: Tư vấn thuốc đau dạ dày, cần thuốc hạ sốt cho trẻ em..."><?php echo htmlspecialchars($_POST['ghi_chu'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Upload hình ảnh toa thuốc -->
                    <div class="consultation-card">
                        <h5 class="card-title"><i class="bi bi-image"></i> Hình ảnh toa thuốc (Không bắt buộc)</h5>

                        <div class="upload-area" onclick="document.getElementById('hinh_anh_toa').click()">
                            <i class="bi bi-cloud-upload"></i>
                            <p class="mb-1">Nhấn để chọn ảnh hoặc kéo thả vào đây</p>
                            <small class="text-muted">Hỗ trợ: JPG, PNG, GIF (Tối đa 5MB)</small>
                            <input type="file" id="hinh_anh_toa" name="hinh_anh_toa" accept="image/*" style="display: none">
                            <img id="preview" class="upload-preview">
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="bi bi-info-circle"></i> Upload hình toa thuốc giúp dược sĩ tư vấn chính xác hơn
                        </small>
                    </div>

                    <!-- Danh sách thuốc cần tư vấn -->
                    <div class="consultation-card">
                        <h5 class="card-title"><i class="bi bi-list-ul"></i> Thuốc cần tư vấn (Không bắt buộc)</h5>

                        <div id="medicineList">
                            <!-- Medicine items will be added here -->
                        </div>

                        <button type="button" class="btn-add-medicine" onclick="addMedicine()">
                            <i class="bi bi-plus-circle"></i> Thêm thuốc cần tư vấn
                        </button>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Thông tin hướng dẫn -->
                    <div class="consultation-card">
                        <h5 class="card-title"><i class="bi bi-info-circle"></i> Hướng dẫn</h5>

                        <div class="info-box">
                            <p class="mb-2"><i class="bi bi-check-circle me-2"></i> Điền đầy đủ thông tin liên hệ</p>
                            <p class="mb-2"><i class="bi bi-check-circle me-2"></i> Mô tả tình trạng càng chi tiết càng tốt</p>
                            <p class="mb-2"><i class="bi bi-check-circle me-2"></i> Upload hình toa thuốc nếu có</p>
                            <p class="mb-0"><i class="bi bi-check-circle me-2"></i> Dược sĩ sẽ gọi lại trong 30 phút</p>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send"></i> Gửi yêu cầu tư vấn
                            </button>
                        </div>

                        <hr>

                        <div class="text-center">
                            <p class="mb-2">Đã gửi yêu cầu trước đó?</p>
                            <a href="/pages/my-requests.php" class="quick-link">
                                <i class="bi bi-arrow-right-circle"></i> Xem lại đơn thuốc của tôi
                            </a>
                        </div>

                        <hr>

                        <div class="text-center">
                            <p class="mb-2 text-muted">Hotline hỗ trợ:</p>
                            <h4 class="text-success mb-0">
                                <i class="bi bi-telephone-fill"></i> 0123 456 789
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
let medicineCount = 0;

function addMedicine() {
    medicineCount++;
    const html = `
        <div class="medicine-item" id="medicine_${medicineCount}">
            <button type="button" class="btn-remove-medicine" onclick="removeMedicine(${medicineCount})">
                <i class="bi bi-x"></i>
            </button>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Tên thuốc / Sản phẩm</label>
                    <input type="text" class="form-control" name="thuoc[${medicineCount}][ten_thuoc]"
                        placeholder="VD: Panadol, Vitamin C...">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Triệu chứng (nếu có)</label>
                    <input type="text" class="form-control" name="thuoc[${medicineCount}][trieu_chung]"
                        placeholder="VD: Đau đầu, sốt...">
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">Số lượng</label>
                    <input type="number" class="form-control" name="thuoc[${medicineCount}][so_luong]"
                        value="1" min="1">
                </div>
                <div class="col-md-8 mb-2">
                    <label class="form-label">Ghi chú</label>
                    <input type="text" class="form-control" name="thuoc[${medicineCount}][ghi_chu]"
                        placeholder="Ghi chú thêm...">
                </div>
            </div>
        </div>
    `;
    document.getElementById('medicineList').insertAdjacentHTML('beforeend', html);
}

function removeMedicine(id) {
    document.getElementById('medicine_' + id).remove();
}

// Preview image
document.getElementById('hinh_anh_toa').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('preview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

// Drag and drop
const uploadArea = document.querySelector('.upload-area');
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.style.background = '#e3f8fb';
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.style.background = '#f8f9fa';
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.style.background = '#f8f9fa';
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        document.getElementById('hinh_anh_toa').files = e.dataTransfer.files;
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('preview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
