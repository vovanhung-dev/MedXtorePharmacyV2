<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Quản lý thể loại bài viết';
include_once('../includes/ad-header.php');
include_once('../includes/ad-sidebar.php');

$db = new Database();
$conn = $db->getConnection();

// Lấy danh sách thể loại bài viết
$stmt = $conn->query("SELECT * FROM loai_baiviet ORDER BY id DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý thêm thể loại bài viết
$addMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $tenLoai = trim($_POST['ten_loai']);
    if (!empty($tenLoai)) {
        $stmt = $conn->prepare("INSERT INTO loai_baiviet (ten_loai) VALUES (?)");
        if ($stmt->execute([$tenLoai])) {
            $addMessage = '<div class="alert alert-success">Thêm thể loại bài viết thành công!</div>';
            // Refresh danh sách
            $stmt = $conn->query("SELECT * FROM loai_baiviet ORDER BY id DESC");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $addMessage = '<div class="alert alert-danger">Lỗi khi thêm thể loại bài viết.</div>';
        }
    } else {
        $addMessage = '<div class="alert alert-danger">Vui lòng nhập tên thể loại bài viết.</div>';
    }
}

// Xử lý cập nhật thể loại bài viết
$updateMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $tenLoai = trim($_POST['ten_loai']);
    if (!empty($tenLoai)) {
        $stmt = $conn->prepare("UPDATE loai_baiviet SET ten_loai = ? WHERE id = ?");
        if ($stmt->execute([$tenLoai, $id])) {
            $updateMessage = '<div class="alert alert-success">Cập nhật thể loại bài viết thành công!</div>';
            // Refresh danh sách
            $stmt = $conn->query("SELECT * FROM loai_baiviet ORDER BY id DESC");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $updateMessage = '<div class="alert alert-danger">Lỗi khi cập nhật thể loại bài viết.</div>';
        }
    } else {
        $updateMessage = '<div class="alert alert-danger">Vui lòng nhập tên thể loại bài viết.</div>';
    }
}

// Xử lý xóa thể loại bài viết
$deleteMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $id = $_POST['category_id'];
    $stmt = $conn->prepare("DELETE FROM loai_baiviet WHERE id = ?");
    if ($stmt->execute([$id])) {
        $deleteMessage = '<div class="alert alert-success">Xóa thể loại bài viết thành công!</div>';
        // Refresh danh sách
        $stmt = $conn->query("SELECT * FROM loai_baiviet ORDER BY id DESC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $deleteMessage = '<div class="alert alert-danger">Lỗi khi xóa thể loại bài viết.</div>';
    }
}
?>

<!-- Main Content -->
<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Quản Lý Thể Loại Bài Viết</h3>
    <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
      <i class="fas fa-plus me-2"></i>Thêm Thể Loại
    </button>
  </div>

  <!-- Messages -->
  <?= $addMessage ?>
  <?= $updateMessage ?>
  <?= $deleteMessage ?>

  <!-- Category Table -->
  <div class="card shadow-sm fade-in">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col" width="80">ID</th>
            <th scope="col">Tên Thể Loại</th>
            <th scope="col" class="text-end" width="150">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($categories)): ?>
            <tr>
              <td colspan="3" class="text-center py-3">Chưa có thể loại bài viết nào</td>
            </tr>
          <?php else: ?>
            <?php foreach ($categories as $category): ?>
              <tr>
                <td><?= $category['id'] ?></td>
                <td><?= htmlspecialchars($category['ten_loai']) ?></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-secondary edit-btn" 
                          data-id="<?= $category['id'] ?>" 
                          data-name="<?= htmlspecialchars($category['ten_loai']) ?>"
                          data-bs-toggle="modal" 
                          data-bs-target="#editCategoryModal">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger delete-btn" 
                          data-id="<?= $category['id'] ?>" 
                          data-name="<?= htmlspecialchars($category['ten_loai']) ?>"
                          data-bs-toggle="modal" 
                          data-bs-target="#deleteCategoryModal">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addCategoryLabel">Thêm Thể Loại Bài Viết</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tên thể loại</label>
            <input type="text" class="form-control" name="ten_loai" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" name="add_category" class="btn btn-primary">Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editCategoryLabel">Sửa Thể Loại Bài Viết</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tên thể loại</label>
            <input type="text" class="form-control" name="ten_loai" id="edit_ten_loai" required>
            <input type="hidden" name="category_id" id="edit_category_id">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" name="update_category" class="btn btn-primary">Cập nhật</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteCategoryLabel">Xác nhận xóa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Bạn có chắc chắn muốn xóa thể loại bài viết <strong id="delete_category_name"></strong>?</p>
      </div>
      <form method="post" action="">
        <input type="hidden" name="category_id" id="delete_category_id">
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" name="delete_category" class="btn btn-danger">Xóa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Xử lý form chỉnh sửa
document.querySelectorAll('.edit-btn').forEach(button => {
  button.addEventListener('click', function() {
    const categoryId = this.dataset.id;
    const categoryName = this.dataset.name;
    
    document.getElementById('edit_category_id').value = categoryId;
    document.getElementById('edit_ten_loai').value = categoryName;
  });
});

// Xử lý form xóa
document.querySelectorAll('.delete-btn').forEach(button => {
  button.addEventListener('click', function() {
    const categoryId = this.dataset.id;
    const categoryName = this.dataset.name;
    
    document.getElementById('delete_category_id').value = categoryId;
    document.getElementById('delete_category_name').textContent = categoryName;
  });
});
</script>

<?php include_once('../includes/ad-footer.php'); ?>