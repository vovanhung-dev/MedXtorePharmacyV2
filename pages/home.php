<script src="/chatbot.js"></script>
<?php
include('../includes/header.php');
include('../includes/navbar.php');
require_once('../config/database.php'); // ✅ Thêm dòng này để kết nối DB
require_once('../models/Product.php');
require_once('../controllers/BlogController.php');


// Khởi tạo kết nối
$db = new Database();
$conn = $db->getConnection();

// Lấy danh sách danh mục sản phẩm
$stmt = $conn->query("SELECT id, ten_loai FROM loai_thuoc ORDER BY ten_loai ASC");
$dsDanhMuc = $stmt->fetchAll(PDO::FETCH_ASSOC);

$productModel = new Product();
$sanPhamMoi = $productModel->getLatestProducts(6);
$blogCtrl = new BlogController();
$tinmoi = $blogCtrl->getLatestPosts(3);
?>

<style>
  .fixed-img {
    width: 100%;
    height: 200px;
    object-fit: contain;
    border-radius: 0.75rem;
    background-color: #e0ecef;
    padding: 10px;
  }
  /* Style cho trigger (nút mở) ở góc trái */
  #chat-trigger {
        position: fixed;
        bottom: 20px;
        left: 20px;
        width: 60px;
        height: 60px;
        background-color: #009688;
        border-radius: 50%;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        cursor: pointer;
        z-index: 9999;
    }

    /* Style cho chatbot (ẩn ban đầu) */
    #chat-widget {
        position: fixed;
        bottom: 80px;
        left: 20px;
        width: 350px;
        height: 500px;
        border: none;
        border-radius: 10px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        display: none; /* Ẩn chatbot khi không nhấp vào nút trigger */
    }

</style>


<!-- Phần Giới Thiệu -->
<section class="py-5 text-center bg-info bg-opacity-10">
  <div class="container">
    <h1 class="display-4 fw-bold text-primary">Nhà thuốc MedXtore</h1>
    <p class="lead">Chăm sóc sức khỏe gia đình bạn</p>
    <p>Chuyên cung cấp vitamin, thực phẩm chức năng và dược phẩm chất lượng cao.</p>
    <a href="<?= page_url('products/products.php') ?>" class="btn btn-dark">Xem ngay</a>
  </div>
</section>

<!-- Lợi ích sản phẩm -->
<section class="py-5 text-center">
  <h6 class="text-info fw-semibold">Chào mừng đến với MedXtore</h6>
  <h2 class="fw-bold mb-5">Lợi ích khi sử dụng sản phẩm</h2>

  <div class="container">
    <div class="row align-items-center justify-content-center">
      <!-- Bên trái -->
      <div class="col-md-3 text-center">
        <div class="mb-5">
          <img src="../assets/images/save.png" alt="Tim mạch" width="80">
          <h6 class="fw-bold mt-3">TỐT CHO TIM MẠCH</h6>
          <p class="text-muted small">Hỗ trợ hệ tim mạch khỏe mạnh, phòng ngừa nguy cơ cao huyết áp.</p>
        </div>
        <div>
          <img src="../assets/images/health.png" alt="Sức khỏe" width="80">
          <h6 class="fw-bold mt-3">TĂNG CƯỜNG SỨC KHỎE</h6>
          <p class="text-muted small">Bổ sung năng lượng, cải thiện hệ miễn dịch và tăng sức đề kháng.</p>
        </div>
      </div>

      <!-- Ảnh trung tâm -->
      <div class="col-md-6 mb-4 mb-md-0">
        <img src="../assets/images/vitamin.png" class="img-fluid" alt="Vitamin tổng hợp">
      </div>

      <!-- Bên phải -->
      <div class="col-md-3 text-center">
        <div class="mb-5">
          <img src="../assets/images/strongbone.png" alt="Xương" width="80">
          <h6 class="fw-bold mt-3">XƯƠNG CHẮC KHỎE</h6>
          <p class="text-muted small">Hỗ trợ hấp thụ canxi, duy trì hệ xương chắc khỏe lâu dài.</p>
        </div>
        <div>
          <img src="../assets/images/goodmemory.png" alt="Trí nhớ" width="80">
          <h6 class="fw-bold mt-3">CẢI THIỆN TRÍ NHỚ</h6>
          <p class="text-muted small">Giúp tinh thần minh mẫn, giảm nguy cơ mất trí nhớ ở người lớn tuổi.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Danh mục sản phẩm -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="mb-5 text-center">
      <h6 class="text-info fw-semibold">Sản phẩm nổi bật</h6>
      <h2 class="fw-bold">Danh mục sản phẩm</h2>
      <p class="text-muted mx-auto" style="max-width: 600px;">
        Khám phá các dòng sản phẩm chăm sóc sức khỏe được nhiều người tin dùng tại MedXtore.
      </p>
    </div>

    <div class="row g-4 justify-content-center">
    <?php foreach ($dsDanhMuc as $dm): ?>
  <div class="col-md-4 col-sm-6">
    <div class="border rounded-4 p-4 bg-white shadow-sm text-center h-100 hover-shadow">
      <h5 class="fw-bold text-primary mb-2"><?= htmlspecialchars($dm['ten_loai']) ?></h5>
      <p class="text-muted mb-3">Sản phẩm thuộc nhóm <?= htmlspecialchars($dm['ten_loai']) ?> đang rất được ưa chuộng.</p>
      <a href="/pages/products/products.php?loai=<?= $dm['id'] ?>" class="btn btn-outline-info rounded-pill px-4">
        Xem sản phẩm
      </a>
    </div>
  </div>
