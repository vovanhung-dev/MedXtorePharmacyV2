<?php
/**
 * POS Payment API
 * Handle payment processing
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

        if ($action === 'process') {
            $paymentData = [
                'payment_method' => $input['payment_method'] ?? 'cash',
                'customer_id' => $input['customer_id'] ?? null,
                'cash_received' => $input['cash_received'] ?? 0,
                'change_given' => $input['change_given'] ?? 0,
                'transaction_ref' => $input['transaction_ref'] ?? null
            ];

            $result = $posController->processPayment($paymentData);
            echo json_encode($result);
        } else {
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
