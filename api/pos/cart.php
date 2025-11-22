<?php
/**
 * POS Cart API
 * Handle cart operations for POS
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
            case 'add':
                $result = $posController->addToCart($input);
                echo json_encode($result);
                break;

            case 'update_quantity':
                $key = $input['key'] ?? '';
                $change = (int)($input['change'] ?? 0);

                // Get current cart
                $cartResponse = $posController->getCurrentCart();
                if (!$cartResponse['success']) {
                    echo json_encode($cartResponse);
                    break;
                }

                $cart = $cartResponse['data'];
                if (!isset($cart['items'][$key])) {
                    echo json_encode(['success' => false, 'message' => 'Item not found']);
                    break;
                }

                $newQuantity = $cart['items'][$key]['soluong'] + $change;
                if ($newQuantity < 1) {
                    $newQuantity = 1;
                }

                $result = $posController->updateCartQuantity($key, $newQuantity);
                echo json_encode($result);
                break;

            case 'remove':
                $key = $input['key'] ?? '';
                $result = $posController->removeFromCart($key);
                echo json_encode($result);
                break;

            case 'clear':
                $result = $posController->clearCart();
                echo json_encode($result);
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $posController->getCurrentCart();
        echo json_encode($result);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
