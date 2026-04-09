<?php
// =============================================================
// Admin/phieunhap.php  –  Quan ly Phieu Nhap Hang
// =============================================================
session_start();
require_once '../includes/db_connect.php';
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
    <a class="chucnang-item" href="giaban.php">        <i class="fas fa-tags"></i>          Giá bán</a>
    <a class="chucnang-item" href="SanPham.php">        <i class="fas fa-box-open"></i>       Sản phẩm</a>
    <a class="chucnang-item active" href="phieunhap.php">   <i class="fas fa-file-invoice"></i>   Phiếu nhập</a>
    <a class="chucnang-item" href="donhang.php">       <i class="fas fa-shopping-cart"></i> Đơn hàng</a>
    <a class="chucnang-item" href="khovan.php">        <i class="fas fa-truck-loading"></i> Kho vận</a>
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

  <!-- ===== 1. TAO PHIEU NHAP MOI ===== -->
  <section>
    <h2><i class="fas fa-file-invoice-dollar" style="font-size:22px"></i> Tạo phiếu nhập mới</h2>
    <div style="margin-bottom: 20px;">
        <button type="button" id="btnTaoPhieuMoiNhanh" class="btn-them" style="padding: 12px 24px; font-size: 15px;">
          <i class="fas fa-plus"></i> Tạo phiếu nhập ngay
        </button>
    </div>
  </section>

  <!-- ===== 2. TIM KIEM PHIEU NHAP ===== -->
  <section>
    <h2><i class="fas fa-search" style="font-size:22px"></i> Tìm kiếm phiếu nhập</h2>
    <div class="search">
      <input type="text" id="timKiemPhieu"
             placeholder="Tìm theo mã phiếu hoặc tên sản phẩm..." />
      <button class="btn-them" id="btnTimKiem">
        <i class="fas fa-search"></i> Tìm kiếm
      </button>
    </div>

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
    
    <div class="popup-box-2-cot">
        <h2 class="popup-title-new">
            <i class="fas fa-edit"></i>
            Phiếu nhập: <span id="popupMaPhieu"></span>
            <span id="popupBadge"></span>
        </h2>

        <div class="popup-main-layout">
            
            <div class="popup-side-left">
                
              <div class="box-white-item" id="khuTimSP">
                  <h3><i class="fas fa-plus-circle"></i> Thêm sản phẩm</h3>
                    <div class="field-item">
                        <label>Tìm sản phẩm</label>
                        <div style="position: relative;">
                            <input type="text" id="timSPPhieu" placeholder="Nhập mã hoặc tên..." autocomplete="off" />
                            <div id="goiYSP" class="goi-y-sp"></div>
                        </div>
                    </div>

                    <div class="row-flex">
                        <div class="field-item flex-1">
                            <label>Số lượng</label>
                            <input type="number" id="soLuongThem" value="1" min="1" />
                        </div>
                        <div class="field-item flex-1">
                            <label>Giá nhập (VNĐ)</label>
                            <input type="number" id="donGiaThem" placeholder="0" />
                        </div>
                    </div>

                    <button type="button" id="btnThemSPVaoPhieu" class="btn-green-add">
                        <i class="fas fa-plus"></i> Thêm vào danh sách
                    </button>
                </div>

                <div class="box-white-item mt-20">
                    <h3><i class="fas fa-info-circle"></i> Thông tin phiếu</h3>
                    <div class="field-item">
                        <label>Ngày nhập</label>
                        <input type="date" id="popupNgayNhap" />
                    </div>
                    <div class="field-item">
                        <label>Ghi chú</label>
                        <input type="text" id="popupGhiChu" placeholder="Nhập ghi chú nếu có..." />
                    </div>
                    <button type="button" id="btnLuuDauPhieu" class="btn-blue-save">
                        <i class="fas fa-save"></i> Cập nhật thông tin
                    </button>
                </div>
            </div>

            <div class="popup-side-right">
                <div class="box-white-item h-full">
                    <h3><i class="fas fa-list"></i> Danh sách sản phẩm trong phiếu</h3>
                    
                    <div class="table-scroll-area">
                        <table class="table-new-style">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Mã SP</th>
                                    <th>Tên sản phẩm</th>
                                    <th>DVT</th>
                                    <th>SL</th>
                                    <th>Giá nhập</th>
                                    <th>Thành tiền</th>
                                    <th id="cotChucNang">Sửa/Xóa</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyChiTiet">
                                </tbody>
                        </table>
                    </div>

                    <div class="footer-summary" id="khuHoanThanh">
                        <div class="total-text">
                            Tổng cộng: <span id="tongTienChiTiet">0 đ</span>
                        </div>
                        <button type="button" id="btnHoanThanhPhieu" class="btn-red-final">
                            <i class="fas fa-check-circle"></i> Hoàn thành & Lưu phiếu
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="popup-close-row">
             <a href="#" class="btn-close-text"><i class="fas fa-times"></i> Đóng cửa sổ</a>
        </div>
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