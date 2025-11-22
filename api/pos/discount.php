<?php
/**
 * POS Discount API
 * Handle discount and promotion operations
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

require_once __DIR__ . '/../../controllers/POSController.php';

try {
    $posController = new POSController();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        switch ($action) {
            case 'apply_promotion':
                $discountData = [
                    'type' => 'promotion',
                    'promotion_code' => $input['promotion_code'] ?? ''
                ];
                $result = $posController->applyDiscount($discountData);
                echo json_encode($result);
                break;

            case 'apply_manual':
                $discountData = [
                    'type' => $input['type'] ?? 'percentage',
                    'value' => $input['value'] ?? 0,
                    'reason' => $input['reason'] ?? 'Manual discount',
                    'approved_by' => $_SESSION['user_id']
                ];
                $result = $posController->applyDiscount($discountData);
                echo json_encode($result);
                break;

            case 'remove_discount':
                $result = $posController->removeDiscount();
                echo json_encode($result);
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
