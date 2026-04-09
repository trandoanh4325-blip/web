# 📖 HƯỚNG DẪN NHẬP DỮ LIỆU - phpmyadmin

## ✅ CHUẨN BỊ

Tất cả files và dữ liệu đã sẵn sàng:
- [x] SQL file: `Web/database/shop_hoa_db.sql` 
- [x] Ảnh mẫu: `Web/ImageSanPham/` (10 files)
- [x] Dữ liệu bảng `san_pham_hinh_anh` có sẵn trong SQL

---

## 🚀 BƯỚC 1: TRUY CẬP phpMyAdmin

```
http://localhost/phpmyadmin
```

**Đăng nhập:**
- Username: `root`
- Password: `(để trống)`
- Server: `localhost`

---

## 📋 BƯỚC 2: TẠO DATABASE

### Option A: Dùng phpMyAdmin UI
1. Click **"Databases"** → New database
2. Database name: `shop_hoa_db`
3. Collation: `utf8mb4_general_ci`
4. Click **"Create"**

### Option B: Dùng SQL Console
1. Click **SQL** tab
2. Paste code:
```sql
CREATE DATABASE IF NOT EXISTS shop_hoa_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;
```
3. Click **Execute**

---

## 📥 BƯỚC 3: NHẬP DỮ LIỆU

### Option A: Dùng phpMyAdmin Import (Recommended)
1. Click database `shop_hoa_db`
2. Click tab **"Import"**
3. Click **"Choose File"**
4. Chọn file: `Web/database/shop_hoa_db.sql`
5. Encoding: `utf-8`
6. Click **"Go"** (Import)

### Option B: Dùng Command Line (Terminal)
```bash
# Windows - PowerShell
cd C:\xampp\mysql\bin
.\mysql.exe -u root shop_hoa_db < "C:\xampp\htdocs\web\Web\database\shop_hoa_db.sql"

# hoặc (nếu path MySQL khác)
mysql -u root shop_hoa_db < "C:\xampp\htdocs\web\Web\database\shop_hoa_db.sql"
```

---

## ✅ BƯỚC 4: KIỂM TRA DỮ LIỆU

Sau khi Import xong, kiểm tra:

1. **Bảng `san_pham`** (19 sản phẩm)
   ```sql
   SELECT COUNT(*) FROM san_pham;
   ```
   Kết quả: **19 records** ✓

2. **Bảng `san_pham_hinh_anh`** (13 hình)
   ```sql
   SELECT COUNT(*) FROM san_pham_hinh_anh;
   ```
   Kết quả: **13 records** ✓

3. **Xem ảnh SP001** (3 ảnh)
   ```sql
   SELECT * FROM san_pham_hinh_anh WHERE ma_sp = 'SP001';
   ```
   Kết quả:
   ```
   | ma_sp | thu_tu | duong_dan   |
   |-------|--------|-------------|
   | SP001 |   1    | SP001.jpg   |
   | SP001 |   2    | SP001_1.jpg |
   | SP001 |   3    | SP001_2.jpg |
   ```

4. **Kiểm tra hinh_anh SP001**
   ```sql
   SELECT ma_sp, ten_sp, hinh_anh FROM san_pham WHERE ma_sp = 'SP001';
   ```
   Kết quả:
   ```
   | ma_sp | ten_sp           | hinh_anh  |
   |-------|------------------|-----------|
   | SP001 | Thiệp đỏ trái tim | SP001.jpg |
   ```

---

## 📁 BƯỚC 5: KIỂM TRA FOLDER HÌNH ẢNH

Verify file ảnh được copy vào đúng folder:

```
C:\xampp\htdocs\web\Web\ImageSanPham\
├── SP001.jpg       ✓
├── SP001_1.jpg     ✓
├── SP001_2.jpg     ✓
├── 2.jpg           ✓
├── 3.webp          ✓
├── 4.jpg           ✓
├── 5.jpg           ✓
├── 6.jpg           ✓
├── 7.webp          ✓
└── 8.jpg           ✓
```

Tất cả 10 files đã có ✓

---

## 🔧 BƯỚC 6: KIỂM TRA ADMIN PANEL

