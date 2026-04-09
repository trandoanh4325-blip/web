<?php
require_once __DIR__ . '/../includes/shop_helpers.php';
ensure_logged_in();

$userId = (int)$_SESSION['user_id'];
$placed = trim($_GET['placed'] ?? '');
$cancelled = isset($_GET['cancelled']);

$hoatDongLabels = [
    'dang_cho' => 'Đang chờ',
    'dang_chuan_bi' => 'Đang chuẩn bị',
    'cho_lay_hang' => 'Chờ lấy hàng',
    'dang_van_chuyen' => 'Đang vận chuyển',
    'giao_thanh_cong' => 'Giao thành công',
    'da_huy' => 'Đã hủy',
];
$ttLabels = [
    'chua_thanh_toan' => 'Chưa thanh toán',
    'da_thanh_toan' => 'Đã thanh toán',
    'hoan_tien' => 'Hoàn tiền',
];

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
        .wrap { width: 100%; max-width: 1000px; margin: 20px auto ; padding: 0 16px; }
        .order { background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 14px; margin-bottom: 12px; }
        .row { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .status { text-transform: uppercase; font-size: 12px; padding: 4px 8px; border-radius: 999px; background: #f4ecff; color: #6a4cbf; }
        .ok { background: #e9f9ef; color: #1f8a4d; padding: 9px 10px; border-radius: 8px; margin-bottom: 14px; }
        .order-actions { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .btn-detail {
            padding: 8px 14px; border-radius: 8px; border: none; cursor: pointer;
            background: #007bff; color: #fff; font-size: 14px; font-weight: 500;
        }
        .btn-detail:hover { background: #0069d9; }
        .hint-cancel { font-size: 12px; color: #666; }
        /* Modal */
        #orderDetailModal {
            display: none; position: fixed; inset: 0; z-index: 10000;
            background: rgba(15, 23, 42, 0.45); align-items: center; justify-content: center;
            padding: 16px; opacity: 0; transition: opacity 0.2s ease;
        }
        #orderDetailModal.open { opacity: 1; }
        .order-modal-box {
            background: #fff; border-radius: 12px; max-width: 720px; width: 100%; max-height: 90vh;
            overflow: hidden; display: flex; flex-direction: column;
            box-shadow: 0 20px 50px rgba(0,0,0,.2); transform: translateY(8px);
            transition: transform 0.2s ease;
        }
        #orderDetailModal.open .order-modal-box { transform: translateY(0); }
        .order-modal-head {
            padding: 14px 18px; border-bottom: 1px solid #eee; display: flex;
            justify-content: space-between; align-items: center;
        }
        .order-modal-head h3 { margin: 0; font-size: 1.1rem; color: #1e3a5f; }
        .order-modal-body { padding: 16px 18px; overflow-y: auto; flex: 1; }
        .order-modal-foot {
            padding: 12px 18px; border-top: 1px solid #eee; display: flex; gap: 10px;
            justify-content: flex-end; flex-wrap: wrap;
        }
        .order-detail-grid { display: grid; gap: 12px; grid-template-columns: 1fr 1fr; }
        @media (max-width: 640px) { .order-detail-grid { grid-template-columns: 1fr; } }
        .order-detail-card {
            border: 1px solid #e8ecf4; border-radius: 10px; padding: 12px 14px; background: #fafbff;
        }
        .order-detail-card h4 { margin: 0 0 10px; font-size: 15px; color: #007bff; }
        .order-detail-card p { margin: 6px 0; font-size: 14px; line-height: 1.45; }
        .order-detail-card .sum { color: #c0392b; font-weight: 700; }
        .order-detail-card .ly-do { color: #c0392b; }
        .order-detail-card .muted, .muted { color: #888; font-size: 13px; }
        .center { text-align: center; }
        .order-items-table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 8px; }
        .order-items-table th, .order-items-table td { padding: 8px 6px; border-bottom: 1px solid #eee; text-align: left; }
        .order-items-table th { background: #f1f5f9; }
        .td-product { display: flex; align-items: center; gap: 10px; }
        .order-item-img { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; }
        .btn-modal {
            padding: 9px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 500;
        }
        .btn-modal.btn-close { background: #e9ecef; color: #333; }
        .btn-modal.btn-close:hover { background: #dee2e6; }
        .btn-modal.btn-danger-outline { background: #fff; color: #c0392b; border: 1px solid #c0392b; }
        .btn-modal.btn-danger-outline:hover { background: #fff5f5; }
        .cancel-panel {
            margin-top: 14px; padding: 12px; border-radius: 10px; background: #fff8f8;
            border: 1px solid #f5c6cb;
        }
        .cancel-panel label { display: block; font-size: 13px; margin-bottom: 6px; font-weight: 600; color: #721c24; }
        .cancel-panel textarea {
            width: 100%; min-height: 72px; padding: 8px; border-radius: 8px; border: 1px solid #ddd;
            font-family: inherit; font-size: 14px; box-sizing: border-box;
        }
        .cancel-panel-actions { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-cancel-confirm { background: #c0392b; color: #fff; }
        .btn-cancel-confirm:hover { background: #a93226; }
        .order-toast-wrap { position: fixed; bottom: 24px; right: 24px; z-index: 10001; display: flex; flex-direction: column; gap: 8px; }
        .order-toast {
            padding: 12px 16px; border-radius: 8px; background: #1e3a5f; color: #fff;
            font-size: 14px; box-shadow: 0 4px 16px rgba(0,0,0,.2); animation: orderToastIn .25s ease;
        }
        .order-toast.err { background: #c0392b; }
        .order-toast.hide { opacity: 0; transform: translateY(6px); transition: .3s ease; }
        @keyframes orderToastIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
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
    <?php if ($cancelled): ?><div class="ok">Đơn hàng đã được hủy.</div><?php endif; ?>

    <?php if (!$orders): ?>
        <p>Bạn chưa có đơn hàng nào.</p>
    <?php else: ?>
        <?php foreach ($orders as $o): ?>
            <div class="order">
                <div class="row">
                    <strong>Đơn #<?= h($o['ma_don']) ?></strong>
                    <span class="status"><?= h($hoatDongLabels[$o['hoat_dong']] ?? $o['hoat_dong']) ?></span>
                </div>
                <div>Địa chỉ giao: <?= h($o['dia_chi_giao']) ?></div>
                <div>Khu vực: <?= h(trim($o['phuong'] . ' - ' . $o['quan'] . ' - ' . $o['thanh_pho'], ' -')) ?></div>
                <div>Thanh toán: <?= h($ttLabels[$o['trang_thai_tt']] ?? $o['trang_thai_tt']) ?></div>
                <div>Tổng tiền: <strong><?= format_vnd((float)$o['tong_tien']) ?></strong></div>
                <div>Thời gian: <?= h($o['ngay_dat']) ?></div>
                <div class="order-actions">
                    <button type="button" class="btn-detail js-order-detail" data-ma-don="<?= h($o['ma_don']) ?>">Xem chi tiết</button>
                    <?php if ($o['hoat_dong'] === 'dang_cho'): ?>
                        <span class="hint-cancel">Bạn có thể hủy đơn trong màn hình chi tiết.</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="orderDetailModal" aria-hidden="true">
    <div class="order-modal-box" role="dialog" aria-labelledby="orderModalTitle">
        <div class="order-modal-head">
            <h3 id="orderModalTitle">Chi tiết đơn hàng</h3>
            <button type="button" class="btn-modal btn-close" data-close-modal aria-label="Đóng">✕</button>
        </div>
        <div class="order-modal-body" id="orderModalBody"></div>
        <div id="cancelOrderPanel" class="cancel-panel" hidden>
            <label for="cancelReasonInput">Lý do hủy (tuỳ chọn)</label>
            <textarea id="cancelReasonInput" placeholder="Ví dụ: Đổi ý, đặt nhầm…"></textarea>
            <div class="cancel-panel-actions">
                <button type="button" class="btn-modal btn-close" id="btnCloseCancelPanel">Huỷ bỏ</button>
                <button type="button" class="btn-modal btn-cancel-confirm" id="btnConfirmCancel">Xác nhận hủy đơn</button>
            </div>
        </div>
        <div class="order-modal-foot" id="orderModalFooter"></div>
    </div>
</div>

<script src="../js/order_history.js"></script>

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
