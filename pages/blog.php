<?php 
include('../includes/header.php'); 
include('../includes/navbar.php'); 
require_once('../controllers/BlogController.php');

$blogCtrl = new BlogController();
// Lấy danh sách thể loại
$categories = $blogCtrl->getCategories();

// Lấy danh mục được chọn (nếu có)
$selectedCategory = $_GET['category'] ?? '';

// Lấy từ khóa tìm kiếm (nếu có)
$search = $_GET['search'] ?? '';

// Phân trang
$limit = 6; // Số bài viết trên mỗi trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Lấy bài viết theo danh mục hoặc từ khóa tìm kiếm
$baiviet = $blogCtrl->filterWithPagination($search, $selectedCategory, $limit, $offset);
// Lấy tổng số bài viết để tính tổng số trang
$totalPosts = $blogCtrl->countPosts($search, $selectedCategory);
$totalPages = ceil($totalPosts / $limit);
?>
<style>
    /* Custom Styles */
    :root {
      --primary-color: #0d6efd;
      --secondary-color: #6c757d;
      --light-color: #f8f9fa;
      --dark-color: #212529;
      --success-color: #198754;
    }
    
    /* Smooth Scrolling */
    html {
      scroll-behavior: smooth;
    }
    
    /* Banner Styles */
    .blog-banner {
      position: relative;
      height: 400px;
      overflow: hidden;
      z-index: 1;
    }
    
    .blog-banner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.6), rgba(255, 255, 255, 0.9));
      z-index: 2;
    }
    
    .blog-banner-image {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: 1;
      transform: scale(1.05);
      animation: slowly-zoom 20s infinite alternate ease-in-out;
    }
    
    @keyframes slowly-zoom {
      0% {
        transform: scale(1);
      }
      100% {
        transform: scale(1.1);
      }
    }
    
    .blog-banner-content {
      position: relative;
      z-index: 3;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .blog-title {
      font-size: 2.8rem;
      font-weight: 800;
      background: linear-gradient(to right, #3a3a3a, #000000);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 1rem;
      transition: transform 0.3s ease;
      text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
    }
    
    .blog-description {
      font-size: 1.1rem;
      color: #505050;
      text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
    }
    
    /* Blog Card Styles */
    .blog-card {
      transition: all 0.4s ease;
      overflow: hidden;
      border-radius: 16px !important;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05) !important;
      position: relative;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    
    .blog-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important;
    }
    
    .blog-card-img-wrapper {
      overflow: hidden;
      position: relative;
      border-radius: 16px 16px 0 0;
    }
    
    .blog-card-img {
      transition: transform 8s ease;
      height: 220px;
      object-fit: cover;
      width: 100%;
    }
    
    .blog-card:hover .blog-card-img {
      transform: scale(1.2);
    }
    
    .blog-card-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to bottom, rgba(0,0,0,0), rgba(0,0,0,0.3));
      opacity: 0;
      transition: opacity 0.4s ease;
    }
    
    .blog-card:hover .blog-card-overlay {
      opacity: 1;
    }
    
    .blog-card-body {
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
      position: relative;
    }
    
    .blog-card-date {
      display: inline-block;
      padding: 0.3rem 0.8rem;
      background-color: var(--light-color);
      border-radius: 30px;
      font-size: 0.8rem;
      color: var(--secondary-color);
      margin-bottom: 1rem;
      transition: all 0.3s ease;
      border: 1px solid #eaeaea;
    }
    
    .blog-card:hover .blog-card-date {
      background-color: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
    }
    
    .blog-card-title {
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
      color: var(--dark-color);
      transition: color 0.3s ease;
      line-height: 1.4;
      height: 52px;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      line-clamp: 2;
      -webkit-box-orient: vertical;
    }
    
    .blog-card:hover .blog-card-title {
      color: var(--primary-color);
    }
    
    .blog-card-text {
      color: var(--secondary-color);
      margin-bottom: 1.5rem;
      flex-grow: 1;
      line-height: 1.6;
      height: 60px;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      line-clamp: 3;
      -webkit-box-orient: vertical;
    }
    
    .blog-card-link {
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
      padding: 0.5rem 1.2rem;
      z-index: 1;
      align-self: flex-start;
      margin-top: auto;
    }
    
    .blog-card-link::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background-color: var(--primary-color);
      transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      z-index: -1;
      border-radius: 30px;
    }
    
    .blog-card:hover .blog-card-link {
      color: white;
      border-color: var(--primary-color);
    }
    
    .blog-card:hover .blog-card-link::before {
      left: 0;
    }
    
    .blog-card-link i {
      transition: transform 0.3s ease;
      display: inline-block;
      margin-left: 2px;
    }
    
    .blog-card:hover .blog-card-link i {
      transform: translateX(5px);
    }
    
    /* Category Tags */
    .blog-category {
      position: absolute;
      top: 15px;
      right: 15px;
      padding: 0.3rem 0.8rem;
      border-radius: 30px;
      font-size: 0.75rem;
      z-index: 5;
      background-color: rgba(255, 255, 255, 0.85);
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    
    .blog-card:hover .blog-category {
      background-color: var(--primary-color);
      color: white !important;
    }
    
    /* Blog Filters */
    .blog-filter-container {
      background-color: white;
      border-radius: 15px;
      margin-bottom: 3rem;
      padding: 1.5rem;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .blog-filter-btn {
    padding: 0.6rem 1.2rem;
    border-radius: 30px;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
    border: 1px solid #eaeaea;
    background-color: white;
    color: var(--dark-color);
    text-decoration: none; /* Xóa gạch chân */
}

.blog-filter-btn:hover,
.blog-filter-btn.active {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 10px rgba(13, 110, 253, 0.15);
    text-decoration: none; /* Đảm bảo không có gạch chân khi hover hoặc active */
}
    /* Search Box */
    .blog-search {
      position: relative;
      margin-bottom: 3rem;
    }
    
    .blog-search-input {
      padding: 0.8rem 1.5rem;
      padding-right: 3rem;
      border-radius: 30px;
      border: 1px solid #eaeaea;
      width: 100%;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }
    
    .blog-search-input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 5px 15px rgba(13, 110, 253, 0.15);
      outline: none;
    }
    
    .blog-search-btn {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--secondary-color);
      transition: all 0.3s ease;
    }
    
    .blog-search-btn:hover {
      color: var(--primary-color);
    }
    
    /* Featured Article */
    .featured-article {
      position: relative;
      border-radius: 16px;
      overflow: hidden;
      margin-bottom: 3rem;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .featured-article-img {
      width: 100%;
      height: 450px;
      object-fit: cover;
      transition: transform 8s ease;
    }
    
    .featured-article:hover .featured-article-img {
      transform: scale(1.1);
    }
    
    .featured-article-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 2rem;
      background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.4), transparent);
      color: white;
    }
    
    .featured-article-tag {
      display: inline-block;
      padding: 0.3rem 0.8rem;
      background-color: var(--primary-color);
      border-radius: 30px;
      font-size: 0.8rem;
      color: white;
      margin-bottom: 1rem;
    }
    
    .featured-article-title {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
    }
    
    .featured-article-link {
      color: white;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      transition: all 0.3s ease;
    }
    
    .featured-article-link i {
      margin-left: 0.5rem;
      transition: transform 0.3s ease;
    }
    
    .featured-article-link:hover {
      color: var(--primary-color);
    }
    
    .featured-article-link:hover i {
      transform: translateX(5px);
    }
    
    /* Pagination */
    .pagination {
      margin-top: 3rem;
    }
    
    .page-link {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 3px;
      border-radius: 50% !important;
      transition: all 0.3s ease;
      color: var(--dark-color);
      border: 1px solid #eaeaea;
    }
    
    .page-link:hover,
    .page-item.active .page-link {
      background-color: var(--primary-color);
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 5px 10px rgba(13, 110, 253, 0.15);
      border-color: var(--primary-color);
    }
    
    /* Newsletter Section */
    .newsletter-section {
      background-color: white;
      border-radius: 16px;
      padding: 3rem;
      margin-top: 4rem;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
      background-image: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23f1f1f1' fill-opacity='1' fill-rule='evenodd'%3E%3Cpath d='M0 40L40 0H20L0 20M40 40V20L20 40'/%3E%3C/g%3E%3C/svg%3E");
      background-size: 30px 30px;
    }
    
    .newsletter-input {
      padding: 0.8rem 1.5rem;
      border-radius: 8px;
      border: 1px solid #eaeaea;
      width: 100%;
      transition: all 0.3s ease;
    }
    
    .newsletter-input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 5px 15px rgba(13, 110, 253, 0.15);
      outline: none;
    }
    
    .newsletter-btn {
      padding: 0.8rem 2rem;
      border-radius: 8px;
      background-color: var(--primary-color);
      color: white;
      transition: all 0.3s ease;
      font-weight: 600;
    }
    
    .newsletter-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 15px rgba(13, 110, 253, 0.2);
    }
    
    /* No Results */
    .no-results {
      text-align: center;
      padding: 3rem;
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .no-results-icon {
      font-size: 3rem;
      color: var(--secondary-color);
      margin-bottom: 1rem;
    }
    
    /* Responsive Styles */
    @media (max-width: 992px) {
      .blog-title {
        font-size: 2.3rem;
      }
      
      .featured-article-img {
        height: 350px;
      }
      
      .featured-article-title {
        font-size: 1.5rem;
      }
      
      .newsletter-section {
        padding: 2rem;
      }
    }
    
    @media (max-width: 768px) {
      .blog-banner {
        height: 350px;
      }
      
      .blog-title {
        font-size: 2rem;
      }
      
      .blog-filter-container {
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 0.5rem;
      }
      
      .featured-article-img {
        height: 300px;
      }
      
      .featured-article-overlay {
        padding: 1.5rem;
      }
      
      .featured-article-title {
        font-size: 1.3rem;
      }
      
      .newsletter-section {
        padding: 1.5rem;
      }
    }
    
    @media (max-width: 576px) {
      .blog-banner {
        height: 300px;
      }
      
      .blog-title {
        font-size: 1.8rem;
      }
      
      .newsletter-btn {
        width: 100%;
        margin-top: 1rem;
      }
    }
  </style>
<!-- Banner -->
<section class="blog-banner">
    <img src="../assets/images/pharmacy-banner.png" alt="Banner Image" class="blog-banner-image">
    <div class="container blog-banner-content">
      <div class="text-end" data-aos="fade-left" data-aos-delay="100">
        <h1 class="blog-title">Chia sẻ kiến thức</h1>
        <p class="blog-description col-md-8 ms-auto">MedXtore cung cấp những bài viết hữu ích về sức khỏe, vitamin và dinh dưỡng để bạn chăm sóc bản thân và gia đình tốt hơn mỗi ngày.</p>
      </div>
    </div>
  </section>
  
  <!-- Search and Filters -->
  <div class="container mt-n5 position-relative">
    <div class="row">
    <!-- Search Box -->
<div class="container mt-4">
    <form class="row g-3" method="GET" action="blog.php">
        <div class="col-md-10">
            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm bài viết..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Tìm kiếm</button>
        </div>
    </form>
</div>
    
<!-- Filters -->
<div class="blog-filter-container" data-aos="fade-up" data-aos-delay="200">
    <h5 class="mb-3">Danh mục bài viết</h5>
    <div class="d-flex flex-wrap">
        <a href="blog.php" class="blog-filter-btn <?= empty($selectedCategory) ? 'active' : '' ?>">Tất cả</a>
        <?php foreach ($categories as $category): ?>
            <a href="blog.php?category=<?= urlencode($category['id']) ?>" 
               class="blog-filter-btn <?= $selectedCategory == $category['id'] ? 'active' : '' ?>">
                <?= htmlspecialchars($category['ten_loai']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>



<!-- Blog Cards -->
<section class="container py-4">
    <h3 class="mb-4" data-aos="fade-up">Bài viết mới nhất</h3>
    <div class="row g-4">
        <?php if (!empty($baiviet)): ?>
            <?php foreach ($baiviet as $index => $bv): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= 100 + ($index * 100) ?>">
                    <div class="blog-card">
                        <div class="blog-card-img-wrapper">
                            <img src="../assets/images/blog/<?= htmlspecialchars($bv['hinhanh']) ?>" 
                                 class="blog-card-img" 
                                 alt="<?= htmlspecialchars($bv['tieude']) ?>">
                            <div class="blog-card-overlay"></div>
                            <span class="blog-category text-primary"><?= htmlspecialchars($bv['ten_loai']) ?></span>
                        </div>

                        <div class="blog-card-body">
                            <span class="blog-card-date">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= date('d/m/Y', strtotime($bv['ngay_dang'])) ?>
                            </span>

                            <h5 class="blog-card-title"><?= htmlspecialchars($bv['tieude']) ?></h5>

                            <p class="blog-card-text">
                                <?= mb_strimwidth(strip_tags($bv['tomtat']), 0, 100, '...') ?>
                            </p>

                            <a href="blog-detail.php?slug=<?= urlencode($bv['slug']) ?>" class="btn btn-primary">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="no-results" data-aos="fade-up">
                    <i class="bi bi-journal-x no-results-icon"></i>
                    <h4>Chưa có bài viết</h4>
                    <p class="text-muted">Chưa có bài viết nào được đăng. Vui lòng quay lại sau.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>    
   <!-- Pagination -->
<nav class="mt-5" data-aos="fade-up">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($selectedCategory) ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($selectedCategory) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($selectedCategory) ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
  </section>
  
  <!-- Newsletter Section -->
  <section class="container" data-aos="fade-up">
    <div class="newsletter-section">
      <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0">
          <h3>Đăng ký nhận tin</h3>
          <p class="text-muted">Nhận thông tin về các bài viết mới nhất và lời khuyên sức khỏe hàng tuần.</p>
        </div>
        <div class="col-lg-6">
          <div class="input-group">
            <input type="email" class="newsletter-input" placeholder="Email của bạn">
            <button class="btn newsletter-btn" type="button">Đăng ký</button>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php include('../includes/footer.php'); ?>

 <!-- jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Popper JS -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- AOS Animation Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS animation library
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });

    // Initialize Bootstrap dropdowns
    const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    const dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // Ensure dropdowns work on hover (optional)
    document.querySelectorAll('.dropdown').forEach(function(dropdown) {
        let timeoutId;

        // Khi hover vào dropdown
        dropdown.addEventListener('mouseenter', function() {
            clearTimeout(timeoutId);
            const dropdownToggle = this.querySelector('.dropdown-toggle');
            const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggle) 
                || new bootstrap.Dropdown(dropdownToggle);
            dropdownInstance.show();
        });

        // Khi hover ra khỏi dropdown
        dropdown.addEventListener('mouseleave', function() {
            const dropdownToggle = this.querySelector('.dropdown-toggle');
            const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggle);
            
            timeoutId = setTimeout(() => {
                if (dropdownInstance) {
                    dropdownInstance.hide();
                }
            }, 200); // Delay 200ms trước khi ẩn
        });

        // Khi click vào toggle
        dropdown.querySelector('.dropdown-toggle').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdownInstance = bootstrap.Dropdown.getInstance(this) 
                || new bootstrap.Dropdown(this);
            dropdownInstance.toggle();
        });

        // Giữ dropdown menu mở khi hover vào menu
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');
        dropdownMenu.addEventListener('mouseenter', function() {
            clearTimeout(timeoutId);
        });

        dropdownMenu.addEventListener('mouseleave', function() {
            const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
            const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggle);
            
            timeoutId = setTimeout(() => {
                if (dropdownInstance) {
                    dropdownInstance.hide();
                }
            }, 200);
        });
    });

    // Filter buttons functionality (chỉ giữ một phiên bản)
    const filterButtons = document.querySelectorAll('.blog-filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
        });
    });
});

// Đảm bảo dropdown hoạt động khi click
document.querySelectorAll('.dropdown-toggle').forEach(function(element) {
    element.addEventListener('click', function(e) {
        e.preventDefault();
        const dropdownInstance = bootstrap.Dropdown.getInstance(this);
        if (dropdownInstance) {
            dropdownInstance.toggle();
        } else {
            new bootstrap.Dropdown(this).toggle();
        }
    });
});
</script>


