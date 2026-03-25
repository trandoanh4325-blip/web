<?php
session_start();
// Kiểm tra nếu chưa đăng nhập thì đuổi về trang Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login.html"); 
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
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông tin cá nhân</title>
    <link rel="stylesheet" href="../css/styleTT.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
</head>
<body>
    <div class="header">
        <div class="logo">
            <a href="User.html">
                <img src="../Image/logostore-Photoroom.png" alt="Logo">
            </a>
        </div>
        <div class="others">
            <ul>
                <a class="header-icon" href="User.html" title="Trở về trang chủ">
                    <i class="fa-solid fa-house-chimney"></i>
                </a>
            </ul>
            <ul>
                <a class="header-icon" href="giohang.html" title="Giỏ hàng">
                    <i class="fa-solid fa-shopping-bag"></i>
                </a>
            </ul>
            <ul>
                <a href="../Main.html" style="text-decoration: none;">
                    <button><i class="fa-solid fa-right-from-bracket" style="margin-right: 5px;"></i> Đăng Xuất</button>
                </a>
            </ul>
        </div>
    </div>

    <div class="content">
        <div class="profile-card">
            
            <div class="profile-header-bg">
                <div class="profile-avatar" id="avatar-container" title="Nhấn để thay đổi ảnh đại diện">
                    <i class="fa-solid fa-user-tie" id="default-avatar-icon"></i>
                    <img id="avatar-image" src="" alt="Avatar" style="display: none;">
                    
                    <div class="avatar-remove-badge" id="remove-avatar-btn" title="Gỡ ảnh đại diện" style="display: none;">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>

                    <div class="avatar-edit-badge" title="Thay đổi ảnh đại diện">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                </div>
            </div>

            <input type="file" id="avatar-input" accept="image/*" style="display: none;">

            <div class="profile-body">
                <h2 class="profile-name"><?php echo $user['full_name']; ?></h2>
                <p class="profile-role">Khách Hàng Thành Viên</p>

                <div class="info-list">
                    <div class="info-item">
                        <div class="info-icon"><i class="fa-solid fa-id-card"></i></div>
                        <div class="info-text">
                            <label>Tên đăng nhập (Username)</label>
                            <span><?php echo $user['username']; ?></span>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
                        <div class="info-text">
                            <label>Số điện thoại</label>
                            <span><?php echo $user['phone']; ?></span>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fa-solid fa-envelope"></i></div>
                        <div class="info-text">
                            <label>Email liên hệ</label>
                            <span><?php echo $user['email']; ?></span>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="info-text">
                            <label>Địa chỉ giao hàng</label>
                            <span><?php echo $user['address']; ?></span>
                        </div>
                    </div>
                </div>

                <button class="btn-edit-profile" onclick="window.location.href='DoiTT.php'">
                    <i class="fa-solid fa-arrows-rotate"></i> Cập Nhật Thông Tin
                </button>
            </div>
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