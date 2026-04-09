# 🎉 SETUP HOÀN TẤT - SẴN SÀNG CHẠY TRÊN LOCALHOST

## ✅ TẬT CẢ ĐÃ CHUẨN BỊ

### 1️⃣ Ảnh Sản Phẩm (10 files)
```
C:\xampp\htdocs\web\Web\ImageSanPham\
├── SP001.jpg      ← Ảnh 1 của SP001
├── SP001_1.jpg    ← Ảnh 2 của SP001
├── SP001_2.jpg    ← Ảnh 3 của SP001
├── 2.jpg          ← SP002
├── 3.webp         ← SP003
├── 4.jpg          ← SP004
├── 5.jpg          ← SP005
├── 6.jpg          ← SP006
├── 7.webp         ← SP007
└── 8.jpg          ← SP008
```

### 2️⃣ Dữ Liệu SQL (13 hình ảnh mẫu)
```sql
-- Bảng san_pham_hinh_anh có 13 records:
-- SP001: 3 ảnh
-- SP002-SP008: 1 ảnh mỗi cái

-- SP001:
('SP001', 1, 'SP001.jpg'),
('SP001', 2, 'SP001_1.jpg'),
('SP001', 3, 'SP001_2.jpg'),

-- SP002-SP008:
('SP002', 1, '2.jpg'),
('SP003', 1, '3.webp'),
('SP004', 1, '4.jpg'),
('SP005', 1, '5.jpg'),
('SP006', 1, '6.jpg'),
('SP007', 1, '7.webp'),
('SP008', 1, '8.jpg'),
```

### 3️⃣ Dữ Liệu Mẫu (19 SP + 8 loại)
- ✅ Bảng `loai_san_pham`: 8 loại
- ✅ Bảng `san_pham`: 19 sản phẩm
- ✅ Bảng `san_pham_hinh_anh`: 13 hình ảnh (NEW)
- ✅ Cột `hinh_anh` của SP001 = `SP001.jpg` (trỏ vào ImageSanPham)

---

## 🚀 BƯỚC CHẠY TRÊN LOCALHOST

### **BƯỚC 1: Import Database vào phpMyAdmin**

**Truy cập:**
```
http://localhost/phpmyadmin
```

**Đăng nhập:**
- Username: `root`
- Password: `(để trống)`

**Import SQL:**
1. Click **Databases** → New → Tạo `shop_hoa_db`
2. Click database `shop_hoa_db`
3. Tab **Import**
4. Choose File: `C:\xampp\htdocs\web\Web\database\shop_hoa_db.sql`
5. Click **Go** (Import)

**Hoặc dùng Command Line:**
```bash
cd C:\xampp\mysql\bin
mysql -u root shop_hoa_db < "C:\xampp\htdocs\web\Web\database\shop_hoa_db.sql"
```

---

### **BƯỚC 2: Copy Files sang MyAdminPHP**

Copy 3 files này sang `http://localhost/MyAdminPHP/`:

```
Web/Admin/SanPham.php 
  → C:\xampp\htdocs\MyAdminPHP\Admin\SanPham.php

Web/Admin/process_SanPham.php 
  → C:\xampp\htdocs\MyAdminPHP\Admin\process_SanPham.php

Web/JSAdmin/SanPham.js 
  → C:\xampp\htdocs\MyAdminPHP\JSAdmin\SanPham.js
```

**Folder ảnh (thử copy):**
```
Web/ImageSanPham/ 
  → C:\xampp\htdocs\MyAdminPHP\ImageSanPham\
```

Hoặc giữ ảnh ở Web folder và update đường dẫn trong PHP:
- Thay `../ImageSanPham/` → `../../../web/Web/ImageSanPham/`

---

### **BƯỚC 3: Kiểm tra Dữ Liệu**

**phpMyAdmin SQL Console:**
```sql
-- Kiểm tra SP001 có 3 ảnh
SELECT * FROM san_pham_hinh_anh WHERE ma_sp = 'SP001';

-- Result:
| ma_sp | thu_tu | duong_dan   | ngay_them |
|-------|--------|-------------|-----------|
| SP001 |   1    | SP001.jpg   | 2026-04-09|
| SP001 |   2    | SP001_1.jpg | 2026-04-09|
| SP001 |   3    | SP001_2.jpg | 2026-04-09|
```

---

### **BƯỚC 4: Truy cập Admin Panel**

```
http://localhost/MyAdminPHP/Admin/SanPham.php
```

**Kiểm tra:**
- [x] Bảng sản phẩm hiển thị 19 SP
- [x] SP001 có thumbnail ảnh
- [x] Click "Sửa" SP001 → Hiển thị 3 ảnh (SP001.jpg, SP001_1.jpg, SP001_2.jpg)
- [x] Có nút ✕ để xóa ảnh lẻ
- [x] Có input để thêm ảnh mới

