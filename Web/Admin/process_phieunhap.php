<?php
// =============================================================
// Admin/process_phieunhap.php  –  REST API Quan ly Phieu Nhap
// ĐÃ LOẠI BỎ ID, SỬ DỤNG MÃ PHIẾU LÀM KHÓA CHÍNH
// =============================================================

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// =============================================================
// CAU HINH KET NOI MYSQL (Đã gộp trực tiếp vào file)
// =============================================================
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'shop_hoa_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối CSDL: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $pdo;
}

$resource = trim($_GET['resource'] ?? '');
$method   = $_SERVER['REQUEST_METHOD'];

$body = [];
if (in_array($method, ['POST','PUT','DELETE'], true)) {
    $raw = file_get_contents('php://input');
    if ($raw !== '' && $raw !== false) $body = json_decode($raw, true) ?? [];
}

match($resource) {
    'phieu-nhap'    => handlePhieuNhap($method, $body),
    'chi-tiet'      => handleChiTiet($method, $body),
    'hoan-thanh'    => handleHoanThanh($body),
    'tim-san-pham'  => handleTimSanPham(),
    default         => jsonResp(false, 'Resource không hợp lệ', null, 404),
};

// =============================================================
//  PHIEU NHAP
// =============================================================
function handlePhieuNhap(string $method, array $body): void
{
    $pdo = getDB();

    switch ($method) {
        case 'GET':
            $keyword = trim($_GET['q'] ?? '');
            $sql = "
                SELECT pn.*,
                       COUNT(ct.ma_sp)               AS so_dong,
                       IFNULL(SUM(ct.so_luong * ct.don_gia), 0) AS tong_tien
                FROM phieu_nhap pn
                LEFT JOIN chi_tiet_phieu_nhap ct ON ct.ma_phieu = pn.ma_phieu
            ";
            $params = [];
            if ($keyword !== '') {
                $sql .= " WHERE pn.ma_phieu LIKE ? OR EXISTS (
                    SELECT 1 FROM chi_tiet_phieu_nhap c2
                    JOIN san_pham sp ON sp.ma_sp = c2.ma_sp
                    WHERE c2.ma_phieu = pn.ma_phieu AND sp.ten_sp LIKE ?
                )";
                $params = ["%$keyword%", "%$keyword%"];
            }
            $sql .= " GROUP BY pn.ma_phieu ORDER BY pn.ma_phieu DESC";
            
            $st = $pdo->prepare($sql);
            $st->execute($params);
            jsonResp(true, 'OK', $st->fetchAll());
            break;

        case 'POST':
            $ngayNhap = trim($body['ngay_nhap'] ?? '');
            $ghiChu   = trim($body['ghi_chu']   ?? '');
            if ($ngayNhap === '') jsonResp(false, 'Ngày nhập không được để trống!');

            // Lấy số đuôi lớn nhất từ chuỗi PNxxx
            $maxNum = (int) $pdo->query("SELECT IFNULL(MAX(CAST(SUBSTRING(ma_phieu, 3) AS UNSIGNED)), 0) FROM phieu_nhap")->fetchColumn();
            $maPhieu = 'PN' . str_pad((string)($maxNum + 1), 3, '0', STR_PAD_LEFT);

            $st = $pdo->prepare('INSERT INTO phieu_nhap (ma_phieu, ngay_nhap, ghi_chu) VALUES (?,?,?)');
            $st->execute([$maPhieu, $ngayNhap, $ghiChu ?: null]);

            jsonResp(true, "Đã tạo phiếu: $maPhieu", null, 201, ['ma_phieu' => $maPhieu]);
            break;

        case 'PUT':
            $maPhieu  = trim($body['ma_phieu'] ?? '');
            $ngayNhap = trim($body['ngay_nhap'] ?? '');
            if ($maPhieu === '' || $ngayNhap === '') jsonResp(false, 'Thiếu mã phiếu hoặc ngày nhập!');

            $ck = $pdo->prepare('SELECT trang_thai FROM phieu_nhap WHERE ma_phieu=?');
            $ck->execute([$maPhieu]);
            $row = $ck->fetch();
            if (!$row) jsonResp(false, 'Không tìm thấy phiếu nhập!');
            if ($row['trang_thai'] === 'hoan_thanh') jsonResp(false, 'Phiếu đã hoàn thành, không thể sửa!');

            $st = $pdo->prepare('UPDATE phieu_nhap SET ngay_nhap=?, ghi_chu=? WHERE ma_phieu=?');
            $st->execute([$ngayNhap, trim($body['ghi_chu'] ?? '') ?: null, $maPhieu]);
            jsonResp(true, 'Cập nhật phiếu thành công!');
            break;

        case 'DELETE':
            $maPhieu = trim($body['ma_phieu'] ?? '');
            if ($maPhieu === '') jsonResp(false, 'Thiếu mã phiếu!');

            $ck = $pdo->prepare('SELECT trang_thai FROM phieu_nhap WHERE ma_phieu=?');
            $ck->execute([$maPhieu]);
            $row = $ck->fetch();
            if (!$row) jsonResp(false, 'Không tìm thấy phiếu nhập!');
            if ($row['trang_thai'] === 'hoan_thanh') jsonResp(false, 'Phiếu đã hoàn thành, không thể xóa!');

            $pdo->prepare('DELETE FROM phieu_nhap WHERE ma_phieu=?')->execute([$maPhieu]);
            jsonResp(true, 'Đã xóa phiếu nhập!');
            break;

        default:
            jsonResp(false, 'Method không hỗ trợ!', null, 405);
    }
}

