<?php
// =============================================================
// Admin/phieunhap.php  –  Quan ly Phieu Nhap Hang
// =============================================================
session_start();
// Bat comment khi da co he thong dang nhap
// if (empty($_SESSION['admin_logged_in'])) { header('Location: LoginA.php'); exit; }
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quản Lý Phiếu Nhập</title>
  <link rel="stylesheet" href="../cssAdmin/phieunhap.css" />
  <link rel="stylesheet" href="../cssAdmin/styleAdmin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
</head>
<body>

<header class="header">
  <div class="logo">
    <a href="Admin.html"><img src="../Image/logostore-Photoroom.png" alt="Logo" /></a>
  </div>
  <div class="header-right">
    <a class="chucnang-item" href="Admin.html">         <i class="fas fa-home"></i>         Tổng quan</a>
    <a class="chucnang-item" href="giaban.html">        <i class="fas fa-tags"></i>          Giá bán</a>
    <a class="chucnang-item" href="SanPham.php">        <i class="fas fa-box-open"></i>      Sản phẩm</a>
    <a class="chucnang-item active" href="phieunhap.php"><i class="fas fa-file-invoice"></i> Phiếu nhập</a>
    <a class="chucnang-item" href="donhang.html">       <i class="fas fa-shopping-cart"></i> Đơn hàng</a>
    <a class="chucnang-item" href="khovan.html">        <i class="fas fa-truck-loading"></i> Kho vận</a>
    <a class="chucnang-item" href="khachhang.php">      <i class="fas fa-users"></i>         Khách hàng</a>
    <a class="chucnang-item" href="LoginA.php"
       style="background:rgba(255,0,0,.1);color:#e74c3c">
      <i class="fas fa-sign-out-alt"></i> Đăng xuất
    </a>
  </div>
</header>

<div class="tab-content active">
<div class="content">
<div class="container-phieunhap">

  <!-- ===== 1. TIM KIEM PHIEU NHAP ===== -->
  <section>
    <h2><i class="fas fa-search" style="font-size:22px"></i> Tìm kiếm phiếu nhập</h2>
    <div class="search">
      <input type="text" id="timKiemPhieu"
             placeholder="Tìm theo mã phiếu hoặc tên sản phẩm..." />
      <button class="btn-them" id="btnTimKiem">
        <i class="fas fa-search"></i> Tìm kiếm
      </button>
    </div>

    <h2><i class="fas fa-clipboard-list" style="font-size:22px"></i> Kết quả tìm kiếm</h2>
    <div class="DanhMucTimPhieu">
      <table id="bangTimKiem">
        <thead>
          <tr>
            <th>Mã phiếu</th><th>Ngày nhập</th><th>Số SP</th>
            <th>Tổng tiền</th><th>Trạng thái</th>
          </tr>
        </thead>
        <tbody id="tbodyTimKiem">
          <tr><td colspan="5" style="color:#999;text-align:center">
            Nhập từ khóa và nhấn Tìm kiếm
          </td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- ===== 2. TAO PHIEU NHAP MOI ===== -->
  <section>
    <h2><i class="fas fa-file-invoice-dollar" style="font-size:22px"></i> Tạo phiếu nhập mới</h2>
    <form id="formTaoPhieu">
      <label for="ngayNhapMoi">Ngày nhập <span style="color:red">*</span></label>
      <input type="date" id="ngayNhapMoi" value="<?= date('Y-m-d') ?>" required />

      <label for="ghiChuMoi">Ghi chú</label>
      <input type="text" id="ghiChuMoi" placeholder="VD: Nhập hàng đợt 1 tháng 4..." />

      <div class="btn-wrapper">
        <button type="submit" class="btn-them">
          <i class="fas fa-plus"></i> Tạo phiếu
        </button>
      </div>
    </form>
  </section>

  <!-- ===== 3. DANH SACH PHIEU NHAP ===== -->
  <section>
    <h2><i class="fas fa-clipboard-check" style="font-size:22px"></i> Danh mục phiếu nhập</h2>
    <div style="overflow-x:auto">
      <table id="bangPhieuNhap">
        <thead>
          <tr>
            <th>Mã phiếu</th><th>Ngày nhập</th><th>Số SP</th>
            <th>Tổng tiền</th><th>Ghi chú</th><th>Trạng thái</th>
            <th style="width:170px">Chức năng</th>
          </tr>
        </thead>
        <tbody id="tbodyPhieuNhap">
          <tr><td colspan="7" style="text-align:center;color:#999;padding:16px">
            <i class="fas fa-spinner fa-spin"></i> Đang tải...
          </td></tr>
        </tbody>
      </table>
    </div>
  </section>

</div><!-- /container-phieunhap -->
</div>
</div>

