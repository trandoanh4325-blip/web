<?php
require_once __DIR__ . '/../includes/shop_helpers.php';
ensure_logged_in();

$userId = (int)$_SESSION['user_id'];
$placed = trim($_GET['placed'] ?? '');

$orders = [];
$sql = "SELECT ma_don, dia_chi_giao, phuong, quan, thanh_pho, tong_tien, hoat_dong, trang_thai_tt, ngay_dat
        FROM don_hang
        WHERE id = ?
        ORDER BY ngay_dat DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử mua hàng</title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/giohang.css">
    <link rel="stylesheet" href="../css/thanhtoan.css">
    <link rel="stylesheet" href="../css/styleUser.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        .wrap { max-width: 1000px; margin: 140px auto 20px; padding: 0 16px; }
        .order { background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 14px; margin-bottom: 12px; }
        .row { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .status { text-transform: uppercase; font-size: 12px; padding: 4px 8px; border-radius: 999px; background: #f4ecff; color: #6a4cbf; }
        .ok { background: #e9f9ef; color: #1f8a4d; padding: 9px 10px; border-radius: 8px; margin-bottom: 14px; }
    </style>
</head>
<body>
<header class="main-header">
  <div class="logo">
    <a href="User.php"><img src="../Image/logostore-Photoroom.png" class="store-logo" alt="Logo"></a>
  </div>

  <div class="search-bar">
    <input type="text" placeholder="Tìm kiếm sản phẩm..." disabled>
    <button type="button">
      <img src="https://cdn-icons-png.flaticon.com/512/149/149852.png" alt="">
    </button>
  </div>

  <nav class="nav-links">
    <a href="User.php">Trang chủ</a>
    <a href="ThongTin.php">Tài khoản</a>
    <a href="cart.php">Giỏ hàng</a>
    <a href="order_history.php">Lịch sử giao dịch</a>
    <a href="#">Tư vấn KH</a>
  </nav>
</header>
<div class="wrap">
    <h2>Lịch sử mua hàng</h2>
    <?php if ($placed !== ''): ?><div class="ok">Đặt hàng thành công. Mã đơn: #<?= h($placed) ?></div><?php endif; ?>

    <?php if (!$orders): ?>
        <p>Bạn chưa có đơn hàng nào.</p>
    <?php else: ?>
        <?php foreach ($orders as $o): ?>
            <div class="order">
                <div class="row">
                    <strong>Đơn #<?= h($o['ma_don']) ?></strong>
                    <span class="status"><?= h($o['hoat_dong']) ?></span>
                </div>
                <div>Địa chỉ giao: <?= h($o['dia_chi_giao']) ?></div>
                <div>Khu vực: <?= h(trim($o['phuong'] . ' - ' . $o['quan'] . ' - ' . $o['thanh_pho'], ' -')) ?></div>
                <div>Thanh toán: <?= h($o['trang_thai_tt']) ?></div>
                <div>Tổng tiền: <strong><?= format_vnd((float)$o['tong_tien']) ?></strong></div>
                <div>Thời gian: <?= h($o['ngay_dat']) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
        <div class="contact">
    <h3>Liên hệ với chúng tôi</h3>
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
</body>
</html>
