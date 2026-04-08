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
CREATE TABLE IF NOT EXISTS `loai_san_pham` (
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
  ('SP001','Thiệp đỏ trái tim',      'LSP001','Tấm',  80,  30000, 40,  42000,'Thiệp đỏ in hình trái tim lãng mạn, phù hợp tặng người yêu',          'hien_thi','2026-03-02'),
  ('SP002','Thiệp hoa cúc vàng',     'LSP001','Tấm',  60,  25000, 40,  35000,'Thiệp in hoa cúc vàng tươi sáng, thích hợp tặng bạn bè',              'hien_thi','2026-03-02'),
  ('SP003','Thiệp sinh nhật 3D',     'LSP001','Tấm',  50,  45000, 35,  60750,'Thiệp nổi 3D cắt giấy tinh xảo, ấn tượng và độc đáo',                'hien_thi','2026-03-03'),
  ('SP004','Thiệp giáng sinh',       'LSP001','Tấm', 100,  20000, 50,  30000,'Thiệp Noel in hình tuyết rơi và ông già Noel dễ thương',               'hien_thi','2026-03-04'),
  ('SP005','Phong bì nhung đỏ',      'LSP001','Cái', 200,   8000, 50,  12000,'Phong bì nhung màu đỏ sang trọng, dùng đựng thiệp hoặc tiền mừng',     'hien_thi','2026-03-05'),
  ('SP006','Nơ ruy băng satin',      'LSP001','Cái', 300,   5000, 60,   8000,'Nơ ruy băng satin nhiều màu, dùng trang trí hộp quà',                  'hien_thi','2026-03-06'),
  ('SP007','Dây thừng bao bì jute',  'LSP001','Mét', 500,   3000, 67,   5000,'Dây jute thô tự nhiên, decor phong cách rustic',                       'hien_thi','2026-03-07'),
  ('SP008','Sticker hoa trang trí',  'LSP001','Tờ',  150,  12000, 42,  17000,'Tờ sticker hoa nhiều mẫu, dán trang trí thiệp và quà tặng',            'hien_thi','2026-03-08'),
  ('SP009','Kẹp gỗ mini',           'LSP001','Hộp', 120,  18000, 39,  25000,'Hộp 20 kẹp gỗ mini xinh xắn, kẹp ảnh hoặc thiệp',                    'hien_thi','2026-03-09'),
  ('SP010','Thiệp tốt nghiệp',       'LSP001','Tấm',  70,  30000, 40,  42000,'Thiệp chúc mừng tốt nghiệp, in hình học vị và hoa rực rỡ',             'hien_thi','2026-03-10'),
  ('SP011','Lọ thủy tinh mini',      'LSP002','Cái',  60,  35000, 43,  50000,'Lọ thủy tinh nhỏ xinh cắm hoa đơn, trang trí bàn làm việc',           'hien_thi','2026-03-03'),
  ('SP012','Đèn fairy light 3m',     'LSP002','Cuộn', 80,  55000, 45,  79750,'Đèn đom đóm 3m pin AA, trang trí phòng và tiệc',                       'hien_thi','2026-03-03'),
  ('SP013','Khung ảnh gỗ 10x15',    'LSP002','Cái',  50,  40000, 50,  60000,'Khung ảnh gỗ thông tự nhiên 10x15cm, phong cách vintage',               'hien_thi','2026-03-04'),
  ('SP014','Bình gốm nhỏ trắng',    'LSP002','Cái',  40,  65000, 38,  89700,'Bình gốm trắng tối giản, cắm hoa khô hoặc cành lá xanh',               'hien_thi','2026-03-05'),
  ('SP015','Nến thơm hoa hồng',      'LSP002','Cái',  90,  45000, 44,  64800,'Nến thơm hương hoa hồng tự nhiên, thời gian cháy 20h',                 'hien_thi','2026-03-06'),
  ('SP016','Vòng hoa khô treo tường','LSP002','Cái',  30, 120000, 42, 170400,'Vòng hoa khô handmade đường kính 25cm, trang trí cửa hoặc tường',       'hien_thi','2026-03-07'),
  ('SP017','Chậu đất nung mini',     'LSP002','Cái', 100,  25000, 48,  37000,'Chậu đất nung mini 8cm, trồng xương rồng hoặc succulents',              'hien_thi','2026-03-08'),
  ('SP018','Pebble đá trang trí',    'LSP002','Túi',  70,  30000, 43,  42900,'Túi đá cuội nhiều màu 200g, trang trí chậu cây và thủy sinh',           'hien_thi','2026-03-09'),
  ('SP019','Hộp gỗ đựng quà',       'LSP002','Cái',  45,  75000, 40, 105000,'Hộp gỗ thông nắp trượt, đựng quà tặng cao cấp',                        'hien_thi','2026-03-10'),
  ('SP020','Dây đèn LED hình sao',   'LSP002','Cuộn', 60,  48000, 46,  70080,'Dây LED 2m hình ngôi sao nhỏ, trang trí phòng ngủ ấm áp',              'hien_thi','2026-03-11'),
  ('SP021','Set quà sinh nhật hồng', 'LSP003','Set',  25, 180000, 44, 259200,'Set gồm thiệp, nơ, 2 nến thơm và 1 lọ hoa khô nhỏ',                   'hien_thi','2026-03-15'),
  ('SP022','Set quà Valentine đỏ',   'LSP003','Set',  30, 250000, 40, 350000,'Set Valentine gồm hộp chocolate, thiệp và hoa hồng nhỏ',               'hien_thi','2026-03-15'),
  ('SP023','Set quà tốt nghiệp xanh','LSP003','Set',  20, 200000, 45, 290000,'Set mừng tốt nghiệp gồm thiệp, bút ký và khung ảnh gỗ',                'hien_thi','2026-03-16'),
  ('SP024','Set quà 8/3 pastel',     'LSP003','Set',  35, 220000, 41, 310200,'Set Ngày Quốc tế Phụ nữ gồm nước hoa mini, thiệp hoa cúc và nơ lụa',   'hien_thi','2026-03-16'),
  ('SP025','Set quà trẻ em gấu bông','LSP003','Set',  28, 150000, 47, 220500,'Set dành cho bé gồm gấu bông nhỏ, thiệp màu và kẹo',                   'hien_thi','2026-03-17'),
  ('SP026','Set quà cưới vàng',      'LSP003','Set',  15, 350000, 43, 500500,'Set mừng đám cưới sang trọng gồm thiệp, nến cặp và hoa khô',            'hien_thi','2026-03-17'),
  ('SP027','Set quà sức khỏe xanh',  'LSP003','Set',  22, 280000, 39, 389200,'Set chăm sóc sức khỏe gồm dầu gội thảo dược, nến và trà hoa cúc',      'hien_thi','2026-03-18'),
  ('SP028','Set quà Trung thu',      'LSP003','Set',  40, 190000, 42, 269800,'Set Trung thu gồm đèn lồng mini, bánh và thiệp chúc',                   'hien_thi','2026-03-18'),
  ('SP029','Set quà Tết đỏ vàng',   'LSP003','Set',  50, 320000, 41, 451200,'Set Tết gồm hộp mứt, phong bao lì xì và hoa đào nhỏ',                  'hien_thi','2026-03-19'),
  ('SP030','Set quà thầy cô',        'LSP003','Set',  18, 170000, 47, 249900,'Set tri ân thầy cô gồm thiệp, bút gỗ và hoa cúc khô',                  'hien_thi','2026-03-20'),
  ('SP031','Vòng tay đan dây macrame','LSP004','Cái',  50,  45000, 44,  64800,'Vòng tay macrame thủ công, chỉnh được kích thước, nhiều màu sắc',       'hien_thi','2026-03-15'),
  ('SP032','Túi vải canvas vẽ tay',  'LSP004','Cái',  30,  90000, 44, 129600,'Túi canvas vẽ tay hình hoa, mỗi cái là một tác phẩm độc nhất',          'hien_thi','2026-03-15'),
  ('SP033','Bookmark sách da khắc', 'LSP004','Cái',  80,  30000, 50,  45000,'Bookmark da bò thật khắc hình hoa, bền đẹp theo năm tháng',              'hien_thi','2026-03-16'),
  ('SP034','Tranh len thêu hoa',     'LSP004','Bức',  20, 150000, 47, 220500,'Tranh thêu len hình hoa đồng nội, khung 20x20cm sẵn treo tường',        'hien_thi','2026-03-16'),
  ('SP035','Gương soi decor macrame','LSP004','Cái',  15, 200000, 45, 290000,'Gương tròn viền macrame thủ công đường kính 30cm',                      'hien_thi','2026-03-17'),
  ('SP036','Móc khóa đất sét mini', 'LSP004','Cái', 100,  20000, 50,  30000,'Móc khóa đất sét nung hình hoa quả dễ thương, nhiều mẫu',               'hien_thi','2026-03-17'),
  ('SP037','Hộp nhạc gỗ khắc tên', 'LSP004','Cái',  12, 280000, 43, 400400,'Hộp nhạc gỗ có thể khắc tên theo yêu cầu, giai điệu tùy chọn',          'hien_thi','2026-03-18'),
  ('SP038','Nến tạo hình bó hoa',   'LSP004','Cái',  40,  85000, 47, 124950,'Nến tạo hình bó hoa hồng thủ công, mùi hương nhẹ nhàng',               'hien_thi','2026-03-18'),
  ('SP039','Ví gấp giấy origami',   'LSP004','Cái',  60,  35000, 43,  50050,'Ví gấp giấy origami bọc nhựa chống nước, nhiều họa tiết',               'hien_thi','2026-03-19'),
  ('SP040','Khung ảnh decoupage',   'LSP004','Cái',  25,  70000, 43, 100100,'Khung ảnh trang trí kỹ thuật decoupage hoa văn vintage',                'hien_thi','2026-03-20'),
  ('SP041','Cốc sứ in hình hoa',    'LSP005','Cái',  60,  55000, 45,  79750,'Cốc sứ trắng in họa tiết hoa nổi bật, dung tích 350ml',                'hien_thi','2026-03-15'),
  ('SP042','Móc khóa pha lê hoa',   'LSP005','Cái', 120,  25000, 48,  37000,'Móc khóa pha lê hình bông hoa lấp lánh, nhiều màu',                     'hien_thi','2026-03-15'),
  ('SP043','Gối tựa in hoa vintage', 'LSP005','Cái',  35,  80000, 44, 115200,'Gối vuông vải cotton in hoa, vỏ tháo ra giặt được, 40x40cm',            'hien_thi','2026-03-16'),
  ('SP044','Tạp dề vải hoa bếp',    'LSP005','Cái',  40,  65000, 46,  94900,'Tạp dề vải in hoa, có túi đựng đồ, unisex',                             'hien_thi','2026-03-16'),
  ('SP045','Nam châm hoa tủ lạnh',  'LSP005','Cái', 200,  12000, 58,  18960,'Nam châm hoa tủ lạnh resin đổ khuôn, nhiều mẫu màu sắc',                'hien_thi','2026-03-17'),
  ('SP046','Sổ tay bìa da hoa',     'LSP005','Quyển', 70, 60000, 42,  85200,'Sổ tay A5 bìa da PU in hoa, 200 trang dot-grid',                        'hien_thi','2026-03-17'),
  ('SP047','Tô màu tranh hoa lớn',  'LSP005','Bộ',   30,  90000, 39, 125100,'Bộ tô màu tranh hoa khổ A3 kèm 12 bút màu nước',                       'hien_thi','2026-03-18'),
  ('SP048','Lịch để bàn hoa 2026',  'LSP005','Cái',  55,  45000, 42,  63900,'Lịch để bàn 12 tháng ảnh hoa nghệ thuật, giấy couche cao cấp',          'hien_thi','2026-03-18'),
  ('SP049','Thảm lót chuột hoa',    'LSP005','Cái',  45,  35000, 43,  50050,'Thảm lót chuột máy tính họa tiết hoa, chống trượt, 25x20cm',             'hien_thi','2026-03-19'),
  ('SP050','Gương bỏ túi hoa',      'LSP005','Cái',  90,  22000, 50,  33000,'Gương bỏ túi tròn in hoa bìa cứng, đường kính 8cm',                     'hien_thi','2026-03-20'),
  ('SP051','Giỏ mây đan tự nhiên',  'LSP006','Cái',  40,  95000, 42, 134900,'Giỏ mây đan thủ công tự nhiên, nhiều kích cỡ, đựng quà rất đẹp',        'hien_thi','2026-03-29'),
  ('SP052','Giỏ quà Tết đỏ vàng',   'LSP006','Cái',  50, 280000, 43, 400400,'Giỏ quà Tết hoàn chỉnh: bánh kẹo, mứt và trái cây sấy cao cấp',        'hien_thi','2026-03-29'),
  ('SP053','Giỏ quà sức khỏe xanh', 'LSP006','Cái',  25, 350000, 40, 490000,'Giỏ quà gồm trà, mật ong, yến mạch và tinh dầu thiên nhiên',            'hien_thi','2026-03-30'),
  ('SP054','Giỏ quà spa thư giãn',   'LSP006','Cái',  20, 420000, 40, 588000,'Giỏ spa gồm muối tắm hoa hồng, sữa tắm, nến thơm và khăn mặt',         'hien_thi','2026-03-30'),
  ('SP055','Hộp quà vuông nắp rời', 'LSP006','Cái',  80,  60000, 42,  85200,'Hộp giấy cứng vuông có nắp rời, nhiều màu, đựng quà đa dạng',           'hien_thi','2026-03-31'),
  ('SP056','Giỏ quà trẻ em vui',    'LSP006','Cái',  35, 200000, 45, 290000,'Giỏ quà cho bé gồm đồ chơi nhỏ, kẹo, sách tô màu và bút sáp',          'hien_thi','2026-03-31'),
  ('SP057','Giỏ quà cưới hạnh phúc','LSP006','Cái',  18, 500000, 40, 700000,'Giỏ quà đám cưới sang trọng gồm rượu vang, socola và hoa khô',          'hien_thi','2026-04-01'),
  ('SP058','Túi giấy kraft có quai','LSP006','Cái', 200,  12000, 58,  18960,'Túi giấy kraft nâu có quai dây, nhiều size, in logo theo yêu cầu',       'hien_thi','2026-04-01'),
  ('SP059','Giấy gói quà họa tiết', 'LSP006','Tờ',  300,   8000, 50,  12000,'Giấy gói quà khổ lớn 70x100cm, họa tiết hoa và sọc đa dạng',            'hien_thi','2026-04-02'),
  ('SP060','Ruy băng voan lụa 5cm', 'LSP006','Cuộn', 150,  18000, 44,  25920,'Cuộn ruy băng voan lụa 5cm x 10m, nhiều màu pastel',                   'hien_thi','2026-04-02'),
  ('SP061','Hoa hồng giấy nhung đỏ','LSP007','Bông',  80, 120000, 46, 175200,'Hoa hồng giấy nhung đỏ siêu thực, cánh mịn không phai màu',            'hien_thi','2026-03-31'),
  ('SP062','Hoa cúc giấy vàng',     'LSP007','Bông', 100,  80000, 44, 115200,'Hoa cúc vàng giấy nghệ thuật, cánh chi tiết, cành dài 40cm',            'hien_thi','2026-03-31'),
  ('SP063','Hoa anh đào giấy hồng', 'LSP007','Cành',  60, 150000, 47, 220500,'Cành anh đào giấy Nhật, 12 bông/cành, cực kỳ tinh tế',                 'hien_thi','2026-04-01'),
  ('SP064','Hoa mẫu đơn giấy tím',  'LSP007','Bông',  45, 200000, 45, 290000,'Hoa mẫu đơn tím thủ công, đường kính 20cm, sang trọng',                'hien_thi','2026-04-01'),
  ('SP065','Bó hoa giấy mix pastel','LSP007','Bó',    30, 350000, 43, 500500,'Bó hoa giấy mix pastel 20 bông, không phai, bền đẹp mãi mãi',           'hien_thi','2026-04-01'),
  ('SP066','Hoa hướng dương giấy',  'LSP007','Bông',  70,  90000, 44, 129600,'Hoa hướng dương giấy vàng tươi, đường kính 15cm',                      'hien_thi','2026-04-02'),
  ('SP067','Lẵng hoa giấy trang trí','LSP007','Lẵng', 20, 450000, 44, 648000,'Lẵng hoa giấy nghệ thuật 30 bông, trang trí bàn hội nghị',             'hien_thi','2026-04-02'),
  ('SP068','Hoa tulip giấy xanh',   'LSP007','Bông',  90, 100000, 45, 145000,'Hoa tulip xanh dương giấy mềm, nhụy hoa vàng nổi bật',                 'hien_thi','2026-04-03'),
  ('SP069','Hoa sen giấy trắng',    'LSP007','Bông',  55, 130000, 38, 179400,'Hoa sen trắng giấy origami thuần thục, biểu tượng thanh cao',           'hien_thi','2026-04-03'),
  ('SP070','Vòng hoa giấy treo cửa','LSP007','Cái',   25, 280000, 43, 400400,'Vòng hoa giấy đường kính 35cm, treo cửa hoặc trang trí tiệc',           'hien_thi','2026-04-04'),
  ('SP071','Hoa hồng đỏ Ecuador',   'LSP008','Bông',  200, 25000, 60,  40000,'Hoa hồng đỏ Ecuador nhập khẩu, cánh dày, hương thơm nồng nàn',          'hien_thi','2026-03-31'),
  ('SP072','Hoa cẩm tú cầu tím',    'LSP008','Cành',   80, 45000, 56,  70200,'Hoa cẩm tú cầu tím/xanh, 1 cành nhiều bông tròn đẹp',                  'hien_thi','2026-03-31'),
  ('SP073','Hoa lan hồ điệp trắng', 'LSP008','Chậu',   30,320000, 44, 460800,'Chậu lan hồ điệp trắng 2 cành, sang trọng, thích hợp làm quà biếu',    'hien_thi','2026-04-01'),
  ('SP074','Hoa baby trắng',        'LSP008','Bó',    150, 30000, 50,  45000,'Bó hoa baby trắng nhỏ xinh, phụ kiện không thể thiếu trong bó hoa',     'hien_thi','2026-04-01'),
  ('SP075','Hoa lily cam',          'LSP008','Bông',  100, 35000, 57,  54950,'Hoa lily cam tươi thơm nức, 1 bông nhiều nụ nở dần',                    'hien_thi','2026-04-01'),
  ('SP076','Hoa hướng dương tươi',  'LSP008','Bông',  120, 20000, 50,  30000,'Hoa hướng dương tươi rói, mang lại năng lượng tích cực',                'hien_thi','2026-04-02'),
  ('SP077','Bó hoa sinh nhật mix',  'LSP008','Bó',     50,180000, 44, 259200,'Bó hoa sinh nhật mix hồng, cúc, baby được cắm nghệ thuật sẵn',          'hien_thi','2026-04-02'),
  ('SP078','Hoa tulip hà lan đỏ',   'LSP008','Bông',   90, 30000, 50,  45000,'Hoa tulip nhập từ Hà Lan, cánh mịn màu đỏ thuần khiết',                'hien_thi','2026-04-03'),
  ('SP079','Hoa cúc vạn thọ vàng',  'LSP008','Bó',   160, 15000, 53,  22950,'Bó cúc vạn thọ vàng tươi, biểu tượng may mắn và phồn thịnh',            'hien_thi','2026-04-03'),
  ('SP080','Chậu sen đá mix màu',   'LSP008','Chậu',   60, 85000, 41, 119850,'Chậu sen đá mix nhiều loại, ít tưới, thích hợp để bàn làm việc',        'hien_thi','2026-04-04');

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
  
  -- 6. BẢNG ĐƠN HÀNG
CREATE TABLE IF NOT EXISTS `don_hang` (
  `ma_don`        VARCHAR(50)    NOT NULL,
  `username` INT(11)        NOT NULL,
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

-- 7. BẢNG CHI TIẾT ĐƠN HÀNG
CREATE TABLE IF NOT EXISTS `chi_tiet_don_hang` (
  `ma_don` VARCHAR(50)        NOT NULL,
  `ma_sp` VARCHAR(30)        NOT NULL,
  `so_luong`    INT(11)        NOT NULL DEFAULT 1,
  `gia_ban`     DECIMAL(12,2)  NOT NULL,
  PRIMARY KEY (`ma_don`,`ma_sp`),
  CONSTRAINT `fk_ct_donhang` FOREIGN KEY (`ma_don`) REFERENCES `don_hang` (`ma_don`),
  CONSTRAINT `fk_ct_sanpham` FOREIGN KEY (`ma_sp`) REFERENCES `san_pham` (`ma_sp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

USE shop_hoa_db;

ALTER TABLE `loai_san_pham`
  ADD COLUMN IF NOT EXISTS `ty_le_loi_nhuan`
    DECIMAL(6,2) NOT NULL DEFAULT 0.00
    COMMENT '% lợi nhuận mặc định áp dụng cho SP thuộc loại này'
  AFTER `ten_loai`;