1. Truy cập: `http://localhost/MyAdminPHP/Admin/SanPham.php`
2. Kiểm tra:
   - [x] Bảng Sản phẩm hiển thị đúng
   - [x] Ảnh SP001 show up trong bảng (thumbnail)
   - [x] Click "Sửa" SP001 → hiển thị 3 ảnh
   - [x] Có nút ✕ để xóa ảnh lẻ
   - [x] Có input để thêm ảnh mới

3. Test thêm sản phẩm:
   - [x] Upload 1 ảnh
   - [x] Upload 2+ ảnh cùng lúc
   - [x] Preview ảnh trước upload
   - [x] Xóa ảnh từ preview

---

## 🎯 DỮ LIỆU MẪU CÓ

### Loại Sản Phẩm (8 loại)
- LSP001: Thiệp & Phụ kiện
- LSP002: Đồ trang trí
- LSP003: Set quà tặng
- LSP004: Handmade
- LSP005: Quà lưu niệm
- LSP006: Quà tặng & giỏ quà
- LSP007: Hoa giấy
- LSP008: Hoa thật 100%

### Sản Phẩm (19 sản phẩm)
- SP001-SP010: Từ loại LSP001
- SP011-SP020: Từ loại LSP002
- SP021-SP030: Từ loại LSP003-008

### Hình Ảnh (13 hình)
- SP001: 3 ảnh (SP001.jpg, SP001_1.jpg, SP001_2.jpg)
- SP002-SP008: 1 ảnh mỗi cái (2.jpg, 3.webp, 4.jpg, 5.jpg, 6.jpg, 7.webp, 8.jpg)

---

## ⚠️ TROUBLESHOOT

### ❌ Import bị lỗi: "Database already exists"
**Giải pháp:**
1. Click database `shop_hoa_db`
2. Tab **Operations**
3. Click **"Drop the database"**
4. Confirm
5. Quay lại Bước 2 & 3

### ❌ File `shop_hoa_db.sql` không tìm thấy
**Giải pháp:**
1. Đảm bảo file tồn tại: `C:\xampp\htdocs\web\Web\database\shop_hoa_db.sql`
2. Nếu không, download lại từ repo
3. Hoặc dùng command line import bằng full path

### ❌ Ảnh không hiển thị trong admin
**Kiểm tra:**
1. Verify file tồn tại: `C:\xampp\htdocs\web\Web\ImageSanPham\SP001.jpg`
2. Verify `hinh_anh` field trong DB = `SP001.jpg` (không có path)
3. URL phải là: `http://localhost/MyAdminPHP/ImageSanPham/SP001.jpg`
4. Check browser console (F12) có error không

### ❌ Phiếu Nhập không hiển thị
**Lý do:** Chưa import bảng `phieu_nhap` & `chi_tiet_phieu_nhap` (optional)
**Giải pháp:** Skip, không bắt buộc cho test

---

## 📊 SQL QUERIES HỮUÍCH

**Xem tất cả sản phẩm + ảnh chính:**
```sql
SELECT sp.ma_sp, sp.ten_sp, sp.hinh_anh, COUNT(ha.thu_tu) as so_anh
FROM san_pham sp
LEFT JOIN san_pham_hinh_anh ha ON sp.ma_sp = ha.ma_sp
GROUP BY sp.ma_sp
ORDER BY sp.ma_sp;
```

**Xem ảnh của 1 sản phẩm:**
```sql
SELECT * FROM san_pham_hinh_anh 
WHERE ma_sp = 'SP001' 
ORDER BY thu_tu;
```

**Cập nhật đường dẫn ảnh (nếu cần):**
```sql
UPDATE san_pham 
SET hinh_anh = 'SP001.jpg' 
WHERE ma_sp = 'SP001';
```

---

## ✨ READY TO GO!

Sau khi hoàn tất các bước trên:
- ✅ Database setup hoàn tất
- ✅ Dữ liệu mẫu nhập vào
- ✅ Ảnh copy vào ImageSanPham
- ✅ Admin panel sẵn sàng test

**Enjoy! 🚀**

---

**Ngày cập nhật:** 9/4/2026
**Version:** 2.0 - Multiple Images Support
