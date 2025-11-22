<?php
/**
 * POS Held Bills API
 * Handle held bill operations
 */

// Disable error output to prevent JSON corruption
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

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'list') {
            $result = $posController->getHeldBills();
            echo json_encode($result);
            exit();
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action parameter required']);
            exit();
        }

    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        switch ($action) {
            case 'hold':
                $billName = $input['bill_name'] ?? null;
                $result = $posController->holdBill($billName);
                echo json_encode($result);
                exit();

            case 'retrieve':
                $billId = $input['bill_id'] ?? 0;
                $result = $posController->retrieveHeldBill($billId);
                echo json_encode($result);
                exit();

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit();
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit();
}