// =============================================================
//  CHI TIET PHIEU NHAP
// =============================================================
function handleChiTiet(string $method, array $body): void
{
    $pdo = getDB();

    switch ($method) {
        case 'GET':
            $maPhieu = trim($_GET['ma_phieu'] ?? '');
            if ($maPhieu === '') jsonResp(false, 'Thiếu ma_phieu!');

            $sql = "
                SELECT ct.*, sp.ten_sp, sp.don_vi_tinh,
                       (ct.so_luong * ct.don_gia) AS thanh_tien
                FROM chi_tiet_phieu_nhap ct
                JOIN san_pham sp ON sp.ma_sp = ct.ma_sp
                WHERE ct.ma_phieu = ?
                ORDER BY ct.ma_sp ASC
            ";
            $st = $pdo->prepare($sql);
            $st->execute([$maPhieu]);
            jsonResp(true, 'OK', $st->fetchAll());
            break;

        case 'POST':
            $maPhieu = trim($body['ma_phieu'] ?? '');
            $maSp    = trim($body['ma_sp'] ?? '');
            $soLuong = (int)($body['so_luong'] ?? 0);
            $donGia  = (float)($body['don_gia'] ?? 0);

            if ($maPhieu === '' || $maSp === '' || $soLuong <= 0 || $donGia < 0)
                jsonResp(false, 'Dữ liệu không hợp lệ!');

            $ck = $pdo->prepare('SELECT trang_thai FROM phieu_nhap WHERE ma_phieu=?');
            $ck->execute([$maPhieu]);
            $phieu = $ck->fetch();
            if (!$phieu) jsonResp(false, 'Không tìm thấy phiếu nhập!');
            if ($phieu['trang_thai'] === 'hoan_thanh') jsonResp(false, 'Phiếu đã hoàn thành!');

            $ck2 = $pdo->prepare('SELECT ma_sp FROM chi_tiet_phieu_nhap WHERE ma_phieu=? AND ma_sp=?');
            $ck2->execute([$maPhieu, $maSp]);
            if ($ck2->fetch()) {
                $pdo->prepare('UPDATE chi_tiet_phieu_nhap SET so_luong=?, don_gia=? WHERE ma_phieu=? AND ma_sp=?')
                    ->execute([$soLuong, $donGia, $maPhieu, $maSp]);
                jsonResp(true, 'Cập nhật số lượng sản phẩm trong phiếu!');
            } else {
                $pdo->prepare('INSERT INTO chi_tiet_phieu_nhap (ma_phieu, ma_sp, so_luong, don_gia) VALUES (?,?,?,?)')
                    ->execute([$maPhieu, $maSp, $soLuong, $donGia]);
                jsonResp(true, 'Đã thêm sản phẩm vào phiếu!', null, 201);
            }
            break;

        case 'PUT':
            // Update dòng chi tiết
            $maPhieu = trim($body['ma_phieu'] ?? '');
            $maSp    = trim($body['ma_sp'] ?? '');
            $soLuong = (int)($body['so_luong'] ?? 0);
            $donGia  = (float)($body['don_gia'] ?? 0);

            if ($maPhieu === '' || $maSp === '' || $soLuong <= 0 || $donGia < 0) jsonResp(false, 'Dữ liệu không hợp lệ!');

            $ck = $pdo->prepare('SELECT trang_thai FROM phieu_nhap WHERE ma_phieu=?');
            $ck->execute([$maPhieu]);
            $row = $ck->fetch();
            if (!$row || $row['trang_thai'] === 'hoan_thanh') jsonResp(false, 'Phiếu đã hoàn thành, không thể sửa!');

            $pdo->prepare('UPDATE chi_tiet_phieu_nhap SET so_luong=?, don_gia=? WHERE ma_phieu=? AND ma_sp=?')
                ->execute([$soLuong, $donGia, $maPhieu, $maSp]);
            jsonResp(true, 'Cập nhật dòng thành công!');
            break;

        case 'DELETE':
            $maPhieu = trim($body['ma_phieu'] ?? '');
            $maSp    = trim($body['ma_sp'] ?? '');
            if ($maPhieu === '' || $maSp === '') jsonResp(false, 'Thiếu thông tin!');

            $ck = $pdo->prepare('SELECT trang_thai FROM phieu_nhap WHERE ma_phieu=?');
            $ck->execute([$maPhieu]);
            $row = $ck->fetch();
            if (!$row || $row['trang_thai'] === 'hoan_thanh') jsonResp(false, 'Phiếu đã hoàn thành, không thể xóa dòng!');

            $pdo->prepare('DELETE FROM chi_tiet_phieu_nhap WHERE ma_phieu=? AND ma_sp=?')->execute([$maPhieu, $maSp]);
            jsonResp(true, 'Đã xóa dòng chi tiết!');
            break;

        default:
            jsonResp(false, 'Method không hỗ trợ!', null, 405);
    }
}

