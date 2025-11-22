<?php
// Check if session is already active before starting it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/config.php';

/**
 * Class to handle all voucher-related operations
 */
class VoucherController {
    private $conn;
    
    /**
     * Constructor - initialize database connection
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Generate a random voucher code
     * 
     * @param int $length Length of the code to generate
     * @return string The generated code
     */
    public function generateCode($length = 6) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        return $code;
    }
    
    /**
     * Create a new voucher
     * 
     * @param int $discount_percent Percentage discount
     * @param int $expiry_days Days until expiration
     * @param string $code Optional code (will generate if not provided)
     * @return array Result of operation
     */
    public function createVoucher($discount_percent, $expiry_days, $code = null) {
        // Validate discount percentage
        if ($discount_percent < 1 || $discount_percent > 99) {
            return ['success' => false, 'message' => 'Phần trăm giảm giá phải từ 1% đến 99%'];
        }
        
        // Validate expiry days
        if ($expiry_days < 1) {
            return ['success' => false, 'message' => 'Số ngày hiệu lực phải lớn hơn 0'];
        }
        
        // Generate code if not provided
        if (empty($code)) {
            $code = $this->generateCode();
            
            // Make sure code is unique
            $codeExists = true;
            $attempts = 0;
            
            while ($codeExists && $attempts < 5) {
                $check_sql = "SELECT id FROM vouchers WHERE code = ?";
                $check_stmt = $this->conn->prepare($check_sql);
                $check_stmt->bind_param("s", $code);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows === 0) {
                    $codeExists = false;
                } else {
                    $code = $this->generateCode(); // Generate a new code
                    $attempts++;
                }
            }
            
            if ($codeExists) {
                return ['success' => false, 'message' => 'Không thể tạo mã giảm giá duy nhất. Vui lòng thử lại sau.'];
            }
        } else {
            // Check if provided code already exists
            $check_sql = "SELECT id FROM vouchers WHERE code = ?";
            $check_stmt = $this->conn->prepare($check_sql);
            $check_stmt->bind_param("s", $code);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Mã giảm giá đã tồn tại!'];
            }
        }
        
        // Calculate expiration date
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
        
        // Insert the voucher
        $sql = "INSERT INTO vouchers (code, discount_percent, expires_at) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sis", $code, $discount_percent, $expires_at);
        
        if ($stmt->execute()) {
            return [
                'success' => true, 
                'message' => 'Tạo mã giảm giá thành công!',
                'code' => $code,
                'voucher_id' => $stmt->insert_id
            ];
        } else {
            return ['success' => false, 'message' => 'Không thể tạo mã giảm giá: ' . $this->conn->error];
        }
    }
    
    /**
     * Update an existing voucher
     * 
     * @param int $voucher_id ID of the voucher to update
     * @param int $discount_percent New percentage discount
     * @param int $expiry_days New days until expiration
     * @return array Result of operation
     */
    public function updateVoucher($voucher_id, $discount_percent, $expiry_days) {
        // Validate discount percentage
        if ($discount_percent < 1 || $discount_percent > 99) {
            return ['success' => false, 'message' => 'Phần trăm giảm giá phải từ 1% đến 99%'];
        }
        
        // Validate expiry days
        if ($expiry_days < 1) {
            return ['success' => false, 'message' => 'Số ngày hiệu lực phải lớn hơn 0'];
        }
        
        // Calculate new expiration date
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
        
        // Update the voucher
        $sql = "UPDATE vouchers SET discount_percent = ?, expires_at = ? WHERE id = ? AND is_used = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $discount_percent, $expires_at, $voucher_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return ['success' => true, 'message' => 'Cập nhật mã giảm giá thành công!'];
            } else {
                return ['success' => false, 'message' => 'Không thể cập nhật: Mã không tồn tại hoặc đã được sử dụng!'];
            }
        } else {
            return ['success' => false, 'message' => 'Không thể cập nhật mã giảm giá: ' . $this->conn->error];
        }
    }
    
    /**
     * Delete a voucher
     * 
     * @param int $voucher_id ID of the voucher to delete
     * @return array Result of operation
     */
    public function deleteVoucher($voucher_id) {
        $sql = "DELETE FROM vouchers WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $voucher_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return ['success' => true, 'message' => 'Xóa mã giảm giá thành công!'];
            } else {
                return ['success' => false, 'message' => 'Không tìm thấy mã giảm giá!'];
            }
        } else {
            return ['success' => false, 'message' => 'Không thể xóa mã giảm giá: ' . $this->conn->error];
        }
    }
    
    /**
     * Validate a voucher code
     * 
     * @param string $code Code to validate
     * @return array Result containing voucher info if valid
     */
    public function validateVoucher($code) {
        if (empty($code)) {
            return ['valid' => false, 'message' => 'Vui lòng nhập mã giảm giá!'];
        }

        $sql = "SELECT * FROM vouchers WHERE code = ? AND is_used = 0 AND expires_at > NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $voucher = $result->fetch_assoc();
            return [
                'valid' => true,
                'voucher_id' => $voucher['id'],
                'code' => $voucher['code'],
                'discount_percent' => $voucher['discount_percent'],
                'expires_at' => $voucher['expires_at'],
                'message' => "Mã hợp lệ! Giảm {$voucher['discount_percent']}% đơn hàng."
            ];
        }

        return ['valid' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn!'];
    }
    
    /**
     * Apply a voucher to an order
     * 
     * @param int $voucher_id ID of the voucher to apply
     * @param int $user_id ID of the user using the voucher
     * @return array Result of operation
     */
    public function applyVoucher($voucher_id, $user_id) {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE vouchers SET is_used = 1, used_by = ?, used_at = ? 
                WHERE id = ? AND is_used = 0 AND expires_at > NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $user_id, $now, $voucher_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Mã giảm giá đã được áp dụng!'];
        }

        return ['success' => false, 'message' => 'Mã đã hết hạn hoặc đã được sử dụng!'];
    }
    
    /**
     * Get all active vouchers
     * 
     * @return array List of active vouchers
     */
    public function getActiveVouchers() {
        $sql = "SELECT * FROM vouchers WHERE is_used = 0 AND expires_at > NOW() ORDER BY created_at DESC";
        $result = $this->conn->query($sql);
        $vouchers = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $vouchers[] = $row;
            }
        }
        
        return $vouchers;
    }
    
    /**
     * Clean up expired and used vouchers
     * Can be called periodically to keep the database clean
     */
    public function cleanupVouchers() {
        // Create a backup table for historical data if needed
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS vouchers_history (
                id int(11) NOT NULL,
                code varchar(10) NOT NULL,
                discount_percent int(11) NOT NULL,
                created_at datetime DEFAULT NULL,
                expired_at datetime DEFAULT NULL,
                used_by int(11) DEFAULT NULL,
                used_at datetime DEFAULT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Move expired/used vouchers to history
        $this->conn->query("
            INSERT INTO vouchers_history
            SELECT id, code, discount_percent, created_at, expires_at, used_by, used_at
            FROM vouchers 
            WHERE expires_at < NOW() OR is_used = 1
        ");
        
        // Delete expired/used vouchers
        $sql = "DELETE FROM vouchers WHERE expires_at < NOW() OR is_used = 1";
        $this->conn->query($sql);
        
        return ['success' => true, 'message' => 'Đã dọn dẹp các mã giảm giá hết hạn và đã sử dụng!'];
    }
}

/**
 * Function to handle AJAX requests for voucher validation
 */
// ✅ Xử lý các request AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $voucherController = new VoucherController($conn);

    try {
        switch ($_POST['action']) {
            case 'validate':
                echo json_encode($voucherController->validateVoucher($_POST['code'] ?? ''));
                break;

            case 'apply':
                if (!isset($_SESSION['user_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để áp dụng mã!']);
                    break;
                }
                echo json_encode($voucherController->applyVoucher($_POST['voucher_id'], $_SESSION['user_id']));
                break;

            case 'generate':
                echo json_encode(['code' => $voucherController->generateCode()]);
                break;

            default:
                echo json_encode(['error' => 'Yêu cầu không hợp lệ']);
        }
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi máy chủ: ' . $e->getMessage()]);
    }

    exit;
}
