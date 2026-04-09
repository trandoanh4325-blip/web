<?php
// =============================================================
// Admin/process_donhang.php  –  API Quản lý Đơn Hàng
// =============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shop_hoa_db');

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function ok($data = null, $msg = '') {
    echo json_encode(['ok' => true, 'message' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function err($msg = 'Lỗi hệ thống', $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        switch ($action) {
            
            // ── 1. Lấy toàn bộ đơn hàng ──
            case 'get_all':
                // ĐÃ SỬA: d.username = u.id
                $st = db()->query(
                    "SELECT d.ma_don, u.username, d.ngay_dat, d.hoat_dong,
                            d.trang_thai_tt, d.dia_chi_giao, d.phuong, d.quan,
                            d.thanh_pho, d.ly_do_huy, d.tong_tien
                     FROM don_hang d
                     JOIN users u ON d.username = u.id
                     ORDER BY d.ngay_dat DESC"
                );
                ok($st->fetchAll());

            // ── 2. Chi tiết 1 đơn hàng (kèm SP) ──
            case 'get_detail':
                $maDon = trim($_GET['ma_don'] ?? '');
                if ($maDon === '') err('Thiếu mã đơn');

                // ĐÃ SỬA: d.username = u.id
                $st = db()->prepare(
                    "SELECT d.ma_don, u.username, d.ngay_dat, d.hoat_dong,
                            d.trang_thai_tt, d.dia_chi_giao, d.phuong, d.quan,
                            d.thanh_pho, d.ly_do_huy, d.tong_tien
                     FROM don_hang d
                     JOIN users u ON d.username = u.id
                     WHERE d.ma_don = ?"
                );
                $st->execute([$maDon]);
                $don = $st->fetch();
                if (!$don) err('Không tìm thấy đơn', 404);

                // Lấy chi tiết sản phẩm
                $st2 = db()->prepare(
                    "SELECT c.ma_sp, s.ten_sp, c.so_luong, c.gia_ban,
                            (c.so_luong * c.gia_ban) AS thanh_tien
                     FROM chi_tiet_don_hang c
                     JOIN san_pham s ON c.ma_sp = s.ma_sp
                     WHERE c.ma_don = ?"
                );
                $st2->execute([$maDon]);
                $don['chi_tiet'] = $st2->fetchAll();

                ok($don);

            // ── 3. Tìm kiếm đơn hàng ──
            case 'search':
                $tu    = trim($_GET['tu_ngay'] ?? '');
                $den   = trim($_GET['den_ngay'] ?? '');
                $hd    = trim($_GET['hoat_dong'] ?? '');
                $user  = trim($_GET['username'] ?? '');
                $ph    = trim($_GET['phuong'] ?? '');
                $tp    = trim($_GET['thanh_pho'] ?? '');

                $w = ["1=1"];
                $p = [];

                if ($tu !== '') {
                    $w[] = "DATE(d.ngay_dat) >= :tu";
                    $p[':tu'] = $tu;
                }
                if ($den !== '') {
                    $w[] = "DATE(d.ngay_dat) <= :den";
                    $p[':den'] = $den;
                }
                if ($hd !== '' && $hd !== 'all') {
                    $w[] = "d.hoat_dong = :hd";
                    $p[':hd'] = $hd;
                }
                if ($user !== '') {
                    $w[] = "u.username LIKE :u";
                    $p[':u'] = "%$user%";
                }
                if ($ph !== '') {
                    $w[] = "d.phuong LIKE :ph";
                    $p[':ph'] = "%$ph%";
                }
                if ($tp !== '') {
                    $w[] = "d.thanh_pho LIKE :tp";
                    $p[':tp'] = "%$tp%";
                }

                $whereSql = implode(' AND ', $w);
                
                // ĐÃ SỬA: d.username = u.id
                $sql = "SELECT d.ma_don, u.username, d.ngay_dat, d.hoat_dong,
                               d.trang_thai_tt, d.dia_chi_giao, d.phuong, d.quan,
                               d.thanh_pho, d.ly_do_huy, d.tong_tien
                        FROM don_hang d
                        JOIN users u ON d.username = u.id
                        WHERE $whereSql
                        ORDER BY d.ngay_dat DESC";

                $st = db()->prepare($sql);
                $st->execute($p);
                ok($st->fetchAll());

            default: err('Action GET không hợp lệ');
        }
    }
    elseif ($method === 'POST') {
        switch ($action) {
            
            // ── Cập nhật trạng thái (WHERE ma_don) ──
            case 'update_status':
                $b        = json_decode(file_get_contents('php://input'), true) ?? [];
                $maDon    = trim($b['ma_don']    ?? '');
                $hoatDong = trim($b['hoat_dong'] ?? '');
                $lyDo     = trim($b['ly_do_huy'] ?? '');

                if ($maDon === '' || $hoatDong === '') err('Dữ liệu không hợp lệ');

                $trangThaiTT = match($hoatDong) {
                    'dang_cho'                                => 'chua_xac_nhan',
                    'dang_chuan_bi','cho_lay_hang',
                    'dang_van_chuyen'                         => 'da_xac_nhan',
                    'giao_thanh_cong'                         => 'giao_thanh_cong',
                    'da_huy'                                  => 'da_huy',
                    default                                   => 'chua_xac_nhan',
                };

                $st = db()->prepare(
                    "UPDATE don_hang
                     SET hoat_dong = :hd, trang_thai_tt = :tt, ly_do_huy = :ly
                     WHERE ma_don = :ma_don"
                );
                $st->execute([
                    ':hd'     => $hoatDong,
                    ':tt'     => $trangThaiTT,
                    ':ly'     => $lyDo,
                    ':ma_don' => $maDon
                ]);

                if ($st->rowCount() === 0) {
                    err('Không tìm thấy đơn hàng hoặc trạng thái không đổi');
                }
                ok(null, 'Đã cập nhật trạng thái');

            default: err('Action POST không hợp lệ');
        }
    }
} catch (Exception $e) {
    err('Lỗi SQL: ' . $e->getMessage(), 500);
}