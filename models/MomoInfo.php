<?php

class MomoInfo {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($data) {
        $sql = "INSERT INTO momo_payments (order_id, full_name, amount, order_info, date_paid) 
                VALUES (:order_id, :full_name, :amount, :order_info, :date_paid)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':order_id' => $data['orderId'],
            ':full_name' => $data['fullName'],
            ':amount' => $data['amount'],
            ':order_info' => $data['orderInfo'],
            ':date_paid' => date('Y-m-d H:i:s')
        ]);
    }

    public function getByOrderId($orderId) {
        $sql = "SELECT * FROM momo_payments WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
} 