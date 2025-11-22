<?php 
include('../includes/header.php'); 
include('../includes/navbar.php'); 
require_once('../controllers/DoctorController.php');

$doctorController = new DoctorController();
$featuredDoctors = $doctorController->getFeaturedDoctors();
?>


<!-- Banner Giới thiệu -->
<section class="py-5" style="background: url('../assets/images/product-images/banner-orange.jpg') no-repeat right center/cover; background-color: #f6f7fb;">
  <div class="container py-5">
    <h1 class="fw-bold text-dark">Về Chúng Tôi</h1>
    <p class="text-muted col-md-6">MedXtore - Nhà thuốc hiện đại, uy tín và tận tâm với sức khỏe cộng đồng.</p>
  </div>
</section>

<!-- Giới thiệu hệ thống -->
<section class="container py-5">
  <div class="row align-items-center">
    <div class="col-md-6">
      <img src="../assets/images/pharmacy-store.jpg" alt="Pharmacy" class="img-fluid rounded-circle">
    </div>
    <div class="col-md-6">
      <h2 class="fw-bold">Hệ thống nhà thuốc phủ rộng toàn quốc</h2>
      <p class="text-muted">MedXtore không ngừng mở rộng mạng lưới, mang đến giải pháp chăm sóc sức khỏe chất lượng, tiện lợi và an toàn đến từng khách hàng trên khắp cả nước.</p>
    </div>
  </div>
</section>

<!-- Uy tín & chất lượng -->
<section class="container py-5">
  <div class="row align-items-center">
    <div class="col-md-6 order-md-2">
      <img src="../assets/images/reliable-pharma.jpg" alt="Reliability" class="img-fluid rounded-circle">
    </div>
    <div class="col-md-6 order-md-1">
      <h3 class="fw-bold">Uy tín hàng đầu trong chăm sóc khách hàng</h3>
      <p class="text-muted">Đội ngũ dược sĩ chuyên môn cao, tư vấn tận tình, sẵn sàng đồng hành cùng bạn trong hành trình nâng cao sức khỏe.</p>
    </div>
  </div>
</section>

<!-- Video giới thiệu -->
<section class="container py-5 text-center">
  <div class="ratio ratio-16x9">
  <iframe width="560" height="315" src="https://www.youtube.com/embed/fSY7LITk1gk?si=moXBTqm_tX4Qr487"
     title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
  </div>
</section>

<!-- Thống kê -->
<section class="container py-5 text-center">
  <div class="row g-4">
    <div class="col-md-3"><div class="bg-info text-white rounded py-4"><h2>283</h2><p>Nhà thuốc trên toàn quốc</p></div></div>
    <div class="col-md-3"><div class="bg-info text-white rounded py-4"><h2>3</h2><p>Quốc gia hoạt động</p></div></div>
    <div class="col-md-3"><div class="bg-info text-white rounded py-4"><h2>19 Triệu</h2><p>Khách hàng tin dùng</p></div></div>
    <div class="col-md-3"><div class="bg-info text-white rounded py-4"><h2>50</h2><p>Nhà máy sản xuất đạt chuẩn</p></div></div>
  </div>
</section>

<!-- Đội ngũ chuyên gia -->
<section class="container py-5 text-center">
  <h2 class="fw-bold mb-4">Đội ngũ chuyên gia</h2>
  <p class="text-muted mb-4">Những con người tận tâm, giỏi chuyên môn và luôn đặt sức khỏe của bạn lên hàng đầu.</p>
  
  <div class="row g-4">
    <?php foreach ($featuredDoctors as $doctor): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <img 
            src="../assets/images/<?= htmlspecialchars($doctor['image']) ?>" 
            alt="<?= htmlspecialchars($doctor['name']) ?>" 
            class="card-img-top"
            style="height: 300px; object-fit: cover;"
          >
          <div class="card-body">
            <a href="doctor-detail.php?id=<?= $doctor['id'] ?>" class="text-decoration-none">
              <h5 class="card-title fw-bold text-dark"><?= htmlspecialchars($doctor['name']) ?></h5>
            </a>
            <p class="card-text text-muted"><?= htmlspecialchars($doctor['specialization']) . ' - ' . htmlspecialchars($doctor['qualification']) ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php include('../includes/footer.php'); ?>
