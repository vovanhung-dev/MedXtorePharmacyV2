<?php
// Thông tin kết nối MySQL
define('DB_HOST', 'localhost');     // hoặc '127.0.0.1'
define('DB_USER', 'root');          // username của bạn
define('DB_PASS', 'root');              // password của bạn
define('DB_NAME', 'pharmacy');      // tên database của bạn

// Tạo kết nối mới
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Đặt charset là utf8
$conn->set_charset("utf8");

// Đặt timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

class Database {
    private $host = "localhost";
    private $db_name = "pharmacy";
    private $username = "root";
    private $password = "root";
    public $conn;

    public function getConnection(){
        $this->conn = null;
        try{
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->db_name}", 
                                  $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e){
            echo "Lỗi kết nối: " . $e->getMessage();
        }
        return $this->conn;
    } 
}