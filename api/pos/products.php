<?php
/**
 * POS Products API
 * Handle product listing and search for POS
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../../controllers/POSController.php';

try {
    $posController = new POSController();

    $action = $_REQUEST['action'] ?? 'get_products';

    if ($action === 'get_products') {
        $search = $_REQUEST['search'] ?? '';
        $loaiId = $_REQUEST['loai_id'] ?? '';
        $limit = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 50;
        $offset = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0;

        $result = $posController->getProductsForPOS($search, $loaiId, $limit, $offset);

        if ($result['success']) {
            // Calculate total count
            require_once __DIR__ . '/../../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();

            $countQuery = "SELECT COUNT(DISTINCT CONCAT(t.id, '_', dv.id)) as total
                          FROM thuoc t
                          JOIN loai_thuoc l ON t.loai_id = l.id
                          JOIN thuoc_donvi td ON t.id = td.thuoc_id
                          JOIN donvi dv ON td.donvi_id = dv.id
                          LEFT JOIN khohang k ON t.id = k.thuoc_id AND td.donvi_id = k.donvi_id
                          WHERE td.gia > 0";

            $params = [];

            if (!empty($search)) {
                $countQuery .= " AND (t.ten_thuoc LIKE ? OR t.id LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if (!empty($loaiId)) {
                $countQuery .= " AND t.loai_id = ?";
                $params[] = $loaiId;
            }

            $countQuery .= " GROUP BY t.id, dv.id HAVING COALESCE(SUM(k.soluong), 0) > 0";

            $stmt = $conn->prepare($countQuery);
            $stmt->execute($params);
            $total = $stmt->rowCount();

            echo json_encode([
                'success' => true,
                'data' => $result['data'],
                'total' => $total
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
