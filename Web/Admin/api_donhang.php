<?php
// ============================================================
//  api_donhang.php  —  Đặt cùng thư mục donhang.html
//  Database: shop_hoa_db
// ============================================================
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
function ok($data)           { echo json_encode(['ok'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE); exit; }
function err($msg,$code=400) { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$msg],JSON_UNESCAPED_UNICODE); exit; }

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
    case 'get_all':
        ok(db()->query('SELECT * FROM v_don_hang')->fetchAll());
        break; // Dừng lại sau khi lấy tất cả

    case 'search':
        $where = []; 
        $params = [];
        
        if (!empty($_GET['tu_ngay'])) { 
            $where[] = 'ngay_dat >= :tu'; 
            $params[':tu'] = $_GET['tu_ngay']; 
        }
        if (!empty($_GET['den_ngay'])) { 
            $where[] = 'ngay_dat <= :den'; 
            $params[':den'] = $_GET['den_ngay']; 
        }
        if (!empty($_GET['trang_thai'])) { 
            $where[] = 'hoat_dong = :tt'; // Đổi từ trang_thai thành hoat_dong cho khớp SQL
            $params[':tt'] = $_GET['trang_thai']; 
        }
        if (!empty($_GET['phuong'])) { 
            $where[] = 'phuong LIKE :ph'; 
            $params[':ph'] = '%' . $_GET['phuong'] . '%'; 
        }
        
        $sql = 'SELECT * FROM v_don_hang';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $st = db()->prepare($sql); 
        $st->execute($params); 
        ok($st->fetchAll());
        break; // Dừng lại sau khi tìm kiếm

    case 'get_detail':
        $id = (int)($_GET['id'] ?? 0); 
        if (!$id) err('Thieu ID don hang');
        
        $st = db()->prepare('SELECT * FROM v_don_hang WHERE id = :id');
        $st->execute([':id' => $id]);
        $row = $st->fetch(); 
        
        if (!$row) err('Khong tim thay don hang', 404);
        ok($row);
        break; // Dừng lại sau khi lấy chi tiết

    case 'update_status':
        $b = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int)($b['id'] ?? 0);
        $ht = trim($b['hoat_dong'] ?? ''); // Trạng thái hoạt động (dang_cho, da_huy...)
        $tt = trim($b['trang_thai_tt'] ?? ''); // Trạng thái thanh toán
        
        // Mảng cho phép phải khớp 100% với ENUM trong SQL
        $allowedHT = ['dang_cho', 'dang_chuan_bi', 'cho_lay_hang', 'dang_van_chuyen', 'giao_thanh_cong', 'da_huy'];
        $allowedTT = ['chua_thanh_toan', 'da_thanh_toan', 'hoan_tien'];
        
        if (!$id) err('Thiếu ID để cập nhật');
        if ($ht && !in_array($ht, $allowedHT)) err('Hoat dong khong hop le');
        if ($tt && !in_array($tt, $allowedTT)) err('Thanh toan khong hop le');
        
        $sets = []; 
        $params = [':id' => $id];
        
        if ($ht) { $sets[] = 'hoat_dong = :ht'; $params[':ht'] = $ht; }
        if ($tt) { $sets[] = 'trang_thai_tt = :tt'; $params[':tt'] = $tt; }
        
        // Nếu là đã hủy, bắt buộc hoặc cho phép lưu lý do
        if ($ht === 'da_huy' && !empty($b['ly_do_huy'])) {
            $sets[] = 'ly_do_huy = :ly';
            $params[':ly'] = $b['ly_do_huy'];
        }
        
        if (!$sets) err('Khong co gi de cap nhat');
        
        $st = db()->prepare('UPDATE don_hang SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $st->execute($params); 
        ok(['updated' => $st->rowCount()]);
        break; // Dừng lại sau khi cập nhật

    default: 
        err('Khong ton tai', 404);
        break;
}
} catch(PDOException $e){err('DB: '.$e->getMessage(),500);}
  catch(Throwable $e){err('Server: '.$e->getMessage(),500);}
