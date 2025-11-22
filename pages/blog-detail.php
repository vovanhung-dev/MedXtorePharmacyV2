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
    
    body {
      font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
      line-height: 1.7;
      background-color: #fbfbfb;
    }
    
    /* Banner Styles */
    .blog-banner {
      background: linear-gradient(135deg, rgba(13, 110, 253, 0.05), rgba(13, 110, 253, 0.2));
      position: relative;
      overflow: hidden;
      padding: 6rem 0 4rem;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    
    .blog-banner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cpath fill='%230d6efd' fill-opacity='0.05' d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z'%3E%3C/path%3E%3C/svg%3E") repeat;
      opacity: 0.5;
      z-index: 0;
    }
    
    .blog-banner .container {
      position: relative;
      z-index: 1;
    }
    
    .blog-title {
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--dark-color);
      text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);
      background: linear-gradient(to right, var(--primary-color), #4d94ff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 1rem;
      transition: transform 0.3s ease;
    }
    
    .blog-title:hover {
      transform: translateY(-3px);
    }
    
    .blog-date {
      background-color: white;
      border-radius: 30px;
      padding: 0.5rem 1rem;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
    }
    
    .blog-date:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    /* Featured Image */
    .blog-image-container {
      position: relative;
      margin-bottom: 2.5rem;
      border-radius: 12px;
      overflow: hidden;
      transition: transform 0.5s ease;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
    }
    
    .blog-image-container::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to bottom, transparent 70%, rgba(0, 0, 0, 0.15));
      z-index: 1;
      opacity: 0;
      transition: opacity 0.5s ease;
    }
    
    .blog-image-container:hover::after {
      opacity: 1;
    }
    
    .blog-image-container:hover {
      transform: translateY(-5px);
    }
    
    .blog-image {
      width: 100%;
      height: 450px;
      object-fit: cover;
      transition: transform 8s ease;
    }
    
    .blog-image-container:hover .blog-image {
      transform: scale(1.05);
    }
    
    /* Blog Summary */
    .blog-summary {
      background-color: white;
      border-left: 4px solid var(--primary-color);
      padding: 1.5rem;
      margin-bottom: 2.5rem;
      font-size: 1.1rem;
      color: var(--secondary-color);
      box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
      border-radius: 0 8px 8px 0;
      position: relative;
      overflow: hidden;
      z-index: 1;
    }
    
    .blog-summary::before {
      content: "";
      position: absolute;
      top: -20px;
      left: 10px;
      font-size: 8rem;
      font-family: serif;
      color: rgba(13, 110, 253, 0.05);
      z-index: -1;
    }
    
    /* Blog Content */
    .blog-content {
      background-color: white;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.05);
      position: relative;
    }
    
    .blog-content::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(to right, var(--primary-color), rgba(13, 110, 253, 0.5));
      border-radius: 12px 12px 0 0;
    }
    
    .blog-content h2, 
    .blog-content h3 {
      margin-top: 2.5rem;
      margin-bottom: 1.25rem;
      font-weight: 700;
      position: relative;
      padding-bottom: 0.75rem;
    }
    
    .blog-content h2 {
      color: var(--primary-color);
      font-size: 1.75rem;
    }
    
    .blog-content h3 {
      color: #3f87f5;
      font-size: 1.4rem;
    }
    
    .blog-content h2::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 60px;
      height: 3px;
      background-color: var(--primary-color);
      border-radius: 3px;
    }
    
    .blog-content p {
      margin-bottom: 1.5rem;
      font-size: 1.05rem;
      line-height: 1.8;
      color: #444;
      text-align: justify;
    }
    
    .blog-content a {
      color: var(--primary-color);
      text-decoration: none;
      border-bottom: 1px dashed var(--primary-color);
      transition: all 0.3s ease;
    }
    
    .blog-content a:hover {
      border-bottom: 1px solid var(--primary-color);
      padding-bottom: 2px;
    }
    
    .blog-content img {
      max-width: 100%;
      height: auto;
      border-radius: 8px;
      margin: 1.5rem 0;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }
    
    .blog-content img:hover {
      transform: scale(1.01);
    }
    
    .blog-content ul, 
    .blog-content ol {
      margin-bottom: 1.5rem;
      padding-left: 1.5rem;
    }
    
    .blog-content li {
      margin-bottom: 0.5rem;
    }
    
    .blog-content blockquote {
      border-left: 3px solid var(--primary-color);
      padding: 1rem 1.5rem;
      margin: 1.5rem 0;
      background-color: rgba(13, 110, 253, 0.05);
      font-style: italic;
      color: #555;
      border-radius: 0 5px 5px 0;
    }
    
    /* Back Button */
    .back-button {
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
      padding: 0.75rem 1.5rem;
      z-index: 1;
    }
    
    .back-button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background-color: var(--primary-color);
      transition: all 0.4s ease;
      z-index: -1;
    }
    
    .back-button:hover {
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
    }
    
    .back-button:hover::before {
      left: 0;
    }
    
    .back-button i {
      transition: transform 0.3s ease;
    }
    
    .back-button:hover i {
      transform: translateX(-5px);
    }
    
    /* Related Articles Section */
    .related-articles {
      margin-top: 4rem;
      padding-top: 2rem;
      border-top: 1px solid #eee;
    }
    
    .related-article-card {
      transition: all 0.3s ease;
      overflow: hidden;
      border: none;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .related-article-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .related-article-img {
      height: 180px;
      object-fit: cover;
      transition: transform 3s ease;
    }
    
    .related-article-card:hover .related-article-img {
      transform: scale(1.1);
    }
    
    /* Share Buttons */
    .share-section {
      margin-top: 2rem;
      padding: 1rem;
      border-radius: 10px;
      background-color: #f8f9fa;
    }
    
    .share-buttons {
      display: flex;
      gap: 0.5rem;
    }
    
    .share-btn {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background-color: white;
      color: var(--primary-color);
      transition: all 0.3s ease;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .share-btn:hover {
      transform: translateY(-3px);
      background-color: var(--primary-color);
      color: white;
      box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
    }
    
    /* Author Info */
    .author-box {
      display: flex;
      align-items: center;
      gap: 1rem;
      background-color: #f8f9fa;
      border-radius: 12px;
      padding: 1.5rem;
      margin-top: 3rem;
      box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
    }
    
    .author-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid white;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    
    .author-bio {
      flex: 1;
    }
    
    /* Reading Progress Bar */
    .reading-progress {
      position: fixed;
      top: 0;
      left: 0;
      height: 5px;
      width: 0;
      background-color: var(--primary-color);
      z-index: 9999;
      transition: width 0.1s ease;
    }
    
    /* Table of Contents */
    .toc {
      background-color: #f8f9fa;
      border-radius: 10px;
      padding: 1.5rem;
      margin-bottom: 2rem;
      border-left: 4px solid var(--primary-color);
      box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
    }
    
    .toc-list {
      list-style-type: none;
      padding-left: 0;
    }
    
    .toc-item {
      margin-bottom: 0.75rem;
    }
    
    .toc-link {
      display: flex;
      align-items: center;
      color: #444;
      text-decoration: none;
      padding: 0.5rem 0;
      transition: all 0.3s ease;
    }
    
    .toc-link:hover {
      color: var(--primary-color);
      transform: translateX(5px);
    }
    
    .toc-link i {
      margin-right: 0.5rem;
      font-size: 0.8rem;
    }
    
    /* Responsive Styles */
    @media (max-width: 992px) {
      .blog-title {
        font-size: 2rem;
      }
      
      .blog-image {
        height: 350px;
      }
    }
    
    @media (max-width: 768px) {
      .blog-title {
        font-size: 1.75rem;
      }
      
      .blog-content {
        padding: 1.5rem;
      }
      
      .blog-banner {
        padding: 4rem 0 2rem;
      }
      
      .blog-image {
        height: 250px;
      }
      
      .author-box {
        flex-direction: column;
        text-align: center;
      }
    }
  </style>

 <!-- Reading Progress Bar -->
 <div class="reading-progress" id="reading-progress"></div>

<!-- Banner -->
<section class="blog-banner">
    <div class="container text-center">
        <h1 class="blog-title"><?= htmlspecialchars($baiviet['tieude']) ?></h1>
        <p class="blog-date">
            <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($baiviet['ngay_dang'])) ?> |
            <i class="bi bi-person"></i> <?= htmlspecialchars($baiviet['tac_gia']) ?> |
            <i class="bi bi-tag"></i> <?= htmlspecialchars($baiviet['ten_loai'] ?? 'Không có') ?>
        </p>
    </div>
</section>
<!-- Nội dung bài viết -->
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="blog-image-container">
                <img src="../assets/images/blog/<?= htmlspecialchars($baiviet['hinhanh']) ?>" alt="<?= htmlspecialchars($baiviet['tieude']) ?>" class="blog-image">
            </div>
            <div class="blog-content">
                <?= $baiviet['noidung'] ?>
            </div>
        </div>
    </div>
</section>
      <!-- Table of Contents -->
      <div class="toc" data-aos="fade-up" data-aos-delay="150">
        <h4 class="mb-3"><i class="bi bi-list-ul me-2"></i>Mục lục</h4>
        <ul class="toc-list" id="toc-list">
          <!-- Mục lục sẽ được tạo bằng JavaScript -->
        </ul>
      </div>

      <!-- Tóm tắt -->
      <div class="blog-summary" data-aos="fade-up" data-aos-delay="200">
        <?= nl2br(htmlspecialchars($baiviet['tomtat'])) ?>
      </div>

      <!-- Nội dung -->
      <div class="blog-content fs-6 lh-lg" data-aos="fade-up" data-aos-delay="250">
        <?= $baiviet['noidung'] ?>
        
        <!-- Share Section -->
        <div class="share-section mt-5" data-aos="fade-up">
          <h5 class="mb-3"><i class="bi bi-share me-2"></i>Chia sẻ bài viết</h5>
          <div class="share-buttons">
            <a href="#" class="share-btn"><i class="bi bi-facebook"></i></a>
            <a href="#" class="share-btn"><i class="bi bi-twitter"></i></a>
            <a href="#" class="share-btn"><i class="bi bi-linkedin"></i></a>
            <a href="#" class="share-btn"><i class="bi bi-pinterest"></i></a>
            <a href="#" class="share-btn"><i class="bi bi-envelope"></i></a>
          </div>
        </div>
        
      
     <!-- Related Articles -->
<div class="related-articles mt-5">
    <h4 class="mb-4">Bài viết liên quan</h4>
    <div class="row g-4">
        <?php if (!empty($relatedPosts)): ?>
            <?php foreach ($relatedPosts as $post): ?>
                <div class="col-md-4">
                    <div class="card related-article-card">
                        <img src="../assets/images/blog/<?= htmlspecialchars($post['hinhanh']) ?>" 
                             class="card-img-top related-article-img" 
                             alt="<?= htmlspecialchars($post['tieude']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($post['tieude']) ?></h5>
                            <p class="card-text"><?= mb_strimwidth(strip_tags($post['tomtat']), 0, 100, '...') ?></p>
                            <a href="blog-detail.php?slug=<?= urlencode($post['slug']) ?>" class="btn btn-primary">Đọc thêm</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Không có bài viết liên quan.</p>
        <?php endif; ?>
    </div>
</div>
      <!-- Quay lại -->
      <div class="mt-5 text-end" data-aos="fade-up">
        <a href="blog.php" class="btn btn-outline-primary rounded-pill back-button">
          <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách bài viết
        </a>
      </div>

    </div>
  </div>
</section>
<?php include('../includes/footer.php'); ?>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AOS Animation Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

<script>
  // Initialize AOS animation library
  AOS.init({
    duration: 800,
    once: true,
    offset: 100
  });
  
  // Reading progress bar
  document.addEventListener('scroll', function() {
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight - windowHeight;
    const scrollTop = window.scrollY;
    const progress = (scrollTop / documentHeight) * 100;
    
    document.getElementById('reading-progress').style.width = progress + '%';
  });
  
  // Generate Table of Contents
  document.addEventListener('DOMContentLoaded', function() {
    const blogContent = document.querySelector('.blog-content');
    const tocList = document.getElementById('toc-list');
    
    // Find all headings in blog content
    const headings = blogContent.querySelectorAll('h2, h3');
    
    // Generate TOC based on headings
    headings.forEach((heading, index) => {
      // Add ID to heading if not exists
      if (!heading.id) {
        heading.id = 'heading-' + index;
      }
      
      const listItem = document.createElement('li');
      listItem.className = 'toc-item';
      
      const link = document.createElement('a');
      link.href = '#' + heading.id;
      link.className = 'toc-link';
      
      // Add icon and indent based on heading level
      if (heading.tagName === 'H2') {
        link.innerHTML = `<i class="bi bi-circle-fill"></i>${heading.textContent}`;
      } else {
        link.innerHTML = `<i class="bi bi-dash"></i>${heading.textContent}`;
        link.style.paddingLeft = '1rem';
      }
      
      listItem.appendChild(link);
      tocList.appendChild(listItem);
    });
    
    // Show/hide TOC based on content
    if (headings.length === 0) {
      document.querySelector('.toc').style.display = 'none';
    }
  });
  
  // Image Zoom Effect
  const blogImages = document.querySelectorAll('.blog-content img');
  blogImages.forEach(img => {
    img.addEventListener('click', () => {
      img.classList.toggle('zoomed');
      if (img.classList.contains('zoomed')) {
        img.style.transform = 'scale(1.5)';
        img.style.cursor = 'zoom-out';
        img.style.zIndex = '999';
        img.style.transition = 'transform 0.3s ease';
      } else {
        img.style.transform = 'scale(1)';
        img.style.cursor = 'zoom-in';
        img.style.zIndex = '1';
      }
    });
  });
  
  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      
      const targetId = this.getAttribute('href');
      if (targetId === '#') return;
      
      const targetElement = document.querySelector(targetId);
      if (targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 100,
          behavior: 'smooth'
        });
      }
    });
  });
</script>


