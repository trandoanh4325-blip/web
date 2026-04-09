<?php
session_start();
require_once '../includes/db_connect.php';

// Lấy danh sách Loại sản phẩm đổ vào thẻ Select
$sql_loai = "SELECT ma_loai, ten_loai FROM loai_san_pham ORDER BY ten_loai ASC";
$result_loai = $conn->query($sql_loai);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quản Lý Kho Vận</title>
    <link rel="stylesheet" href="../cssAdmin/khovan.css" />
    <link rel="stylesheet" href="../cssAdmin/styleAdmin.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <style>
        .tonkho-card { margin-bottom: 30px; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .tonkho-form-row { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 15px; }
        .tonkho-form-row label { font-weight: bold; margin-bottom: 5px; display: block; font-size: 14px;}
        .tonkho-form-row input, .tonkho-form-row select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; }
        .tonkho-form-row button { padding: 9px 15px; background: #0195b2; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .tonkho-form-row button:hover { background: #017a93; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; color: white; }
        .bg-danger { background: #e74c3c; }
        .bg-warning { background: #f39c12; }
        .bg-success { background: #2ecc71; }
    </style>
</head>
<body>
    
   <header class="header">
      <div class="logo">
        <a href="Admin.html">
          <img src="../Image/logostore-Photoroom.png" alt="Logo" />
        </a>
      </div>
      <div class="header-right">
        <a class="chucnang-item" href="Admin.html"><i class="fas fa-home"></i> Tổng quan</a>
        <a class="chucnang-item" href="giaban.php"><i class="fas fa-tags"></i> Giá bán</a>
        <a class="chucnang-item" href="SanPham.php"><i class="fas fa-box-open"></i> Sản phẩm</a>
        <a class="chucnang-item" href="phieunhap.php"><i class="fas fa-file-invoice"></i> Phiếu nhập</a>
        <a class="chucnang-item" href="donhang.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a>
        <a class="chucnang-item active" href="khovan.php"><i class="fas fa-truck-loading"></i> Kho vận</a>
        <a class="chucnang-item" href="Khachhang.php"><i class="fas fa-users"></i> Khách hàng</a>
        <a class="chucnang-item" href="LoginA.php" style="background: rgba(255,0,0,0.1); color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
      </div>
    </header>

    <div class="content" style="margin-top: 110px;">
      <div class="tonkho-container">
        <main class="tonkho-main">

          <div class="tonkho-card">
            <h2><i class="fas fa-exclamation-triangle" style="color:#e74c3c"></i> Cảnh báo sản phẩm sắp hết hàng</h2>
            <form id="formCanhBao" class="tonkho-filter-form">
              <div class="tonkho-form-row">
                <div>
                    <label for="nguongCanhBao">Định mức gọi là "Sắp hết" (Số lượng <=):</label>
                    <input type="number" id="nguongCanhBao" value="10" min="1" style="width: 150px;" />
                </div>
                <button type="submit"><i class="fa fa-search"></i> Kiểm tra ngay</button>
              </div>
            </form>
            <table class="tonkho-table">
              <thead><tr><th>Mã SP</th><th>Tên sản phẩm</th><th>Loại</th><th>Số lượng tồn</th><th>Tình trạng</th></tr></thead>
              <tbody id="tbodyCanhBao">
                </tbody>
            </table>
          </div>

          <div class="tonkho-card">
            <h2><i class="fas fa-history" style="color:#0195b2"></i> Tra cứu số lượng tồn tại 1 thời điểm</h2>
            <form id="formTonKhoThoiDiem" class="tonkho-filter-form">
              <div class="tonkho-form-row">
                <div>
                    <label for="loaiSPTraCuu">Loại sản phẩm</label>
                    <select id="loaiSPTraCuu">
                      <option value="">-- Tất cả loại --</option>
                      <?php while($row = $result_loai->fetch_assoc()): ?>
                          <option value="<?php echo $row['ma_loai']; ?>"><?php echo htmlspecialchars($row['ten_loai']); ?></option>
                      <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label for="ngayTraCuu">Tại thời điểm (Cuối ngày):</label>
                    <input type="date" id="ngayTraCuu" value="<?php echo date('Y-m-d'); ?>" required />
                </div>
                <button type="submit"><i class="fa fa-search"></i> Tra cứu tồn kho</button>
              </div>
            </form>
            <table class="tonkho-table">
              <thead><tr><th>Mã SP</th><th>Tên sản phẩm</th><th>Loại sản phẩm</th><th>Tồn kho tại thời điểm đó</th></tr></thead>
              <tbody id="tbodyTonKhoThoiDiem">
                 </tbody>
            </table>
          </div>

          <div class="tonkho-card">
            <h2><i class="fas fa-exchange-alt" style="color:#2ecc71"></i> Báo cáo tổng số lượng Nhập - Xuất</h2>
            <form id="formBaoCaoNhapXuat" class="tonkho-filter-form">
              <div class="tonkho-form-row">
                <div>
                    <label for="tuNgay">Từ ngày</label>
                    <input type="date" id="tuNgay" value="<?php echo date('Y-m-01'); ?>" required />
                </div>
                <div>
                    <label for="denNgay">Đến ngày</label>
                    <input type="date" id="denNgay" value="<?php echo date('Y-m-d'); ?>" required />
                </div>
                <button type="submit"><i class="fa fa-chart-bar"></i> Lập báo cáo</button>
              </div>
            </form>
            <table class="tonkho-table">
              <thead><tr><th>Mã SP</th><th>Tên sản phẩm</th><th>Tổng Nhập</th><th>Tổng Xuất</th><th>Tồn hiện tại (Tham khảo)</th></tr></thead>
              <tbody id="tbodyBaoCaoNhapXuat">
                </tbody>
            </table>
          </div>

        </main>
      </div>
    </div>

    <script src="../JSAdmin/khovan.js"></script>
  </body>
</html>