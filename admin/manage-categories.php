<?php 
require_once __DIR__ . '/../controllers/CategoryController.php';
$pageTitle = 'Quản lý loại thuốc';
include_once('../includes/ad-header.php'); 
include_once('../includes/ad-sidebar.php');

$controller = new CategoryController();
$categories = $controller->index();

// Xử lý thêm loại thuốc mới
$addMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $result = $controller->create($_POST);
    if ($result['success']) {
        $addMessage = '<div class="alert alert-success">Thêm loại thuốc thành công!</div>';
        // Refresh data
        $categories = $controller->index();
    } else {
        $addMessage = '<div class="alert alert-danger">' . $result['message'] . '</div>';
    }
}

// Xử lý cập nhật loại thuốc
$updateMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $result = $controller->update($id, $_POST);
    if ($result['success']) {
        $updateMessage = '<div class="alert alert-success">Cập nhật loại thuốc thành công!</div>';
        // Refresh data
        $categories = $controller->index();
    } else {
        $updateMessage = '<div class="alert alert-danger">' . $result['message'] . '</div>';
    }
}

// Xử lý xóa loại thuốc
$deleteMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $id = $_POST['category_id'];
    $result = $controller->delete($id);
    if ($result['success']) {
        $deleteMessage = '<div class="alert alert-success">Xóa loại thuốc thành công!</div>';
        // Refresh data
        $categories = $controller->index();
    } else {
        $deleteMessage = '<div class="alert alert-danger">' . $result['message'] . '</div>';
    }
}
?>

<!-- Main Content -->
<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Quản Lý Loại Thuốc</h3>
    <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
      <i class="fas fa-plus me-2"></i>Thêm Loại Thuốc
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
            <th scope="col">Tên Loại Thuốc</th>
            <th scope="col" class="text-end" width="150">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($categories)): ?>
            <tr>
              <td colspan="3" class="text-center py-3">Chưa có loại thuốc nào</td>
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
        <h5 class="modal-title" id="addCategoryLabel">Thêm Loại Thuốc Mới</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tên loại thuốc</label>
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
        <h5 class="modal-title" id="editCategoryLabel">Sửa Loại Thuốc</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tên loại thuốc</label>
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
        <p>Bạn có chắc chắn muốn xóa loại thuốc <strong id="delete_category_name"></strong>?</p>
        <p class="text-danger"><small>Lưu ý: Bạn không thể xóa loại thuốc đang được sử dụng.</small></p>
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
