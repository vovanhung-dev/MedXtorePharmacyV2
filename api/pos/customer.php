<?php
/**
 * POS Customer API
 * Handle customer operations for POS
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'search') {
            $keyword = $_GET['keyword'] ?? $_GET['term'] ?? '';

            if (empty($keyword)) {
                echo json_encode(['success' => false, 'message' => 'Search keyword required']);
                exit();
            }

            // Search by phone or name
            $query = "SELECT id, ten_khachhang as ten, sodienthoai as sdt, email, diachi
                     FROM khachhang
                     WHERE sodienthoai LIKE ? OR ten_khachhang LIKE ?
                     LIMIT 10";

            $stmt = $conn->prepare($query);
            $searchTerm = "%$keyword%";
            $stmt->execute([$searchTerm, $searchTerm]);

            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($customers && count($customers) > 0) {
                echo json_encode([
                    'success' => true,
                    'data' => $customers
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy khách hàng'
                ]);
            }
        }

    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add new customer
        $ten = $_POST['ten'] ?? '';
        $sdt = $_POST['sdt'] ?? '';
        $email = $_POST['email'] ?? '';
        $diachi = $_POST['diachi'] ?? '';

        if (empty($ten) || empty($sdt)) {
            echo json_encode(['success' => false, 'message' => 'Name and phone are required']);
            exit();
        }

        // Check if phone already exists
        $checkQuery = "SELECT id FROM khachhang WHERE sodienthoai = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([$sdt]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Phone number already exists']);
            exit();
        }

        // Insert new customer
        $insertQuery = "INSERT INTO khachhang (ten_khachhang, sodienthoai, email, diachi, ngay_tao)
                       VALUES (?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($insertQuery);
        $stmt->execute([$ten, $sdt, $email, $diachi]);

        $customerId = $conn->lastInsertId();

        // Get customer data with aliases
        $stmt = $conn->prepare("SELECT id, ten_khachhang as ten, sodienthoai as sdt, email, diachi FROM khachhang WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Customer added successfully',
            'customer' => $customer
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
