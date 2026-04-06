CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL, -- Dùng cho trang cá nhân
  `phone` varchar(20) NOT NULL, -- Dùng để liên hệ giao hàng
  `address` text NOT NULL, -- Địa chỉ mặc định khi xuất hóa đơn/giỏ hàng
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL, -- Mật khẩu sẽ được mã hóa dài nên để 255
  `role` enum('customer','admin') DEFAULT 'customer', -- Mặc định đăng ký là khách
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE users ADD COLUMN status ENUM('active', 'locked') DEFAULT 'active';


-- -------------------------------------------------------------
-- 1. BANG LOAI SAN PHAM
-- -------------------------------------------------------------
CREATE TABLE `loai_san_pham` (
  `id`        INT(11)      NOT NULL AUTO_INCREMENT,
  `ma_loai`   VARCHAR(20)  NOT NULL,
  `ten_loai`  VARCHAR(100) NOT NULL,
  `ngay_them` DATE         DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ma_loai`  (`ma_loai`),
  UNIQUE KEY `uk_ten_loai` (`ten_loai`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci
  COMMENT='Phan loai san pham cua shop hoa';
 
-- -------------------------------------------------------------
-- 2. BANG SAN PHAM
-- -------------------------------------------------------------
CREATE TABLE `san_pham` (
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
  KEY `fk_sp_loai_idx` (`id_loai`),
  CONSTRAINT `fk_sp_loai`
    FOREIGN KEY (`id_loai`)
    REFERENCES `loai_san_pham` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci
  COMMENT='Danh muc san pham cua shop hoa';
 
-- -------------------------------------------------------------
-- -------------------------------------------------------------
-- 3. DU LIEU MAU (tuy chon – xoa neu khong can)
-- -------------------------------------------------------------
INSERT IGNORE INTO `loai_san_pham` (`ma_loai`, `ten_loai`, `ngay_them`) VALUES
  ('LSP001', 'Hoa tuoi',          '2025-01-01'),
  ('LSP002', 'Hoa kho / lua',     '2025-01-01'),
  ('LSP003', 'Chau cay canh',     '2025-01-01'),
  ('LSP004', 'Bo hoa qua tang',   '2025-01-01'),
  ('LSP005', 'Phu kien trang tri','2025-01-01');
 
INSERT IGNORE INTO `san_pham`
  (`ma_sp`,`ten_sp`,`id_loai`,`don_vi_tinh`,
   `so_luong_ton`,`gia_von`,`ty_le_loi_nhuan`,`gia_ban`,
   `mo_ta`,`hien_trang`,`ngay_them`)
VALUES
  ('SP001','Bo hoa hong do',   1,'Bo',   50, 80000, 30, 104000,'Bo hoa hong do 12 bong dep',      'hien_thi','2025-01-10'),
  ('SP002','Hoa huong duong',  1,'Bong',100, 15000, 40,  21000,'Hoa huong duong tuoi sang',       'hien_thi','2025-01-10'),
  ('SP003','Chau lan ho diep', 3,'Chau', 20,250000, 50, 375000,'Chau lan ho diep nhap khau',      'hien_thi','2025-02-01'),
  ('SP004','Bo hoa cuoi',      4,'Bo',   10,500000, 60, 800000,'Bo hoa cuoi cao cap phong cach',  'hien_thi','2025-03-01'),
  ('SP005','Hoa kho lavender', 2,'Bo',   30, 45000, 55,  69750,'Hoa kho lavender nhap Phap',      'an',       '2025-03-15');



-- 1. BANG PHIEU NHAP (dau phieu - thong tin chung)
-- -------------------------------------------------------------
CREATE TABLE `phieu_nhap` (
  `id`         INT(11)   NOT NULL AUTO_INCREMENT,
  `ma_phieu`   VARCHAR(20) NOT NULL,
  `ngay_nhap`  DATE        NOT NULL,
  `trang_thai` ENUM('chua_hoan_thanh','hoan_thanh') NOT NULL DEFAULT 'chua_hoan_thanh',
  `ghi_chu`    TEXT        DEFAULT NULL,
  `ngay_tao`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ma_phieu` (`ma_phieu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Dau phieu nhap hang';
 
 CREATE TABLE `chi_tiet_phieu_nhap` (
  `id`        INT(11)       NOT NULL AUTO_INCREMENT,
  `id_phieu`  INT(11)       DEFAULT NULL,
  `id_sp`     INT(11)       NOT NULL,
  `so_luong`  INT(11)       NOT NULL DEFAULT 0,
  `don_gia`   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_ctpn_sp_idx` (`id_sp`),
  KEY `fk_ctpn_phieu_idx` (`id_phieu`),
  CONSTRAINT `fk_ctpn_sp`
    FOREIGN KEY (`id_sp`) REFERENCES `san_pham`(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ctpn_phieu`
    FOREIGN KEY (`id_phieu`) REFERENCES `phieu_nhap`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
<<<<<<< HEAD

-- ===== BẢNG ĐƠN HÀNG (Cập nhật theo Giao diện của bạn) =====
CREATE TABLE IF NOT EXISTS don_hang (
  id            INT(11)        NOT NULL AUTO_INCREMENT,
  ma_don        VARCHAR(50)    NOT NULL, -- Hiển thị ở cột "Mã đơn"
  id_khach_hang INT(11)        NOT NULL, -- Liên kết với bảng users
  ngay_dat      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Cột "Ngày đặt"
  
  -- Hoạt động (Trạng thái xử lý vận chuyển)
  hoat_dong     ENUM(
                  'dang_cho', 
                  'dang_chuan_bi', 
                  'cho_lay_hang', 
                  'dang_van_chuyen', 
                  'giao_thanh_cong', 
                  'da_huy'
                )              NOT NULL DEFAULT 'dang_cho',

  -- Trạng thái thanh toán (Dùng cho Badge Trạng thái)
  trang_thai_tt ENUM(
                  'chua_thanh_toan',
                  'da_thanh_toan',
                  'hoan_tien'
                )              NOT NULL DEFAULT 'chua_thanh_toan',

  -- Thông tin giao hàng (Khớp với các ô tìm kiếm/lọc của bạn)
  dia_chi_giao  VARCHAR(255)   NOT NULL, -- Số nhà, tên đường
  phuong        VARCHAR(100)   NOT NULL, -- Cột "Phường" & Ô lọc Phường
  quan          VARCHAR(100)   NOT NULL, 
  thanh_pho     VARCHAR(100)   NOT NULL, -- Cột "Thành phố" & Ô lọc Thành phố
  
  ly_do_huy     TEXT           DEFAULT NULL, -- Cột "Lý do hủy"
  tong_tien     DECIMAL(12,2)  NOT NULL DEFAULT 0, -- Cột "Tổng tiền"
  
  created_at    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY ma_don (ma_don),
  KEY idx_id_khach_hang (id_khach_hang),
  CONSTRAINT fk_donhang_user FOREIGN KEY (id_khach_hang) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== BẢNG CHI TIẾT ĐƠN HÀNG (Để hiển thị trong Modal chi tiết) =====
CREATE TABLE IF NOT EXISTS chi_tiet_don_hang (
  id          INT(11)        NOT NULL AUTO_INCREMENT,
  id_don_hang INT(11)        NOT NULL,
  id_san_pham INT(11)        NOT NULL,
  so_luong    INT(11)        NOT NULL DEFAULT 1,
  gia_ban     DECIMAL(12,2)  NOT NULL, -- Lưu giá tại thời điểm mua
  PRIMARY KEY (id),
  CONSTRAINT fk_ct_donhang FOREIGN KEY (id_don_hang) REFERENCES don_hang (id),
  CONSTRAINT fk_ct_sanpham FOREIGN KEY (id_san_pham) REFERENCES san_pham (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
=======
-- -------------------------------------------------------------
-- 3. DU LIEU MAU PHIEU NHAP (tuy chon)
-- -------------------------------------------------------------
INSERT IGNORE INTO `phieu_nhap` (`ma_phieu`,`ngay_nhap`,`trang_thai`,`ghi_chu`) VALUES
  ('PN001','2025-01-10','hoan_thanh',      'Nhap hang thang 1 - lan 1'),
  ('PN002','2025-02-01','hoan_thanh',      'Nhap hang thang 2'),
  ('PN003','2025-03-15','chua_hoan_thanh', 'Dang cho kiem hang - chua duyet');
 
-- Chi tiet PN001 (da hoan thanh)
INSERT IGNORE INTO `chi_tiet_phieu_nhap` (`id_phieu`,`id_sp`,`so_luong`,`don_gia`) VALUES
  (1,1,50, 80000),(1,2,100,15000);
 
-- Chi tiet PN002 (da hoan thanh)
INSERT IGNORE INTO `chi_tiet_phieu_nhap` (`id_phieu`,`id_sp`,`so_luong`,`don_gia`) VALUES
  (2,3,20,250000),(2,4,10,500000);
 
-- Chi tiet PN003 (chua hoan thanh - co the sua/them dong)
INSERT IGNORE INTO `chi_tiet_phieu_nhap` (`id_phieu`,`id_sp`,`so_luong`,`don_gia`) VALUES
  (3,5,30,45000);
>>>>>>> 6cb93ff38a2b765a4a4d8c58e4bd049c674dc369
