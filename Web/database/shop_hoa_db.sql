-- ===== DATABASE SHOP HOA (QUẢN LÝ SẢN PHẨM) =====
-- Phiên bản: Chỉ cấu trúc, không có dữ liệu mẫu

-- Tạo database
CREATE DATABASE IF NOT EXISTS shop_hoa;
USE shop_hoa;

-- ===== BẢNG USERS =====
CREATE TABLE IF NOT EXISTS users (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(50) NOT NULL,
  full_name varchar(100) NOT NULL,
  phone varchar(20) NOT NULL,
  address text NOT NULL,
  email varchar(100) NOT NULL,
  password varchar(255) NOT NULL,
  role enum('customer','admin') DEFAULT 'customer',
  status enum('active','locked') DEFAULT 'active',
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY username (username),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== BẢNG LOẠI SẢN PHẨM =====
CREATE TABLE IF NOT EXISTS loai_san_pham (
  id int(11) NOT NULL AUTO_INCREMENT,
  ma_loai varchar(50) NOT NULL,
  ten_loai varchar(100) NOT NULL,
  ngay_them date NOT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== BẢNG SẢN PHẨM =====
CREATE TABLE IF NOT EXISTS san_pham (
  id int(11) NOT NULL AUTO_INCREMENT,
  ma_sp varchar(50) NOT NULL,
  ten_sp varchar(100) NOT NULL,
  id_loai int(11) NOT NULL,
  mo_ta longtext,
  so_luong_ton int(11) DEFAULT 0,
  gia_von decimal(10,2) DEFAULT 0,
  gia_ban decimal(10,2) DEFAULT 0,
  hinh_anh varchar(255),
  ngay_them date NOT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY ma_sp (ma_sp),
  KEY id_loai (id_loai),
  CONSTRAINT san_pham_ibfk_1 FOREIGN KEY (id_loai) REFERENCES loai_san_pham (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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