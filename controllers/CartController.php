<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();

// Nếu chưa đăng nhập thì chuyển hướng
if (!isset($_SESSION['user_id'])) {
  header("Location: /MedXtorePharmacy/pages/login.php");
  exit;
}

$userId = $_SESSION['user_id'];

// Khởi tạo giỏ hàng riêng cho mỗi user
if (!isset($_SESSION['carts'])) {
  $_SESSION['carts'] = [];
}
if (!isset($_SESSION['carts'][$userId])) {
  $_SESSION['carts'][$userId] = [];
}

// Dùng biến $cart để thao tác dễ
$cart = &$_SESSION['carts'][$userId];

// =======================
//1️⃣ THÊM VÀO GIỎ HÀNG
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['thuoc_id'])) {
  $thuoc_id = $_POST['thuoc_id'];
  $ten_thuoc = $_POST['ten_thuoc'] ?? 'Không rõ';
  $hinhanh = $_POST['hinhanh'] ?? 'default.png';
  $donvi_id = $_POST['donvi_id'] ?? 0;
  $ten_donvi = $_POST['ten_donvi'] ?? '';
  $gia = (float)$_POST['gia'];
  $soluong = max(1, (int)$_POST['soluong']);

  $key = $thuoc_id . '_' . $donvi_id;

  if (isset($cart[$key])) {
    $cart[$key]['soluong'] += $soluong;
  } else {
    $cart[$key] = [
      'thuoc_id' => $thuoc_id,
      'ten_thuoc' => $ten_thuoc,
      'hinhanh' => $hinhanh,
      'donvi_id' => $donvi_id,
      'ten_donvi' => $ten_donvi,
      'gia' => $gia,
      'soluong' => $soluong
    ];
  }

  $cart[$key]['tongtien'] = $cart[$key]['soluong'] * $gia;

  header("Location: /MedXtorePharmacy/pages/cart.php");
  exit;
}

// =======================
// 2️⃣ CẬP NHẬT SỐ LƯỢNG
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
  $key = $_POST['key'];
  $soluong = max(1, (int)$_POST['soluong']);

  if (isset($cart[$key])) {
    $cart[$key]['soluong'] = $soluong;
    $cart[$key]['tongtien'] = $soluong * $cart[$key]['gia'];
  }

  header("Location: /MedXtorePharmacy/pages/cart.php");
  exit;
}

// =======================
// 3️⃣ XOÁ MỘT SẢN PHẨM
// =======================
if (isset($_GET['delete'])) {
  $key = $_GET['delete'];
  unset($cart[$key]);
  header("Location: /MedXtorePharmacy/pages/cart.php");
  exit;
}

// =======================
// 4️⃣ XOÁ TOÀN BỘ GIỎ HÀNG
// =======================
if (isset($_GET['clear']) && $_GET['clear'] === 'all') {
  unset($_SESSION['carts'][$userId]);
  header("Location: /MedXtorePharmacy/pages/cart.php");
  exit;
}

// =======================
// 5️⃣ CHUYỂN SANG CHECKOUT
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
  if (empty($cart)) {
    header("Location: /MedXtorePharmacy/pages/cart.php");
    exit;
  }

  // Tính tổng đơn
  $_SESSION['checkout_summary'] = [
    'cart' => $cart,
    'tongtien' => array_sum(array_column($cart, 'tongtien'))
  ];

  header("Location: /MedXtorePharmacy/pages/checkout.php");
  exit;
}
