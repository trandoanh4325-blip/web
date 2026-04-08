<?php
// =============================================================
// Admin/process_donhang.php  –  API Quản lý Đơn Hàng
// Cấu trúc DB thực tế:
//   don_hang  : ma_don(PK), id(FK→users.id), ngay_dat, hoat_dong,
//               trang_thai_tt, dia_chi_giao, phuong, quan,
//               thanh_pho, ly_do_huy, tong_tien, created_at
//   users     : id(PK), username, full_name, ...
//   → JOIN don_hang.id = users.id để lấy username
//   → Dùng ma_don làm định danh duy nhất cho đơn hàng
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

function ok($data)            { echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function err($msg, $code=400) { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$msg], JSON_UNESCAPED_UNICODE); exit; }

$action = $_GET['action'] ?? '';

// ── Cột SELECT dùng chung: JOIN users để lấy username ──
$SELECT = "
    dh.ma_don, dh.id AS id_user,
    u.username,
    dh.ngay_dat, dh.hoat_dong, dh.trang_thai_tt,
    dh.dia_chi_giao, dh.phuong, dh.thanh_pho,
    dh.ly_do_huy, dh.tong_tien, dh.created_at
";

$FROM_JOIN = "
    FROM don_hang dh
    LEFT JOIN users u ON dh.id = u.id
";

try {
    switch ($action) {

        // ── Lấy tất cả đơn hàng ──
        case 'get_all':
            $sql = "SELECT $SELECT $FROM_JOIN ORDER BY dh.ngay_dat DESC";
            ok(db()->query($sql)->fetchAll());
            break;

        // ── Tìm kiếm ──
        case 'search':
            $where = []; $params = [];
            if (!empty($_GET['tu_ngay']))    { $where[] = 'dh.ngay_dat >= :tu';        $params[':tu']  = $_GET['tu_ngay']; }
            if (!empty($_GET['den_ngay']))   { $where[] = 'dh.ngay_dat <= :den';       $params[':den'] = $_GET['den_ngay']; }
            if (!empty($_GET['trang_thai'])) { $where[] = 'dh.hoat_dong = :tt';        $params[':tt']  = $_GET['trang_thai']; }
            if (!empty($_GET['username']))   { $where[] = 'u.username LIKE :un';       $params[':un']  = '%'.$_GET['username'].'%'; }
            if (!empty($_GET['phuong']))     { $where[] = 'dh.phuong LIKE :ph';        $params[':ph']  = '%'.$_GET['phuong'].'%'; }
            if (!empty($_GET['thanh_pho']))  { $where[] = 'dh.thanh_pho LIKE :tp';    $params[':tp']  = '%'.$_GET['thanh_pho'].'%'; }

            $sql = "SELECT $SELECT $FROM_JOIN";
            if ($where) $sql .= " WHERE " . implode(" AND ", $where);
            $sql .= " ORDER BY dh.ngay_dat DESC";

            $st = db()->prepare($sql);
            $st->execute($params);
            ok($st->fetchAll());
            break;

        // ── Chi tiết 1 đơn (WHERE ma_don) ──
        case 'get_detail':
            $maDon = trim($_GET['ma_don'] ?? '');
            if ($maDon === '') err('Thiếu mã đơn hàng');

            $st = db()->prepare("SELECT $SELECT $FROM_JOIN WHERE dh.ma_don = :ma_don");
            $st->execute([':ma_don' => $maDon]);
            $row = $st->fetch();
            if (!$row) err('Không tìm thấy đơn hàng');
            ok($row);
            break;

        // ── Sản phẩm trong đơn (JOIN san_pham theo ma_sp) ──
        case 'get_items':
            $maDon = trim($_GET['ma_don'] ?? '');
            if ($maDon === '') ok([]);

            $st = db()->prepare("
                SELECT ct.ma_don, ct.ma_sp, ct.so_luong, ct.gia_ban,
                       sp.ten_sp, sp.hinh_anh
                FROM   chi_tiet_don_hang ct
                LEFT JOIN san_pham sp ON ct.ma_sp = sp.ma_sp
                WHERE  ct.ma_don = :ma_don
            ");
            $st->execute([':ma_don' => $maDon]);
            ok($st->fetchAll());
            break;

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
                ':ma_don' => $maDon,
            ]);
            ok(['updated' => true]);
            break;

        default:
            err('Action không hợp lệ', 400);
    }

} catch (Exception $e) { err($e->getMessage(), 500); }
