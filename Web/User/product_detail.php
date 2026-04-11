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
// Lấy danh sách ảnh phụ từ bảng san_pham_hinh_anh
$imgStmt = $conn->prepare("SELECT duong_dan FROM san_pham_hinh_anh WHERE ma_sp = ? ORDER BY thu_tu ASC");
$imgStmt->bind_param('s', $id);
$imgStmt->execute();
$list_images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
        <?php 
            // Đường dẫn ảnh chính
            $mainImgPath = !empty($product['hinh_anh']) ? '../ImageSanPham/' . $product['hinh_anh'] : '../ImageSanPham/sp.jpg'; 
        ?>
        <img id="mainImage" src="<?= h($mainImgPath) ?>" alt="<?= h($product['ten_sp']) ?>" class="main-img">
        
        <div class="thumbnail-container">
            <img src="<?= h($mainImgPath) ?>" class="thumbnail" alt="thumb-main" onclick="document.getElementById('mainImage').src=this.src;">
            
            <?php foreach ($list_images as $img): ?>
                <?php $thumbPath = '../ImageSanPham/' . $img['duong_dan']; ?>
                <img src="<?= h($thumbPath) ?>" class="thumbnail" alt="thumb" onclick="document.getElementById('mainImage').src=this.src;">
            <?php endforeach; ?>
    </div>
    </div>

    <div class="info-section">
        <h2 class="product-title"><?= h($product['ten_sp']) ?></h2>
        <p class="price">Giá bán: <span><?= format_vnd((float)$product['gia_ban']) ?></span></p>
        <div class="voucher"><i class="fa fa-ticket"></i> <span>Miễn phí vận chuyển cho tất cả đơn hàng</span></div>

        <div class="option"><label>Phân loại:</label> <?= h($product['ten_loai']) ?></div>
        <div class="option"><label>Mã SP:</label> <?= h($product['ma_sp']) ?></div>
        <div class="option"><label>Số lượng tồn:</label> <?= (int)$product['so_luong_ton'] ?></div>
        <div class="option"><label>Số lượng:</label> <input type="number" id="quantity" value="1" min="1" max="<?= (int)$product['so_luong_ton'] ?>"></div>

        <div class="buttons">
    <!-- 🔥 FIX: Thêm giỏ -->
    <button class="add-cart" onclick="addToCart()">Thêm vào giỏ hàng</button>

    <!-- 🔥 FIX: Mua ngay -->
    <button class="buy-now" onclick="buyNow()">Mua ngay</button>
</div>

<script>
function checkStock() {
    let qtyInput = document.getElementById("quantity");
    let qty = parseInt(qtyInput.value);
    let stock = parseInt(qtyInput.getAttribute("max"));

    if (qty > stock) {
        // Thay alert bằng hàm mới
        showCustomAlert("Sản phẩm này chỉ còn lại " + stock + " cái trong kho!", qtyInput);
        return false;
    }
    if (qty < 1 || isNaN(qty)) {
        showCustomAlert("Số lượng không hợp lệ!", qtyInput);
        return false;
    }
    return true;
}

function addToCart() {
    if (!checkStock()) return; 
    let qty = document.getElementById("quantity").value;
    let id = "<?= $product['ma_sp'] ?>";
    window.location.href = "cart.php?action=add&id=" + id + "&qty=" + qty;
}

function buyNow() {
    if (!checkStock()) return; 
    let qty = document.getElementById("quantity").value;
    let id = "<?= $product['ma_sp'] ?>";
    window.location.href = "checkout.php?buy_now=" + id + "&qty=" + qty;
}
</script>
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
<style>
/* Làm mờ nền phía sau */
.custom-alert-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    visibility: hidden;
    opacity: 0;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}
/* Hiển thị popup */
.custom-alert-overlay.show {
    visibility: visible;
    opacity: 1;
}
/* Khối nội dung popup */
.custom-alert-box {
    background: #fff;
    padding: 30px 20px;
    border-radius: 12px;
    text-align: center;
    width: 350px;
    max-width: 90%;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    transform: translateY(-30px);
    transition: transform 0.3s ease;
}
.custom-alert-overlay.show .custom-alert-box {
    transform: translateY(0);
}
/* Icon cảnh báo */
.custom-alert-icon i {
    font-size: 55px;
    color: #e74c3c;
    margin-bottom: 15px;
}
.custom-alert-box h3 {
    margin: 0 0 10px;
    font-size: 22px;
    color: #333;
}
.custom-alert-box p {
    margin: 0 0 25px;
    color: #666;
    font-size: 16px;
    line-height: 1.5;
}
/* Nút đóng */
.custom-alert-btn {
    background: #e74c3c;
    color: #fff;
    border: none;
    padding: 10px 30px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.custom-alert-btn:hover {
    background: #c0392b;
}
</style>

<div id="customAlertOverlay" class="custom-alert-overlay">
    <div class="custom-alert-box">
        <div class="custom-alert-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h3>Thông báo</h3>
        <p id="customAlertMessage">Nội dung thông báo sẽ hiện ở đây</p>
        <button onclick="closeCustomAlert()" class="custom-alert-btn">Đã hiểu</button>
    </div>
</div>

<script>
let currentFocusElement = null; // Lưu lại ô input bị lỗi để focus lại sau khi đóng popup

// Hàm gọi popup thay cho alert()
function showCustomAlert(message, elementToFocus) {
    document.getElementById('customAlertMessage').innerText = message;
    document.getElementById('customAlertOverlay').classList.add('show');
    currentFocusElement = elementToFocus;
}

// Hàm đóng popup
function closeCustomAlert() {
    document.getElementById('customAlertOverlay').classList.remove('show');
    if (currentFocusElement) {
        currentFocusElement.focus(); // Đưa con trỏ chuột về lại ô nhập lỗi
        currentFocusElement = null;
    }
}
</script>
</body>
</html>
