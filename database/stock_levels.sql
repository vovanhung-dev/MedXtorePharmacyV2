-- Bảng cấu hình mức dự trữ cho từng thuốc
CREATE TABLE IF NOT EXISTS stock_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thuoc_id INT NOT NULL,
    muc_toi_thieu INT DEFAULT 20 COMMENT 'Số lượng tối thiểu cần có trong kho',
    muc_toi_da INT DEFAULT 200 COMMENT 'Số lượng tối đa nên có trong kho',
    diem_dat_hang INT DEFAULT 30 COMMENT 'Điểm đặt hàng - khi tồn kho còn bằng này thì đặt hàng',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_capnhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (thuoc_id) REFERENCES thuoc(id) ON DELETE CASCADE,
    UNIQUE KEY unique_thuoc (thuoc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index cho tìm kiếm nhanh
CREATE INDEX idx_stock_levels_thuoc ON stock_levels(thuoc_id);
