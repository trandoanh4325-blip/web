# CẬP NHẬT HỆ THỐNG QUẢN LÝ SẢN PHẨM

## 📋 TÓM TẮT CÁC THAY ĐỔI

### 1. **HỖ TRỢ NHIỀU HÌNH ẢNH PER SẢN PHẨM**
- Thêm bảng `san_pham_hinh_anh` trong database để quản lý nhiều ảnh cho 1 sản phẩm
- Cấu trúc bảng:
  ```sql
  - ma_sp: Mã sản phẩm (khóa ngoại)
  - thu_tu: Thứ tự ảnh (1, 2, 3, ...)
  - duong_dan: Đường dẫn file ảnh
  - ngay_them: Timestamp thêm
  ```

### 2. **API ENDPOINTS MỚI**
- `GET /san-pham-hinh?ma_sp=SP001` - Lấy tất cả ảnh của 1 sản phẩm
- `POST /san-pham-hinh` - Thêm ảnh mới (body: `{ma_sp, duong_dan}`)
- `DELETE /san-pham-hinh-xoa` - Xóa ảnh (body: `{ma_sp, thu_tu}`)

### 3. **GIAO DIỆN CẬP NHẬT**
#### Form Thêm Sản Phẩm:
- Input file hỗ trợ `multiple` (chọn nhiều ảnh 1 lần)
- Preview các ảnh đã chọn với nút xóa
- Thêm tất cả sản phẩm + ảnh vào database

#### Form Sửa Sản Phẩm:
- Hiển thị tất cả ảnh hiện có
- Nút xóa (✕) trên mỗi ảnh
- Có thể thêm ảnh mới bằng input file `multiple`
- Auto sắp xếp lại thứ tự ảnh khi xóa

### 4. **FIX VÒNG LẶP REQUEST**
**Nguyên nhân:** Event listener được bind nhiều lần, hoặc fetch request không có timeout

**Giải pháp:**
- ✅ Thêm `isLoadingData` flag để tránh request trùng lặp
- ✅ Thêm `AbortSignal.timeout(30000)` cho tất cả fetch
- ✅ Bind event listener 1 lần trong `DOMContentLoaded`
- ✅ Sử dụng `addEventListener` thay vì `onclick` trong HTML

### 5. **CÁCH SỬ DỤNG**

#### Thêm sản phẩm:
1. Nhập mã, tên, loại sản phẩm
2. Chọn file ảnh (1 hoặc nhiều)
3. Preview sẽ hiển thị các ảnh
4. Click "Thêm sản phẩm"
5. Tất cả ảnh sẽ được upload + lưu vào DB

#### Sửa sản phẩm:
1. Click "Sửa" trên sản phẩm
2. Hiện tại ảnh đã có sẽ show phía trên
3. Click ✕ để xóa ảnh không cần
4. Chọn file ảnh mới nếu muốn thêm
5. Click "Lưu thay đổi"

#### Xóa ảnh:
- Click nút ✕ trên ảnh (sẽ confirm)
- Ảnh sẽ được xóa từ file system + database
- Thứ tự ảnh tự động sắp xếp lại

---

## 📁 THAY ĐỔI FILE

### Database (SQL)
- **File:** `database/shop_hoa_db.sql`
- **Thay đổi:** Thêm bảng `san_pham_hinh_anh` (cascade delete trên `ma_sp`)

### PHP API
- **File:** `Admin/process_SanPham.php`
- **Thay đổi:** 
  - Thêm 2 function mới: `handleSanPhamHinh()`, `handleXoaHinhSanPham()`
  - Update route match để hỗ trợ endpoint mới

### HTML
- **File:** `Admin/SanPham.php`
- **Thay đổi:**
  - Input file trong form Thêm: `multiple` support
  - Popup Sửa: thêm container `hinhAnhHienTai` & `previewHinhSuaMoi`
  - Preview container: hiển thị thumbnail + nút xóa

### JavaScript
- **File:** `JSAdmin/SanPham.js`
- **Thay đổi:**
  - Viết lại hoàn toàn để fix request loop
  - Thêm `filesThemTam`, `filesSuaTam` arrays
  - Thêm `isLoadingData` flag
  - Thêm timeout vào fetch
  - Hàm `loadHinhAnhSanPham()`, `confirmXoaHinh()` mới
  - Hàm `bindPreviewHinhThem()`, `bindPreviewHinhSua()` hỗ trợ multiple

### Thư mục
- **Thêm:** `Web/ImageSanPham/` (lưu ảnh sản phẩm)

---

## 🔧 CÁCH DEPLOY

1. **Backup database cũ:**
   ```bash
   mysqldump -u root shop_hoa_db > backup_cũ.sql
   ```

2. **Import SQL mới:**
   ```bash
   mysql -u root shop_hoa_db < database/shop_hoa_db.sql
   ```

3. **Thay thế các file:**
   - `Admin/SanPham.php` ← ghi đè
   - `Admin/process_SanPham.php` ← ghi đè
   - `JSAdmin/SanPham.js` ← ghi đè

4. **Verify:**
   - Thư mục `Web/ImageSanPham/` phải tồn tại + writable
   - Truy cập `localhost/MyAdminPHP/Admin/SanPham.php`
   - Test thêm/sửa sản phẩm

---

## ✅ KIỂM TRA

- [x] Multiple images upload
- [x] Preview images
- [x] Delete images (với confirm)
- [x] Request loop fixed (timeout + flag)
- [x] Database schema updated
- [x] API endpoints working
- [x] HTML/JS refactored
- [x] Folder ImageSanPham created

---

## 📝 GHI CHÚ

- Ảnh đầu tiên sẽ được lưu vào `san_pham.hinh_anh` (để hiển thị thumbnail trong bảng)
- Các ảnh còn lại lưu trong bảng `san_pham_hinh_anh`
- Khi xóa sản phẩm, tất cả ảnh sẽ cascade delete
- Khi xóa ảnh riêng lẻ, thứ tự sẽ tự động reindex
- Timeout 30 giây cho upload + API call

---

**Cập nhật ngày:** 9/4/2026
**Version:** 2.0
