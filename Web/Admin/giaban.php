<?php
// =============================================================
// htmlAdmin/giaban.php  –  Trang quản lý giá bán (Admin)
// =============================================================
session_start();
require_once '../includes/db_connect.php';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quản Lý Giá Bán – <?= $adminName ?></title>
  <link rel="stylesheet" href="../cssAdmin/giaban.css" />
  <link rel="stylesheet" href="../cssAdmin/styleAdmin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
</head>
<body>

  <!-- ════ HEADER ════ -->
  <header class="header">
    <div class="logo">
      <a href="Admin.php"><img src="../Image/logostore-Photoroom.png" alt="Logo" /></a>
    </div>
    <div class="header-right">
      <a class="chucnang-item"        href="Admin.php">    <i class="fas fa-home"></i>         Tổng quan</a>
      <a class="chucnang-item active" href="giaban.php">    <i class="fas fa-tags"></i>          Giá bán</a>
      <a class="chucnang-item"        href="SanPham.php">   <i class="fas fa-box-open"></i>      Sản phẩm</a>
      <a class="chucnang-item"        href="phieunhap.php"> <i class="fas fa-file-invoice"></i>  Phiếu nhập</a>
      <a class="chucnang-item"        href="donhang.php">   <i class="fas fa-shopping-cart"></i> Đơn hàng</a>
      <a class="chucnang-item"        href="khovan.php">   <i class="fas fa-truck-loading"></i> Kho vận</a>
      <a class="chucnang-item"        href="khachhang.php"> <i class="fas fa-users"></i>         Khách hàng</a>
      <a class="chucnang-item" href="LoginA.php" style="background: rgba(255,0,0,0.1); color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
    </div>
  </header>

  <!-- ════ NỘI DUNG ════ -->
  <div class="content">
    <div class="container">

      <h2>
        <i class="fas fa-dollar-sign" style="color:#0195b2;margin-right:8px;"></i>
        Lợi nhuận &amp; Giá bán
      </h2>

      <!-- ── Autocomplete tìm sản phẩm ── -->
      <label for="timSanPham">Tìm sản phẩm (gõ mã / tên để gợi ý)</label>
      <div class="sp-search-wrap">
        <input type="text" id="timSanPham"
               placeholder="Nhập mã SP hoặc tên sản phẩm để tìm nhanh..."
               autocomplete="off" />
        <i class="fas fa-search sp-search-icon"></i>
        <ul id="spSuggestList" class="sp-suggest-list"></ul>
      </div>
      <p><i>* Chọn sản phẩm từ gợi ý để tự điền thông tin, hoặc nhập thủ công bên dưới</i></p>

      <!-- Card SP đã chọn qua autocomplete -->
      <div id="spDaChon" class="sp-da-chon" style="display:none;">
        <div class="sp-da-chon-inner">
          <i class="fas fa-box-open"></i>
          <div>
            <span id="spDaChonMa" class="sp-ma"></span>
            <span id="spDaChonTen" class="sp-ten"></span>
            <span id="spDaChonLoai" class="sp-loai"></span>
          </div>
          <button type="button" id="btnBoChon" title="Bỏ chọn">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <!-- Hidden: lưu ma_sp đang làm việc -->
      <input type="hidden" id="maSPDangChon" />

      <!-- ── Tất cả field đều mở, không lock ── -->
      <div id="formGiaBanFields">

        <label for="loiNhuanTheoLoai">Lợi nhuận theo loại (%)</label>
        <input type="number" id="loiNhuanTheoLoai"
               placeholder="Tự điền khi chọn SP qua gợi ý, hoặc nhập thủ công"
               min="0" step="0.01" />
        <p><i>* % lợi nhuận mặc định của loại; sẽ dùng nếu không nhập lợi nhuận riêng SP</i></p>

        <label for="giaVon">Giá vốn (đồng) <span style="color:#e74c3c">*</span></label>
        <input type="number" id="giaVon"
               placeholder="vd: 12000" min="0" />
        <p><i>* Nhập giá vốn để tính giá bán</i></p>

        <label for="loiNhuanTheoSanPham">Lợi nhuận riêng sản phẩm (%)</label>
        <input type="number" id="loiNhuanTheoSanPham"
               placeholder="vd: 35 (để trống = dùng % theo loại)"
               min="0" step="0.01" />
        <p><i>* Lợi nhuận riêng SP ưu tiên hơn lợi nhuận theo loại</i></p>

        <!-- Preview giá bán real-time -->
        <div class="preview-box">
          <span class="label"><i class="fas fa-calculator"></i> Giá bán dự tính:</span>
          <span class="value" id="previewGiaBan">—</span>
          <span class="note" id="previewNote"></span>
        </div>

        <!-- Nút luôn bật, không disabled -->
        <button id="btnThem">
          <i class="fas fa-save"></i> Cập nhật giá bán
        </button>

      </div><!-- /formGiaBanFields -->

      <!-- ── Bảng danh sách ── -->
      <div class="list-section">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Mã SP</th>
              <th>Loại</th>
              <th>Sản phẩm</th>
              <th>Giá vốn</th>
              <th>Lợi nhuận</th>
              <th>Giá bán</th>
              <th style="text-align:center;">Sửa</th>
              <th style="text-align:center;">Xóa</th>
            </tr>
          </thead>
          <tbody id="tbodyMain">
            <tr class="loading-row"><td colspan="9">Đang tải dữ liệu...</td></tr>
          </tbody>
        </table>
      </div>

      <!-- ── Tra cứu ── -->
      <h2 style="margin-top:40px;">
        <i class="fas fa-search" style="color:#0195b2;margin-right:8px;"></i>
        Tra cứu theo giá vốn, lợi nhuận, giá bán
      </h2>
      <div class="search">
        <input type="text" id="timKiemPhieu"
               placeholder="Nhập tên SP / mã SP / loại / giá vốn / % lợi nhuận / giá bán" />
        <button id="btnTimKiem">
          <i class="fas fa-search"></i> Tìm kiếm
        </button>
      </div>

      <div class="list-section">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Mã SP</th>
              <th>Loại</th>
              <th>Sản phẩm</th>
              <th>Giá vốn</th>
              <th>Lợi nhuận</th>
              <th>Giá bán</th>
              <th style="text-align:center;">Sửa</th>
              <th style="text-align:center;">Xóa</th>
            </tr>
          </thead>
          <tbody id="tbodySearch">
            <tr class="loading-row"><td colspan="9">Nhập từ khóa và bấm Tìm kiếm.</td></tr>
          </tbody>
        </table>
      </div>

    </div><!-- /container -->
  </div><!-- /content -->

  <!-- ════ MODAL SỬA ════ -->
  <div class="modal-overlay" id="modalSua">
    <div class="modal-box">
      <div class="modal-header">
        <h3><i class="fas fa-pen"></i> Sửa thông tin giá bán</h3>
        <button class="modal-close-btn" id="btnDongModal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="suaId" />

        <label>Mã sản phẩm</label>
        <input type="text" id="suaMaSP" readonly style="background:#f5f5f5;color:#888;" />

        <label for="suaLoai">Loại sản phẩm</label>
        <select id="suaLoai">
          <option value="">-- Chọn loại --</option>
        </select>

        <label for="suaLoiNhuanLoai">Lợi nhuận theo loại (%)</label>
        <input type="number" id="suaLoiNhuanLoai" placeholder="vd: 30" min="0" step="0.01" />
        <p><i>* Thay đổi ở đây sẽ cập nhật % mặc định cho cả loại đó</i></p>

        <label for="suaTen">Tên sản phẩm</label>
        <input type="text" id="suaTen" placeholder="Tên sản phẩm" />

        <label for="suaGiaVon">Giá vốn (đồng)</label>
        <input type="number" id="suaGiaVon" placeholder="vd: 12000" min="0" />
        <p><i>* Giá vốn bình quân được tự động cập nhật khi hoàn thành phiếu nhập hàng</i></p>

        <label for="suaLoiNhuanSP">Lợi nhuận riêng sản phẩm (%)</label>
        <input type="number" id="suaLoiNhuanSP"
               placeholder="vd: 35 (để trống = dùng % theo loại)" min="0" step="0.01" />
        <p><i>* Lợi nhuận riêng SP ưu tiên hơn lợi nhuận theo loại</i></p>

        <div class="preview-box">
          <span class="label"><i class="fas fa-calculator"></i> Giá bán mới:</span>
          <span class="value" id="suaPreviewGiaBan">—</span>
          <span class="note" id="suaPreviewNote"></span>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-modal-cancel" id="btnCancelModal">Hủy bỏ</button>
        <button class="btn-modal-save" id="btnSua">
          <i class="fas fa-save"></i> Lưu thay đổi
        </button>
      </div>
    </div>
  </div>

  <!-- ════ CONFIRM XÓA ════ -->
  <div class="confirm-overlay" id="confirmDel">
    <div class="confirm-box">
      <div class="confirm-icon"><i class="fas fa-trash-alt"></i></div>
      <h4>Xác nhận xóa?</h4>
      <p id="confirmMsg">Bạn có chắc muốn xóa sản phẩm này?</p>
      <div class="confirm-actions">
        <button class="btn-cf-cancel" id="btnCancelDel">Hủy</button>
        <button class="btn-cf-delete" id="btnOkDel">
          <i class="fas fa-trash"></i> Xóa
        </button>
      </div>
    </div>
  </div>

  <!-- ════ TOAST ════ -->
  <div id="toast"></div>

  <script>
    const GIABAN_API = '../Admin/process_giaban.php';
    const SP_API     = '../Admin/process_SanPham.php';
  </script>
  <script src="../JSAdmin/giaban.js?v=1.1"></script>

</body>
</html>
