<?php
// Kiểm tra phiên đã được bắt đầu chưa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../includes/config.php');
require_once(__DIR__ . '/../models/Mailer.php');

class AuthController {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    // Đăng ký người dùng mới
    public function register($ten, $email, $matkhau, $xacnhan_matkhau) {
        $errors = [];
        
        // Kiểm tra tên
        if (empty($ten)) {
            $errors['ten'] = 'Vui lòng nhập họ tên.';
        }

        // Kiểm tra email
        if (empty($email)) {
            $errors['email'] = 'Vui lòng nhập email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ.';
        } else {
            // Kiểm tra email đã tồn tại chưa
            $stmt = $this->conn->prepare("SELECT id FROM nguoidung WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors['email'] = 'Email này đã được sử dụng.';
            }
            $stmt->close();
        }

        // Kiểm tra mật khẩu
        if (empty($matkhau)) {
            $errors['matkhau'] = 'Vui lòng nhập mật khẩu.';
        } elseif (strlen($matkhau) < 6) {
            $errors['matkhau'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        }

        // Kiểm tra xác nhận mật khẩu
        if ($matkhau !== $xacnhan_matkhau) {
            $errors['xacnhan_matkhau'] = 'Xác nhận mật khẩu không khớp.';
        }
        
        // Nếu có lỗi, trả về mảng lỗi
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Hash mật khẩu
        $hashed_password = password_hash($matkhau, PASSWORD_DEFAULT);
        
        // Mặc định là người dùng thường (vaitro_id = 2)
        $vaitro_id = 2;
        
        // Thêm người dùng mới
        $insert_query = "INSERT INTO nguoidung (ten, email, matkhau, vaitro_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($insert_query);
        $stmt->bind_param("sssi", $ten, $email, $hashed_password, $vaitro_id);
        
        if ($stmt->execute()) {
            // Gửi email chào mừng - comment dòng này nếu chưa cấu hình email
            // $subject = "Chào mừng bạn đến với MedXtore";
            // $message = $this->getWelcomeEmailTemplate($ten);
            // Mailer::sendNotification($email, $subject, $message);
            
            return [
                'success' => true,
                'message' => "Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ."
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['db' => "Đã xảy ra lỗi: " . $stmt->error]
            ];
        }
    }
    
    // Đăng nhập người dùng
    public function login($email, $matkhau, $remember = false) {
        $errors = [];
        
        // Kiểm tra email
        if (empty($email)) {
            $errors['email'] = 'Vui lòng nhập email.';
        }

        // Kiểm tra mật khẩu
        if (empty($matkhau)) {
            $errors['matkhau'] = 'Vui lòng nhập mật khẩu.';
        }
        
        // Nếu có lỗi, trả về mảng lỗi
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        $stmt = $this->conn->prepare("SELECT id, ten, matkhau, vaitro_id FROM nguoidung WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Kiểm tra mật khẩu
            if (password_verify($matkhau, $user['matkhau'])) {
                // Đăng nhập thành công, lưu thông tin vào session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['ten'];
                $_SESSION['user_role'] = $user['vaitro_id'];
                
                // Lưu cookie nếu đã chọn "Ghi nhớ đăng nhập"
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 ngày
                    
                    // Lưu token vào database - comment các dòng này nếu bảng chưa được tạo
                    // $this->saveRememberToken($user['id'], $token, $expires);
                    
                    // Đặt cookie
                    setcookie("remember_token", $token, $expires, "/", "", true, true);
                }
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'ten' => $user['ten'],
                        'vaitro_id' => $user['vaitro_id']
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => ['login' => 'Email hoặc mật khẩu không đúng.']
                ];
            }
        } else {
            return [
                'success' => false,
                'errors' => ['login' => 'Email hoặc mật khẩu không đúng.']
            ];
        }
    }
    
    // Lưu token "Ghi nhớ đăng nhập"
    private function saveRememberToken($user_id, $token, $expires) {
        // Xóa token cũ
        $delete = $this->conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $delete->bind_param("i", $user_id);
        $delete->execute();
        
        // Lưu token mới
        $expires_date = date('Y-m-d H:i:s', $expires);
        $insert = $this->conn->prepare("INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)");
        $insert->bind_param("iss", $user_id, $token, $expires_date);
        $insert->execute();
    }
    
    // Đăng xuất
    public function logout() {
        // Xóa cookie nếu có
        if (isset($_COOKIE['remember_token'])) {
            setcookie("remember_token", "", time() - 3600, "/", "", true, true);
        }
    
        // Chỉ xóa session người dùng, giữ giỏ hàng
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_role']);
    
        return true;
    }
    
    // Quên mật khẩu
    public function forgotPassword($email) {
        $errors = [];
        
        // Kiểm tra email
        if (empty($email)) {
            $errors['email'] = 'Vui lòng nhập email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ.';
        }
        
        // Nếu có lỗi, trả về mảng lỗi
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        $stmt = $this->conn->prepare("SELECT id, ten FROM nguoidung WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Tạo token để đặt lại mật khẩu
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + (1 * 60 * 60)); // Hết hạn sau 1 giờ
            
            // Xóa token cũ - comment các dòng này nếu bảng chưa được tạo
            // Xóa token cũ
            $delete = $this->conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $delete->bind_param("s", $email);
            $delete->execute();

            // Lưu token mới
            $insert = $this->conn->prepare("INSERT INTO password_resets (email, token, expires) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $email, $token, $expires);

            if ($insert->execute()) {
                // Tạo link đặt lại mật khẩu
                global $base_url;
                $server_name = $_SERVER['SERVER_NAME'];
                $server_port = ($_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443') ? '' : ':' . $_SERVER['SERVER_PORT'];
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $reset_link = $protocol . '://' . $server_name . $server_port . $base_url . "/pages/reset_password.php?token=" . $token;
                
                // Gửi email đặt lại mật khẩu
                $result = Mailer::sendPasswordReset($email, $reset_link);
                
                if ($result) {
                    return [
                        'success' => true,
                        'message' => "Hướng dẫn đặt lại mật khẩu đã được gửi tới email của bạn. Vui lòng kiểm tra hộp thư."
                    ];
                } else {
                    // Xóa token nếu không gửi được email
                    $delete = $this->conn->prepare("DELETE FROM password_resets WHERE token = ?");
                    $delete->bind_param("s", $token);
                    $delete->execute();
                    
                    return [
                        'success' => false,
                        'errors' => ['email' => "Không thể gửi email. Vui lòng thử lại sau."]
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'errors' => ['db' => "Đã xảy ra lỗi: " . $insert->error]
                ];
            }
        }
    }
    // Kiểm tra token đặt lại mật khẩu
    public function checkResetToken($token) {
        if (empty($token)) {
            return false;
        }
        
        $stmt = $this->conn->prepare("SELECT email, expires FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $reset = $result->fetch_assoc();
        
        // Kiểm tra thời gian hết hạn
        if (strtotime($reset['expires']) < time()) {
            return false;
        }
        
        return $reset['email'];
    }
    
    // Đặt lại mật khẩu
    public function resetPassword($token, $matkhau, $xacnhan_matkhau) {
        $errors = [];
        
        // Kiểm tra mật khẩu
        if (empty($matkhau)) {
            $errors['matkhau'] = 'Vui lòng nhập mật khẩu mới.';
        } elseif (strlen($matkhau) < 6) {
            $errors['matkhau'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        }

        // Kiểm tra xác nhận mật khẩu
        if ($matkhau !== $xacnhan_matkhau) {
            $errors['xacnhan_matkhau'] = 'Xác nhận mật khẩu không khớp.';
        }
        
        // Kiểm tra token
        $email = $this->checkResetToken($token);
        if (!$email) {
            $errors['token'] = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';
        }
        
        // Nếu có lỗi, trả về mảng lỗi
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Mã hóa mật khẩu mới
        $hashed_password = password_hash($matkhau, PASSWORD_DEFAULT);
        
        // Cập nhật mật khẩu
        $update = $this->conn->prepare("UPDATE nguoidung SET matkhau = ? WHERE email = ?");
        $update->bind_param("ss", $hashed_password, $email);
        
        if ($update->execute()) {
            // Xóa token reset - comment các dòng này nếu bảng chưa được tạo
            // $delete = $this->conn->prepare("DELETE FROM password_resets WHERE token = ?");
            // $delete->bind_param("s", $token);
            // $delete->execute();
            
            return [
                'success' => true,
                'message' => "Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập ngay bây giờ."
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['db' => "Đã xảy ra lỗi: " . $update->error]
            ];
        }
    }
    
    // Template email chào mừng
    private function getWelcomeEmailTemplate($name) {
        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4285f4; color: white; padding: 15px; text-align: center; }
                    .content { padding: 20px; background-color: #f9f9f9; }
                    .button { background-color: #4285f4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 15px 0; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Chào mừng đến với MedXtore</h2>
                    </div>
                    <div class='content'>
                        <p>Xin chào {$name},</p>
                        <p>Cảm ơn bạn đã đăng ký tài khoản tại MedXtore - nơi cung cấp các sản phẩm vitamin và thực phẩm chức năng chất lượng cao.</p>
                        <p>Tài khoản của bạn đã được tạo thành công và bạn có thể sử dụng email và mật khẩu để đăng nhập vào hệ thống.</p>
                        <p style='text-align: center;'>
                            <a href='" . page_url('login.php') . "' class='button'>Đăng nhập ngay</a>
                        </p>
                        <p>Nếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi.</p>
                    </div>
                    <div class='footer'>
                        <p>Trân trọng,<br>Đội ngũ MedXtore</p>
                        <p>&copy; " . date('Y') . " MedXtore. Tất cả quyền được bảo lưu.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    // ✅ Kiểm tra người dùng đã đăng nhập hay chưa
public function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ✅ Kiểm tra người dùng có quyền truy cập admin
public function canAccessAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1; // 1 = admin
}

// ✅ Đăng nhập bằng Google
public function loginWithGoogle($email, $name, $avatar, $google_id) {
    // Kiểm tra xem người dùng đã tồn tại chưa
    $stmt = $this->conn->prepare("SELECT id, ten, vaitro_id FROM nguoidung WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        // Chưa có, thì tạo mới (vai trò mặc định là user thường)
        $vaitro_id = 2;
        $stmtInsert = $this->conn->prepare("INSERT INTO nguoidung (ten, email, vaitro_id) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("ssi", $name, $email, $vaitro_id);
        if (!$stmtInsert->execute()) {
            return ['success' => false, 'message' => 'Không thể tạo tài khoản Google.'];
        }
        $user = [
            'id' => $stmtInsert->insert_id,
            'ten' => $name,
            'vaitro_id' => $vaitro_id
        ];
    }

    // Thiết lập session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['ten'];
    $_SESSION['user_role'] = $user['vaitro_id'];

    return ['success' => true];
}

}