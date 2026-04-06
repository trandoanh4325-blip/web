-- ================================================================
--  giaban.sql  –  Cập nhật schema cho tính năng Giá Bán
--  Database: shop_hoa_db (đã có sẵn từ shop_hoa_db.sql)
--
--  File này CHỈ chứa phần CHƯA có trong shop_hoa_db.sql:
--    → Thêm cột ty_le_loi_nhuan vào loai_san_pham
--      để lưu % lợi nhuận mặc định theo loại.
--
--  Chạy 1 LẦN DUY NHẤT sau khi đã import shop_hoa_db.sql.
-- ================================================================

USE shop_hoa_db;

-- ----------------------------------------------------------------
--  Thêm cột ty_le_loi_nhuan vào loai_san_pham
--
--  Mục đích:
--    - Lưu % lợi nhuận mặc định cho cả loại
--    - Khi admin chọn loại trong giaban.html → % này tự điền
--    - Nếu san_pham.ty_le_loi_nhuan = 0 → dùng % của loại
--    - Nếu san_pham.ty_le_loi_nhuan > 0 → dùng % riêng của SP
--
--  Công thức giá bán:
--    gia_ban = gia_von * (1 + ty_le_hieu_dung / 100)
--    ty_le_hieu_dung = IF(sp.ty_le > 0, sp.ty_le, loai.ty_le)
-- ----------------------------------------------------------------
ALTER TABLE `loai_san_pham`
  ADD COLUMN IF NOT EXISTS `ty_le_loi_nhuan`
    DECIMAL(6,2) NOT NULL DEFAULT 0.00
    COMMENT '% lợi nhuận mặc định áp dụng cho SP thuộc loại này'
  AFTER `ten_loai`;

-- ----------------------------------------------------------------
--  Kiểm tra sau khi chạy
-- ----------------------------------------------------------------
-- DESCRIBE loai_san_pham;
--
-- SELECT
--   sp.ma_sp, sp.ten_sp,
--   l.ten_loai,
--   l.ty_le_loi_nhuan                                                AS pct_loai,
--   sp.ty_le_loi_nhuan                                               AS pct_sp,
--   IF(sp.ty_le_loi_nhuan > 0,
--      sp.ty_le_loi_nhuan,
--      l.ty_le_loi_nhuan)                                            AS pct_hieu_dung,
--   sp.gia_von,
--   sp.gia_ban
-- FROM san_pham sp
-- LEFT JOIN loai_san_pham l ON sp.id_loai = l.id;
