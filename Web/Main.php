<?php
session_start();
// Gọi file kết nối CSDL và các hàm tiện ích
require_once __DIR__ . '/includes/shop_helpers.php';

// Lấy danh mục nếu có lọc
$category = trim($_GET['category'] ?? '');

$where = "WHERE hien_trang = 'hien_thi'";
$types = '';
$params = [];
if ($category !== '') {
    $where .= " AND ma_loai = ?";
    $types .= 's';
    $params[] = $category;
}

// 1. ĐÃ BỎ "LIMIT 8" ĐỂ LẤY TOÀN BỘ SẢN PHẨM TRONG DATABASE
$productSql = "SELECT ma_sp, ten_sp, gia_ban, hinh_anh FROM san_pham $where ORDER BY ngay_them DESC";
$productStmt = $conn->prepare($productSql);
if ($types !== '') {
    $productStmt->bind_param($types, ...$params);
}
$productStmt->execute();
$featured = $productStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <title>Trang chủ - Florentino Shop</title>
    <link rel="stylesheet" href="css/styleMain.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
  </head>
  <body>
   
    <div class="header">
      <div class="logo">
        <a href="Main.php">
            <img src="Image/logostore-Photoroom.png" alt="Logo"/>
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
        <ul>
          <a class="fa fa-user require-login" href="#"></a>
        </ul>
        <ul>
          <a class="fa fa-shopping-bag require-login" href="#"></a>
        </ul>
        <ul>
          <a href="Login.php">
            <button>Đăng Nhập</button>
          </a>
        </ul>
      </div>
    </div>

    <div class="slideshow-container">
      <div class="mySlides fade"><img src="Image/slide1.jpg" alt="Banner 1"></div>
      <div class="mySlides fade"><img src="Image/slide2.jpg" alt="Banner 2"></div>
      <div class="mySlides fade"><img src="Image/slide3.png" alt="Banner 3"></div>
      <div class="mySlides fade"><img src="Image/slide4.jpg" alt="Banner 4"></div>
      <div class="mySlides fade"><img src="Image/slide5.jpg" alt="Banner 5"></div>

      <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
      <a class="next" onclick="plusSlides(1)">&#10095;</a>
    </div>

    <div class="dot-container">
      <span class="dot" onclick="currentSlide(1)"></span> 
      <span class="dot" onclick="currentSlide(2)"></span> 
      <span class="dot" onclick="currentSlide(3)"></span> 
      <span class="dot" onclick="currentSlide(4)"></span> 
      <span class="dot" onclick="currentSlide(5)"></span> 
    </div>

    <div class="main-container">
    
      <div class="danhsach-container">
        <h2 class="danhsach-title">DANH MỤC</h2>
        <div class="danhsach-grid">
          <div class="danh-muc"><a href="Hienthicp.php?category=LSP001"><img src="Image/1.webp" alt="thiep"/></a><p>Thiệp và Phụ kiện</p></div>
          <div class="danh-muc"><a href="Hienthicp.php?category=LSP002"><img src="Image/2.jpg" alt="trangtri"/></a><p>Đồ trang trí</p></div>
          <div class="danh-muc"><a href="Hienthicp.php?category=LSP003"><img src="Image/3.webp" alt="setqua"/></a><p>Set quà tặng</p></div>
          <div class="danh-muc"><a href="Hienthicp.php?category=LSP004"><img src="Image/4.jpg" alt="handmade"/></a><p>Handmade</p></div>
          <div class="danh-muc"><a href="Hienthicp.php?category=LSP005"><img src="Image/5.jpg" alt="luuniem"/></a><p>Quà lưu niệm</p></div>
          <div class="danh-muc"><a href="Hienthicp.php?category=LSP006"><img src="Image/6.jpg" alt="Quatang"/></a><p>Quà tặng & Giỏ quà</p></div>
          <div class="danh-muc"><a href="Hienthicp.php?category=LSP007"><img src="Image/7.webp" alt="hoagiay"/></a><p>Hoa giấy</p></div>
          <div class="danh-muc"><a href="Hienthicp.php?category=LSP008"><img src="Image/8.jpg" alt="hoathat"/></a><p>Hoa thật 100%</p></div>
        </div>
      </div>

      <section class="goi-y-hom-nay" id="khu-vuc-san-pham">
        <h2>SẢN PHẨM CỦA SHOP LORENTINO<?= $category !== '' ? '- ĐÃ LỌC THEO LOẠI' : '' ?></h2>
        <div style="margin-bottom: 12px;">
          <a href="Main.php" style="text-decoration:none; color:#d4497f; font-weight:700;">Sản Phẩm</a>
        </div>
        
        <div class="goi-y-grid" id="grid-san-pham">
          <?php if (!$featured): ?>
            <p>Không có sản phẩm phù hợp.</p>
          <?php endif; ?>
          
          <?php foreach ($featured as $sp): ?>
          <div class="goi-y-card js-product-item">
            <a href="Chitietcp.php?id=<?= urlencode($sp['ma_sp']) ?>">
              <?php $imgPath = !empty($sp['hinh_anh']) ? 'ImageSanPham/' . $sp['hinh_anh'] : 'ImageSanPham/sp.jpg'; ?>
              <img src="<?= h($imgPath) ?>" alt="<?= h($sp['ten_sp']) ?>">
            </a>
            <div class="goi-y-info">
              <p><?= h($sp['ten_sp']) ?></p>
              <span class="gia"><?= format_vnd((float)$sp['gia_ban']) ?></span>
              <a href="#" class="require-login"><i style="font-size: 24px" class="fas fa-cart-shopping"></i></a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        
        <div id="js-pagination-controls" class="js-pagination"></div>

      </section>

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
    </div> 
    
    <div class="footer-wrapper">
      <footer class="footer-box">
        <div class="footer-container">
          <div class="footer-column">
            <h3>FLORENTINO SHOP</h3>
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

    <div id="loginPopup" class="popup-overlay">
      <div class="popup-content">
        <span class="close-btn" id="closePopupBtn">&times;</span>
        <div class="popup-icon">
          <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <h3>Yêu cầu đăng nhập</h3>
        <p>Bạn cần đăng nhập để xem thông tin, thêm sản phẩm vào giỏ hàng hoặc mua hàng.</p>
        <div class="popup-actions">
          <button class="btn-cancel" id="cancelPopupBtn">Hủy</button>
          <a href="Login.php" class="btn-login">Đăng nhập ngay</a>
        </div>
      </div>
    </div>

    <script src="js/Main.js"></script>
    <script src="js/popup.js"></script>
  </body>
</html>