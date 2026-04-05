<?php
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
function ok($data) { echo json_encode(['ok'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE); exit; }
function err($msg,$code=400) { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$msg],JSON_UNESCAPED_UNICODE); exit; }

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            $sql = "SELECT dh.*, u.full_name AS ten_khach_hang 
                    FROM don_hang dh 
                    LEFT JOIN users u ON dh.id_khach_hang = u.id 
                    ORDER BY dh.ngay_dat DESC";
            ok(db()->query($sql)->fetchAll());
            break;

        case 'search':
            $where = []; $params = [];
            if (!empty($_GET['tu_ngay'])) { $where[] = 'dh.ngay_dat >= :tu'; $params[':tu'] = $_GET['tu_ngay']; }
            if (!empty($_GET['den_ngay'])) { $where[] = 'dh.ngay_dat <= :den'; $params[':den'] = $_GET['den_ngay']; }
            if (!empty($_GET['trang_thai'])) { $where[] = 'dh.hoat_dong = :tt'; $params[':tt'] = $_GET['trang_thai']; }
            if (!empty($_GET['phuong'])) { $where[] = 'dh.phuong LIKE :ph'; $params[':ph'] = '%' . $_GET['phuong'] . '%'; }
            if (!empty($_GET['thanh_pho'])) { $where[] = 'dh.thanh_pho LIKE :tp'; $params[':tp'] = '%' . $_GET['thanh_pho'] . '%'; }
            
            $sql = "SELECT dh.*, u.full_name AS ten_khach_hang FROM don_hang dh LEFT JOIN users u ON dh.id_khach_hang = u.id";
            if ($where) { $sql .= " WHERE " . implode(" AND ", $where); }
            $st = db()->prepare($sql); 
            $st->execute($params); 
            ok($st->fetchAll());
            break;

        case 'get_detail':
            $id = (int)($_GET['id'] ?? 0); 
            $st = db()->prepare("SELECT dh.*, u.full_name AS ten_khach_hang FROM don_hang dh LEFT JOIN users u ON dh.id_khach_hang = u.id WHERE dh.id = :id");
            $st->execute([':id' => $id]);
            $row = $st->fetch(); 
            if (!$row) err('Không tìm thấy đơn hàng');
            ok($row);
            break;

        case 'get_items':
            // Lấy danh sách sản phẩm trong 1 đơn hàng (JOIN với bảng san_pham)
            $id = (int)($_GET['id'] ?? 0);
            $st = db()->prepare("
                SELECT ct.*, sp.ten_sp, sp.ma_sp, sp.hinh_anh
                FROM chi_tiet_don_hang ct
                LEFT JOIN san_pham sp ON ct.id_san_pham = sp.id
                WHERE ct.id_don_hang = :id
            ");
            $st->execute([':id' => $id]);
            ok($st->fetchAll());
            break;

        case 'update_status':
            $b = json_decode(file_get_contents('php://input'), true);
            $st = db()->prepare("UPDATE don_hang SET hoat_dong = :ht, ly_do_huy = :ly WHERE id = :id");
            $st->execute([':ht' => $b['hoat_dong'], ':ly' => $b['ly_do_huy'] ?? '', ':id' => $b['id']]);
            ok(['updated' => true]);
            break;
    }
} catch(Exception $e){err($e->getMessage(),500);}