<?php
session_start();
// Chỉ cho phép Admin truy cập (nếu bạn đã làm Login Admin)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: LoginA.php"); exit();
// }

require_once '../includes/db_connect.php';

// Lấy danh sách khách hàng (bỏ qua admin)
$sql = "SELECT * FROM users WHERE role = 'customer' ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quản Lý Khách Hàng</title>
    <link rel="stylesheet" href="../cssAdmin/Khachhang.css?v=1.1" />
    <link rel="stylesheet" href="../cssAdmin/styleAdmin.css?v=1.1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
  </head>
  <body>
    <!-- Header -->
    <header class="header">
      <div class="logo">
        <a href="Admin.html">
          <img src="../Image/logostore-Photoroom.png" alt="Logo" />
        </a>
      </div>
      <div class="header-right">
        <a class="chucnang-item" href="Admin.html"><i class="fas fa-home"></i> Tổng quan</a>
        <a class="chucnang-item" href="giaban.html"><i class="fas fa-tags"></i> Giá bán</a>
        <a class="chucnang-item" href="SanPham.php"><i class="fas fa-box-open"></i> Sản phẩm</a>
        <a class="chucnang-item" href="phieunhap.php"><i class="fas fa-file-invoice"></i> Phiếu nhập</a>
        <a class="chucnang-item" href="donhang.html"><i class="fas fa-shopping-cart"></i> Đơn hàng</a>
        <a class="chucnang-item" href="khovan.html"><i class="fas fa-truck-loading"></i> Kho vận</a>
        <a class="chucnang-item active" href="Khachhang.php"><i class="fas fa-users"></i> Khách hàng</a>
        <a class="chucnang-item" href="LoginA.php" style="background: rgba(255,0,0,0.1); color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
      </div>
    </header>

    <div class="content">
      <div class="container">
        <h2><i style="font-size: 24px" class="fas">&#xf2c2;</i> Danh sách khách hàng</h2>
        
        <!-- Thanh công cụ: Tìm kiếm & Thêm mới -->
        <div class="search">
            <input type="text" id="search-input" placeholder="Tìm kiếm theo tên hoặc username...">
            <button onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Thêm tài khoản</button>
        </div>

        <div class="list-section">
          <table class="table-khachhang">
            <thead>
              <tr>
                <th>ID</th>
                <th>Tài khoản</th>
                <th>Tên khách hàng</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Địa chỉ</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    
                    <!-- Hiển thị trạng thái -->
                    <td style="color: <?php echo (isset($row['status']) && $row['status'] == 'active') ? 'green' : 'red'; ?>; font-weight: bold;">
                        <?php echo (isset($row['status']) && $row['status'] == 'active') ? 'Đang hoạt động' : 'Đang Khóa'; ?>
                    </td>
                    
                    <td>
                      <!-- Nút Reset Mật Khẩu -->
                      <button class="action-btn reset" title="Khôi phục mật khẩu mặc định (123456)" onclick="resetPassword(<?php echo $row['id']; ?>)">
                        <i class="fa-solid fa-rotate-left"></i> <span style="font-size: 15px; font-weight: bold; margin-left: 5px;">Reset</span>
                      </button>
                      
                      <!-- Nút Khóa / Mở Khóa -->
                      <button class="action-btn <?php echo (isset($row['status']) && $row['status'] == 'active') ? 'unlocked' : 'locked'; ?>" 
                              title="<?php echo (isset($row['status']) && $row['status'] == 'active') ? 'Khóa tài khoản' : 'Mở khóa tài khoản'; ?>"
                              onclick="toggleLock(<?php echo $row['id']; ?>, '<?php echo isset($row['status']) ? $row['status'] : 'active'; ?>')">
                        <i class="fa-solid <?php echo (isset($row['status']) && $row['status'] == 'active') ? 'fa-lock-open' : 'fa-lock'; ?>"></i>
                        <span style="font-size: 15px; font-weight: bold; margin-left: 5px;"><?php echo (isset($row['status']) && $row['status'] == 'active') ? 'Khóa' : 'Mở'; ?></span>
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="7">Chưa có khách hàng nào trong hệ thống.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL THÊM KHÁCH HÀNG -->
    <div id="add-user-modal" class="overlay" style="display: none; opacity: 1; visibility: visible; position: fixed;">
      <div class="popup-box">
        <h3 style="margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">Thêm Tài Khoản Mới</h3>
        <form id="form-add-user" onsubmit="handleAddUser(event)">
            <input type="text" id="add-username" placeholder="Tên đăng nhập..." required>
            <input type="text" id="add-fullname" placeholder="Họ và tên..." required>
            <input type="email" id="add-email" placeholder="Email..." required>
            <input type="text" id="add-address" placeholder="Địa chỉ..." required>
            <input type="tel" id="add-phone" placeholder="Số điện thoại..." required>
            <input type="password" id="add-password" placeholder="Mật khẩu..." required>
            
            <button type="submit" style="background-color: #0195b2; color: white;">Thêm Khách Hàng</button>
            <a href="javascript:void(0)" class="close" onclick="closeAddModal()">Hủy bỏ</a>
        </form>
      </div>
    </div>

    <!-- MODAL XÁC NHẬN CHUNG (MỚI THÊM) -->
    <div id="custom-confirm-modal" class="custom-modal-overlay">
      <div class="custom-modal-box">
        <i id="modal-icon-status" class="fa-solid fa-circle-exclamation custom-modal-icon warning"></i>
        <h3 id="modal-title-text" class="custom-modal-title">Xác nhận</h3>
        <p id="modal-message-text" class="custom-modal-message">Nội dung thông báo</p>
        <div class="custom-modal-actions">
          <button id="btn-cancel" class="btn-modal cancel" onclick="closeCustomModal()">Hủy bỏ</button>
          <button id="btn-confirm" class="btn-modal confirm" onclick="executeReset()">Đồng ý</button>
          <button id="btn-ok" class="btn-modal ok" onclick="closeCustomModal()">Đóng</button>
        </div>
      </div>
    </div>

    <script src="../js/QLKhachHang.js"></script>
  </body>
</html>