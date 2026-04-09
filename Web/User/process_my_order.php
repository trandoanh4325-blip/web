<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db_connect.php';

function json_ok($data): void
{
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_err(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    json_err('Vui lòng đăng nhập.', 401);
}

$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_detail':
            $maDon = trim($_GET['ma_don'] ?? '');
            if ($maDon === '') {
                json_err('Thiếu mã đơn hàng.');
            }
            $stmt = $conn->prepare(
                'SELECT ma_don, ngay_dat, hoat_dong, trang_thai_tt, dia_chi_giao, phuong, quan, thanh_pho, ly_do_huy, tong_tien
                 FROM don_hang WHERE ma_don = ? AND username = ?'
            );
            $stmt->bind_param('si', $maDon, $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) {
                json_err('Không tìm thấy đơn hàng.');
            }
            json_ok($row);
            break;

        case 'get_items':
            $maDon = trim($_GET['ma_don'] ?? '');
            if ($maDon === '') {
                json_ok([]);
            }
            $chk = $conn->prepare('SELECT 1 FROM don_hang WHERE ma_don = ? AND username = ?');
            $chk->bind_param('si', $maDon, $userId);
            $chk->execute();
            if ($chk->get_result()->num_rows === 0) {
                json_err('Không tìm thấy đơn hàng.');
            }
            $stmt = $conn->prepare(
                'SELECT ct.ma_sp, ct.so_luong, ct.gia_ban, sp.ten_sp, sp.hinh_anh
                 FROM chi_tiet_don_hang ct
                 LEFT JOIN san_pham sp ON ct.ma_sp = sp.ma_sp
                 WHERE ct.ma_don = ?'
            );
            $stmt->bind_param('s', $maDon);
            $stmt->execute();
            $items = [];
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $items[] = $r;
            }
            json_ok($items);
            break;

        case 'cancel_order':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_err('Phương thức không hợp lệ.', 405);
            }
            $b = json_decode(file_get_contents('php://input'), true) ?? [];
            $maDon = trim($b['ma_don'] ?? '');
            $lyDo = trim($b['ly_do_huy'] ?? '');
            if ($maDon === '') {
                json_err('Thiếu mã đơn hàng.');
            }
            $stmt = $conn->prepare('SELECT hoat_dong, trang_thai_tt FROM don_hang WHERE ma_don = ? AND username = ?');
            $stmt->bind_param('si', $maDon, $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) {
                json_err('Không tìm thấy đơn hàng.');
            }
            if ($row['hoat_dong'] !== 'dang_cho') {
                json_err('Chỉ có thể hủy đơn đang chờ xác nhận từ cửa hàng.');
            }
            if ($lyDo === '') {
                $lyDo = 'Khách hàng hủy đơn';
            }
            $newTt = $row['trang_thai_tt'] === 'da_thanh_toan' ? 'hoan_tien' : $row['trang_thai_tt'];
            $upd = $conn->prepare(
                'UPDATE don_hang SET hoat_dong = \'da_huy\', trang_thai_tt = ?, ly_do_huy = ? WHERE ma_don = ? AND username = ? AND hoat_dong = \'dang_cho\''
            );
            $upd->bind_param('sssi', $newTt, $lyDo, $maDon, $userId);
            $upd->execute();
            if ($upd->affected_rows === 0) {
                json_err('Không thể hủy đơn. Vui lòng tải lại trang.');
            }
            json_ok(['cancelled' => true]);
            break;

        default:
            json_err('Thao tác không hợp lệ.');
    }
} catch (Throwable $e) {
    json_err('Lỗi máy chủ: ' . $e->getMessage(), 500);
}
