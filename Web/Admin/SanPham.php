<?php
// =============================================================
// htmlAdmin/SanPham.php  –  Trang quản lý sản phẩm (Admin)
// =============================================================
session_start();
require_once '../includes/db_connect.php';

// Kiểm tra đăng nhập: nếu chưa có session thì chuyển về trang login
// if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: LoginA.php');
//     exit;
// }

// Lấy tên admin để hiển thị (nếu có)
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quản lý sản phẩm – <?= $adminName ?></title>
  <link rel="stylesheet" href="../cssAdmin/SanPham.css" />
  <link rel="stylesheet" href="../cssAdmin/styleAdmin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
</head>
<body>

<!-- ==================== HEADER ==================== -->
<header class="header">
  <div class="logo">
    <a href="Admin.html"><img src="../Image/logostore-Photoroom.png" alt="Logo" /></a>
  </div>
  <div class="header-right">
    <a class="chucnang-item"        href="Admin.html">       <i class="fas fa-home"></i>          Tổng quan</a>
    <a class="chucnang-item"        href="giaban.php">      <i class="fas fa-tags"></i>           Giá bán</a>
    <a class="chucnang-item active" href="SanPham.php">     <i class="fas fa-box-open"></i>       Sản phẩm</a>
    <a class="chucnang-item"        href="phieunhap.php">   <i class="fas fa-file-invoice"></i>   Phiếu nhập</a>
    <a class="chucnang-item"        href="donhang.php">     <i class="fas fa-shopping-cart"></i>  Đơn hàng</a>
    <a class="chucnang-item"        href="khovan.html">      <i class="fas fa-truck-loading"></i>  Kho vận</a>
    <a class="chucnang-item"        href="khachhang.php">   <i class="fas fa-users"></i>          Khách hàng</a>
    <a class="chucnang-item"        href="LoginA.php" style="background: rgba(255,0,0,0.1); color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
  </div>
</header>
<!-- Hiển thị tên admin góc trên -->
<div style="text-align:right;padding:6px 30px 0;font-size:13px;color:#555">
  <i class="fas fa-user-circle"></i> <?= $adminName ?>
</div>

