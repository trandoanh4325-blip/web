<?php
require_once __DIR__ . '/../includes/shop_helpers.php';
ensure_logged_in();

$category = trim($_GET['category'] ?? '');

$where = "WHERE hien_trang = 'hien_thi'";
$types = '';
$params = [];
if ($category !== '') {
    $where .= " AND ma_loai = ?";
    $types .= 's';
    $params[] = $category;
}

$productSql = "SELECT ma_sp, ten_sp, gia_ban, hinh_anh FROM san_pham $where ORDER BY ngay_them DESC LIMIT 8";
$productStmt = $conn->prepare($productSql);
if ($types !== '') {
    $productStmt->bind_param($types, ...$params);
}
$productStmt->execute();
$featured = $productStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <title>Trang chủ người dùng</title>
    <link rel="stylesheet" href="../css/styleUser.css" />
    <link
      rel="stylesheet"href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
  </head>
  <body>
   
    <div class="header">
      <div class="logo">
            <a href="User.php">
                <img src="../Image/logostore-Photoroom.png" alt="Logo"/>
            </a></div>
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
          <a class="fa fa-user" href="ThongTin.php"></a>
        </ul>
        <ul>
          <a class="fa fa-shopping-bag" href="cart.php"></a>
        </ul>
        <ul>
          <a href="../Main.html">
            <button>Đăng Xuất</button>
          </a>
        </ul>
      </div>
    </div>

  
    <div class="slideshow-container">
      
      <!-- Slide 1 -->
      <div class="mySlides fade">
        <img src="../Image/slide1.jpg" alt="Banner 1">
      </div>

      <!-- Slide 2 -->
      <div class="mySlides fade">
        <img src="../Image/slide2.jpg" alt="Banner 2">
      </div>

      <!-- Slide 3 -->
      <div class="mySlides fade">
        <img src="../Image/slide3.png" alt="Banner 3">
      </div>

      <!-- Slide 4 -->
      <div class="mySlides fade">
        <img src="../Image/slide4.jpg" alt="Banner 4">
      </div>

      <!-- Slide 5 -->
      <div class="mySlides fade">
        <img src="../Image/slide5.jpg" alt="Banner 5">
      </div>

      <!-- Nút Tới/Lui -->
      <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
      <a class="next" onclick="plusSlides(1)">&#10095;</a>
    </div>

    <!-- Nút Chấm (Dots) -->
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
        <div class="danh-muc">
          <a href="products.php?category=LSP001"><img src="../Image/1.webp" alt="thiep"/></a>
          <p>Thiệp và Phụ kiện</p>
        </div>

        <div class="danh-muc">
          <a href="products.php?category=LSP002"><img src="../Image/2.jpg" alt="trangtri"/></a>
          <p>Đồ trang trí</p>
        </div>

        <div class="danh-muc">
          <a href="products.php?category=LSP003"><img src="../Image/3.webp" alt="setqua"/></a>
          <p>Set quà tặng</p>
        </div>

        <div class="danh-muc">
          <a href="products.php?category=LSP004"><img src="../Image/4.jpg" alt="handmade"/></a>
          <p>Handmade</p>
        </div>

        <div class="danh-muc">
          <a href="products.php?category=LSP005"><img src="../Image/5.jpg" alt="luuniem"/></a>
          <p>Quà lưu niệm</p>
        </div>

        <div class="danh-muc">
          <a href="products.php?category=LSP006"><img src="../Image/6.jpg" alt="Quatang"/></a>
          <p>Quà tặng & Giỏ quà</p>
        </div>

        <div class="danh-muc">
          <a href="products.php?category=LSP007"><img src="../Image/7.webp" alt="hoagiay"/></a>
          <p>Hoa giấy</p>
        </div>

        <div class="danh-muc">
          <a href="products.php?category=LSP008"><img src="../Image/8.jpg" alt="hoathat"/></a>
          <p>Hoa thật 100%</p>
        </div>
      </div>
    </div>

    <section class="goi-y-hom-nay">
      <h2>GỢI Ý HÔM NAY <?= $category !== '' ? '- ĐÃ LỌC THEO LOẠI' : '' ?></h2>
      <div style="margin-bottom: 12px;">
        <a href="User.php" style="text-decoration:none; color:#d4497f; font-weight:700;">Tất cả sản phẩm</a>
      </div>
      <div class="goi-y-grid">
        <?php if (!$featured): ?>
          <p>Không có sản phẩm phù hợp.</p>
        <?php endif; ?>
        <?php foreach ($featured as $sp): ?>
        <div class="goi-y-card">
          <a href="product_detail.php?id=<?= urlencode($sp['ma_sp']) ?>">
          <img src="<?= h($sp['hinh_anh'] ?: '../Image/sp.jpg') ?>" alt="<?= h($sp['ten_sp']) ?>"></a>
          <div class="goi-y-info">
            <p><?= h($sp['ten_sp']) ?></p>
            <span class="gia"><?= format_vnd((float)$sp['gia_ban']) ?></span>
            <a href="cart.php?action=add&id=<?= urlencode($sp['ma_sp']) ?>"><i style="font-size: 24px" class="fas">&#xf217;</i></a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <!-- Liên hệ -->
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
          <h3>SHOP QUÀ TẶNG </h3>
          <p>Trụ sở chính: 123 An Dương Vương, Phường 3, Quận 5, TP Hồ Chí Minh</p>
          <p>Văn phòng: Tầng 60, Tòa nhà Lankmark 81, số 208 đường Nguyễn Hữu Cảnh, P.22, Q.Bình Thạnh, TP.HCM.</p>
          <p>Chịu trách nhiệm nội dung: <b>HDQH</b></p>
          <p>Số Điện Thoại: 0123 456 789 </p>
          <p>Email: shopquatang@gmai.com</p>
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
          <img src="http://dangkywebvoibocongthuong.com/wp-content/uploads/2021/11/logo-da-thong-bao-bo-cong-thuong.png" alt="Bộ Công Thương 1" class="conthuong_img"></a>
          <img src="http://dangkywebvoibocongthuong.com/wp-content/uploads/2021/11/logo-da-dang-ky-bo-cong-thuong.png" alt="Bộ Công Thương 2" class="conthuong_img"></a>
        </div>
      </div>
    </footer>
  </div>
  <script src="../js/Main.js"></script>
  </body>
</html>

