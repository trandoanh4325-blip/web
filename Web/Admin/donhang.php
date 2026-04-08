<?php
// =============================================================
// htmlAdmin/donhang.php  –  Trang quản lý đơn hàng (Admin)
// Đổi tên từ donhang.html → donhang.php
// =============================================================
session_start();
require_once '../includes/db_connect.php';

// Kiểm tra đăng nhập (bỏ comment khi deploy)
// if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: LoginA.php');
//     exit;
// }

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quản Lý Đơn Hàng – <?= $adminName ?></title>
  <link rel="stylesheet" href="../cssAdmin/donhang.css" />
  <link rel="stylesheet" href="../cssAdmin/styleAdmin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
</head>
<body>

  <!-- Toast container -->
  <div id="toastWrap"></div>

  <!-- ===== HEADER ===== -->
  <header class="header">
    <div class="logo">
      <a href="Admin.html">
        <img src="../Image/logostore-Photoroom.png" alt="Logo" />
      </a>
    </div>
    <div class="header-right">
      <a class="chucnang-item"        href="Admin.html">      <i class="fas fa-home"></i>          Tổng quan</a>
      <a class="chucnang-item"        href="giaban.php">     <i class="fas fa-tags"></i>           Giá bán</a>
      <a class="chucnang-item"        href="SanPham.php">     <i class="fas fa-box-open"></i>       Sản phẩm</a>
      <a class="chucnang-item"        href="phieunhap.php">   <i class="fas fa-file-invoice"></i>   Phiếu nhập</a>
      <a class="chucnang-item active" href="donhang.php">     <i class="fas fa-shopping-cart"></i>  Đơn hàng</a>
      <a class="chucnang-item"        href="khovan.html">     <i class="fas fa-truck-loading"></i>  Kho vận</a>
      <a class="chucnang-item"        href="khachhang.php">   <i class="fas fa-users"></i>          Khách hàng</a>
      <a class="chucnang-item"        href="LoginA.php"
         style="background:rgba(255,0,0,0.1);color:#e74c3c;">
        <i class="fas fa-sign-out-alt"></i> Đăng xuất
      </a>
    </div>
  </header>

  <!-- Hiển thị tên admin góc trên -->
  <div style="text-align:right;padding:6px 30px 0;font-size:13px;color:#555">
    <i class="fas fa-user-circle"></i> <?= $adminName ?>
  </div>

  <!-- ===== NỘI DUNG CHÍNH ===== -->
  <div class="tab-content active">
    <div class="content">
      <div class="container">

        <!-- ===== BẢNG TẤT CẢ ĐƠN HÀNG (7 cột) ===== -->
        <h2 class="section-title">
          <i class="fas fa-shopping-bag"></i> Quản lý đơn hàng
        </h2>

        <div class="table-wrapper">
          <table class="order-table">
            <thead>
              <tr>
                <th><i class="fas fa-barcode"></i> Mã đơn</th>
                <th><i class="fas fa-user-circle"></i> Khách hàng</th>
                <th><i class="far fa-calendar-alt"></i> Ngày đặt</th>
                <th><i class="fas fa-tasks"></i> Hoạt động</th>
                <th><i class="fas fa-circle-dot"></i> Trạng thái</th>
                <th><i class="fas fa-map-marker-alt"></i> Chi tiết đơn hàng</th>
                <th><i class="fas fa-money-bill-wave"></i> Tổng tiền</th>
              </tr>
            </thead>
            <tbody id="tbodyDonHang">
              <tr class="skeleton-row">
                <td><div class="skeleton-cell" style="width:70px"></div></td>
                <td><div class="skeleton-cell" style="width:140px"></div></td>
                <td><div class="skeleton-cell" style="width:110px"></div></td>
                <td><div class="skeleton-cell" style="width:130px"></div></td>
                <td><div class="skeleton-cell" style="width:120px"></div></td>
                <td><div class="skeleton-cell" style="width:100px"></div></td>
                <td><div class="skeleton-cell" style="width:90px"></div></td>
              </tr>
              <tr class="skeleton-row">
                <td><div class="skeleton-cell" style="width:60px"></div></td>
                <td><div class="skeleton-cell" style="width:120px"></div></td>
                <td><div class="skeleton-cell" style="width:100px"></div></td>
                <td><div class="skeleton-cell" style="width:140px"></div></td>
                <td><div class="skeleton-cell" style="width:110px"></div></td>
                <td><div class="skeleton-cell" style="width:90px"></div></td>
                <td><div class="skeleton-cell" style="width:80px"></div></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ===== TÌM KIẾM ===== -->
        <h2 class="section-title" style="margin-top:10px">
          <i class="fas fa-filter"></i> Tra cứu đơn hàng
        </h2>

        <div class="search-card">
          <form id="formTimKiem">
            <div class="search-row">

              <div class="search-field">
                <label>Từ ngày</label>
                <input type="date" id="tuNgay" name="tu_ngay" />
              </div>

              <div class="search-field">
                <label>Đến ngày</label>
                <input type="date" id="denNgay" name="den_ngay" />
              </div>

              <div class="search-field">
                <label>Hoạt động</label>
                <select id="trangThaiFilter" name="trang_thai" style="min-width:180px">
                  <option value="">Tất cả trạng thái</option>
                  <option value="dang_cho">Đang chờ</option>
                  <option value="dang_chuan_bi">Đang chuẩn bị hàng</option>
                  <option value="cho_lay_hang">Chờ lấy hàng</option>
                  <option value="dang_van_chuyen">Đang vận chuyển</option>
                  <option value="giao_thanh_cong">Giao hàng thành công</option>
                  <option value="da_huy">Đã hủy</option>
                </select>
              </div>

              <div class="search-field">
                <label>Khách hàng (username)</label>
                <input type="text" id="usernameFilter" name="username" placeholder="VD: nguyenvana" style="min-width:150px" />
              </div>

              <div class="search-field">
                <label>Phường giao hàng</label>
                <input type="text" id="phuongFilter" name="phuong" placeholder="VD: Bến Nghé" style="min-width:150px" />
              </div>

              <div class="search-field">
                <label>Thành phố</label>
                <input type="text" id="thanhPhoFilter" name="thanh_pho" placeholder="VD: Hồ Chí Minh" style="min-width:150px" />
              </div>

              <div class="search-field" style="justify-content:flex-end">
                <button type="submit" class="btn-them">
                  <i class="fas fa-search"></i> Tìm kiếm
                </button>
              </div>

            </div>
          </form>
        </div>

        <p class="so-ket-qua" id="soKetQua"></p>

        <!-- ===== BẢNG KẾT QUẢ TÌM KIẾM (9 cột) ===== -->
        <div class="table-wrapper">
          <table class="order-table">
            <thead>
              <tr>
                <th><i class="fas fa-barcode"></i> Mã đơn</th>
                <th><i class="fas fa-user-circle"></i> Khách hàng</th>
                <th><i class="far fa-calendar-alt"></i> Ngày đặt</th>
                <th><i class="fas fa-circle-dot"></i> Trạng thái</th>
                <th><i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng</th>
                <th><i class="fas fa-map-pin"></i> Phường</th>
                <th><i class="fas fa-city"></i> Thành phố</th>
                <th><i class="fas fa-ban"></i> Lý do hủy</th>
                <th><i class="fas fa-money-bill-wave"></i> Tổng tiền</th>
              </tr>
            </thead>
            <tbody id="tbodyTimKiem">
              <tr>
                <td colspan="9">
                  <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <p>Nhập điều kiện và nhấn Tìm kiếm</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div><!-- /container -->
    </div><!-- /content -->
  </div><!-- /tab-content -->

  <!-- ===== MODAL CHI TIẾT ===== -->
  <div id="modalChiTiet" class="modal"
       style="display:none;position:fixed;inset:0;background:rgba(15,15,30,0.6);
              z-index:9999;align-items:center;justify-content:center;padding:16px;
              opacity:0;transition:opacity 0.28s ease;">
    <div class="modal-content">
      <div class="modal-header"
           style="padding:20px 24px;border-bottom:2px solid #f0f0f0;
                  background:linear-gradient(135deg,#e74c3c,#c0392b);
                  border-radius:18px 18px 0 0;
                  display:flex;align-items:center;justify-content:space-between;">
        <h3 style="color:#fff;font-size:18px;font-weight:800;margin:0;
                   display:flex;align-items:center;gap:8px;">
          <i class="fas fa-receipt"></i> Chi tiết đơn hàng
        </h3>
        <button class="modal-close-btn" onclick="dongModal()"
                style="background:rgba(255,255,255,0.2);border:none;color:#fff;
                       width:32px;height:32px;border-radius:50%;cursor:pointer;
                       font-size:16px;display:flex;align-items:center;justify-content:center;
                       padding:0;margin:0;">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-main">
        <div id="modalBody" class="modal-body"></div>
        <div id="modalFooter" class="modal-actions"></div>
      </div>
    </div>
  </div>

  <!-- API URL truyền sang JS qua biến PHP -->
  <script>
    const PROCESS_URL = 'process_donhang.php';
  </script>
  <script src="../JSAdmin/donhang.js"></script>

</body>
</html>