<!-- ==================== NỘI DUNG ==================== -->
<div id="sanpham" class="tab-content active">
<div class="content">
<div class="sanpham-container">

  <!-- ========== LOẠI SẢN PHẨM ========== -->
  <section>
    <h2><i class="fas fa-plus-circle" style="font-size:22px"></i> Loại sản phẩm</h2>

    <form id="formLoaiSanPham">
      <input type="text" id="tenLoai" placeholder="Nhập tên loại sản phẩm" required />
      <div class="loai-date-row">
        <label for="tungay">Ngày thêm</label>
        <!-- Mặc định là ngày hôm nay -->
        <input type="date" id="tungay" value="<?= date('Y-m-d') ?>" required />
      </div>
      <div class="loai-btn-row">
        <button type="button"><i class="fas fa-plus"></i> Thêm</button>
      </div>
    </form>

    <h2><i class="fa-solid fa-list"></i> Danh sách loại</h2>
    <div class="bang-loai-wrapper">
      <table id="danhSachLoai">
        <thead>
          <tr>
            <th>STT</th>
            <th>Mã loại</th>
            <th>Tên loại</th>
            <th>Ngày thêm</th>
            <th>Chức năng</th>
          </tr>
        </thead>
        <tbody>
          <tr><td colspan="5" style="text-align:center;color:#999;padding:16px">
            <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
          </td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Popup sửa loại sản phẩm -->
  <div id="popup-themsp" class="overlay">
    <a href="#" class="overlay-bg"></a>
    <div class="popup-box">
      <h2><i class="fas fa-edit"></i> Sửa loại sản phẩm</h2>
      <form id="formLoaiSanPhamPopup">
        <label>Tên loại sản phẩm <span class="required">*</span></label>
        <input type="text" id="editTenLoai" placeholder="Tên loại sản phẩm" required />
        <label>Ngày thêm</label>
        <input type="date" id="tungayPopup" />
        <div class="popup-btn-row">
          <button type="button"><i class="fas fa-save"></i> Lưu</button>
        </div>
      </form>
      <a href="#" class="close">&#x2715; Đóng</a>
    </div>
  </div>

  <!-- ========== THÊM SẢN PHẨM ========== -->
  <section>
    <h2><i class="fas fa-plus-square" style="font-size:22px"></i> Thêm sản phẩm</h2>
    <form id="formThemSanPham">

      <!-- Hàng 1: Mã SP, Tên SP, Loại SP -->
      <div class="form-row">
        <div class="form-group">
          <label>Mã sản phẩm <span class="required">*</span></label>
          <input type="text" id="maSP" placeholder="VD: SP001" required />
        </div>
        <div class="form-group">
          <label>Tên sản phẩm <span class="required">*</span></label>
          <input type="text" id="tenSP" placeholder="Tên sản phẩm" required />
        </div>
        <div class="form-group">
          <label>Loại sản phẩm <span class="required">*</span></label>
          <select id="loaiSP">
            <option value="">-- Chọn loại --</option>
          </select>
        </div>
      </div>

      <!-- Hàng 2: Số lượng tồn, Đơn vị tính, Hình ảnh -->
      <div class="form-row">
        <div class="form-group">
          <label>Số lượng tồn ban đầu</label>
          <input type="number" id="soLuongTon" min="0" value="0" />
        </div>
        <div class="form-group">
          <label>Đơn vị tính</label>
          <input type="text" id="donViTinh" value="Cái" placeholder="Cái / Bó / Hộp..." />
        </div>
        <div class="form-group">
          <label>Hình ảnh</label>
          <input type="file" id="hinhAnh" accept="image/*" />
          <div id="previewHinhThem" style="margin-top:6px;display:none">
            <img id="imgPreviewThem" src="" alt="Xem trước"
                 style="width:70px;height:70px;object-fit:cover;border-radius:8px;border:1px solid #ddd" />
          </div>
        </div>
      </div>

      <!-- Hàng 3: Mô tả (full width) -->
      <div class="form-row">
        <div class="form-group form-group-full">
          <label>Mô tả</label>
          <textarea id="moTa" rows="3" placeholder="Mô tả sản phẩm..."></textarea>
        </div>
      </div>

      <!-- Hàng 4: Giá vốn, Tỷ lệ LN, Giá bán -->
      <div class="form-row">
        <div class="form-group">
          <label>Giá vốn (VND)</label>
          <input type="number" id="giaVon" min="0" step="1000" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Tỷ lệ lợi nhuận (%)</label>
          <input type="number" id="tyleLN" min="0" max="1000" step="0.1" placeholder="0" />
          <small style="color:#888;font-size:11px">Giá bán sẽ tự động tính</small>
        </div>
        <div class="form-group">
          <label>Giá bán (VND)</label>
          <input type="number" id="giaBan" min="0" step="1000" placeholder="Tự động tính" />
        </div>
      </div>

      <!-- Hàng 5: Hiện trạng -->
      <div class="form-row">
        <div class="form-group">
          <label>Hiện trạng</label>
          <select id="hienTrang">
            <option value="hien_thi">Đang bán (Hiển thị)</option>
            <option value="an">Ẩn (Không bán)</option>
          </select>
        </div>
      </div>

      <button type="submit"><i class="fas fa-plus"></i> Thêm sản phẩm</button>
    </form>
  </section>

  <br />

  <!-- ========== DANH MUC SAN PHAM ========== -->
  <section>
    <h2><i class="fas fa-clipboard-list" style="font-size:22px"></i> Danh mục sản phẩm</h2>

    <!-- Thanh tim kiem nhanh -->
    <div style="margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <input type="text" id="searchSP" placeholder="&#xf002; Tìm mã / tên sản phẩm..."
             style="padding:8px 12px;border:1px solid #ccc;border-radius:8px;font-size:14px;width:260px" />
      <select id="filterLoai"
              style="padding:8px 10px;border:1px solid #ccc;border-radius:8px;font-size:14px">
        <option value="">-- Tất cả loại --</option>
      </select>
      <select id="filterTrang"
              style="padding:8px 10px;border:1px solid #ccc;border-radius:8px;font-size:14px">
        <option value="">-- Tất cả trạng thái --</option>
        <option value="hien_thi">Đang bán</option>
        <option value="an">Ẩn</option>
      </select>
    </div>

    <div style="overflow-x:auto">
      <table id="bangSanPham">
        <thead>
          <tr>
            <th>STT</th><th>Hình</th><th>Mã</th><th>Tên</th>
            <th>Loại</th><th>DVT</th><th>Số lượng</th>
            <th>Giá vốn</th><th>Tỷ lệ LN</th><th>Giá bán</th>
            <th>Mô tả</th><th>Hiện trạng</th><th>Chức năng</th>
          </tr>
        </thead>
        <tbody id="tableProductBody">
          <tr><td colspan="13" style="text-align:center;color:#999;padding:16px">
            <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
          </td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Popup sửa sản phẩm -->
  <div id="popup-suasp" class="overlay">
    <a href="#" class="overlay-bg"></a>
    <div class="popup-box popup-large">
      <h2><i class="fas fa-edit"></i> Sửa sản phẩm</h2>
      <form id="formSuaSanPham">

        <div class="form-row">
          <div class="form-group">
            <label>Mã sản phẩm <span class="required">*</span></label>
            <input type="text" id="suaMaSP" required />
          </div>
          <div class="form-group">
            <label>Tên sản phẩm <span class="required">*</span></label>
            <input type="text" id="suaTenSP" required />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Loại sản phẩm <span class="required">*</span></label>
            <select id="suaLoaiSP"><option value="">-- Chọn loại --</option></select>
          </div>
          <div class="form-group">
            <label>Đơn vị tính </label>
            <input type="text" id="suaDonViTinh" placeholder="Cái / Bó / Hộp..." />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Giá vốn (VND)</label>
            <input type="number" id="suaGiaVon" min="0" step="1000" />
          </div>
          <div class="form-group">
            <label>Tỷ lệ lợi nhuận (%)</label>
            <input type="number" id="suaTyleLN" min="0" step="0.1" />
          </div>
          <div class="form-group">
            <label>Giá bán (VND)</label>
            <input type="number" id="suaGiaBan" min="0" step="1000" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Số lượng tồn</label>
            <input type="number" id="suaSoLuongTon" min="0" />
          </div>
          <div class="form-group">
            <label>Hiện trạng <strong style="color:#e74c3c">(quan trọng)</strong></label>
            <select id="suaHienTrang">
              <option value="hien_thi">Đang bán (Hiển thị)</option>
              <option value="an">Ẩn (Không bán)</option>
            </select>
          </div>
        </div>

        <div class="form-group full-width">
          <label>Hình ảnh (bỏ trống = giữ hình cũ)</label>
          <div style="display:flex;align-items:center;gap:12px;margin:6px 0 4px">
            <img id="previewHinhSua" src="" alt="Hình hiện tại"
                 style="display:none;width:90px;height:90px;object-fit:cover;
                        border-radius:8px;border:1px solid #ddd" />
            <button type="button" id="btnXoaHinh"
                    style="display:none;padding:5px 12px;background:#e74c3c;color:#fff;
                           border:none;border-radius:6px;cursor:pointer;font-size:13px">
              <i class="fas fa-trash"></i> Bỏ hình
            </button>
          </div>
          <input type="file" id="suaHinhAnh" accept="image/*" />
          <input type="hidden" id="suaXoaHinh" value="0" />
        </div>

        <div class="form-group full-width">
          <label>Mô tả</label>
          <textarea id="suaMoTa" rows="3" placeholder="Mô tả sản phẩm..."></textarea>
        </div>

        <button type="submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
      </form>
      <a href="#" class="close">&#x2715; Đóng</a>
    </div>
  </div>

</div><!-- /sanpham-container -->
</div><!-- /content -->
</div><!-- /sanpham -->

<script src="../JSAdmin/SanPham.js"></script>
</body>
</html>