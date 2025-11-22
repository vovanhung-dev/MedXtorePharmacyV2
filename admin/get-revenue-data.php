<?php
include_once("../includes/config.php");

// Hàm lấy dữ liệu doanh thu
function getRevenueData($period) {
    global $conn;
    
    switch ($period) {
        case "7days":
            $startDate = date("Y-m-d", strtotime("-6 days"));
            $sql = "SELECT 
                        DATE(ngay_dat) as date,
                        COUNT(*) as order_count,
                        SUM(tongtien) as revenue
                    FROM donhang 
                    WHERE trangthai = 'dadat'
                    AND ngay_dat BETWEEN ? AND CURDATE()
                    GROUP BY DATE(ngay_dat)
                    ORDER BY date";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $startDate);
            break;

        case "30days":
            $startDate = date("Y-m-d", strtotime("-29 days")); 
            $sql = "SELECT 
                        DATE(ngay_dat) as date,
                        COUNT(*) as order_count,
                        SUM(tongtien) as revenue  
                    FROM donhang
                    WHERE trangthai = 'dadat'
                    AND ngay_dat BETWEEN ? AND CURDATE()
                    GROUP BY DATE(ngay_dat)
                    ORDER BY date";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $startDate);
            break;

        case "year":
        default:
            $currentYear = date("Y");
            $sql = "SELECT 
                        MONTH(ngay_dat) as month,
                        COUNT(*) as order_count,
                        SUM(tongtien) as revenue
                    FROM donhang
                    WHERE trangthai = 'dadat' 
                    AND YEAR(ngay_dat) = ?
                    GROUP BY MONTH(ngay_dat)
                    ORDER BY month";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $currentYear);
            break;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Lấy tham số period từ request
$period = isset($_GET["period"]) ? $_GET["period"] : "year";

// Lấy dữ liệu và trả về dạng JSON
$data = getRevenueData($period);
header("Content-Type: application/json");
echo json_encode($data);
?>