---

### **BƯỚC 5: Test Chức Năng**

#### Test Thêm Sản Phẩm:
1. Click "Thêm sản phẩm"
2. Nhập: Mã, Tên, Loại, etc.
3. Chọn file ảnh (1 hoặc nhiều)
4. Xem preview
5. Click "Thêm sản phẩm"
6. Verify database: `SELECT * FROM san_pham_hinh_anh WHERE ma_sp = 'SPxxx';`

#### Test Sửa Sản Phẩm:
1. Click "Sửa" trên SP001
2. Xem 3 ảnh hiện có
3. Click ✕ để xóa ảnh
4. Chọn ảnh mới (optional)
5. Click "Lưu thay đổi"
6. Verify: Ảnh được xóa, ảnh mới được thêm

#### Test Request Loop Fix:
1. F12 → Network tab
2. Click "Sửa" SP001
3. Xem Network: **Không có request lặp** ✓
4. Timeout 30 giây có effect ✓

---

## 📊 DỮ LIỆU HIỆN CÓ

### Loại Sản Phẩm (8)
| ma_loai | ten_loai |
|---------|----------|
| LSP001  | Thiệp & Phụ kiện |
| LSP002  | Đồ trang trí |
| LSP003  | Set quà tặng |
| LSP004  | Handmade |
| LSP005  | Quà lưu niệm |
| LSP006  | Quà tặng & giỏ quà |
| LSP007  | Hoa giấy |
| LSP008  | Hoa thật 100% |

### Sản Phẩm (19)
- SP001-SP010: LSP001
- SP011-SP020: LSP002
- SP021-SP030: LSP003-LSP008

### Hình Ảnh (13)
- SP001: 3 ảnh ✓✓✓
- SP002-SP008: 1 ảnh mỗi cái ✓
- Total: 13 images in `san_pham_hinh_anh` table

---

## 🎯 FOLDER LOCATIONS

```
C:\xampp\htdocs\
├── phpmyadmin\         ← http://localhost/phpmyadmin
├── MyAdminPHP\         ← http://localhost/MyAdminPHP
│   ├── Admin/
│   │   ├── SanPham.php (UPDATE)
│   │   └── process_SanPham.php (UPDATE)
│   ├── JSAdmin/
│   │   └── SanPham.js (UPDATE)
│   └── ImageSanPham/   ← Lưu ảnh sản phẩm
└── web\
    └── Web\
        ├── Admin/
        │   ├── SanPham.php ← Original
        │   └── process_SanPham.php ← Original
        ├── JSAdmin/
        │   └── SanPham.js ← Original
        ├── ImageSanPham/   ← 10 ảnh mẫu sẵn ✓
        └── database/
            └── shop_hoa_db.sql ← Có dữ liệu mẫu ✓
```

---

## ⚙️ CÁCH XỬ LÝ ĐƯỜNG DẪN ẢNH

### Option 1: Copy folder ImageSanPham sang MyAdminPHP
```
C:\xampp\htdocs\MyAdminPHP\ImageSanPham\ ← Copy hết 10 files
```
Sau đó trong HTML & JS, link ảnh:
```javascript
'../ImageSanPham/SP001.jpg' ✓
```

### Option 2: Giữ ảnh ở Web, link từ MyAdminPHP
```
C:\xampp\htdocs\web\Web\ImageSanPham\ ← Giữ nguyên
```
Cập nhật link trong HTML:
```javascript
'../../../web/Web/ImageSanPham/SP001.jpg'
```

---

## ✨ CHECKLIST

Trước khi chạy:
- [x] Ảnh copy vào ImageSanPham (10 files)
- [x] SQL có dữ liệu mẫu bảng san_pham_hinh_anh
- [x] 3 files (SanPham.php, process_SanPham.php, SanPham.js) cập nhật
- [x] Database shop_hoa_db có sẵn trong SQL
- [x] phpMyAdmin sẵn sàng import

Khi chạy:
- [ ] Import SQL vào phpmyadmin
- [ ] Verify dữ liệu trong database
- [ ] Copy 3 files sang MyAdminPHP
- [ ] Copy ImageSanPham folder sang MyAdminPHP (hoặc update links)
- [ ] Truy cập Admin Panel
- [ ] Test tất cả chức năng

---

## 🚀 GO LIVE!

```bash
# Terminal
cd C:\xampp\mysql\bin
mysql -u root shop_hoa_db < "C:\xampp\htdocs\web\Web\database\shop_hoa_db.sql"
```

```
http://localhost/phpmyadmin → Verify data ✓
http://localhost/MyAdminPHP/Admin/SanPham.php → Test features ✓
```

**Enjoy! 🎉**

---

**Last Updated:** 9/4/2026  
**Version:** 2.0 - Multiple Images + Fix Request Loop  
**Status:** ✅ READY TO DEPLOY