<?php endforeach; ?>

    </div>
  </div>
</section>

<!-- Sản phẩm mới -->
<section class="container py-5 text-center">
  <h3 class="mb-3">Sản phẩm mới</h3>
  <p class="text-muted mb-4">Khám phá các sản phẩm vừa được cập nhật tại MedXtore.</p>

  <div class="row g-4">
    <?php if (!empty($sanPhamMoi)): ?>
      <?php foreach ($sanPhamMoi as $sp): ?>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm border-0 rounded-4">
            <div class="p-3">
              <img src="/assets/images/product-images/<?= htmlspecialchars($sp['hinhanh']) ?>"
                alt="<?= htmlspecialchars($sp['ten_thuoc']) ?>"
                class="w-100 fixed-img rounded bg-light">
            </div>
            <div class="card-body">
              <h5 class="fw-bold"><?= htmlspecialchars($sp['ten_thuoc']) ?></h5>
              <p class="fw-semibold text-primary mb-1">
                <?= number_format($sp['gia']) ?>đ / <?= htmlspecialchars($sp['ten_donvi']) ?>
              </p>
              <p class="text-muted mb-2"><?= htmlspecialchars(mb_strimwidth(strip_tags($sp['mota'] ?? ''), 0, 80, "...")) ?></p>
              <a href="/pages/products/product-detail.php?id=<?= $sp['id'] ?>"
                class="btn btn-outline-primary btn-sm rounded-pill">
                Xem chi tiết
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted">Hiện chưa có sản phẩm mới.</p>
    <?php endif; ?>
  </div>
</section>


<!-- Câu hỏi thường gặp -->
<section class="container py-5">
  <h3 class="mb-4">Câu hỏi thường gặp</h3>
  <div class="faq-item"><strong>Nên sử dụng vitamin từ bao nhiêu tuổi?</strong>
    <div class="faq-answer">Thông thường từ 18 tuổi trở lên có thể sử dụng, tùy theo nhu cầu và chỉ định.</div>
  </div>
  <div class="faq-item"><strong>Nên chọn loại vitamin nào?</strong>
    <div class="faq-answer">Vitamin tổng hợp, Vitamin C, D, Magie... tùy theo thể trạng và lời khuyên từ bác sĩ.</div>
  </div>
  <div class="faq-item"><strong>Lợi ích và tác dụng phụ của vitamin?</strong>
    <div class="faq-answer">Lợi ích: cải thiện sức khỏe, năng lượng. Tác dụng phụ: có thể gây rối loạn tiêu hóa nếu dùng quá liều.</div>
  </div>
</section>

<!-- Tin tức -->
<section class="container py-5">
  <h4 class="text-muted">Tin tức</h4>
  <h2 class="fw-bold mb-4">Tin mới nhất</h2>
  <p class="mb-5">Cập nhật các thông tin về sức khỏe, bệnh lý và sản phẩm từ chuyên gia.</p>

  <div class="row g-4">
    <?php foreach ($tinmoi as $tin): ?>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <a href="blog-detail.php?slug=<?= urlencode($tin['slug']) ?>" class="text-decoration-none text-dark">
            <img
              src="../assets/images/blog/<?= htmlspecialchars($tin['hinhanh']) ?>"
              class="card-img-top rounded-top"
              alt="<?= htmlspecialchars($tin['tieude']) ?>"
              style="height: 200px; object-fit: cover; width: 100%;">
            <div class="card-body">
              <h6 class="card-title fw-bold"><?= htmlspecialchars($tin['tieude']) ?></h6>
              <p class="card-text text-muted small mb-0">
                <?= date('d/m/Y', strtotime($tin['ngay_dang'])) ?>
              </p>
            </div>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Thêm vào cuối trang (trước footer) -->
<section id="chatbot-section">
    <div id="chat-trigger" onclick="toggleChat()">
        &#128172; <!-- Biểu tượng chat -->
    </div>

    <iframe
        id="chat-widget"
        src="https://cdn.botpress.cloud/webchat/v2.3/shareable.html?configUrl=https://files.bpcontent.cloud/2025/04/06/07/20250406074143-K5TWDCDU.json"
        title="Chatbot"
    ></iframe>
</section>

<script>
    // Hàm bật/tắt chatbot khi nhấp vào nút trigger
    function toggleChat() {
        const chatWidget = document.getElementById('chat-widget');
        const chatTrigger = document.getElementById('chat-trigger');

        // Kiểm tra xem chatbot có đang mở hay không
        if (chatWidget.style.display === "none" || chatWidget.style.display === "") {
            chatWidget.style.display = "block"; // Hiển thị chatbot
            chatTrigger.style.backgroundColor = "#00796b"; // Thay đổi màu của nút trigger khi chatbot mở
        } else {
            chatWidget.style.display = "none"; // Ẩn chatbot
            chatTrigger.style.backgroundColor = "#009688"; // Quay lại màu ban đầu của nút trigger
        }
    }
</script>



<?php include('../includes/footer.php'); ?>