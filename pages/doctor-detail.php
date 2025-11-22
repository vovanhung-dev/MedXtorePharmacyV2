<?php 
include('../includes/header.php'); 
include('../includes/navbar.php'); 
require_once('../controllers/DoctorController.php');

// Lấy ID bác sĩ từ URL
$doctorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Khởi tạo controller
$doctorController = new DoctorController();
$doctor = $doctorController->getDoctorById($doctorId);

// Nếu không tìm thấy bác sĩ, chuyển hướng về trang about
if (!$doctor) {
    header('Location: about.php');
    exit;
}

// Lấy các bác sĩ liên quan (ví dụ: cùng chuyên khoa)
$relatedDoctors = $doctorController->getAllDoctors(); // Bạn có thể tạo phương thức riêng để lấy bác sĩ liên quan
?>

<!-- Banner bác sĩ -->
<section class="py-5" style="background: url('../assets/images/product-images/banner-orange.jpg') no-repeat right center/cover; background-color: #f6f7fb;">
  <div class="container py-5">
    <h1 class="fw-bold text-dark"><?= htmlspecialchars($doctor['name']) ?></h1>
    <p class="text-muted col-md-6"><?= htmlspecialchars($doctor['specialization']) ?> - <?= htmlspecialchars($doctor['qualification']) ?></p>
  </div>
</section>

<!-- Thông tin chi tiết bác sĩ -->
<section class="container py-5">
  <div class="row">
    <div class="col-md-4">
      <img 
        src="../assets/images/<?= htmlspecialchars($doctor['image']) ?>" 
        alt="<?= htmlspecialchars($doctor['name']) ?>" 
        class="img-fluid rounded"
        style="width: 100%; height: 400px; object-fit: cover;"
      >
    </div>
    <div class="col-md-8">
      <h2 class="fw-bold mb-4">Thông tin bác sĩ</h2>
      <div class="mb-4">
        <h5 class="fw-bold">Chuyên khoa</h5>
        <p><?= htmlspecialchars($doctor['specialization']) ?></p>
      </div>
      <div class="mb-4">
        <h5 class="fw-bold">Bằng cấp</h5>
        <p><?= htmlspecialchars($doctor['qualification']) ?></p>
      </div>
      <div class="mb-4">
        <h5 class="fw-bold">Kinh nghiệm</h5>
        <p><?= htmlspecialchars($doctor['experience']) ?> năm kinh nghiệm</p>
      </div>
      <div class="mb-4">
        <h5 class="fw-bold">Giới thiệu</h5>
        <p><?= $doctor['description'] ?></p>
      </div>
    </div>
  </div>

  <!-- Phần bác sĩ liên quan -->
  <div class="mt-5">
    <h3 class="fw-bold mb-4">Đội ngũ bác sĩ khác</h3>
    <div class="row">
      <?php foreach ($relatedDoctors as $relatedDoctor): ?>
        <?php if ($relatedDoctor['id'] != $doctor['id']): ?>
          <div class="col-md-4 mb-4">
            <div class="card h-100">
              <img 
                src="../assets/images/<?= htmlspecialchars($relatedDoctor['image']) ?>" 
                alt="<?= htmlspecialchars($relatedDoctor['name']) ?>" 
                class="card-img-top"
                style="height: 250px; object-fit: cover;"
              >
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($relatedDoctor['name']) ?></h5>
                <p class="card-text text-muted"><?= htmlspecialchars($relatedDoctor['specialization']) ?></p>
                <a href="doctor-detail.php?id=<?= $relatedDoctor['id'] ?>" class="btn btn-outline-primary">Xem chi tiết</a>
              </div>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include('../includes/footer.php'); ?>