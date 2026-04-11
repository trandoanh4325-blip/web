<?php
require_once __DIR__ . '/../includes/shop_helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
ensure_logged_in();

$action = $_GET['action'] ?? '';
$id = trim($_GET['id'] ?? '');
$cart = get_cart();

if (($action === 'add' || $action === 'inc') && $id !== '') {

    // 🔥 lấy số lượng từ URL
    $qty = ($action === 'inc') ? 1 : (int)($_GET['qty'] ?? 1);
    $qty = max(1, $qty);

    // Lấy tồn kho
    $stmt = $conn->prepare("SELECT so_luong_ton FROM san_pham WHERE ma_sp = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $stock = (int)($row['so_luong_ton'] ?? 0);

    // 🔥 nếu chưa có trong giỏ
    if (!isset($cart[$id])) {
        $cart[$id] = 0;
    }

    // 🔥 FIX CHUẨN
    $newQty = $cart[$id] + $qty;

    if ($newQty > $stock) {
        $cart[$id] = $stock;
        echo "<script>alert('Vượt quá số lượng tồn kho!');</script>";
    } else {
        $cart[$id] = $newQty;
    }

    set_cart($cart);
    header('Location: cart.php');
    exit();
}

// ================== GIẢM SỐ LƯỢNG ==================
if ($action === 'dec' && $id !== '') {

    if (isset($cart[$id])) {

        // ❗ Không cho nhỏ hơn 1
        if ($cart[$id] > 1) {
            $cart[$id]--;
        } else {
            // nếu =1 thì xóa luôn
            unset($cart[$id]);
        }
    }

    set_cart($cart);
    header('Location: cart.php');
    exit();
}

// ================== XÓA ==================
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
            $stmt = $conn->prepare("SELECT so_luong_ton FROM san_pham WHERE ma_sp = ?");
$stmt->bind_param("s", $pid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$stock = (int)$row['so_luong_ton'];

$cart[$pid] = min($qty, $stock);
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
    $sql = "SELECT ma_sp, ten_sp, gia_ban, hinh_anh, so_luong_ton FROM san_pham WHERE ma_sp IN ($placeholders) AND hien_trang = 'hien_thi'";
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
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/giohang.css">
    <link rel="stylesheet" href="../css/thanhtoan.css">
    <link rel="stylesheet" href="../css/styleUser.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
      html { text-decoration: none; }
    </style>
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
  <form method="post" action="checkout.php">
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
        <?php $imgPath = !empty($p['hinh_anh']) ? '../ImageSanPham/' . $p['hinh_anh'] : '../ImageSanPham/sp.jpg'; ?>
<td><img src="<?= h($imgPath) ?>" alt="<?= h($p['ten_sp']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;"></td>
        <td><?= format_vnd((float)$p['gia_ban']) ?></td>
        <td style="white-space: nowrap;">
          <a class="btn btn-secondary" style="padding:6px 10px; display:inline-block; text-decoration:none;" href="cart.php?action=dec&id=<?= urlencode($p['ma_sp']) ?>">-</a>
          <input class="qty-input" data-stock="<?= (int)$p['so_luong_ton'] ?>" name="qty[<?= h($p['ma_sp']) ?>]" value="<?= (int)$item['qty'] ?>" min="1" style="width:70px; text-align:center;">
          <a class="btn btn-secondary" style="padding:6px 10px; display:inline-block; text-decoration:none;" href="cart.php?action=inc&id=<?= urlencode($p['ma_sp']) ?>">+</a>
        </td>
        <td><?= format_vnd((float)$item['line_total']) ?></td>
        <td><a class="delete-btn" style="text-decoration:none;" href="cart.php?action=remove&id=<?= urlencode($p['ma_sp']) ?>">✖</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="total">Tổng: <?= format_vnd($total) ?></div>

<div class="cart-actions">
    <a href="User.php" class="btn-back">← Quay lại mua hàng</a>
    <button type="submit" class="btn-thanhtoan" onclick="return validateCartCheckout();">Thanh toán</button>
</div>
<style id="y0p3kc">
.cart-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 20px;
}

.btn-back {
    background: #e0e0e0;
    color: #333;
    padding: 10px 18px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: 0.2s;
}

.btn-back:hover {
    background: #c7c7c7;
}

.btn-thanhtoan {
    background: #28a745;
    color: #fff;
    padding: 10px 18px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.btn-thanhtoan:hover {
    background: #218838;
}
</style>
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
<script>
function validateCartCheckout() {
    let inputs = document.querySelectorAll('.qty-input');
    for(let i = 0; i < inputs.length; i++) {
        let qty = parseInt(inputs[i].value);
        let stock = parseInt(inputs[i].getAttribute('data-stock'));
        
        if (qty > stock) {
            // Thay alert bằng hàm mới, truyền thêm ô input để nó tự focus lại
            showCustomAlert('Vượt quá số lượng tồn kho! (Chỉ còn ' + stock + ' sản phẩm)', inputs[i]);
            return false;
        }
        if (qty < 1 || isNaN(qty)) {
            showCustomAlert('Số lượng không hợp lệ!', inputs[i]);
            return false;
        }
    }
    return true; 
}
</script>
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