<?php
require_once __DIR__ . '/../includes/shop_helpers.php';
ensure_logged_in();

$action = $_GET['action'] ?? '';
$id = trim($_GET['id'] ?? '');
$cart = get_cart();

if ($action === 'add' && $id !== '') {
    if (!isset($cart[$id])) {
        $cart[$id] = 0;
    }
    $cart[$id] += 1;
    set_cart($cart);
    header('Location: cart.php');
    exit();
}

if ($action === 'remove' && $id !== '') {
    unset($cart[$id]);
    set_cart($cart);
    header('Location: cart.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty']) && is_array($_POST['qty'])) {
    foreach ($_POST['qty'] as $pid => $qty) {
        $pid = trim((string)$pid);
        $qty = max(0, (int)$qty);
        if ($pid === '') {
            continue;
        }
        if ($qty === 0) {
            unset($cart[$pid]);
        } else {
            $cart[$pid] = $qty;
        }
    }
    set_cart($cart);
    header('Location: cart.php');
    exit();
}

$items = [];
$total = 0.0;
if ($cart) {
    $productIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $types = str_repeat('s', count($productIds));
    $sql = "SELECT ma_sp, ten_sp, gia_ban, hinh_anh FROM san_pham WHERE ma_sp IN ($placeholders) AND hien_trang = 'hien_thi'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$productIds);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $qty = $cart[$row['ma_sp']] ?? 0;
        $lineTotal = (float)$row['gia_ban'] * $qty;
        $total += $lineTotal;
        $items[] = ['p' => $row, 'qty' => $qty, 'line_total' => $lineTotal];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ Hàng</title>
    <!-- Dùng lại toàn bộ CSS của bản giohang.html -->
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/giohang.css">
    <link rel="stylesheet" href="../css/thanhtoan.css">
    <link rel="stylesheet" href="../css/styleUser.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<header class="main-header">
  <div class="logo">
    <a href="User.php"><img src="../Image/logostore-Photoroom.png" class="store-logo"></a>
  </div>

  <div class="search-bar">
    <input type="text" placeholder="Tìm kiếm sản phẩm..." disabled>
    <button>
      <img src="https://cdn-icons-png.flaticon.com/512/149/149852.png">
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

<!-- GIỎ HÀNG -->
<div class="container"">
  <h2><i class="fas fa-shopping-cart"></i> Giỏ Hàng</h2>

  <?php if (!$items): ?>
    <p>Giỏ hàng của bạn đang trống. <a href="products.php">Mua hàng ngay</a></p>
  <?php else: ?>
  <form method="post">
  <table>
    <thead>
      <tr>
        <th>Mã SP</th>
        <th>Tên</th>
        <th>Hình</th>
        <th>Giá</th>
        <th>Số lượng</th>
        <th>Thành tiền</th>
        <th>Xóa</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): $p = $item['p']; ?>
      <tr>
        <td><?= h($p['ma_sp']) ?></td>
        <td><?= h($p['ten_sp']) ?></td>
        <td><img src="<?= h($p['hinh_anh'] ?: '../Image/sp.jpg') ?>"></td>
        <td><?= format_vnd((float)$p['gia_ban']) ?></td>
        <td><input type="number" name="qty[<?= h($p['ma_sp']) ?>]" value="<?= (int)$item['qty'] ?>" min="0"></td>
        <td><?= format_vnd((float)$item['line_total']) ?></td>
        <td><a class="delete-btn" href="cart.php?action=remove&id=<?= urlencode($p['ma_sp']) ?>">✖</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="total">Tổng: <?= format_vnd($total) ?></div>

  <div class="buttons">
    <button class="btn btn-secondary" type="submit">Cập nhật giỏ hàng</button>
    <a href="checkout.php" class="btn btn-primary">Thanh toán</a>
  </div>
  </form>
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
