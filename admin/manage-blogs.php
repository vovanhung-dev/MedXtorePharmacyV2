<?php include_once('../includes/ad-header.php'); ?>
<?php include_once('../includes/ad-sidebar.php'); ?>
<?php
require_once '../config/database.php';
require_once '../controllers/BlogController.php';

$db = new Database();
$conn = $db->getConnection();

// Lấy danh sách thể loại bài viết
$loaiStmt = $conn->query("SELECT id, ten_loai FROM loai_baiviet");
$dsLoaiBaiViet = $loaiStmt->fetchAll(PDO::FETCH_ASSOC);

$blogController = new BlogController();
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$blogs = $blogController->filter($search, $category);
?>

<!-- Main Content -->
<div class="main-content">

<?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($_GET['success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
  </div>
<?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Quản Lý Bài Viết</h3>
    <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#addBlogModal">
      <i class="fas fa-plus me-2"></i>Thêm Bài Viết
    </button>
  </div>

  <!-- Search -->
<div class="card p-4 shadow-sm mb-4">
    <form class="row g-3" method="GET">
        <div class="col-md-8">
            <input type="text" name="search" class="form-control" placeholder="Tìm theo tiêu đề bài viết..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($dsLoaiBaiViet as $loai): ?>
                    <option value="<?= $loai['id'] ?>" <?= isset($_GET['category']) && $_GET['category'] == $loai['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loai['ten_loai']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-outline-primary w-100">Tìm kiếm</button>
        </div>
    </form>
</div>
  <!-- Blog Table -->
<div class="card shadow-sm fade-in">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Hình ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Thể loại</th> <!-- Thêm cột Thể loại -->
                    <th>Tóm tắt</th>
                    <th>Ngày đăng</th>
                    <th class="text-end">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blogs as $index => $blog): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <img src="../assets/images/blog/<?= htmlspecialchars($blog['hinhanh']) ?>"
                                 alt="<?= htmlspecialchars($blog['tieude']) ?>"
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; background: #f8f8f8;">
                        </td>
                        <td><?= htmlspecialchars($blog['tieude']) ?></td>
                        <td>
                            <?php
                            // Hiển thị tên thể loại
                            $loai = array_filter($dsLoaiBaiViet, function ($item) use ($blog) {
                                return $item['id'] == $blog['loai_id'];
                            });
                            echo htmlspecialchars(reset($loai)['ten_loai'] ?? 'Không có');
                            ?>
                        </td>
                        <td><?= htmlspecialchars(mb_strimwidth(strip_tags($blog['tomtat']), 0, 60, '...')) ?></td>
                        <td><?= date('d/m/Y', strtotime($blog['ngay_dang'])) ?></td>
                        <td class="text-end">
                            <!-- Nút Sửa -->
                            <button 
                                class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal"
                                data-bs-target="#editBlogModal"
                                data-id="<?= $blog['id'] ?>"
                                data-tieude="<?= htmlspecialchars($blog['tieude']) ?>"
                                data-tomtat="<?= htmlspecialchars($blog['tomtat']) ?>"
                                data-noidung="<?= htmlspecialchars($blog['noidung']) ?>"
                                data-hinhanh="<?= htmlspecialchars($blog['hinhanh']) ?>"
                                data-loai-id="<?= $blog['loai_id'] ?>">
                                <i class="fas fa-edit"></i> Sửa
                            </button>

                            <!-- Nút Xóa -->
                            <form action="delete-blog.php" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa bài viết này không?');">
                                <input type="hidden" name="blog_id" value="<?= $blog['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
  <!-- Add Blog Modal -->
  <div class="modal fade" id="addBlogModal" tabindex="-1" aria-labelledby="addBlogLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="add-blog.php" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBlogLabel">Thêm Bài Viết Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" name="tieude" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tóm tắt</label>
                        <textarea name="tomtat" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nội dung</label>
                        <textarea name="noidung" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
    <label class="form-label">Thể loại</label>
    <select name="loai_id" class="form-select" required>
        <option value="">-- Chọn thể loại --</option>
        <?php foreach ($dsLoaiBaiViet as $loai): ?>
            <option value="<?= $loai['id'] ?>"><?= htmlspecialchars($loai['ten_loai']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
                    <div class="mb-3">
                        <label class="form-label">Hình ảnh</label>
                        <input type="file" name="hinhanh" class="form-control" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Lưu bài viết</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </form>
    </div>
  </div>

  <!-- Edit Blog Modal -->
  <div class="modal fade" id="editBlogModal" tabindex="-1" aria-labelledby="editBlogLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="edit-blog.php" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBlogLabel">Cập Nhật Bài Viết</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="blog_id" id="edit-id">
                    <input type="hidden" name="hinhanh_cu" id="edit-hinhanh-cu">
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" name="tieude" id="edit-tieude" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tóm tắt</label>
                        <textarea name="tomtat" id="edit-tomtat" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nội dung</label>
                        <textarea name="noidung" id="edit-noidung" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
    <label class="form-label">Thể loại</label>
    <select name="loai_id" id="edit-loai-id" class="form-select" required>
        <option value="">-- Chọn thể loại --</option>
        <?php foreach ($dsLoaiBaiViet as $loai): ?>
            <option value="<?= $loai['id'] ?>"><?= htmlspecialchars($loai['ten_loai']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
                    <div class="mb-3">
                        <label class="form-label">Hình ảnh (nếu muốn thay)</label>
                        <input type="file" name="hinhanh" class="form-control" accept="image/*">
                        <div class="mt-2">
                            <label class="form-label">Hình ảnh hiện tại</label><br>
                            <img id="current-image-preview" src="" class="img-thumbnail" style="max-height: 120px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Lưu thay đổi</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                </div>
            </div>
        </form>
    </div>
  </div>
</div>

<script>
const editModal = document.getElementById('editBlogModal');
editModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;

    const id = button.getAttribute('data-id');
    const tieude = button.getAttribute('data-tieude');
    const tomtat = button.getAttribute('data-tomtat');
    const noidung = button.getAttribute('data-noidung');
    const hinhanh = button.getAttribute('data-hinhanh');
    const loaiId = button.getAttribute('data-loai-id');

    document.getElementById('edit-id').value = id;
    document.getElementById('edit-tieude').value = tieude;
    document.getElementById('edit-tomtat').value = tomtat;
    document.getElementById('edit-noidung').value = noidung;
    document.getElementById('edit-hinhanh-cu').value = hinhanh;

    const selectLoai = document.getElementById('edit-loai-id');
    selectLoai.value = loaiId;

    const preview = document.getElementById('current-image-preview');
    if (preview && hinhanh) {
        preview.src = '../assets/images/blog/' + hinhanh;
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>