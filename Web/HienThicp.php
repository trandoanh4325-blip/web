<?php
session_start();
// Gọi file kết nối CSDL và các hàm tiện ích (KHÔNG bắt buộc đăng nhập)
require_once __DIR__ . '/includes/shop_helpers.php';

$query = trim($_GET['q'] ?? '');
$categoryId = trim($_GET['category'] ?? '');
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8; // Số sản phẩm trên mỗi trang

// Lấy danh sách loại sản phẩm cho ô Lọc
$categories = [];
$cateResult = $conn->query("SELECT ma_loai, ten_loai FROM loai_san_pham ORDER BY ten_loai ASC");
if ($cateResult) {
    while ($row = $cateResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

$where = [];
$types = '';
$params = [];

if ($query !== '') {
    $where[] = 'sp.ten_sp LIKE ?';
    $types .= 's';
    $params[] = '%' . $query . '%';
}
if ($categoryId !== '') {
    $where[] = 'sp.ma_loai = ?';
    $types .= 's';
    $params[] = $categoryId;
}
if ($minPrice !== null) {
    $where[] = 'sp.gia_ban >= ?';
    $types .= 'd';
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    $where[] = 'sp.gia_ban <= ?';
    $types .= 'd';
    $params[] = $maxPrice;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Đếm tổng số sản phẩm để chia trang
$countSql = "SELECT COUNT(*) AS total FROM san_pham sp WHERE sp.hien_trang = 'hien_thi'" . ($where ? (' AND ' . implode(' AND ', $where)) : '');
$countStmt = $conn->prepare($countSql);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = (int)$countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Lấy sản phẩm của trang hiện tại
$productSql = "SELECT sp.ma_sp, sp.ten_sp, sp.gia_ban, sp.hinh_anh, l.ten_loai
               FROM san_pham sp
               JOIN loai_san_pham l ON l.ma_loai = sp.ma_loai
               WHERE sp.hien_trang = 'hien_thi'" . ($where ? (' AND ' . implode(' AND ', $where)) : '') . "
               ORDER BY sp.ngay_them DESC
               LIMIT ? OFFSET ?";
$stmt = $conn->prepare($productSql);
$productTypes = $types . 'ii';
$productParams = $params;
$productParams[] = $perPage;
$productParams[] = $offset;
$stmt->bind_param($productTypes, ...$productParams);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hàm tạo URL cho các nút phân trang
function build_url(int $targetPage): string
{
    $base = $_GET;
    $base['page'] = $targetPage;
    return 'HienThicp.php?' . http_build_query($base);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <title>Sản Phẩm - Florentino Shop</title>
  <link rel="stylesheet" href="css/styleUser.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
  <style>
      /* CSS Layout dạng lưới (Grid) thay cho HTML cứng cũ */
      .container { max-width: 1180px; margin: 120px auto; padding: 0 16px; }
      .filter-box { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px; margin-bottom: 18px; }
      .filter-box input, .filter-box select, .filter-box button { padding: 10px; border-radius: 8px; border: 1px solid #ddd; }
      .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
      .card { border: 1px solid #eee; border-radius: 10px; padding: 10px; background: #fff; text-align: center; transition: 0.3s;}
      .card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); transform: translateY(-3px);}
      .card img { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; }
      .price { color: #d4497f; font-weight: 700; margin: 10px 0; font-size: 16px;}
      .cate { color: #777; font-size: 13px; margin: 5px 0;}
      .actions { margin-top: 10px; display: flex; justify-content: space-between; gap: 8px; }
      .actions a { text-decoration: none; text-align: center; padding: 8px 10px; border-radius: 8px; font-size: 14px;}
      .detail { border: 1px solid #ccc; color: #333; flex: 1; }
      .detail:hover { background: #f5f5f5;}
      .add { background: #f59cb7; color: #fff; flex: 1; cursor: pointer;}
      .add:hover { background: #e0809d; }
      .paging { margin: 22px 0; text-align: center; gap: 8px; flex-wrap: wrap; }
      .paging a, .paging span { padding: 7px 12px; border: 1px solid #ccc; border-radius: 6px; text-decoration: none; margin: 0 4px;}
      .paging a:hover { background: #f9f9f9; }
      .paging .active { background: #f59cb7; color: #fff; border-color: #f59cb7; }
  </style>
</head>

<body>
  <!-- ========== HEADER ========== -->
  <div class="header">
    <div class="logo">
      <a href="Main.php">
        <img src="Image/logostore-Photoroom.png" alt="Logo" />
      </a>
    </div>
    <div class="others">
      <!-- Form tìm kiếm dẫn về chính trang này -->
      <form action="HienThicp.php" method="get" class="search-form">
        <div class="search-box">
          <input type="search" name="q" placeholder="Tìm kiếm sản phẩm..." required/>
          <button type="submit" title="Tìm kiếm">
            <i class="fa fa-search"></i>
          </button>
        </div>
      </form>
      <!-- Khóa icon Cá nhân & Giỏ hàng -->
      <ul><a class="fa fa-user require-login" href="#"></a></ul>
      <ul><a class="fa fa-shopping-bag require-login" href="#"></a></ul>
      <ul><a href="Main.php" class="fa fa-home"> </a> </ul>
    </div>
  </div>

  <!-- ========== MAIN CONTENT ========== -->
  <div class="container">
      <h2 style="margin-bottom: 20px; color: #333; border-left: 4px solid #d4497f; padding-left: 10px;">DANH SÁCH SẢN PHẨM</h2>
      
      <!-- BỘ LỌC TÌM KIẾM -->
      <form method="get" class="filter-box" action="HienThicp.php">
          <input type="text" name="q" placeholder="Tìm theo tên sản phẩm..." value="<?= h($query) ?>">
          <select name="category">
              <option value="">-- Tất cả phân loại --</option>
              <?php foreach ($categories as $c): ?>
                  <option value="<?= h($c['ma_loai']) ?>" <?= $categoryId === $c['ma_loai'] ? 'selected' : '' ?>><?= h($c['ten_loai']) ?></option>
              <?php endforeach; ?>
          </select>
          <input type="number" name="min_price" min="0" step="1000" placeholder="Giá từ (VNĐ)" value="<?= h((string)($minPrice ?? '')) ?>">
          <input type="number" name="max_price" min="0" step="1000" placeholder="Đến (VNĐ)" value="<?= h((string)($maxPrice ?? '')) ?>">
          <button type="submit" style="background:#0195b2; color:#fff; font-weight:bold; cursor:pointer;"><i class="fa fa-filter"></i> Lọc</button>
      </form>

      <!-- LƯỚI SẢN PHẨM -->
      <?php if (!$products): ?>
          <p style="text-align: center; margin-top: 50px; font-size: 18px; color: #777;">Không tìm thấy sản phẩm phù hợp.</p>
      <?php else: ?>
          <div class="grid">
              <?php foreach ($products as $p): ?>
                  <div class="card">
                      <!-- Ảnh link tới Chi tiết CP -->
                      <a href="Chitietcp.php?id=<?= urlencode($p['ma_sp']) ?>">
                        <?php $imgPath = !empty($p['hinh_anh']) ? 'ImageSanPham/' . $p['hinh_anh'] : 'ImageSanPham/sp.jpg'; ?>
                        <img src="<?= h($imgPath) ?>" alt="<?= h($p['ten_sp']) ?>">
                      </a>
                      <h4 style="margin: 10px 0 5px; color:#333; font-size: 16px;"><?= h($p['ten_sp']) ?></h4>
                      <div class="cate"><?= h($p['ten_loai']) ?></div>
                      <div class="price"><?= format_vnd((float)$p['gia_ban']) ?></div>
                      <div class="actions">
                          <!-- Link xem chi tiết dành cho Khách -->
                          <a class="detail" href="Chitietcp.php?id=<?= urlencode($p['ma_sp']) ?>">Chi tiết</a>
                          <!-- Nút thêm giỏ hàng bị khóa -->
                          <a class="add require-login" href="#">Thêm giỏ</a>
                      </div>
                  </div>
              <?php endforeach; ?>
          </div>
      <?php endif; ?>

      <!-- PHÂN TRANG -->
      <div class="paging">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <?php if ($i === $page): ?>
                  <span class="active"><?= $i ?></span>
              <?php else: ?>
                  <a href="<?= h(build_url($i)) ?>"><?= $i ?></a>
              <?php endif; ?>
          <?php endfor; ?>
      </div>
  </div>

  <!-- ========== LIÊN HỆ ========== -->
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

  <!-- ========== FOOTER ========== -->
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

    <!-- Gọi file popup.js lên -->
    <script src="js/popup.js"></script>
  </div>
  
  <!-- Giao diện POPUP BẮT ĐĂNG NHẬP -->
  <div id="loginPopup" class="popup-overlay">
    <div class="popup-content">
      <span class="close-btn" id="closePopupBtn">&times;</span>
      <div class="popup-icon">
        <i class="fa-solid fa-circle-exclamation"></i>
      </div>
      <h3>Yêu cầu đăng nhập</h3>
      <p>Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng hoặc mua hàng.</p>
      <div class="popup-actions">
        <button class="btn-cancel" id="cancelPopupBtn">Hủy</button>
        <a href="Login.php" class="btn-login">Đăng nhập ngay</a>
      </div>
    </div>
  </div>
</body>
</html>