<!-- ===================================================== -->
<!-- POPUP: XEM / SUA PHIEU NHAP (them san pham vao phieu) -->
<!-- ===================================================== -->
<div id="popup-suaphieu" class="overlay-phieu">
  <a href="#" class="overlay-bg"></a>
  <div class="popup-box popup-phieu-large">

    <h2><i class="fas fa-edit"></i>
      Phiếu nhập: <span id="popupMaPhieu"></span>
      <span id="popupBadge"></span>
    </h2>

    <!-- Thong tin dau phieu -->
    <div class="popup-info-row">
      <div class="popup-field">
        <label>Ngày nhập</label>
        <input type="date" id="popupNgayNhap" />
      </div>
      <div class="popup-field">
        <label>Ghi chú</label>
        <input type="text" id="popupGhiChu" placeholder="Ghi chú..." />
      </div>
      <div class="popup-field" style="align-self:flex-end">
        <button type="button" id="btnLuuDauPhieu" class="btn-luu-info">
          <i class="fas fa-save"></i> Lưu thông tin
        </button>
      </div>
    </div>

    <hr style="margin:14px 0;border-color:#eee" />

    <!-- Tim kiem san pham de them vao phieu -->
    <div id="khuTimSP" class="khu-tim-sp">
      <h3 style="margin:0 0 10px;font-size:15px">
        <i class="fas fa-plus-circle"></i> Thêm sản phẩm vào phiếu
      </h3>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
        <div style="flex:2;min-width:200px">
          <label style="font-size:12px;font-weight:600">Tìm sản phẩm</label>
          <input type="text" id="timSPPhieu"
                 placeholder="Nhập mã hoặc tên sản phẩm..." />
        </div>
        <div style="flex:1;min-width:130px">
          <label style="font-size:12px;font-weight:600">Số lượng</label>
          <input type="number" id="soLuongThem" min="1" value="1" />
        </div>
        <div style="flex:1;min-width:140px">
          <label style="font-size:12px;font-weight:600">Giá nhập (VND)</label>
          <input type="number" id="donGiaThem" min="0" step="1000" placeholder="0" />
        </div>
        <div style="align-self:flex-end">
          <button type="button" id="btnThemSPVaoPhieu" class="btn-them-sp">
            <i class="fas fa-plus"></i> Thêm
          </button>
        </div>
      </div>

      <!-- Ket qua goi y san pham -->
      <div id="goiYSP" class="goi-y-sp" style="display:none"></div>
    </div>

    <!-- Danh sach chi tiet phieu -->
    <h3 style="margin:14px 0 8px;font-size:15px">
      <i class="fas fa-list"></i> Danh sách sản phẩm trong phiếu
    </h3>
    <div style="overflow-x:auto">
      <table id="bangChiTietPhieu">
        <thead>
          <tr>
            <th>#</th><th>Mã SP</th><th>Tên sản phẩm</th>
            <th>DVT</th><th>Số lượng</th><th>Giá nhập</th>
            <th>Thành tiền</th><th id="cotChucNang">Chức năng</th>
          </tr>
        </thead>
        <tbody id="tbodyChiTiet">
          <tr><td colspan="8" style="text-align:center;color:#999">Chưa có sản phẩm</td></tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="6" style="text-align:right;font-weight:700">Tổng cộng:</td>
            <td id="tongTienChiTiet" style="font-weight:700;color:#e74c3c"></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Nut hoan thanh phieu -->
    <div id="khuHoanThanh" style="margin-top:16px;text-align:right">
      <button type="button" id="btnHoanThanhPhieu" class="btn-hoan-thanh">
        <i class="fas fa-check-circle"></i> Hoàn thành phiếu
      </button>
    </div>

    <a href="#" class="close" id="btnDongPopup">&#x2715; Đóng</a>
  </div>
</div>

<!-- Popup sua 1 dong chi tiet -->
<div id="popup-suadong" class="overlay-phieu" style="z-index:1001">
  <a href="#" class="overlay-bg"></a>
  <div class="popup-box" style="width:360px">
    <h2><i class="fas fa-edit"></i> Sửa dòng sản phẩm</h2>
    <input type="hidden" id="suaDongId" />
    <label>Tên sản phẩm</label>
    <input type="text" id="suaDongTen" readonly
           style="background:#f5f5f5;color:#888" />
    <label>Số lượng</label>
    <input type="number" id="suaDongSoLuong" min="1" />
    <label>Giá nhập (VND)</label>
    <input type="number" id="suaDongDonGia" min="0" step="1000" />
    <div class="popup-btn-row">
      <button type="button" id="btnLuuSuaDong">
        <i class="fas fa-save"></i> Lưu
      </button>
    </div>
    <a href="#popup-suaphieu" class="close">&#x2715; Quay lại</a>
  </div>
</div>

<script src="../JSAdmin/PhieuNhap.js"></script>
</body>
</html>