// =============================================================
//  HOÀN THÀNH PHIẾU NHẬP
// =============================================================
function handleHoanThanh(array $body): void
{
    $maPhieu = trim($body['ma_phieu'] ?? '');
    if ($maPhieu === '') jsonResp(false, 'Thiếu mã phiếu!');

    $pdo = getDB();

    $ck = $pdo->prepare('SELECT * FROM phieu_nhap WHERE ma_phieu=?');
    $ck->execute([$maPhieu]);
    $phieu = $ck->fetch();
    if (!$phieu) jsonResp(false, 'Không tìm thấy phiếu!');
    if ($phieu['trang_thai'] === 'hoan_thanh') jsonResp(false, 'Phiếu này đã hoàn thành trước đó rồi!');

    $stCt = $pdo->prepare('SELECT * FROM chi_tiet_phieu_nhap WHERE ma_phieu=?');
    $stCt->execute([$maPhieu]);
    $chiTiets = $stCt->fetchAll();

    if (count($chiTiets) === 0) jsonResp(false, 'Phiếu không có sản phẩm nào!');

    $pdo->beginTransaction();
    try {
        foreach ($chiTiets as $ct) {
            $pdo->prepare('UPDATE san_pham SET so_luong_ton = so_luong_ton + ? WHERE ma_sp=?')
                ->execute([$ct['so_luong'], $ct['ma_sp']]);

            $pdo->prepare('UPDATE san_pham SET gia_von = ?, gia_ban = ROUND(? * (1 + ty_le_loi_nhuan / 100)) WHERE ma_sp=?')
                ->execute([$ct['don_gia'], $ct['don_gia'], $ct['ma_sp']]);
        }
        $pdo->prepare("UPDATE phieu_nhap SET trang_thai='hoan_thanh' WHERE ma_phieu=?")->execute([$maPhieu]);
        $pdo->commit();
        
        jsonResp(true, "Phiếu $maPhieu đã hoàn thành! Giá vốn và số lượng tồn đã được cập nhật.");
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResp(false, 'Lỗi: ' . $e->getMessage());
    }
}

// =============================================================
//  TÌM KIẾM SẢN PHẨM (Để thêm vào phiếu)
// =============================================================
function handleTimSanPham(): void
{
    $q   = trim($_GET['q'] ?? '');
    $pdo = getDB();

    $sql = "
        SELECT sp.ma_sp, sp.ten_sp, sp.don_vi_tinh, sp.gia_von, lsp.ten_loai
        FROM san_pham sp
        LEFT JOIN loai_san_pham lsp ON lsp.ma_loai = sp.ma_loai
        WHERE sp.hien_trang = 'hien_thi'
    ";
    $params = [];
    if ($q !== '') {
        $sql .= " AND (sp.ten_sp LIKE ? OR sp.ma_sp LIKE ?)";
        $params = ["%$q%", "%$q%"];
    }
    $sql .= " ORDER BY sp.ten_sp ASC LIMIT 50";
    
    $st = $pdo->prepare($sql);
    $st->execute($params);
    jsonResp(true, 'OK', $st->fetchAll());
}

function jsonResp(bool $success, string $message = '', ?array $data = null, int $httpCode = 200, array $extra = []): never {
    http_response_code($httpCode);
    $out = ['success' => $success, 'message' => $message];
    if ($data !== null) $out['data'] = $data;
    foreach ($extra as $k => $v) $out[$k] = $v;
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}