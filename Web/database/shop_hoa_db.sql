-- =====================================================
-- DATABASE: shop_hoa_db (Hệ thống quản lý bán hoa)
-- =====================================================

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
  `ma_loai`   VARCHAR(20)  NOT NULL,
  `ten_loai`  VARCHAR(100) NOT NULL,
  `ty_le_loi_nhuan` DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT '% lợi nhuận mặc định',
  `ngay_them` DATE         DEFAULT NULL,
  PRIMARY KEY (`ma_loai`),
  UNIQUE KEY `uk_ten_loai` (`ten_loai`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci
  COMMENT='Phân loại sản phẩm của shop hoa';

-- 3. BẢNG SẢN PHẨM
CREATE TABLE IF NOT EXISTS `san_pham` (
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

-- 4. BẢNG HÌNH ẢNH SẢN PHẨM
CREATE TABLE IF NOT EXISTS `san_pham_hinh_anh` (
  `ma_sp`       VARCHAR(30)  NOT NULL,
  `thu_tu`      INT(11)      NOT NULL DEFAULT 1,
  `duong_dan`   VARCHAR(255) NOT NULL,
  `ngay_them`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ma_sp`, `thu_tu`),
  CONSTRAINT `fk_sp_hinh_anh`
    FOREIGN KEY (`ma_sp`)
    REFERENCES `san_pham` (`ma_sp`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci
  COMMENT='Hình ảnh sản phẩm (hỗ trợ nhiều ảnh per sản phẩm)';

-- 5. BẢNG PHIẾU NHẬP
CREATE TABLE IF NOT EXISTS `phieu_nhap` (
  `ma_phieu`   VARCHAR(20) NOT NULL,
  `ngay_nhap`  DATE        NOT NULL,
  `trang_thai` ENUM('chua_hoan_thanh','hoan_thanh') NOT NULL DEFAULT 'chua_hoan_thanh',
  `ghi_chu`    TEXT        DEFAULT NULL,
  `ngay_tao`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ma_phieu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. BẢNG CHI TIẾT PHIẾU NHẬP
CREATE TABLE IF NOT EXISTS `chi_tiet_phieu_nhap` (
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

-- 7. BẢNG ĐƠN HÀNG
CREATE TABLE IF NOT EXISTS `don_hang` (
  `ma_don`        VARCHAR(50)    NOT NULL,
  `username`      INT(11)        NOT NULL,
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
  PRIMARY KEY (`ma_don`),
  UNIQUE KEY `ma_don` (`ma_don`),
  CONSTRAINT `fk_donhang_user` FOREIGN KEY (`username`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. BẢNG CHI TIẾT ĐƠN HÀNG
CREATE TABLE IF NOT EXISTS `chi_tiet_don_hang` (
  `ma_don`     VARCHAR(50)   NOT NULL,
  `ma_sp`      VARCHAR(30)   NOT NULL,
  `so_luong`   INT(11)       NOT NULL DEFAULT 1,
  `gia_ban`    DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`ma_don`,`ma_sp`),
  CONSTRAINT `fk_ct_donhang` FOREIGN KEY (`ma_don`) REFERENCES `don_hang` (`ma_don`),
  CONSTRAINT `fk_ct_sanpham` FOREIGN KEY (`ma_sp`) REFERENCES `san_pham` (`ma_sp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DỮ LIỆU MẪU
-- =====================================================

INSERT IGNORE INTO `loai_san_pham` (`ma_loai`, `ten_loai`, `ty_le_loi_nhuan`, `ngay_them`) VALUES
  ('LSP001', 'Thiệp & Phụ kiện', 40, '2026-03-02'),
  ('LSP002', 'Đồ trang trí', 43, '2026-03-03'),
  ('LSP003', 'Set quà tặng', 41, '2026-03-15'),
  ('LSP004', 'Handmade', 44, '2026-03-15'),
  ('LSP005', 'Quà lưu niệm', 45, '2026-03-15'),
  ('LSP006', 'Quà tặng & giỏ quà', 42, '2026-03-29'),
  ('LSP007', 'Hoa giấy', 44, '2026-03-31'),
  ('LSP008', 'Hoa thật 100%', 48, '2026-03-31');

INSERT IGNORE INTO `san_pham` 
  (`ma_sp`,`ten_sp`,`ma_loai`,`don_vi_tinh`,`so_luong_ton`,`gia_von`,`ty_le_loi_nhuan`,`gia_ban`,`hinh_anh`,`mo_ta`,`hien_trang`,`ngay_them`)
VALUES
  ('SP001','Thiệp đỏ trái tim',      'LSP001','Tấm',  80,  30000, 40,  42000,'SP001.jpg','Thiệp đỏ in hình trái tim lãng mạn, phù hợp tặng người yêu',          'hien_thi','2026-03-02'),
  ('SP002','Thiệp hoa cúc vàng',     'LSP001','Tấm',  60,  25000, 40,  35000,'SP002.jpg','Thiệp tươi sáng in họa tiết hoa cúc vàng rực rỡ, phù hợp tặng bạn bè và đồng nghiệp',              'hien_thi','2026-03-02'),
  ('SP003','Thiệp sinh nhật 3D',     'LSP001','Tấm',  50,  45000, 35,  60750,'SP003.jpg','Thiệp sinh nhật nổi 3D cắt giấy tinh xảo, ấn tượng độc đáo, quà tặng sinh nhật hoàn hảo',                'hien_thi','2026-03-03'),
  ('SP004','Thiệp giáng sinh',       'LSP001','Tấm', 100,  20000, 50,  30000,'SP004.jpg','Thiệp Noel đẹp in hình tuyết rơi và ông già Noel dễ thương, tạp chí chúc mừng Giáng sinh',               'hien_thi','2026-03-04'),
  ('SP005','Phong bì nhung đỏ',      'LSP001','Cái', 200,   8000, 50,  12000,'SP005.jpg','Phong bì sang trọng bìa nhung màu đỏ cao cấp, dùng đựng thiệp hoặc tiền mừng, quà tặng sang trọng',     'hien_thi','2026-03-05'),
  ('SP006','Nơ ruy băng satin',      'LSP001','Cái', 300,   5000, 60,   8000,'SP006.jpg','Nơ ruy băng satin mềm mại nhiều màu, dùng trang trí hộp quà và bó hoa đẹp',                  'hien_thi','2026-03-06'),
  ('SP007','Dây thừng bao bì jute',  'LSP001','Mét', 500,   3000, 67,   5000,'SP007.jpg','Dây jute thô tự nhiên, decor phong cách rustic vintage, bao bì bó hoa thân thiện môi trường',                       'hien_thi','2026-03-07'),
  ('SP008','Sticker hoa trang trí',  'LSP001','Tờ',  150,  12000, 42,  17000,'SP008.jpg','Tờ sticker hoa nhiều mẫu đa sắc màu, dán trang trí thiệp, quà tặng, scrapbook sáng tạo',            'hien_thi','2026-03-08'),
  ('SP009','Kẹp gỗ mini',           'LSP001','Hộp', 120,  18000, 39,  25000,'SP009.jpg','Hộp 20 kẹp gỗ mini xinh xắn tiện lợi, kẹp ảnh hoặc thiệp, trang trí nhỏ gọn đáng yêu',                    'hien_thi','2026-03-09'),
  ('SP010','Thiệp tốt nghiệp',       'LSP001','Tấm',  70,  30000, 40,  42000,'SP010.jpg','Thiệp chúc mừng tốt nghiệp in hình học vị vàng và hoa rực rỡ, kỷ niệm một cột mốc quan trọng',             'hien_thi','2026-03-10'),
  ('SP011','Lọ thủy tinh mini',      'LSP002','Cái',  60,  35000, 43,  50000,'SP011.jpg','Lọ thủy tinh nhỏ xinh cắm hoa đơn hoặc hoa khô, trang trí bàn làm việc ấm cúng',           'hien_thi','2026-03-03'),
  ('SP012','Đèn fairy light 3m',     'LSP002','Cuộn', 80,  55000, 45,  79750,'SP012.jpg','Đèn đom đóm LED 3m pin AA tiết kiệm điện, trang trí phòng và tiệc lãng mạn',                       'hien_thi','2026-03-03'),
  ('SP013','Khung ảnh gỗ 10x15',    'LSP002','Cái',  50,  40000, 50,  60000,'SP013.jpg','Khung ảnh gỗ thông tự nhiên 10x15cm phong cách vintage, để bàn hoặc treo tường',               'hien_thi','2026-03-04'),
  ('SP014','Bình gốm nhỏ trắng',    'LSP002','Cái',  40,  65000, 38,  89700,'SP014.jpg','Bình gốm trắng tối giản thiết kế Bắc Âu, cắm hoa khô hoặc cành lá xanh tạo điểm nhấn',               'hien_thi','2026-03-05'),
  ('SP015','Nến thơm hoa hồng',      'LSP002','Cái',  90,  45000, 44,  64800,'SP015.jpg','Nến thơm hương hoa hồng tự nhiên, thời gian cháy 20h, tạo không gian thư giãn',                 'hien_thi','2026-03-06'),
  ('SP016','Vòng hoa khô treo tường','LSP002','Cái',  30, 120000, 42, 170400,'SP016.jpg','Vòng hoa khô handmade đường kính 25cm, trang trí cửa hoặc tường phòng ngủ lãng mạn',       'hien_thi','2026-03-07'),
  ('SP017','Chậu đất nung mini',     'LSP002','Cái', 100,  25000, 48,  37000,'SP017.jpg','Chậu đất nung mini 8cm thiên nhiên, trồng xương rồng hoặc succulent nhỏ',              'hien_thi','2026-03-08'),
  ('SP018','Pebble đá trang trí',    'LSP002','Túi',  70,  30000, 43,  42900,'SP018.jpg','Túi đá cuội nhiều màu 200g, trang trí chậu cây cảnh và hồ thủy sinh',           'hien_thi','2026-03-09'),
  ('SP019','Hộp gỗ đựng quà',       'LSP002','Cái',  45,  75000, 40, 105000,'SP019.jpg','Hộp gỗ thông nắp trượt kiểu nhật bản, đựng quà tặng cao cấp, bền đẹp',                        'hien_thi','2026-03-10'),
  ('SP020','Dây đèn LED hình sao',   'LSP002','Cuộn', 60,  48000, 46,  70080,'SP020.jpg','Dây LED 2m hình ngôi sao nhỏ tiết kiệm điện, trang trí phòng ngủ ấm áp dễ thương',              'hien_thi','2026-03-11'),
  ('SP021','Set quà sinh nhật hồng', 'LSP003','Set',  25, 180000, 44, 259200,'SP021.jpg','Set gồm thiệp, nơ, 2 nến thơm cao cấp và 1 lọ hoa khô nhỏ, quà sinh nhật hoàn chỉnh',                   'hien_thi','2026-03-15'),
  ('SP022','Set quà Valentine đỏ',   'LSP003','Set',  30, 250000, 40, 350000,'SP022.jpg','Set Valentine gồm hộp chocolate, thiệp lãng mạn và hoa hồng nhỏ, thể hiện tình yêu',               'hien_thi','2026-03-15'),
  ('SP023','Set quà tốt nghiệp xanh','LSP003','Set',  20, 200000, 45, 290000,'SP023.jpg','Set mừng tốt nghiệp gồm thiệp, bút ký cao cấp và khung ảnh gỗ, kỷ niệm quan trọng',                'hien_thi','2026-03-16'),
  ('SP024','Set quà 8/3 pastel',     'LSP003','Set',  35, 220000, 41, 310200,'SP024.jpg','Set Ngày Quốc tế Phụ nữ gồm nước hoa mini, thiệp hoa cúc và nơ lụa lãng mạn',   'hien_thi','2026-03-16'),
  ('SP025','Set quà trẻ em gấu bông','LSP003','Set',  28, 150000, 47, 220500,'SP025.jpg','Set dành cho bé gồm gấu bông nhỏ dễ thương, thiệp màu sắc và kẹo ngon',                   'hien_thi','2026-03-17'),
  ('SP026','Set quà cưới vàng',      'LSP003','Set',  15, 350000, 43, 500500,'SP026.jpg','Set mừng đám cưới sang trọng gồm thiệp, nến cặp vàng và hoa khô bền',            'hien_thi','2026-03-17'),
  ('SP027','Set quà sức khỏe xanh',  'LSP003','Set',  22, 280000, 39, 389200,'SP027.jpg','Set chăm sóc sức khỏe gồm dầu gội thảo dược, nến và trà hoa cúc thiên nhiên',      'hien_thi','2026-03-18'),
  ('SP028','Set quà Trung thu',      'LSP003','Set',  40, 190000, 42, 269800,'SP028.jpg','Set Trung thu gồm đèn lồng mini, bánh Trung thu và thiệp chúc',                   'hien_thi','2026-03-18'),
  ('SP029','Set quà Tết đỏ vàng',   'LSP003','Set',  50, 320000, 41, 451200,'SP029.jpg','Set Tết gồm hộp mứt truyền thống, phong bao lì xì tươi và hoa đào nhỏ may mắn',                  'hien_thi','2026-03-19'),
  ('SP030','Set quà thầy cô',        'LSP003','Set',  18, 170000, 47, 249900,'SP030.jpg','Set tri ân thầy cô gồm thiệp, bút gỗ cao cấp và hoa cúc khô bền',                  'hien_thi','2026-03-20'),
  ('SP031','Vòng tay đan dây macrame','LSP004','Cái',  50,  45000, 44,  64800,'SP031.jpg','Vòng tay macrame thủ công thết kế tinh tế, chỉnh được kích thước, nhiều màu sắc',       'hien_thi','2026-03-15'),
  ('SP032','Túi vải canvas vẽ tay',  'LSP004','Cái',  30,  90000, 44, 129600,'SP032.jpg','Túi canvas vẽ tay hình hoa độc đáo, mỗi cái là một tác phẩm chính hãng lạ lẫm',          'hien_thi','2026-03-15'),
  ('SP033','Bookmark sách da khắc', 'LSP004','Cái',  80,  30000, 50,  45000,'SP033.jpg','Bookmark da bò thật khắc hình hoa tinh xảo, bền đẹp theo năm tháng, quà lưu niệm',              'hien_thi','2026-03-16'),
  ('SP034','Tranh len thêu hoa',     'LSP004','Bức',  20, 150000, 47, 220500,'SP034.jpg','Tranh thêu len hình hoa đồng nội thủ công, khung 20x20cm sẵn treo tường nhà',        'hien_thi','2026-03-16'),
  ('SP035','Gương soi decor macrame','LSP004','Cái',  15, 200000, 45, 290000,'SP035.jpg','Gương tròn viền macrame thủ công thiết kế sang trọng đường kính 30cm',                      'hien_thi','2026-03-17'),
  ('SP036','Móc khóa đất sét mini', 'LSP004','Cái', 100,  20000, 50,  30000,'SP036.jpg','Móc khóa đất sét nung hình hoa quả dễ thương, nhiều mẫu màu sắc khác nhau',               'hien_thi','2026-03-17'),
  ('SP037','Hộp nhạc gỗ khắc tên', 'LSP004','Cái',  12, 280000, 43, 400400,'SP037.jpg','Hộp nhạc gỗ có thể khắc tên theo yêu cầu, giai điệu tùy chọn, quà tặng độc đáo',          'hien_thi','2026-03-18'),
  ('SP038','Nến tạo hình bó hoa',   'LSP004','Cái',  40,  85000, 47, 124950,'SP038.jpg','Nến tạo hình bó hoa hồng thủ công đẹp mắt, mùi hương nhẹ nhàng thơm phức',               'hien_thi','2026-03-18'),
  ('SP039','Ví gấp giấy origami',   'LSP004','Cái',  60,  35000, 43,  50050,'SP039.jpg','Ví gấp giấy origami bọc nhựa chống nước tiện lợi, nhiều họa tiết độc đáo',               'hien_thi','2026-03-19'),
  ('SP040','Khung ảnh decoupage',   'LSP004','Cái',  25,  70000, 43, 100100,'SP040.jpg','Khung ảnh trang trí kỹ thuật decoupage hoa văn vintage, để bàn hoặc treo tường',                'hien_thi','2026-03-20'),
  ('SP041','Cốc sứ in hình hoa',    'LSP005','Cái',  60,  55000, 45,  79750,'SP041.jpg','Cốc sứ trắng in họa tiết hoa nổi bật, dung tích 350ml tiêu chuẩn, uống nước ấm tuyệt vời',                'hien_thi','2026-03-15'),
  ('SP042','Móc khóa pha lê hoa',   'LSP005','Cái', 120,  25000, 48,  37000,'SP042.jpg','Móc khóa pha lê hình bông hoa lấp lánh sáng bóng, nhiều màu sắc đẹp mắt',                     'hien_thi','2026-03-15'),
  ('SP043','Gối tựa in hoa vintage', 'LSP005','Cái',  35,  80000, 44, 115200,'SP043.jpg','Gối vuông vải cotton in hoa vintage, vỏ tháo ra giặt được, 40x40cm thoải mái',            'hien_thi','2026-03-16'),
  ('SP044','Tạp dề vải hoa bếp',    'LSP005','Cái',  40,  65000, 46,  94900,'SP044.jpg','Tạp dề vải in hoa bếp cá tính, có túi đựng đồ tiện dụng, unisex chất lượng',                             'hien_thi','2026-03-16'),
  ('SP045','Nam châm hoa tủ lạnh',  'LSP005','Cái', 200,  12000, 58,  18960,'SP045.jpg','Nam châm hoa tủ lạnh resin đổ khuôn cứng cáp, nhiều mẫu màu sắc khác biệt',                'hien_thi','2026-03-17'),
  ('SP046','Sổ tay bìa da hoa',     'LSP005','Quyển', 70, 60000, 42,  85200,'SP046.jpg','Sổ tay A5 bìa da PU in hoa nắn quân, 200 trang dot-grid dòng chấm gọn gàng',                        'hien_thi','2026-03-17'),
  ('SP047','Tô màu tranh hoa lớn',  'LSP005','Bộ',   30,  90000, 39, 125100,'SP047.jpg','Bộ tô màu tranh hoa khổ A3 lớn kèm 12 bút màu nước tươi sáng',                       'hien_thi','2026-03-18'),
  ('SP048','Lịch để bàn hoa 2026',  'LSP005','Cái',  55,  45000, 42,  63900,'SP048.jpg','Lịch để bàn 12 tháng ảnh hoa nghệ thuật, giấy couche cao cấp lì bóng',          'hien_thi','2026-03-18'),
  ('SP049','Thảm lót chuột hoa',    'LSP005','Cái',  45,  35000, 43,  50050,'SP049.jpg','Thảm lót chuột máy tính họa tiết hoa đẹp, chống trượt, 25x20cm tiêu chuẩn',             'hien_thi','2026-03-19'),
  ('SP050','Gương bỏ túi hoa',      'LSP005','Cái',  90,  22000, 50,  33000,'SP050.jpg','Gương bỏ túi tròn in hoa bìa cứng tiện lợi, đường kính 8cm gọn nhẹ',                     'hien_thi','2026-03-20'),
  ('SP051','Giỏ mây đan tự nhiên',  'LSP006','Cái',  40,  95000, 42, 134900,'SP051.jpg','Giỏ mây đan thủ công tự nhiên chắc chắn, nhiều kích cỡ, đựng quà rất đẹp',        'hien_thi','2026-03-29'),
  ('SP052','Giỏ quà Tết đỏ vàng',   'LSP006','Cái',  50, 280000, 43, 400400,'SP052.jpg','Giỏ quà Tết hoàn chỉnh may mắn: bánh kẹo, mứt và trái cây sấy cao cấp',        'hien_thi','2026-03-29'),
  ('SP053','Giỏ quà sức khỏe xanh', 'LSP006','Cái',  25, 350000, 40, 490000,'SP053.jpg','Giỏ quà sức khỏe gồm trà, mật ong, yến mạch và tinh dầu thiên nhiên hữu cơ',            'hien_thi','2026-03-30'),
  ('SP054','Giỏ quà spa thư giãn',   'LSP006','Cái',  20, 420000, 40, 588000,'SP054.jpg','Giỏ spa thư giãn gồm muối tắm hoa hồng, sữa tắm, nến thơm và khăn mặt',         'hien_thi','2026-03-30'),
  ('SP055','Hộp quà vuông nắp rời', 'LSP006','Cái',  80,  60000, 42,  85200,'SP055.jpg','Hộp giấy cứng vuông có nắp rời tinh tế, nhiều màu, đựng quà đa dạng',           'hien_thi','2026-03-31'),
  ('SP056','Giỏ quà trẻ em vui',    'LSP006','Cái',  35, 200000, 45, 290000,'SP056.jpg','Giỏ quà cho bé gồm đồ chơi nhỏ, kẹo ngon, sách tô màu và bút sáp màu',          'hien_thi','2026-03-31'),
  ('SP057','Giỏ quà cưới hạnh phúc','LSP006','Cái',  18, 500000, 40, 700000,'SP057.jpg','Giỏ quà đám cưới sang trọng gồm rượu vang, socola và hoa khô bền',          'hien_thi','2026-04-01'),
  ('SP058','Túi giấy kraft có quai','LSP006','Cái', 200,  12000, 58,  18960,'SP058.jpg','Túi giấy kraft nâu có quai dây bền chắc, nhiều size, in logo theo yêu cầu',       'hien_thi','2026-04-01'),
  ('SP059','Giấy gói quà họa tiết', 'LSP006','Tờ',  300,   8000, 50,  12000,'SP059.jpg','Giấy gói quà khổ lớn 70x100cm họa tiết hoa và sọc đa dạng màu sắc',            'hien_thi','2026-04-02'),
  ('SP060','Ruy băng voan lụa 5cm', 'LSP006','Cuộn', 150,  18000, 44,  25920,'SP060.jpg','Cuộn ruy băng voan lụa 5cm x 10m mềm mại, nhiều màu pastel nhạt',                   'hien_thi','2026-04-02'),
  ('SP061','Hoa hồng giấy nhung đỏ','LSP007','Bông',  80, 120000, 46, 175200,'SP061.jpg','Hoa hồng giấy nhang chề đỏ mượt màu, cánh phồng như màu thẫm không phai, cúc quay tự nhiên',            'hien_thi','2026-03-31'),
  ('SP062','Hoa cúc giấy vàng',     'LSP007','Bông', 100,  80000, 44, 115200,'SP062.jpg','Hoa cúc vàng giấy mỹ chi tiết, cánh rủi lỏng dung dỡ, cành vẫm dài, trang trí sang',            'hien_thi','2026-03-31'),
  ('SP063','Hoa anh đào giấy hồng', 'LSP007','Cành',  60, 150000, 47, 220500,'SP063.jpg','Cành anh đào giấy nhập Nhật, mỗi cành có 12 bông, lá vẽ tinh tế khác bài',                 'hien_thi','2026-04-01'),
  ('SP064','Hoa mẫu đơn giấy tím',  'LSP007','Bông',  45, 200000, 45, 290000,'SP064.jpg','Hoa mẫu đơn tím thủ công tốn kém, đường kính 20cm, cánh dày, sang trọng',                'hien_thi','2026-04-01'),
  ('SP065','Bó hoa giấy mix pastel','LSP007','Bó',    30, 350000, 43, 500500,'SP065.jpg','Bó hoa giấy màu nhạt nhàu 20 bông độc đáo, màu mềm nên không phai, làm quà tặng tối',           'hien_thi','2026-04-01'),
  ('SP066','Hoa hướng dương giấy',  'LSP007','Bông',  70,  90000, 44, 129600,'SP066.jpg','Hoa hướng dương giấy vàng sáng rỏi tươi vui, đường kính 15cm, quà trang trí nhà tươi',                      'hien_thi','2026-04-02'),
  ('SP067','Lẵng hoa giấy trang trí','LSP007','Lẵng', 20, 450000, 44, 648000,'SP067.jpg','Lẵng hoa giấy nghệ thuật 30 bông màu hủ hòa, cắm cần vàng đóc, lý tưởng cho hội nghị',             'hien_thi','2026-04-02'),
  ('SP068','Hoa tulip giấy',   'LSP007','Bông',  90, 100000, 45, 145000,'SP068.jpg','Hoa tulip xanh dương giấy mềm mềt, nhụy hoa vàng sne nổi bật, cây dặc trưng mằng',                 'hien_thi','2026-04-03'),
  ('SP069','Hoa sen giấy',    'LSP007','Bông',  55, 130000, 38, 179400,'SP069.jpg','Hoa sen trắng giấy gập tay cực kỳ tân, biểu tượng thanh cao khiêm nhường, trang trí tinh tế',           'hien_thi','2026-04-03'),
  ('SP070','Vòng hoa giấy treo cửa','LSP007','Cái',   25, 280000, 43, 400400,'SP070.jpg','Vòng hoa giấy đường kính 35cm, cánh hoa rễy rạc, treo cửa lộc mùa hay trang trí tiệc cưới',           'hien_thi','2026-04-04'),
  ('SP071','Hoa hồng đỏ Ecuador',   'LSP008','Bông',  200, 25000, 60,  40000,'SP071.jpg','Hoa hồng đỏ Ecuador nhập khẩu tươi tốt, cánh dày hương thơm nồng nàn',          'hien_thi','2026-03-31'),
  ('SP072','Hoa cẩm tú cầu tím',    'LSP008','Cành',   80, 45000, 56,  70200,'SP072.jpg','Hoa cẩm tú cầu tím xanh, 1 cành nhiều bông tròn đẹp phồng lên',                  'hien_thi','2026-03-31'),
  ('SP073','Hoa lan hồ điệp trắng', 'LSP008','Chậu',   30,320000, 44, 460800,'SP073.jpg','Chậu lan hồ điệp trắng 2 cành tươi tốt, sang trọng làm quà biếu',    'hien_thi','2026-04-01'),
  ('SP074','Hoa baby trắng',        'LSP008','Bó',    150, 30000, 50,  45000,'SP074.jpg','Bó hoa baby trắng nhỏ xinh tươi tắn, phụ kiện không thể thiếu trong bó hoa cưới',     'hien_thi','2026-04-01'),
  ('SP075','Hoa lily cam',          'LSP008','Bông',  100, 35000, 57,  54950,'SP075.jpg','Hoa lily cam tươi thơm nức tuyệt vời, 1 bông nhiều nụ nở dần liên tục',                    'hien_thi','2026-04-01'),
  ('SP076','Hoa hướng dương tươi',  'LSP008','Bông',  120, 20000, 50,  30000,'SP076.jpg','Hoa hướng dương tươi rời sáng rờng, mang lại năng lượng tích cực hạnh phúc',                'hien_thi','2026-04-02'),
  ('SP077','Bó hoa sinh nhật mix',  'LSP008','Bó',     50,180000, 44, 259200,'SP077.jpg','Bó hoa sinh nhật mix hồng, cúc, baby được cắm nghệ thuật sẵn hoàn hảo',          'hien_thi','2026-04-02'),
  ('SP078','Hoa tulip hà lan đỏ',   'LSP008','Bông',   90, 30000, 50,  45000,'SP078.jpg','Hoa tulip nhập từ Hà Lan tươi tốt, cánh mịn màu đỏ thuần khiết',                'hien_thi','2026-04-03'),
  ('SP079','Hoa cúc vạn thọ vàng',  'LSP008','Bó',   160, 15000, 53,  22950,'SP079.jpg','Bó cúc vạn thọ vàng tươi rời trẻng trạng, biểu tượng may mắn, sống lâu trăm năm',            'hien_thi','2026-04-03'),
  ('SP080','Chậu sen đá mix màu',   'LSP008','Chậu',   60, 85000, 41, 119850,'SP080.jpg','Chậu sen đá nhiều loại mix màu tươi tốt, để lâu, chăm sóc dễ dàng, phù hợp bàn làm việc',        'hien_thi','2026-04-04');

INSERT IGNORE INTO `phieu_nhap` (`ma_phieu`,`ngay_nhap`,`trang_thai`,`ghi_chu`) VALUES
  ('PN001', '2026-01-05', 'hoan_thanh', 'Nhập đợt 1 - Khởi đầu năm mới'),
  ('PN002', '2026-01-12', 'hoan_thanh', 'Nhập bổ sung đồ trang trí nội thất'),
  ('PN003', '2026-01-20', 'hoan_thanh', 'Nhập set quà cao cấp'),
  ('PN004', '2026-01-25', 'hoan_thanh', 'Nhập lô hàng handmade'),
  ('PN005', '2026-02-02', 'hoan_thanh', 'Nhập quà lưu niệm số lượng lớn'),
  ('PN006', '2026-02-10', 'hoan_thanh', 'Nhập giỏ quà và hộp quà'),
  ('PN007', '2026-02-14', 'hoan_thanh', 'Nhập gấp hoa giấy phục vụ Valentine'),
  ('PN008', '2026-02-20', 'hoan_thanh', 'Nhập hoa thật 100% đợt 1'),
  ('PN009', '2026-02-28', 'hoan_thanh', 'Nhập phụ kiện gói quà, ruy băng, nơ'),
  ('PN010', '2026-03-05', 'hoan_thanh', 'Nhập đồ trang trí mini để bàn'),
  ('PN011', '2026-03-10', 'hoan_thanh', 'Nhập set quà phục vụ mùng 8/3'),
  ('PN012', '2026-03-15', 'hoan_thanh', 'Nhập đồ handmade và nến thơm'),
  ('PN013', '2026-03-20', 'hoan_thanh', 'Nhập sổ tay và đồ lưu niệm văn phòng'),
  ('PN014', '2026-03-25', 'hoan_thanh', 'Nhập túi giấy, giấy gói quà sỉ'),
  ('PN015', '2026-03-28', 'hoan_thanh', 'Nhập thêm hoa giấy các loại'),
  ('PN016', '2026-04-01', 'hoan_thanh', 'Nhập lan hồ điệp và hoa baby tươi'),
  ('PN017', '2026-04-03', 'hoan_thanh', 'Nhập hoa tulip và cúc vạn thọ tươi'),
  ('PN018', '2026-04-05', 'chua_hoan_thanh', 'Đang kiểm kê lô thiệp 3D và thiệp tốt nghiệp'),
  ('PN019', '2026-04-06', 'chua_hoan_thanh', 'Đợi đối soát set quà Trung thu và Tết'),
  ('PN020', '2026-04-07', 'chua_hoan_thanh', 'Đang nhập kho sen đá mix màu');

INSERT IGNORE INTO `chi_tiet_phieu_nhap` (`ma_phieu`,`ma_sp`,`so_luong`,`don_gia`) VALUES
-- PN001 (Thiệp đỏ, Thiệp hoa cúc)
  ('PN001', 'SP001', 100, 30000),
  ('PN001', 'SP002', 150, 25000),

  -- PN002 (Lọ thủy tinh, Đèn fairy light, Khung ảnh gỗ)
  ('PN002', 'SP011', 50, 35000),
  ('PN002', 'SP012', 60, 55000),
  ('PN002', 'SP013', 40, 40000),

  -- PN003 (Set quà sinh nhật, Set Valentine)
  ('PN003', 'SP021', 30, 180000),
  ('PN003', 'SP022', 20, 250000),

  -- PN004 (Vòng tay macrame, Túi canvas)
  ('PN004', 'SP031', 100, 45000),
  ('PN004', 'SP032', 50, 90000),

  -- PN005 (Cốc sứ, Móc khóa pha lê, Nam châm hoa)
  ('PN005', 'SP041', 80, 55000),
  ('PN005', 'SP042', 200, 25000),
  ('PN005', 'SP045', 300, 12000),

  -- PN006 (Giỏ mây đan, Hộp quà vuông)
  ('PN006', 'SP051', 50, 95000),
  ('PN006', 'SP055', 100, 60000),

  -- PN007 (Hoa hồng giấy đỏ, Bó hoa mix pastel)
  ('PN007', 'SP061', 150, 120000),
  ('PN007', 'SP065', 40, 350000),

  -- PN008 (Hoa hồng đỏ Ecuador, Cẩm tú cầu tím, Hướng dương tươi)
  ('PN008', 'SP071', 300, 25000),
  ('PN008', 'SP072', 100, 45000),
  ('PN008', 'SP076', 200, 20000),

  -- PN009 (Phong bì nhung đỏ, Nơ ruy băng satin)
  ('PN009', 'SP005', 500, 8000),
  ('PN009', 'SP006', 400, 5000),

  -- PN010 (Nến thơm hoa hồng, Chậu đất nung mini)
  ('PN010', 'SP015', 100, 45000),
  ('PN010', 'SP017', 150, 25000),

  -- PN011 (Set quà 8/3 pastel, Set quà cưới vàng)
  ('PN011', 'SP024', 50, 220000),
  ('PN011', 'SP026', 30, 350000),

  -- PN012 (Gương soi decor, Nến tạo hình bó hoa)
  ('PN012', 'SP035', 20, 200000),
  ('PN012', 'SP038', 80, 85000),

  -- PN013 (Sổ tay bìa da, Lịch để bàn 2026)
  ('PN013', 'SP046', 100, 60000),
  ('PN013', 'SP048', 80, 45000),

  -- PN014 (Túi giấy kraft, Giấy gói quà, Ruy băng voan lụa)
  ('PN014', 'SP058', 500, 12000),
  ('PN014', 'SP059', 1000, 8000),
  ('PN014', 'SP060', 300, 18000),

  -- PN015 (Hoa cúc giấy vàng, Hoa tulip giấy xanh)
  ('PN015', 'SP062', 200, 80000),
  ('PN015', 'SP068', 150, 100000),

  -- PN016 (Lan hồ điệp trắng, Hoa baby trắng)
  ('PN016', 'SP073', 50, 320000),
  ('PN016', 'SP074', 200, 30000),

  -- PN017 (Tulip hà lan đỏ, Cúc vạn thọ vàng)
  ('PN017', 'SP078', 150, 30000),
  ('PN017', 'SP079', 200, 15000),

  -- PN018 (Thiệp sinh nhật 3D, Thiệp tốt nghiệp) - Trạng thái: chưa hoàn thành
  ('PN018', 'SP003', 100, 45000),
  ('PN018', 'SP010', 120, 30000),

  -- PN019 (Set Trung thu, Set quà Tết) - Trạng thái: chưa hoàn thành
  ('PN019', 'SP028', 60, 190000),
  ('PN019', 'SP029', 80, 320000),

  -- PN020 (Chậu sen đá mix màu) - Trạng thái: chưa hoàn thành
  ('PN020', 'SP080', 100, 85000);
  
-- -----------------------------------------------
-- DỮ LIỆU MẪU: HÌNH ẢNH SẢN PHẨM
-- -----------------------------------------------
INSERT IGNORE INTO `san_pham_hinh_anh` (`ma_sp`, `thu_tu`, `duong_dan`) VALUES
  ('SP001', 1, 'SP001.jpg'),
  ('SP001', 2, 'SP001_1.jpg'),
  ('SP001', 3, 'SP001_2.jpg'),
  ('SP002', 1, 'SP002.jpg'),
  ('SP002', 2, 'SP002_1.jpg'),
  ('SP002', 3, 'SP002_2.jpg'),
  ('SP003', 1, 'SP003.jpg'),
  ('SP003', 2, 'SP003_1.jpg'),
  ('SP003', 3, 'SP003_2.jpg'),
  ('SP004', 1, 'SP004.jpg'),
  ('SP004', 1, 'SP004.jpg'),
  ('SP004', 2, 'SP004_1.jpg'),
  ('SP004', 3, 'SP004_2.jpg'),
  ('SP005', 1, 'SP005.jpg'),
  ('SP005', 2, 'SP005_1.jpg'),
  ('SP006', 1, 'SP006.jpg'),
  ('SP006', 2, 'SP006_1.jpg'),
  ('SP007', 1, 'SP007.jpg'),
  ('SP007', 2, 'SP007_1.jpg'),
  ('SP007', 3, 'SP007_2.jpg'),
  ('SP008', 1, 'SP008.jpg'),
  ('SP008', 2, 'SP008_1.jpg'),
  ('SP009', 1, 'SP009.jpg'),
  ('SP009', 2, 'SP009_1.jpg'),
  ('SP010', 1, 'SP010.jpg'),
  ('SP010', 2, 'SP010_1.jpg'),
  ('SP011', 1, 'SP011.jpg'),
  ('SP011', 2, 'SP011_1.jpg'),
  ('SP012', 1, 'SP012.jpg'),
  ('SP012', 2, 'SP012_1.jpg'),
  ('SP013', 1, 'SP013.jpg'),
  ('SP013', 2, 'SP013_1.jpg'),
  ('SP014', 1, 'SP014.jpg'),
  ('SP014', 2, 'SP014_1.jpg'),
  ('SP015', 1, 'SP015.jpg'),
  ('SP015', 2, 'SP015_1.jpg'),
  ('SP016', 1, 'SP016.jpg'),
  ('SP016', 2, 'SP016_1.jpg'),
  ('SP017', 1, 'SP017.jpg'),
  ('SP017', 2, 'SP017_1.jpg'),
  ('SP018', 1, 'SP018.jpg'),
  ('SP018', 2, 'SP018_1.jpg'),
  ('SP019', 1, 'SP019.jpg'),
  ('SP019', 2, 'SP019_1.jpg'),
  ('SP020', 1, 'SP020.jpg'),
  ('SP020', 2, 'SP020_1.jpg'),
  ('SP021', 1, 'SP021.jpg'),
  ('SP021', 2, 'SP021_1.jpg'),
  ('SP022', 1, 'SP022.jpg'),
  ('SP022', 2, 'SP022_1.jpg'),
  ('SP023', 1, 'SP023.jpg'),
  ('SP023', 2, 'SP023_1.jpg'),
  ('SP024', 1, 'SP024.jpg'),
  ('SP024', 2, 'SP024_1.jpg'),
  ('SP025', 1, 'SP025.jpg'),
  ('SP025', 2, 'SP025_1.jpg'),
  ('SP026', 1, 'SP026.jpg'),
  ('SP026', 2, 'SP026_1.jpg'),
  ('SP027', 1, 'SP027.jpg'),
  ('SP027', 2, 'SP027_1.jpg'),
  ('SP028', 1, 'SP028.jpg'),
  ('SP028', 2, 'SP028_1.jpg'),
  ('SP029', 1, 'SP029.jpg'),
  ('SP029', 2, 'SP029_1.jpg'),
  ('SP030', 1, 'SP030.jpg'),
  ('SP030', 2, 'SP030_1.jpg'),
  ('SP031', 1, 'SP031.jpg'),
  ('SP031', 2, 'SP031_1.jpg'),
  ('SP032', 1, 'SP032.jpg'),
  ('SP032', 2, 'SP032_1.jpg'),
  ('SP033', 1, 'SP033.jpg'),
  ('SP033', 2, 'SP033_1.jpg'),
  ('SP034', 1, 'SP034.jpg'),
  ('SP034', 2, 'SP034_1.jpg'),
  ('SP035', 1, 'SP035.jpg'),
  ('SP035', 2, 'SP035_1.jpg'),
  ('SP036', 1, 'SP036.jpg'),
  ('SP036', 2, 'SP036_1.jpg'),
  ('SP037', 1, 'SP037.jpg'),
  ('SP037', 2, 'SP037_1.jpg'),
  ('SP038', 1, 'SP038.jpg'),
  ('SP038', 2, 'SP038_1.jpg'),
  ('SP039', 1, 'SP039.jpg'),
  ('SP039', 2, 'SP039_1.jpg'),
  ('SP040', 1, 'SP040.jpg'),
  ('SP040', 2, 'SP040_1.jpg'),
  ('SP041', 1, 'SP041.jpg'),
  ('SP041', 2, 'SP041_1.jpg'),
  ('SP042', 1, 'SP042.jpg'),
  ('SP042', 2, 'SP042_1.jpg'),
  ('SP043', 1, 'SP043.jpg'),
  ('SP043', 2, 'SP043_1.jpg'),
  ('SP044', 1, 'SP044.jpg'),
  ('SP044', 2, 'SP044_1.jpg'),
  ('SP045', 1, 'SP045.jpg'),
  ('SP045', 2, 'SP045_1.jpg'),
  ('SP046', 1, 'SP046.jpg'),
  ('SP046', 2, 'SP046_1.jpg'),
  ('SP047', 1, 'SP047.jpg'),
  ('SP047', 2, 'SP047_1.jpg'),
  ('SP048', 1, 'SP048.jpg'),
  ('SP048', 2, 'SP048_1.jpg'),
  ('SP049', 1, 'SP049.jpg'),
  ('SP049', 2, 'SP049_1.jpg'),
  ('SP050', 1, 'SP050.jpg'),
  ('SP050', 2, 'SP050_1.jpg'),
  ('SP051', 1, 'SP051.jpg'),
  ('SP051', 2, 'SP051_1.jpg'),
  ('SP052', 1, 'SP052.jpg'),
  ('SP052', 2, 'SP052_1.jpg'),
  ('SP053', 1, 'SP053.jpg'),
  ('SP053', 2, 'SP053_1.jpg'),
  ('SP054', 1, 'SP054.jpg'),
  ('SP054', 2, 'SP054_1.jpg'),
  ('SP055', 1, 'SP055.jpg'),
  ('SP055', 2, 'SP055_1.jpg'),
  ('SP056', 1, 'SP056.jpg'),
  ('SP056', 2, 'SP056_1.jpg'),
  ('SP057', 1, 'SP057.jpg'),
  ('SP057', 2, 'SP057_1.jpg'),
  ('SP058', 1, 'SP058.jpg'),
  ('SP058', 2, 'SP058_1.jpg'),
  ('SP059', 1, 'SP059.jpg'),
  ('SP059', 2, 'SP059_1.jpg'),
  ('SP060', 1, 'SP060.jpg'),
  ('SP060', 2, 'SP060_1.jpg'),
  ('SP061', 1, 'SP061.jpg'),
  ('SP061', 2, 'SP061_1.jpg'),
  ('SP062', 1, 'SP062.jpg'),
  ('SP062', 2, 'SP062_1.jpg'),
  ('SP063', 1, 'SP063.jpg'),
  ('SP063', 2, 'SP063_1.jpg'),
  ('SP064', 1, 'SP064.jpg'),
  ('SP064', 2, 'SP064_1.jpg'),
  ('SP065', 1, 'SP065.jpg'),
  ('SP065', 2, 'SP065_1.jpg'),
  ('SP066', 1, 'SP066.jpg'),
  ('SP066', 2, 'SP066_1.jpg'),
  ('SP067', 1, 'SP067.jpg'),
  ('SP067', 2, 'SP067_1.jpg'),
  ('SP068', 1, 'SP068.jpg'),
  ('SP068', 2, 'SP068_1.jpg'),
  ('SP069', 1, 'SP069.jpg'),
  ('SP069', 2, 'SP069_1.jpg'),
  ('SP070', 1, 'SP070.jpg'),
  ('SP070', 2, 'SP070_1.jpg'),
  ('SP071', 1, 'SP071.jpg'),
  ('SP071', 2, 'SP071_1.jpg'),
  ('SP072', 1, 'SP072.jpg'),
  ('SP072', 2, 'SP072_1.jpg'),
  ('SP073', 1, 'SP073.jpg'),
  ('SP073', 2, 'SP073_1.jpg'),
  ('SP074', 1, 'SP074.jpg'),
  ('SP074', 2, 'SP074_1.jpg'),
  ('SP075', 1, 'SP075.jpg'),
  ('SP075', 2, 'SP075_1.jpg'),
  ('SP076', 1, 'SP076.jpg'),
  ('SP076', 2, 'SP076_1.jpg'),
  ('SP077', 1, 'SP077.jpg'),
  ('SP077', 2, 'SP077_1.jpg'),
  ('SP078', 1, 'SP078.jpg'),
  ('SP078', 2, 'SP078_1.jpg'),
  ('SP079', 1, 'SP079.jpg'),
  ('SP079', 2, 'SP079_1.jpg'),
  ('SP080', 1, 'SP080.jpg'),
  ('SP080', 2, 'SP080_1.jpg');

DELIMITER $$

-- 1. Trigger TỰ ĐỘNG GIẢM KHO khi có người đặt hàng
CREATE TRIGGER `trg_BanHang_GiamKho` 
AFTER INSERT ON `chi_tiet_don_hang` 
FOR EACH ROW 
BEGIN
    UPDATE `san_pham` 
    SET `so_luong_ton` = `so_luong_ton` - NEW.so_luong 
    WHERE `ma_sp` = NEW.ma_sp;
END$$

-- 2. Trigger TỰ ĐỘNG TĂNG KHO khi nhập hàng thành công
CREATE TRIGGER `trg_NhapHang_TangKho` 
AFTER INSERT ON `chi_tiet_phieu_nhap` 
FOR EACH ROW 
BEGIN
    UPDATE `san_pham` 
    SET `so_luong_ton` = `so_luong_ton` + NEW.so_luong 
    WHERE `ma_sp` = NEW.ma_sp;
END$$

-- 3. Trigger TỰ ĐỘNG TRẢ KHO khi đơn hàng bị hủy
CREATE TRIGGER `trg_HuyDon_TraKho`
AFTER UPDATE ON `don_hang`
FOR EACH ROW
BEGIN
    -- Nếu trạng thái mới là 'da_huy' và trạng thái cũ không phải 'da_huy'
    IF NEW.hoat_dong = 'da_huy' AND OLD.hoat_dong != 'da_huy' THEN
        UPDATE `san_pham` sp
        JOIN `chi_tiet_don_hang` ct ON sp.ma_sp = ct.ma_sp
        SET sp.so_luong_ton = sp.so_luong_ton + ct.so_luong
        WHERE ct.ma_don = NEW.ma_don;
    END IF;
END$$

DELIMITER ;



