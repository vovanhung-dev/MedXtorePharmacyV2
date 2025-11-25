<?php
session_start();

// Kiểm tra xem có thông báo thành công không
if (!isset($_SESSION['success'])) {
    header("Location: /pages/cart.php");
    exit();
}

$successMessage = $_SESSION['success'];
unset($_SESSION['success']); // Xóa thông báo sau khi hiển thị
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - MedXtore Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            text-align: center;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .success-icon {
            color: #28a745;
            font-size: 5rem;
            margin-bottom: 20px;
        }
        .success-message {
            color: #28a745;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .action-buttons {
            margin-top: 30px;
        }
        .action-buttons .btn {
            margin: 0 10px;
        }
        body {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="success-container">
            <i class="fas fa-check-circle success-icon"></i>
            <h2 class="success-message"><?php echo htmlspecialchars($successMessage); ?></h2>
            <p class="lead">Đơn hàng của bạn đã được xác nhận và đang được xử lý.</p>
            <div class="action-buttons">
                <a href="/pages/order-history.php" class="btn btn-primary">
                    <i class="fas fa-list-ul"></i> Xem đơn hàng
                </a>
                <a href="/pages/home.php" class="btn btn-success">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 