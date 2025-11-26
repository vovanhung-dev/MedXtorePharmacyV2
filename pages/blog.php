<?php
include('../includes/header.php');
include('../includes/navbar.php');
require_once('../controllers/BlogController.php');

$blogCtrl = new BlogController();
$categories = $blogCtrl->getCategories();
$selectedCategory = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$limit = 6;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$baiviet = $blogCtrl->filterWithPagination($search, $selectedCategory, $limit, $offset);
$totalPosts = $blogCtrl->countPosts($search, $selectedCategory);
$totalPages = ceil($totalPosts / $limit);
?>

<style>
.blog-header {
  background: #1976d2;
  padding: 60px 0 40px;
  color: white;
}

.blog-header h1 {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 10px;
}

.blog-header p {
  font-size: 1.1rem;
  opacity: 0.95;
}

.search-filter-section {
  background: white;
  border-radius: 12px;
  padding: 25px;
  margin-top: -30px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  position: relative;
  z-index: 10;
}

.category-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 20px;
}

.category-btn {
  padding: 8px 20px;
  border-radius: 20px;
  border: 1px solid #dee2e6;
  background: white;
  color: #495057;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.category-btn:hover,
.category-btn.active {
  background: #1976d2;
  color: white;
  border-color: #1976d2;
  text-decoration: none;
}

.blog-grid {
  padding: 40px 0;
}

.blog-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.blog-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.blog-img-wrapper {
  position: relative;
  height: 220px;
  overflow: hidden;
  background: #f8f9fa;
}

.blog-img-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.blog-card:hover .blog-img-wrapper img {
  transform: scale(1.08);
}

.blog-category-tag {
  position: absolute;
  top: 12px;
  right: 12px;
  background: rgba(255, 255, 255, 0.95);
  color: #1976d2;
  padding: 5px 15px;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
}

.blog-card-body {
  padding: 20px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

.blog-date {
  color: #6c757d;
  font-size: 0.85rem;
  margin-bottom: 10px;
}

.blog-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 12px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.blog-excerpt {
  color: #6c757d;
  font-size: 0.9rem;
  line-height: 1.6;
  margin-bottom: 15px;
  flex-grow: 1;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.btn-read-more {
  padding: 10px 24px;
  background: #1976d2;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.btn-read-more:hover {
  background: #5568d3;
  color: white;
  transform: translateY(-2px);
}

.pagination {
  margin-top: 40px;
}

.page-link {
  border-radius: 8px;
  margin: 0 3px;
  color: #1976d2;
  border: 1px solid #dee2e6;
  padding: 8px 15px;
  font-weight: 500;
}

.page-link:hover {
  background: #1976d2;
  color: white;
  border-color: #1976d2;
}

.page-item.active .page-link {
  background: #1976d2;
  border-color: #1976d2;
}

.no-results {
  text-align: center;
  padding: 60px 20px;
  color: #6c757d;
}

.no-results i {
  font-size: 4rem;
  opacity: 0.3;
  margin-bottom: 20px;
}

@media (max-width: 768px) {
  .blog-header h1 {
    font-size: 2rem;
  }

  .blog-header {
    padding: 40px 0 30px;
  }

  .category-filters {
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 5px;
  }
}
</style>

<!-- Header -->
<section class="blog-header">
  <div class="container text-center">
    <h1>Chia sẻ kiến thức sức khỏe</h1>
    <p>Cập nhật những thông tin hữu ích về sức khỏe và dinh dưỡng</p>
  </div>
</section>

<!-- Search & Filters -->
<div class="container">
  <div class="search-filter-section">
    <form method="GET" class="row g-3">
      <div class="col-md-10">
        <input type="text" name="search" class="form-control" placeholder="Tìm kiếm bài viết..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100">Tìm kiếm</button>
      </div>
    </form>

    <div class="category-filters">
      <a href="blog.php" class="category-btn <?= empty($selectedCategory) ? 'active' : '' ?>">Tất cả</a>
      <?php foreach ($categories as $category): ?>
        <a href="blog.php?category=<?= $category['id'] ?>"
           class="category-btn <?= $selectedCategory == $category['id'] ? 'active' : '' ?>">
          <?= htmlspecialchars($category['ten_loai']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Blog Grid -->
<section class="container blog-grid">
  <?php if (!empty($baiviet)): ?>
    <div class="row g-4">
      <?php foreach ($baiviet as $bv): ?>
        <div class="col-md-6 col-lg-4">
          <div class="blog-card">
            <div class="blog-img-wrapper">
              <img src="../assets/images/blog/<?= htmlspecialchars($bv['hinhanh']) ?>"
                   alt="<?= htmlspecialchars($bv['tieude']) ?>">
              <span class="blog-category-tag"><?= htmlspecialchars($bv['ten_loai']) ?></span>
            </div>
            <div class="blog-card-body">
              <div class="blog-date">
                <i class="bi bi-calendar3 me-1"></i>
                <?= date('d/m/Y', strtotime($bv['ngay_dang'])) ?>
              </div>
              <h5 class="blog-title"><?= htmlspecialchars($bv['tieude']) ?></h5>
              <p class="blog-excerpt">
                <?= mb_strimwidth(strip_tags($bv['tomtat']), 0, 120, '...') ?>
              </p>
              <a href="blog-detail.php?slug=<?= urlencode($bv['slug']) ?>" class="btn-read-more">
                Đọc tiếp
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav>
        <ul class="pagination justify-content-center">
          <?php if ($page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($selectedCategory) ?>">
                Trước
              </a>
            </li>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($selectedCategory) ?>">
                <?= $i ?>
              </a>
            </li>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($selectedCategory) ?>">
                Sau
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>

  <?php else: ?>
    <div class="no-results">
      <i class="bi bi-journal-x"></i>
      <h4>Không tìm thấy bài viết nào</h4>
      <p>Vui lòng thử tìm kiếm với từ khóa khác</p>
    </div>
  <?php endif; ?>
</section>

<?php include('../includes/footer.php'); ?>
