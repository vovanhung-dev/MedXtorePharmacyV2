-- Bảng lưu yêu cầu tư vấn thuốc
CREATE TABLE IF NOT EXISTS yeucau_tuvan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    so_dien_thoai VARCHAR(20) NOT NULL,
    ghi_chu TEXT,
    hinh_anh_toa VARCHAR(255),
    nguoidung_id INT NULL,
    trang_thai ENUM('cho_xu_ly', 'dang_xu_ly', 'da_hoan_thanh', 'da_huy') DEFAULT 'cho_xu_ly',
    nhan_vien_xu_ly INT NULL,
    ghi_chu_duoc_si TEXT,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoidung_id) REFERENCES nguoidung(id) ON DELETE SET NULL,
    FOREIGN KEY (nhan_vien_xu_ly) REFERENCES nguoidung(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lưu chi tiết thuốc cần tư vấn
CREATE TABLE IF NOT EXISTS chitiet_yeucau_tuvan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    yeucau_id INT NOT NULL,
    ten_thuoc VARCHAR(255),
    trieu_chung VARCHAR(255),
    so_luong INT DEFAULT 1,
    ghi_chu TEXT,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (yeucau_id) REFERENCES yeucau_tuvan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index để tìm kiếm nhanh
CREATE INDEX idx_yeucau_trang_thai ON yeucau_tuvan(trang_thai);
CREATE INDEX idx_yeucau_ngay_tao ON yeucau_tuvan(ngay_tao);
CREATE INDEX idx_yeucau_nguoidung ON yeucau_tuvan(nguoidung_id);
