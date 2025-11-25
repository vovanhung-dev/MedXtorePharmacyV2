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

require_once __DIR__ . '/../../models/PrescriptionScanner.php';
require_once __DIR__ . '/../../controllers/POSController.php';

try {
    $scanner = new PrescriptionScanner();
    $posController = new POSController();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? $_GET['action'] ?? 'scan';

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
                $input = json_decode(file_get_contents('php://input'), true);

                if (empty($input['medicines'])) {
                    echo json_encode(['success' => false, 'message' => 'Không có thuốc để thêm']);
                    break;
                }

                $addedCount = 0;
                $errors = [];

                foreach ($input['medicines'] as $medicine) {
                    if (empty($medicine['product_id'])) {
                        continue;
                    }

                    $addResult = $posController->addToCart([
                        'product_id' => $medicine['product_id'],
                        'quantity' => $medicine['quantity'] ?? 1,
                        'batch_id' => $medicine['batch_id'] ?? null
                    ]);

                    if ($addResult['success']) {
                        $addedCount++;
                    } else {
                        $errors[] = $medicine['name'] . ': ' . ($addResult['message'] ?? 'Lỗi thêm vào giỏ');
                    }
                }

                echo json_encode([
                    'success' => $addedCount > 0,
                    'message' => "Đã thêm $addedCount thuốc vào giỏ hàng",
                    'added_count' => $addedCount,
                    'errors' => $errors
                ]);
                break;

            case 'search_product':
                // Search for a single product by name
                $input = json_decode(file_get_contents('php://input'), true);
                $searchTerm = $input['search'] ?? '';

                if (empty($searchTerm)) {
                    echo json_encode(['success' => false, 'message' => 'Từ khóa tìm kiếm không được trống']);
                    break;
                }

                // Use POSController to search products
                $products = $posController->searchProducts($searchTerm, '', 10);

                echo json_encode([
                    'success' => true,
                    'products' => $products
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
