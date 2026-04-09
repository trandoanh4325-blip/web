# 🎯 HƯỚNG DẪN NHANH - CHẠY TRÊN LOCALHOST

## ✅ BƯỚC 1: Import Database (2 phút)

### Cách 1: Command Line (Nhanh)
```bash
# Mở Command Prompt / PowerShell
cd C:\xampp\mysql\bin
mysql -u root shop_hoa_db < "C:\xampp\htdocs\web\Web\database\shop_hoa_db.sql"
```

### Cách 2: phpMyAdmin (Quen thuộc)
1. Truy cập: **http://localhost/phpmyadmin**
2. Login: root / (trống)
3. Click **Import**
4. Choose File: `C:\xampp\htdocs\web\Web\database\shop_hoa_db.sql`
5. Click **Go**

---

## ✅ BƯỚC 2: Copy Files (1 phút)

Copy 3 files này:
```
From:  C:\xampp\htdocs\web\Web\Admin\SanPham.php
To:    C:\xampp\htdocs\MyAdminPHP\Admin\SanPham.php

From:  C:\xampp\htdocs\web\Web\Admin\process_SanPham.php
To:    C:\xampp\htdocs\MyAdminPHP\Admin\process_SanPham.php

From:  C:\xampp\htdocs\web\Web\JSAdmin\SanPham.js
To:    C:\xampp\htdocs\MyAdminPHP\JSAdmin\SanPham.js
```

Copy folder ảnh:
```
From:  C:\xampp\htdocs\web\Web\ImageSanPham\
To:    C:\xampp\htdocs\MyAdminPHP\ImageSanPham\
```

---

## ✅ BƯỚC 3: Xác Nhận Dữ Liệu (1 phút)

Mở **phpMyAdmin** → Tab **SQL**:

```sql
-- Kiểm tra SP001 có 3 ảnh
SELECT * FROM san_pham_hinh_anh WHERE ma_sp = 'SP001';
```

Kết quả phải có 3 dòng:
```
| SP001 | 1 | SP001.jpg   |
| SP001 | 2 | SP001_1.jpg |
| SP001 | 3 | SP001_2.jpg |
```

---

## ✅ BƯỚC 4: Mở Admin Panel (ngay)

```
http://localhost/MyAdminPHP/Admin/SanPham.php
```

**Thấy:**
- [x] Bảng sản phẩm hiển thị 19 SP
- [x] SP001 có ảnh thumbnail
- [x] Click "Sửa" SP001 → 3 ảnh

---

## 🧪 TEST FEATURES

### Test 1: Thêm sản phẩm 1 ảnh
1. Click "Thêm sản phẩm"
2. Nhập: Mã (SP999), Tên, Loại
3. Chọn 1 file ảnh
4. Click "Thêm sản phẩm"
✓ Success!

### Test 2: Thêm sản phẩm 3 ảnh
1. Click "Thêm sản phẩm"
2. Nhập thông tin
3. Chọn 3 file ảnh cùng lúc
4. Xem preview 3 ảnh
5. Click "Thêm sản phẩm"
✓ 3 ảnh được upload + DB!

### Test 3: Sửa & Xóa ảnh
1. Click "Sửa" SP001
2. Xem 3 ảnh hiện có
3. Click ✕ trên ảnh để xóa
4. Xem confirm → Yes
✓ Ảnh được xóa!

### Test 4: Thêm ảnh mới khi sửa
1. Click "Sửa" SP001
2. Scroll xuống → input "Thêm ảnh mới"
3. Chọn 2 file ảnh
4. Click "Lưu thay đổi"
✓ 2 ảnh mới được thêm!

---

## 🎯 CÓ GÌ TRONG ĐÓ

### ✨ Multiple Images Support
- Chọn 1 hoặc nhiều ảnh cùng lúc (multiple input)
- Preview thumbnail trước upload
- Xóa ảnh lẻ từ form

### 🚀 Fix Request Loop
- Timeout 30 giây mỗi request
- Flag prevent duplicate requests
- Network tab không chạy liên tục

### 📊 Dữ Liệu Mẫu
- 19 sản phẩm (SP001-SP030)
- 8 loại sản phẩm
- 13 hình ảnh (SP001 có 3 ảnh!)
- Ảnh thực file trong ImageSanPham

---

## 📞 LỖI GẶP PHẢI?

### ❌ Ảnh không hiển thị
→ Verify file tồn tại trong: `C:\xampp\htdocs\MyAdminPHP\ImageSanPham\`

### ❌ Upload ảnh bị lỗi
→ Check: ImageSanPham folder có quyền write (755)

### ❌ Request vẫn lặp
→ F12 → Clear cache (Ctrl+Shift+Delete)

### ❌ Database import bị lỗi
→ Xóa database cũ rồi import lại (phpmyadmin → Operations → Drop)

---

## 📁 FILE LOCATIONS

```
Web/
├── Admin/
│   ├── SanPham.php ⭐ (UPDATE)
│   └── process_SanPham.php ⭐ (UPDATE)
├── JSAdmin/
│   └── SanPham.js ⭐ (UPDATE)
├── database/
│   └── shop_hoa_db.sql ⭐ (có dữ liệu mẫu)
└── ImageSanPham/
    ├── SP001.jpg ✓
    ├── SP001_1.jpg ✓
    ├── SP001_2.jpg ✓
    ├── 2.jpg
    ├── 3.webp
    ├── 4.jpg
    ├── 5.jpg
    ├── 6.jpg
    ├── 7.webp
    └── 8.jpg
```

---

## 🎉 DONE!

Xong rồi, bây giờ:
1. ✅ Database có dữ liệu mẫu
2. ✅ Ảnh đã copy sang ImageSanPham
3. ✅ Code đã cập nhật (multiple images + fix loop)
4. ✅ Ready to test!

**Enjoy! 🚀**

---

*P.S: Đọc file READY_TO_RUN.md để biết chi tiết hơn*
