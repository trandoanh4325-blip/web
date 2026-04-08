<?php
require_once __DIR__ . '/../includes/shop_helpers.php';
ensure_logged_in();

$id = trim($_GET['id'] ?? '');
$stmt = $conn->prepare("SELECT sp.*, l.ten_loai FROM san_pham sp JOIN loai_san_pham l ON l.ma_loai = sp.ma_loai WHERE sp.ma_sp = ? AND sp.hien_trang = 'hien_thi'");
$stmt->bind_param('s', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    http_response_code(404);
    echo 'Không tìm thấy sản phẩm.';
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết sản phẩm</title>
    <link rel="stylesheet" href="../css/styleChitiet.css">
    <link rel="stylesheet" href="../css/styleUser.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
</head>
<body>
<div class="header">
    <div class="logo">
        <a href="User.php">
            <img src="../Image/logostore-Photoroom.png" alt="Logo"/>
        </a>
    </div>
    <div class="others">
        <form action="products.php" method="get" class="search-form">
            <div class="search-box">
                <input type="search" name="q" placeholder="Tìm kiếm sản phẩm..." required/>
                <button type="submit" title="Tìm kiếm">
                    <i class="fa fa-search"></i>
                </button>
            </div>
        </form>
        <ul><a class="fa fa-user" href="ThongTin.php"></a></ul>
        <ul><a class="fa fa-shopping-bag" href="cart.php"></a></ul>
        <ul><a href="User.php" class="fa fa-home"> </a></ul>
    </div>
</div>

<div class="product-detail">
    <div class="image-section">
        <img id="mainImage" src="<?= h($product['hinh_anh'] ?: '../Image/sp.jpg') ?>" alt="<?= h($product['ten_sp']) ?>" class="main-img">
        <div class="thumbnail-container">
            <img src="<?= h($product['hinh_anh'] ?: '../Image/sp.jpg') ?>" class="thumbnail" alt="thumb1">
            <img src="<?= h($product['hinh_anh'] ?: '../Image/sp.jpg') ?>" class="thumbnail" alt="thumb2">
            <img src="<?= h($product['hinh_anh'] ?: '../Image/sp.jpg') ?>" class="thumbnail" alt="thumb3">
        </div>
    </div>

    <div class="info-section">
        <h2 class="product-title"><?= h($product['ten_sp']) ?></h2>
        <p class="price">Giá bán: <span><?= format_vnd((float)$product['gia_ban']) ?></span></p>
        <div class="voucher"><i class="fa fa-ticket"></i> <span>Miễn phí vận chuyển cho đơn hàng trên 500.000đ</span></div>

        <div class="option"><label>Phân loại:</label> <?= h($product['ten_loai']) ?></div>
        <div class="option"><label>Mã SP:</label> <?= h($product['ma_sp']) ?></div>
        <div class="option"><label>Số lượng tồn:</label> <?= (int)$product['so_luong_ton'] ?></div>
        <div class="option"><label>Số lượng:</label> <input type="number" id="quantity" value="1" min="1" max="<?= (int)$product['so_luong_ton'] ?>"></div>

        <div class="buttons">
            <a href="cart.php?action=add&id=<?= urlencode($product['ma_sp']) ?>"><button class="add-cart">Thêm vào giỏ hàng</button></a>
            <a href="checkout.php?buy_now=<?= urlencode($product['ma_sp']) ?>"><button class="buy-now">Mua ngay</button></a>
        </div>
        <div class="short-desc">
            <p>Sản phẩm được chuẩn bị cẩn thận, mang phong cách thanh lịch, phù hợp làm quà tặng cho người thân và bạn bè.</p>
        </div>

        <div class="shop-info">
            <img src="../Image/logostore-Photoroom.png" alt="Logo shop">
            <h3>Florentino - Gifts That Bloom</h3>
        </div>
    </div>
</div>

<div class="product-description">
    <h3>Mô Tả Sản Phẩm</h3>
    <h2><?= h($product['ten_sp']) ?></h2>
    <h2>Sản phẩm thuộc nhóm: <?= h($product['ten_loai']) ?>.</h2>
    <details class="desc-box">
        <summary>Xem thêm <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="desc-preview">
            <p><?= nl2br(h((string)($product['mo_ta'] ?: 'Sản phẩm được chuẩn bị cẩn thận, phù hợp làm quà tặng cho nhiều dịp khác nhau.'))) ?></p>
        </div>
    </details>
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
