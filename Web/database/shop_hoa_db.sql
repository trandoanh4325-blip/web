-- 1. BẢNG USERS
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `status` ENUM('active', 'locked') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- 1. BẢNG LOẠI SẢN PHẨM (Khóa chính là ma_loai)
-- -------------------------------------------------------------
CREATE TABLE `loai_san_pham` (
  `ma_loai`   VARCHAR(20)  NOT NULL,
  `ten_loai`  VARCHAR(100) NOT NULL,
  `ngay_them` DATE         DEFAULT NULL,
  PRIMARY KEY (`ma_loai`),
  UNIQUE KEY `uk_ten_loai` (`ten_loai`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci
  COMMENT='Phân loại sản phẩm của shop hoa';
 
-- -------------------------------------------------------------
-- 2. BẢNG SẢN PHẨM (Khóa chính là ma_sp, khóa ngoại ma_loai)
-- -------------------------------------------------------------
CREATE TABLE `san_pham` (
  `ma_sp`            VARCHAR(30)   NOT NULL,
  `ten_sp`           VARCHAR(200)  NOT NULL,
  `ma_loai`          VARCHAR(20)   DEFAULT NULL,
  `don_vi_tinh`      VARCHAR(30)   NOT NULL DEFAULT 'Cai',
  `so_luong_ton`     INT(11)       NOT NULL DEFAULT 0,
  `gia_von`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `ty_le_loi_nhuan`  DECIMAL(6,2)  NOT NULL DEFAULT 0.00,
  `gia_ban`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `hinh_anh`         VARCHAR(255)  DEFAULT NULL,
  `mo_ta`            TEXT          DEFAULT NULL,
  `hien_trang`       ENUM('hien_thi','an') NOT NULL DEFAULT 'hien_thi',
  `ngay_them`        DATE          DEFAULT NULL,
  PRIMARY KEY (`ma_sp`),
  KEY `fk_sp_loai_idx` (`ma_loai`),
  CONSTRAINT `fk_sp_loai`
    FOREIGN KEY (`ma_loai`)
    REFERENCES `loai_san_pham` (`ma_loai`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci
  COMMENT='Danh mục sản phẩm của shop hoa';

-- -------------------------------------------------------------
-- 3. DỮ LIỆU MẪU
-- -------------------------------------------------------------
INSERT IGNORE INTO `loai_san_pham` (`ma_loai`, `ten_loai`, `ngay_them`) VALUES
  ('LSP001', 'Thiệp & Phụ kiện ',    '2026-03-02'),
  ('LSP002', 'Đồ trang trí',         '2026-03-03'),
  ('LSP003', 'Set quà tặng',         '2026-03-15'),
  ('LSP004', 'Handmade',             '2026-03-15'),
  ('LSP005', 'Quà lưu niệm',         '2026-03-15'),
  ('LSP006', 'Quà tặng & giỏ quà',   '2026-03-29'),
  ('LSP007', 'Hoa giấy',             '2026-03-31'),
  ('LSP008', 'Hoa thật 100%',        '2026-03-31');
 
INSERT IGNORE INTO `san_pham`
  (`ma_sp`,`ten_sp`,`ma_loai`,`don_vi_tinh`,
   `so_luong_ton`,`gia_von`,`ty_le_loi_nhuan`,`gia_ban`,
   `mo_ta`,`hien_trang`,`ngay_them`)
VALUES
  ('SP001','Thiệp đỏ',     'LSP001','Tấm',   50,  80000, 30, 104000,'Thiệp đẹp viết tình yêu của bạn tặng người thương','hien_thi','2026-04-02'),
  ('SP002','Hoa hồng',     'LSP008','Bông', 100,  15000, 40,  21000,'Hoa hồng, thể hiện tình yêu và lãng mạn',          'hien_thi','2026-03-31'),
  ('SP003','Hoa giấy tím', 'LSP007','Bông',  20, 250000, 50, 375000,'Hoa giấy tím đẹp, rẻ',                         'hien_thi','2026-04-01');

-- -------------------------------------------------------------
-- BẢNG PHIẾU NHẬP (Khóa chính: ma_phieu)
-- -------------------------------------------------------------
CREATE TABLE `phieu_nhap` (
  `ma_phieu`   VARCHAR(20) NOT NULL,
  `ngay_nhap`  DATE        NOT NULL,
  `trang_thai` ENUM('chua_hoan_thanh','hoan_thanh') NOT NULL DEFAULT 'chua_hoan_thanh',
  `ghi_chu`    TEXT        DEFAULT NULL,
  `ngay_tao`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ma_phieu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
-- -------------------------------------------------------------
-- BẢNG CHI TIẾT PHIẾU NHẬP (Khóa chính gồm 2 cột: ma_phieu và ma_sp)
-- -------------------------------------------------------------
CREATE TABLE `chi_tiet_phieu_nhap` (
  `ma_phieu`  VARCHAR(20)   NOT NULL,
  `ma_sp`     VARCHAR(30)   NOT NULL,
  `so_luong`  INT(11)       NOT NULL DEFAULT 0,
  `don_gia`   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`ma_phieu`, `ma_sp`),
  CONSTRAINT `fk_ctpn_sp`
    FOREIGN KEY (`ma_sp`) REFERENCES `san_pham`(`ma_sp`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ctpn_phieu`
    FOREIGN KEY (`ma_phieu`) REFERENCES `phieu_nhap`(`ma_phieu`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Thêm lại dữ liệu mẫu
INSERT IGNORE INTO `phieu_nhap` (`ma_phieu`,`ngay_nhap`,`trang_thai`,`ghi_chu`) VALUES
  ('PN001','2025-01-10','hoan_thanh',      'Nhập hàng tháng 1 - lần 1'),
  ('PN002','2025-02-01','hoan_thanh',      'Nhập hàng tháng 2');
 
INSERT IGNORE INTO `chi_tiet_phieu_nhap` (`ma_phieu`,`ma_sp`,`so_luong`,`don_gia`) VALUES
  ('PN001','SP001',50, 80000), 
  ('PN001','SP002',100,15000), 
  ('PN002','SP003',20,250000);
