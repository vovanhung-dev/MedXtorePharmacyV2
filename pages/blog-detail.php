<?php
include('../includes/header.php');
include('../includes/navbar.php');
require_once('../controllers/BlogController.php');

// Kiểm tra slug
$slug = $_GET['slug'] ?? null;
$blogCtrl = new BlogController();
$baiviet = $blogCtrl->getPostBySlug($slug);
$relatedPosts = $blogCtrl->getRelatedPosts($baiviet['loai_id'], $baiviet['id']);

// Nếu không tìm thấy bài viết
if (!$baiviet) {
    echo "<div class='container py-5'><h3 class='text-danger'>Bài viết không tồn tại.</h3></div>";
    include('../includes/footer.php');
    exit;
}
?>

<style>
/* Header Section */
.blog-detail-header {
  background: #1976d2;
  padding: 50px 0;
  color: white;
  margin-bottom: 40px;
}

.blog-detail-title {
  font-size: 2rem;
  font-weight: 700;
  color: white;
  margin-bottom: 15px;
}

.blog-meta {
  font-size: 0.95rem;
  opacity: 0.95;
}

.blog-meta i {
  margin-right: 5px;
}

/* Content Section */
.blog-detail-section {
  padding-bottom: 40px;
}

/* Featured Image */
.blog-featured-img {
  width: 100%;
  max-height: 450px;
  object-fit: cover;
  border-radius: 12px;
  margin-bottom: 30px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* Summary Box */
.blog-summary-box {
  background: #f8f9fa;
  border-left: 4px solid #1976d2;
  padding: 20px;
  margin-bottom: 30px;
  border-radius: 0 8px 8px 0;
}

.blog-summary-box p {
  margin: 0;
  font-size: 1.05rem;
  color: #6c757d;
  line-height: 1.6;
}

/* Content Box */
.blog-content-box {
  background: white;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  margin-bottom: 30px;
}

.blog-content-box h2,
.blog-content-box h3 {
  font-weight: 700;
  color: #2d3748;
  margin-top: 25px;
  margin-bottom: 15px;
}

.blog-content-box h2 {
  font-size: 1.5rem;
  color: #1976d2;
}

.blog-content-box h3 {
  font-size: 1.25rem;
}

.blog-content-box p {
  font-size: 1rem;
  line-height: 1.7;
  color: #444;
  margin-bottom: 15px;
}

.blog-content-box img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  margin: 20px 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.blog-content-box ul,
.blog-content-box ol {
  margin-bottom: 15px;
  padding-left: 25px;
}

.blog-content-box li {
  margin-bottom: 8px;
  line-height: 1.6;
}

.blog-content-box a {
  color: #1976d2;
  text-decoration: none;
  border-bottom: 1px solid #1976d2;
}

.blog-content-box a:hover {
  color: #1565c0;
  border-bottom-color: #1565c0;
}

/* Share Section */
.share-section {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  margin-bottom: 30px;
}

.share-section h5 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 15px;
}

.share-buttons {
  display: flex;
  gap: 10px;
}

.share-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #f8f9fa;
  color: #1976d2;
  transition: all 0.2s ease;
  text-decoration: none;
}

.share-btn:hover {
  background: #1976d2;
  color: white;
  transform: translateY(-2px);
}

/* Related Articles */
.related-section {
  margin-top: 40px;
  padding-top: 30px;
  border-top: 1px solid #e9ecef;
}

.related-section h4 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 25px;
}

.related-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
  height: 100%;
}

.related-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.related-img {
  width: 100%;
  height: 180px;
  object-fit: cover;
}

.related-card-body {
  padding: 20px;
}

.related-card-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 10px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.related-card-text {
  font-size: 0.9rem;
  color: #6c757d;
  margin-bottom: 15px;
  line-height: 1.5;
}

.btn-read-more {
  padding: 8px 20px;
  background: #1976d2;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
  text-decoration: none;
  display: inline-block;
  transition: all 0.2s ease;
}

.btn-read-more:hover {
  background: #1565c0;
  color: white;
  transform: translateY(-2px);
}

/* Back Button */
.btn-back {
  padding: 10px 25px;
  border: 1px solid #6c757d;
  color: #6c757d;
  background: white;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
  transition: all 0.2s ease;
}

.btn-back:hover {
  background: #6c757d;
  color: white;
  text-decoration: none;
}

/* Responsive */
@media (max-width: 768px) {
  .blog-detail-title {
    font-size: 1.5rem;
  }

  .blog-detail-header {
    padding: 30px 0;
  }

  .blog-content-box {
    padding: 20px;
  }

  .blog-featured-img {
    max-height: 300px;
  }
}
</style>

<!-- Header -->
<section class="blog-detail-header">
  <div class="container text-center">
    <h1 class="blog-detail-title"><?= htmlspecialchars($baiviet['tieude']) ?></h1>
    <div class="blog-meta">
      <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($baiviet['ngay_dang'])) ?>
      <span class="mx-2">|</span>
      <i class="bi bi-person"></i> <?= htmlspecialchars($baiviet['tac_gia']) ?>
      <span class="mx-2">|</span>
      <i class="bi bi-tag"></i> <?= htmlspecialchars($baiviet['ten_loai'] ?? 'Không có') ?>
    </div>
  </div>
</section>

<!-- Content -->
<section class="container blog-detail-section">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <!-- Featured Image -->
      <img src="../assets/images/blog/<?= htmlspecialchars($baiviet['hinhanh']) ?>"
           alt="<?= htmlspecialchars($baiviet['tieude']) ?>"
           class="blog-featured-img">

      <!-- Summary -->
      <div class="blog-summary-box">
        <p><?= nl2br(htmlspecialchars($baiviet['tomtat'])) ?></p>
      </div>

      <!-- Main Content -->
      <div class="blog-content-box">
        <?= $baiviet['noidung'] ?>
      </div>

      <!-- Share Section -->
      <div class="share-section">
        <h5><i class="bi bi-share me-2"></i>Chia sẻ bài viết</h5>
        <div class="share-buttons">
          <a href="#" class="share-btn" title="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" class="share-btn" title="Twitter"><i class="bi bi-twitter"></i></a>
          <a href="#" class="share-btn" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
          <a href="#" class="share-btn" title="Pinterest"><i class="bi bi-pinterest"></i></a>
          <a href="#" class="share-btn" title="Email"><i class="bi bi-envelope"></i></a>
        </div>
      </div>

      <!-- Related Articles -->
      <?php if (!empty($relatedPosts)): ?>
        <div class="related-section">
          <h4>Bài viết liên quan</h4>
          <div class="row g-4">
            <?php foreach ($relatedPosts as $post): ?>
              <div class="col-md-4">
                <div class="related-card">
                  <img src="../assets/images/blog/<?= htmlspecialchars($post['hinhanh']) ?>"
                       class="related-img"
                       alt="<?= htmlspecialchars($post['tieude']) ?>">
                  <div class="related-card-body">
                    <h5 class="related-card-title"><?= htmlspecialchars($post['tieude']) ?></h5>
                    <p class="related-card-text">
                      <?= mb_strimwidth(strip_tags($post['tomtat']), 0, 100, '...') ?>
                    </p>
                    <a href="blog-detail.php?slug=<?= urlencode($post['slug']) ?>" class="btn-read-more">
                      Đọc tiếp
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Back Button -->
      <div class="mt-5 text-end">
        <a href="blog.php" class="btn-back">
          <i class="bi bi-arrow-left me-2"></i>Quay lại danh sách
        </a>
      </div>
    </div>
  </div>
</section>

<?php include('../includes/footer.php'); ?>
