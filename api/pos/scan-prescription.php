<?php
/**
 * POS Prescription Scanner API
 * Endpoint để scan và phân tích đơn thuốc bằng AI
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
require_once __DIR__ . '/../../models/PrescriptionScanner.php';
require_once __DIR__ . '/../../controllers/POSController.php';

try {
    $scanner = new PrescriptionScanner();
    $posController = new POSController();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check for JSON input first (for search_product action)
        $jsonInput = json_decode(file_get_contents('php://input'), true);
        $action = $jsonInput['action'] ?? $_POST['action'] ?? $_GET['action'] ?? 'scan';

        switch ($action) {
            case 'scan':
                // Handle file upload
                if (!isset($_FILES['prescription']) || $_FILES['prescription']['error'] !== UPLOAD_ERR_OK) {
                    $errorMessage = 'Không nhận được file';
                    if (isset($_FILES['prescription']['error'])) {
                        $uploadErrors = [
                            UPLOAD_ERR_INI_SIZE => 'File vượt quá giới hạn cho phép',
                            UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn form',
                            UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
                            UPLOAD_ERR_NO_FILE => 'Không có file nào được chọn',
                            UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
                            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file',
                            UPLOAD_ERR_EXTENSION => 'Extension chặn upload'
                        ];
                        $errorMessage = $uploadErrors[$_FILES['prescription']['error']] ?? $errorMessage;
                    }
                    echo json_encode(['success' => false, 'message' => $errorMessage]);
                    break;
                }

                // Scan the prescription
                $result = $scanner->scanPrescription($_FILES['prescription']);

                // Save to history if successful
                if ($result['success']) {
                    $scanner->saveScanHistory($result, $_SESSION['user_id']);
                }

                echo json_encode($result);
                break;

            case 'add_to_cart':
                // Add scanned medicines to cart
                $input = $jsonInput ?: json_decode(file_get_contents('php://input'), true);

                if (empty($input['medicines'])) {
                    echo json_encode(['success' => false, 'message' => 'Không có thuốc để thêm']);
                    break;
                }

                $addedCount = 0;
                $errors = [];

                foreach ($input['medicines'] as $medicine) {
                    $productId = $medicine['product_id'] ?? null;
                    $medicineName = $medicine['name'] ?? 'Unknown';

                    if (empty($productId)) {
                        $errors[] = $medicineName . ': Chưa chọn sản phẩm';
                        continue;
                    }

                    // Get product details with price and unit from database
                    $productQuery = "SELECT
                                        t.id as thuoc_id,
                                        t.ten_thuoc,
                                        t.hinhanh,
                                        td.donvi_id,
                                        td.gia,
                                        dv.ten_donvi
                                    FROM thuoc t
                                    JOIN thuoc_donvi td ON t.id = td.thuoc_id
                                    JOIN donvi dv ON td.donvi_id = dv.id
                                    WHERE t.id = ?
                                    ORDER BY td.gia ASC
                                    LIMIT 1";

                    $db = new Database();
                    $conn = $db->getConnection();
                    $stmt = $conn->prepare($productQuery);
                    $stmt->execute([$productId]);
                    $productData = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$productData) {
                        $errors[] = $medicineName . ': Không tìm thấy thông tin sản phẩm';
                        continue;
                    }

                    $quantity = intval($medicine['quantity'] ?? 1);
                    if ($quantity < 1) $quantity = 1;

                    $addResult = $posController->addToCart([
                        'thuoc_id' => $productData['thuoc_id'],
                        'donvi_id' => $productData['donvi_id'],
                        'gia' => $productData['gia'],
                        'soluong' => $quantity,
                        'ten_thuoc' => $productData['ten_thuoc'],
                        'ten_donvi' => $productData['ten_donvi'],
                        'hinhanh' => $productData['hinhanh']
                    ]);

                    if ($addResult['success']) {
                        $addedCount++;
                    } else {
                        $errors[] = $medicineName . ': ' . ($addResult['message'] ?? 'Lỗi thêm vào giỏ');
                    }
                }

                echo json_encode([
                    'success' => $addedCount > 0,
                    'message' => $addedCount > 0 ? "Đã thêm $addedCount thuốc vào giỏ hàng" : "Không thể thêm thuốc vào giỏ",
                    'added_count' => $addedCount,
                    'errors' => $errors
                ]);
                break;

            case 'search_product':
                // Search for a single product by name
                $searchTerm = $jsonInput['search'] ?? '';

                error_log("=== SEARCH PRODUCT DEBUG ===");
                error_log("Search term received: " . $searchTerm);

                if (empty($searchTerm)) {
                    echo json_encode(['success' => false, 'message' => 'Từ khóa tìm kiếm không được trống']);
                    break;
                }

                // Use POSController to search products
                $products = $posController->searchProducts($searchTerm, '', 10);

                error_log("Products found: " . count($products));
                error_log("Products data: " . json_encode($products, JSON_UNESCAPED_UNICODE));

                echo json_encode([
                    'success' => true,
                    'products' => $products,
                    'debug' => [
                        'search_term' => $searchTerm,
                        'count' => count($products)
                    ]
                ]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log('Prescription scan API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
