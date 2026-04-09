<?php
session_start();
// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login.php");
    exit();
}

// Kết nối database
require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Đổi Thông Tin</title>
    <link rel="stylesheet" href="../css/styleTT.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
</head>
<body>
    <div class="header">
        <div class="logo">
            <a href="User.php">
                <img src="../Image/logostore-Photoroom.png" alt="Logo" >
            </a>
        </div>

        <div class="others">
            <ul>
                <a class="header-icon" href="User.php" title="Trở về trang chủ">
                    <i class="fa-solid fa-house-chimney"></i>
                </a>
            </ul>
            <ul>
                <a class="header-icon" href="cart.php" title="Giỏ hàng">
                    <i class="fa-solid fa-shopping-bag"></i>
                </a>
            </ul>
            <ul>
                <a href="../Main.php" style="text-decoration: none;">
                    <button><i class="fa-solid fa-right-from-bracket" style="margin-right: 5px;"></i> Đăng Xuất</button>
                </a>
            </ul>
        </div>
    </div>

    <div class="content">
        <div class="edit-card">
            <h2><i class="fa-solid fa-user-pen"></i> Cập Nhật Thông Tin</h2>
            
            <form onsubmit="handleSaveInfo(event)">
                <div class="input-group">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" id="upd-username" placeholder="<?php echo htmlspecialchars($user['username']); ?>">
                </div>
                
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="upd-fullname" placeholder="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>
                
                <div class="input-group">
                    <i class="fa-solid fa-phone"></i>
                    <input type="tel" id="upd-phone" placeholder="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                
                <div class="input-group">
                    <i class="fa-solid fa-location-dot"></i>
                    <input type="text" id="upd-address" placeholder="<?php echo htmlspecialchars($user['address']); ?>">
                </div>
                
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="upd-email" placeholder="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="upd-password" placeholder="Nhập mật khẩu mới nếu muốn đổi...">
                </div>

                <div class="action-buttons">
                    <a href="ThongTin.php" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Quay lại
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="custom-modal">
        <div class="modal-content" id="modal-content-box">
            <div class="modal-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <h3>Thành công!</h3>
            <p>Thông tin của bạn đã được cập nhật.</p>
            <button class="modal-close" onclick="closeModal()">Đóng</button>
        </div>
    </div>
     
    <div class="contact">
      <div class="icons">
        <i class="fa-brands fa-facebook"></i>
        <i class="fa-brands fa-instagram"></i>
        <i class="fa-brands fa-tiktok"></i>
        <i class="fa-brands fa-facebook-messenger"></i>
        <i class="fa-brands fa-zalo"></i>
        <i class="fa-solid fa-phone"></i>
        <i class="fa-solid fa-envelope"></i>
      </div>
    </div>

    <div class="footer-wrapper">
        <footer class="footer-box">
            <div class="footer-container">
                <div class="footer-column">
                    <h3>FLORENTINO SHOP </h3>
                    <p>Trụ sở chính: 123 An Dương Vương, Phường 3, Quận 5, TP Hồ Chí Minh</p>
                    <p>Văn phòng: Tầng 60, Tòa nhà Lankmark 81, số 208 đường Nguyễn Hữu Cảnh, P.22, Q.Bình Thạnh, TP.HCM.</p>
                    <p>Chịu trách nhiệm nội dung: <b>HDQH</b></p>
                    <p>Số Điện Thoại: 0123 456 789 </p>
                    <p>Email: FlorentinoShop@gmai.com</p>
                </div>
                <div class="footer-column">
                    <h3>HỖ TRỢ KHÁCH HÀNG</h3>
                    <p><a href="#">Điều khoản sử dụng</a></p>
                    <p><a href="#">Chính sách thành viên</a></p>
                    <p><a href="#">Chính sách bảo mật</a></p>
                </div>
                <div class="footer-column">
                    <h3>GIẤY PHÉP & BẢN QUYỀN</h3>
                    <p>Đã đăng ký Bộ Công Thương</p>
                    <img src="http://dangkywebvoibocongthuong.com/wp-content/uploads/2021/11/logo-da-thong-bao-bo-cong-thuong.png" alt="Bộ Công Thương 1" class="conthuong_img">
                    <img src="http://dangkywebvoibocongthuong.com/wp-content/uploads/2021/11/logo-da-dang-ky-bo-cong-thuong.png" alt="Bộ Công Thương 2" class="conthuong_img">
                </div>
            </div>
    </footer>
  </div>
<script src="../js/Thongtin.js"></script>
</body>
</html>