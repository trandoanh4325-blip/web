-- ================================================================
--  giaban.sql
--  Mục đích: Chuẩn bị CSDL cho tính năng Quản lý Giá Bán
--
--  File này gồm 2 phần:
--    [PHẦN 1] Các bảng liên quan đã có sẵn trong schema gốc
--             → KHÔNG cần chạy lại nếu bảng đã tồn tại
--             → Dùng CREATE TABLE IF NOT EXISTS để an toàn
--    [PHẦN 2] Thêm cột ty_le_loi_nhuan vào loai_san_pham
--             → Cột này CHƯA có trong schema gốc, cần chạy 1 lần
--
--  Lý do file migration cũ chỉ có ALTER:
--    Vì bảng san_pham, loai_san_pham đã có đủ trong schema bạn
--    gửi. Chỉ còn thiếu cột ty_le_loi_nhuan ở loai_san_pham
--    để hỗ trợ "lợi nhuận mặc định theo loại".
-- ================================================================

USE shop_hoa_db;  -- ← đổi tên database nếu cần


-- 2. BẢNG LOẠI SẢN PHẨM
CREATE TABLE IF NOT EXISTS `loai_san_pham` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `ma_loai`          VARCHAR(20)  NOT NULL,
  `ten_loai`         VARCHAR(100) NOT NULL,
  `ngay_them`        DATE         DEFAULT NULL,
  -- [PHẦN 2 bên dưới sẽ thêm cột ty_le_loi_nhuan vào đây]
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ma_loai`  (`ma_loai`),
  UNIQUE KEY `uk_ten_loai` (`ten_loai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. BẢNG SẢN PHẨM
-- Các cột liên quan đến giá bán:
--   gia_von          → giá vốn, được cập nhật bởi phiếu nhập (bình quân gia quyền)
--   ty_le_loi_nhuan  → % lợi nhuận riêng của SP (0 = dùng % của loại)
--   gia_ban          → giá bán = gia_von * (1 + ty_le_loi_nhuan/100)
--                      PHP tính và lưu khi admin cập nhật
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
  CONSTRAINT `fk_sp_loai`
    FOREIGN KEY (`id_loai`) REFERENCES `loai_san_pham` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
--  [PHẦN 2] – THÊM CỘT MỚI (chỉ chạy 1 lần)
--
--  loai_san_pham chưa có cột ty_le_loi_nhuan trong schema gốc.
--  Cột này lưu % lợi nhuận mặc định cho cả loại.
--
--  Quy tắc ưu tiên trong giaban_api.php:
--    Nếu san_pham.ty_le_loi_nhuan > 0  → dùng % riêng SP
--    Ngược lại                          → dùng loai_san_pham.ty_le_loi_nhuan
--    gia_ban = gia_von * (1 + ty_le_hieu_dung / 100)
-- ================================================================

ALTER TABLE `loai_san_pham`
  ADD COLUMN IF NOT EXISTS `ty_le_loi_nhuan`
    DECIMAL(6,2) NOT NULL DEFAULT 0.00
    COMMENT '% lợi nhuận mặc định cho SP thuộc loại này'
  AFTER `ten_loai`;

-- ================================================================
--  KIỂM TRA sau khi chạy
-- ================================================================
-- Xem cấu trúc bảng loai_san_pham:
-- DESCRIBE loai_san_pham;

-- Xem giá bán + tỉ lệ lợi nhuận đang có:
-- SELECT
--   sp.ma_sp, sp.ten_sp,
--   l.ten_loai,
--   l.ty_le_loi_nhuan   AS tl_loai,
--   sp.ty_le_loi_nhuan  AS tl_rieng_sp,
--   IF(sp.ty_le_loi_nhuan > 0, sp.ty_le_loi_nhuan, l.ty_le_loi_nhuan) AS tl_hieu_dung,
--   sp.gia_von,
--   sp.gia_ban
-- FROM san_pham sp
-- LEFT JOIN loai_san_pham l ON sp.id_loai = l.id;
