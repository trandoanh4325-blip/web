<?php
require_once __DIR__ . '/../includes/shop_helpers.php';
ensure_logged_in();

$userId = (int)$_SESSION['user_id'];
$userStmt = $conn->prepare("SELECT full_name, phone, address FROM users WHERE id = ?");
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
if (!$user) {
    echo 'Không lấy được thông tin tài khoản.';
    exit();
}

$cart = get_cart();
$buyNowId = trim($_GET['buy_now'] ?? '');
if ($buyNowId !== '' && empty($cart)) {
    $cart[$buyNowId] = 1;
    set_cart($cart);
}

$items = [];
$total = 0.0;
if ($cart) {
    $productIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $types = str_repeat('s', count($productIds));
    $stmt = $conn->prepare("SELECT ma_sp, ten_sp, gia_ban, hinh_anh FROM san_pham WHERE ma_sp IN ($placeholders) AND hien_trang = 'hien_thi'");
    $stmt->bind_param($types, ...$productIds);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $qty = (int)($cart[$row['ma_sp']] ?? 0);
        $line = $qty * (float)$row['gia_ban'];
        $total += $line;
        $items[] = ['product' => $row, 'qty' => $qty, 'line_total' => $line];
    }
}

if (!$items) {
    header('Location: cart.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $addressMode = $_POST['address_mode'] ?? 'account';
    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $wardValue = '';
    $districtValue = '';
    $cityValue = '';

    if ($addressMode === 'new') {
        $receiver = trim($_POST['receiver_name'] ?? '');
        $receiverPhone = trim($_POST['receiver_phone'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $ward = trim($_POST['ward'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $city = trim($_POST['city'] ?? '');
        if (!$receiver || !$receiverPhone || !$street || !$ward || !$district || !$city) {
            $error = 'Vui lòng nhập đủ địa chỉ giao hàng mới.';
        }
        $shippingAddress = "$street, $ward, $district, $city";
        $wardValue = $ward;
        $districtValue = $district;
        $cityValue = $city;
        $name = $receiver;
        $phone = $receiverPhone;
    } else {
        $shippingAddress = trim($user['address']);
    }

    if (!$error && !$name) {
        $error = 'Họ tên không được bỏ trống.';
    }
    if (!$error && !$phone) {
        $error = 'Số điện thoại không được bỏ trống.';
    }

    if (!$error) {
        $conn->begin_transaction();
        try {
            $orderCode = 'DH' . date('YmdHis') . random_int(100, 999);
            $paymentStatus = $paymentMethod === 'online' ? 'da_thanh_toan' : 'chua_thanh_toan';
            $orderStmt = $conn->prepare("INSERT INTO don_hang (ma_don, id, dia_chi_giao, phuong, quan, thanh_pho, tong_tien, trang_thai_tt) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $orderStmt->bind_param('sissssds', $orderCode, $userId, $shippingAddress, $wardValue, $districtValue, $cityValue, $total, $paymentStatus);
            $orderStmt->execute();

            $itemStmt = $conn->prepare("INSERT INTO chi_tiet_don_hang (ma_don, ma_sp, so_luong, gia_ban) VALUES (?, ?, ?, ?)");
            foreach ($items as $it) {
                $p = $it['product'];
                $pid = $p['ma_sp'];
                $qty = (int)$it['qty'];
                $price = (float)$p['gia_ban'];
                $itemStmt->bind_param('ssid', $orderCode, $pid, $qty, $price);
                $itemStmt->execute();
            }
            $conn->commit();
            $_SESSION['cart'] = [];
            header('Location: order_history.php?placed=' . urlencode($orderCode));
            exit();
        } catch (Throwable $e) {
            $conn->rollback();
            $error = 'Không thể tạo đơn hàng. Vui lòng thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh toán</title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/giohang.css">
    <link rel="stylesheet" href="../css/thanhtoan.css">
    <link rel="stylesheet" href="../css/styleUser.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        .wrap { max-width: 1100px; margin: 140px auto 20px; display: grid; grid-template-columns: 1.2fr 1fr; gap: 16px; padding: 0 16px; }
        .box { background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 14px; }
        label { display: block; margin-top: 10px; font-weight: 600; }
        input, select { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ddd; border-radius: 7px; }
        .new-address { margin-top: 10px; padding-top: 8px; border-top: 1px dashed #ddd; }
        .item { display: grid; grid-template-columns: 50px 1fr auto; gap: 10px; align-items: center; margin-bottom: 10px; }
        .item img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .bank-info { padding: 10px; margin-top: 10px; background: #fff6e8; border: 1px solid #f2d39a; border-radius: 8px; }
        .btn { margin-top: 12px; width: 100%; border: 0; padding: 10px; border-radius: 8px; background: #f59cb7; color: #fff; font-weight: 700; cursor: pointer; }
        .err { color: #b22; margin-top: 10px; }
    </style>
    <script>
        function toggleNewAddress() {
            const checked = document.getElementById('address_new').checked;
            document.getElementById('new_address_box').style.display = checked ? 'block' : 'none';
        }
        function toggleBankInfo() {
            const payment = document.getElementById('payment_method').value;
            document.getElementById('bank_info').style.display = payment === 'bank' ? 'block' : 'none';
        }
        window.addEventListener('DOMContentLoaded', function() {
            toggleNewAddress();
            toggleBankInfo();
        });
    </script>
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
    <div class="box">
        <h3>Thông tin thanh toán</h3>
        <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
        <form method="post">
            <label>Họ và tên</label>
            <input type="text" name="full_name" value="<?= h($user['full_name']) ?>" required>

            <label>Số điện thoại</label>
            <input type="text" name="phone" value="<?= h($user['phone']) ?>" required>

            <label>Địa chỉ giao hàng</label>
            <div>
                <input type="radio" id="address_account" name="address_mode" value="account" checked onchange="toggleNewAddress()">
                <label for="address_account">Dùng địa chỉ từ tài khoản: <?= h($user['address']) ?></label>
            </div>
            <div>
                <input type="radio" id="address_new" name="address_mode" value="new" onchange="toggleNewAddress()">
                <label for="address_new">Nhập địa chỉ giao hàng mới</label>
            </div>

            <div id="new_address_box" class="new-address">
                <label>Họ tên người nhận</label><input type="text" name="receiver_name">
                <label>SĐT người nhận</label><input type="text" name="receiver_phone">
                <label>Số nhà, đường</label><input type="text" name="street">
                <label>Phường/Xã</label><input type="text" name="ward">
                <label>Quận/Huyện</label><input type="text" name="district">
                <label>Tỉnh/Thành phố</label><input type="text" name="city">
            </div>

            <label>Phương thức thanh toán</label>
            <select name="payment_method" id="payment_method" onchange="toggleBankInfo()">
                <option value="cod">Tiền mặt (COD)</option>
                <option value="bank">Chuyển khoản</option>
                <option value="online">Thanh toán trực tuyến (chưa xử lý tiếp)</option>
            </select>

            <div id="bank_info" class="bank-info">
                Chủ TK: Florentino Shop<br>
                STK: 123456789 - Ngân hàng ACB<br>
                Nội dung CK: DH_[Mã đơn]
            </div>

            <button class="btn" type="submit">Xác nhận đặt hàng</button>
        </form>
    </div>
    <div class="box">
        <h3>Tóm tắt đơn hàng</h3>
        <?php foreach ($items as $it): ?>
            <div class="item">
                <img src="<?= h($it['product']['hinh_anh'] ?: '../Image/sp.jpg') ?>" alt="">
                <div><?= h($it['product']['ten_sp']) ?> x <?= (int)$it['qty'] ?></div>
                <div><?= format_vnd((float)$it['line_total']) ?></div>
            </div>
        <?php endforeach; ?>
        <hr>
        <h4>Tổng thanh toán: <?= format_vnd($total) ?></h4>
    </div>
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
