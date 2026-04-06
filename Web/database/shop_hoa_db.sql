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

-- 2. BẢNG LOẠI SẢN PHẨM
CREATE TABLE IF NOT EXISTS `loai_san_pham` (
  `id`        INT(11)      NOT NULL AUTO_INCREMENT,
  `ma_loai`   VARCHAR(20)  NOT NULL,
  `ten_loai`  VARCHAR(100) NOT NULL,
  `ngay_them` DATE         DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ma_loai`  (`ma_loai`),
  UNIQUE KEY `uk_ten_loai` (`ten_loai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. BẢNG SẢN PHẨM
CREATE TABLE IF NOT EXISTS `san_pham` (
  `id`               INT(11)       NOT NULL AUTO_INCREMENT,
  `ma_sp`            VARCHAR(30)   NOT NULL,
  `ten_sp`           VARCHAR(200)  NOT NULL,
  `id_loai`          INT(11)       DEFAULT NULL,
  `don_vi_tinh`      VARCHAR(30)   NOT NULL DEFAULT 'Cai',
  `so_luong_ton`     INT(11)       NOT NULL DEFAULT 0,
  `gia_von`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `ty_le_loi_nhuan`  DECIMAL(6,2)  NOT NULL DEFAULT 0.00,
  `gia_ban`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `hinh_anh`         VARCHAR(255)  DEFAULT NULL,
  `mo_ta`            TEXT          DEFAULT NULL,
  `hien_trang`       ENUM('hien_thi','an') NOT NULL DEFAULT 'hien_thi',
  `ngay_them`        DATE          DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ma_sp` (`ma_sp`),
  CONSTRAINT `fk_sp_loai` FOREIGN KEY (`id_loai`) REFERENCES `loai_san_pham` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- 4. BẢNG PHIẾU NHẬP
CREATE TABLE IF NOT EXISTS `phieu_nhap` (
  `id`         INT(11)   NOT NULL AUTO_INCREMENT,
  `ma_phieu`   VARCHAR(20) NOT NULL,
  `ngay_nhap`  DATE        NOT NULL,
  `trang_thai` ENUM('chua_hoan_thanh','hoan_thanh') NOT NULL DEFAULT 'chua_hoan_thanh',
  `ghi_chu`    TEXT        DEFAULT NULL,
  `ngay_tao`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ma_phieu` (`ma_phieu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. BẢNG CHI TIẾT PHIẾU NHẬP
CREATE TABLE IF NOT EXISTS `chi_tiet_phieu_nhap` (
  `id`        INT(11)       NOT NULL AUTO_INCREMENT,
  `id_phieu`  INT(11)       DEFAULT NULL,
  `id_sp`     INT(11)       NOT NULL,
  `so_luong`  INT(11)       NOT NULL DEFAULT 0,
  `don_gia`   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ctpn_sp` FOREIGN KEY (`id_sp`) REFERENCES `san_pham`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ctpn_phieu` FOREIGN KEY (`id_phieu`) REFERENCES `phieu_nhap`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. BẢNG ĐƠN HÀNG
CREATE TABLE IF NOT EXISTS `don_hang` (
  `id`            INT(11)        NOT NULL AUTO_INCREMENT,
  `ma_don`        VARCHAR(50)    NOT NULL,
  `id_khach_hang` INT(11)        NOT NULL,
  `ngay_dat`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hoat_dong`     ENUM('dang_cho', 'dang_chuan_bi', 'cho_lay_hang', 'dang_van_chuyen', 'giao_thanh_cong', 'da_huy') NOT NULL DEFAULT 'dang_cho',
  `trang_thai_tt` ENUM('chua_thanh_toan', 'da_thanh_toan', 'hoan_tien') NOT NULL DEFAULT 'chua_thanh_toan',
  `dia_chi_giao`  VARCHAR(255)   NOT NULL,
  `phuong`        VARCHAR(100)   NOT NULL,
  `quan`          VARCHAR(100)   NOT NULL,
  `thanh_pho`     VARCHAR(100)   NOT NULL,
  `ly_do_huy`     TEXT           DEFAULT NULL,
  `tong_tien`     DECIMAL(12,2)  NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_don` (`ma_don`),
  CONSTRAINT `fk_donhang_user` FOREIGN KEY (`id_khach_hang`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. BẢNG CHI TIẾT ĐƠN HÀNG
CREATE TABLE IF NOT EXISTS `chi_tiet_don_hang` (
  `id`          INT(11)        NOT NULL AUTO_INCREMENT,
  `id_don_hang` INT(11)        NOT NULL,
  `id_san_pham` INT(11)        NOT NULL,
  `so_luong`    INT(11)        NOT NULL DEFAULT 1,
  `gia_ban`     DECIMAL(12,2)  NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ct_donhang` FOREIGN KEY (`id_don_hang`) REFERENCES `don_hang` (`id`),
  CONSTRAINT `fk_ct_sanpham` FOREIGN KEY (`id_san_pham`) REFERENCES `san_pham` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


