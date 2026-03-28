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
