<?php
// =============================================================
// phpAdmin/process_SanPham.php – REST API San pham & Loai San pham
// (Đã gộp kèm dbSanPham.php)
// =============================================================

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// =============================================================
// CAU HINH KET NOI MYSQL (Được chuyển từ dbSanPham.php sang)
// =============================================================
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'shop_hoa_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('IMG_UPLOAD_DIR', realpath(__DIR__ . '/../ImageSanPham') . DIRECTORY_SEPARATOR);

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

// ---- Doc tham so ----
$resource = trim($_GET['resource'] ?? '');
$method   = $_SERVER['REQUEST_METHOD'];

// ---- Giai ma body JSON (cho POST / PUT / DELETE) ----
$body = [];
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    $raw  = file_get_contents('php://input');
    if ($raw !== '' && $raw !== false) {
        $body = json_decode($raw, true) ?? [];
    }
}

match ($resource) {
    'loai-san-pham'       => handleLoai($method, $body),
    'san-pham'            => handleSanPham($method, $body),
    'upload-hinh'         => handleUploadHinh(),
    'san-pham-hinh'       => handleSanPhamHinh($method, $body),
    'san-pham-hinh-xoa'   => handleXoaHinhSanPham($body),
    default               => jsonResp(false, 'Resource không hợp lệ', null, 404),
};


// =============================================================
// LOAI SAN PHAM
// =============================================================
function handleLoai(string $method, array $body): void
{
    $pdo = getDB();

    switch ($method) {
        case 'GET':
            $rows = $pdo->query('SELECT * FROM loai_san_pham ORDER BY ma_loai ASC')->fetchAll();
            jsonResp(true, 'OK', $rows);
            break;

        case 'POST':
            $tenLoai  = trim($body['ten_loai'] ?? '');
            $ngayThem = trim($body['ngay_them'] ?? '');

            if ($tenLoai === '') jsonResp(false, 'Tên loại không được để trống!');

            // Sinh ma_loai tự động (tách số sau chữ LSP để tìm mã lớn nhất)
            $maxNum = (int) $pdo->query("SELECT IFNULL(MAX(CAST(SUBSTRING(ma_loai, 4) AS UNSIGNED)), 0) FROM loai_san_pham")->fetchColumn();
            $maLoai = 'LSP' . str_pad((string)($maxNum + 1), 3, '0', STR_PAD_LEFT);

            $ck = $pdo->prepare('SELECT ma_loai FROM loai_san_pham WHERE ten_loai = ?');
            $ck->execute([$tenLoai]);
            if ($ck->fetch()) jsonResp(false, "Tên loại \"$tenLoai\" đã tồn tại!");

            $st = $pdo->prepare('INSERT INTO loai_san_pham (ma_loai, ten_loai, ngay_them) VALUES (?,?,?)');
            $st->execute([$maLoai, $tenLoai, $ngayThem ?: null]);

            jsonResp(true, "Đã thêm loại: $tenLoai", null, 201, ['ma_loai' => $maLoai]);
            break;

        case 'PUT':
            $maLoai   = trim($body['ma_loai'] ?? '');
            $tenLoai  = trim($body['ten_loai'] ?? '');
            $ngayThem = trim($body['ngay_them'] ?? '');

            if ($maLoai === '' || $tenLoai === '') jsonResp(false, 'Thiếu mã loại hoặc tên loại!');

            $ck = $pdo->prepare('SELECT ma_loai FROM loai_san_pham WHERE ten_loai = ? AND ma_loai != ?');
            $ck->execute([$tenLoai, $maLoai]);
            if ($ck->fetch()) jsonResp(false, "Tên loại \"$tenLoai\" đã tồn tại!");

            $st = $pdo->prepare('UPDATE loai_san_pham SET ten_loai=?, ngay_them=? WHERE ma_loai=?');
            $st->execute([$tenLoai, $ngayThem ?: null, $maLoai]);

            jsonResp(true, 'Cập nhật loại thành công!');
            break;

        case 'DELETE':
            $maLoai = trim($body['ma_loai'] ?? '');
            if ($maLoai === '') jsonResp(false, 'Thiếu mã loại!');

            $ck = $pdo->prepare('SELECT COUNT(*) FROM san_pham WHERE ma_loai = ?');
            $ck->execute([$maLoai]);
            if ((int)$ck->fetchColumn() > 0) {
                jsonResp(false, 'Không thể xóa: Loại này đang có sản phẩm!');
            }

            $st = $pdo->prepare('DELETE FROM loai_san_pham WHERE ma_loai=?');
            $st->execute([$maLoai]);

            jsonResp(true, 'Đã xóa loại sản phẩm!');
            break;

        default: jsonResp(false, 'Method không được hỗ trợ!', null, 405);
    }
}


// =============================================================
// SAN PHAM
// =============================================================
function handleSanPham(string $method, array $body): void
{
    $pdo = getDB();

    switch ($method) {
        case 'GET':
            $sql = '
                SELECT sp.*, lsp.ten_loai
                FROM san_pham sp
                LEFT JOIN loai_san_pham lsp ON sp.ma_loai = lsp.ma_loai
                ORDER BY sp.ma_sp ASC
            ';
            $rows = $pdo->query($sql)->fetchAll();
            jsonResp(true, 'OK', $rows);
            break;

        case 'POST':
            $errMsg = validateSpBody($body);
            if ($errMsg) jsonResp(false, $errMsg);

            $ck = $pdo->prepare('SELECT ma_sp FROM san_pham WHERE ma_sp = ?');
            $ck->execute([$body['ma_sp']]);
            if ($ck->fetch()) jsonResp(false, "Mã sản phẩm \"{$body['ma_sp']}\" đã tồn tại!");

            $st = $pdo->prepare('
                INSERT INTO san_pham
                  (ma_sp, ten_sp, ma_loai, don_vi_tinh, so_luong_ton,
                   gia_von, ty_le_loi_nhuan, gia_ban,
                   hinh_anh, mo_ta, hien_trang, ngay_them)
                VALUES (?,?,?,?,?, ?,?,?, ?,?,?,?)
            ');
            $st->execute(buildSpParams($body, true));

            jsonResp(true, 'Đã thêm sản phẩm thành công!', null, 201);
            break;

        case 'PUT':
            $maSpCu = trim($body['ma_sp_cu'] ?? $body['ma_sp'] ?? ''); 
            // Nếu bạn muốn đổi cả mã sản phẩm, bạn cần gửi "ma_sp_cu" từ JS để xác định bản ghi cần cập nhật
            // Còn không, dùng "ma_sp" làm where clause.
            if ($maSpCu === '') jsonResp(false, 'Thiếu mã sản phẩm!');

            $errMsg = validateSpBody($body);
            if ($errMsg) jsonResp(false, $errMsg);

            $hinhMoi = trim($body['hinh_anh'] ?? '');
            if ($hinhMoi === '') {
                $q = $pdo->prepare('SELECT hinh_anh FROM san_pham WHERE ma_sp=?');
                $q->execute([$maSpCu]);
                $hinhMoi = (string)($q->fetchColumn() ?: '');
            }
            $body['hinh_anh'] = $hinhMoi;

            // Đổi `false` thành `true` để lấy ma_sp mới vào đầu mảng params
            $params   = buildSpParams($body, true); 
            $params[] = $maSpCu; // Thêm mã cũ vào cuối cho mệnh đề WHERE

            $st = $pdo->prepare('
                UPDATE san_pham SET
                  ma_sp=?, ten_sp=?, ma_loai=?, don_vi_tinh=?, so_luong_ton=?,
                  gia_von=?, ty_le_loi_nhuan=?, gia_ban=?,
                  hinh_anh=?, mo_ta=?, hien_trang=?, ngay_them=?
                WHERE ma_sp=?
            ');
            $st->execute($params);

            jsonResp(true, 'Cập nhật sản phẩm thành công!');
            break;

        case 'DELETE':
            $maSp = trim($body['ma_sp'] ?? '');
            if ($maSp === '') jsonResp(false, 'Thiếu mã sản phẩm!');

            $ck = $pdo->prepare('SELECT COUNT(*) FROM chi_tiet_phieu_nhap WHERE ma_sp=?');
            $ck->execute([$maSp]);
            $daCoNhapHang = (int)$ck->fetchColumn() > 0;

            if ($daCoNhapHang) {
                $pdo->prepare("UPDATE san_pham SET hien_trang='an' WHERE ma_sp=?")->execute([$maSp]);
                jsonResp(true, 'Sản phẩm đã được ẩn (có phiếu nhập → không xóa hẳn)!');
            } else {
                $q = $pdo->prepare('SELECT hinh_anh FROM san_pham WHERE ma_sp=?');
                $q->execute([$maSp]);
                $hinh = (string)($q->fetchColumn() ?: '');
                if ($hinh !== '' && file_exists(IMG_UPLOAD_DIR . $hinh)) {
                    @unlink(IMG_UPLOAD_DIR . $hinh);
                }
                $pdo->prepare('DELETE FROM san_pham WHERE ma_sp=?')->execute([$maSp]);
                jsonResp(true, 'Đã xóa sản phẩm khỏi cơ sở dữ liệu!');
            }
            break;

        default: jsonResp(false, 'Method không được hỗ trợ!', null, 405);
    }
}


// =============================================================
// UPLOAD HINH ANH
// =============================================================
function handleUploadHinh(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResp(false, 'Chỉ chấp nhận POST cho upload-hinh!', null, 405);
    }

    if (!isset($_FILES['hinh_anh']) || $_FILES['hinh_anh']['error'] !== UPLOAD_ERR_OK) {
        $errCode = $_FILES['hinh_anh']['error'] ?? -1;
        jsonResp(false, "Lỗi nhận file (mã: $errCode). Vui lòng kiểm tra lại!");
    }

    $file    = $_FILES['hinh_anh'];
    $maxSize = 5 * 1024 * 1024; // 5 MB

    if ($file['size'] > $maxSize) jsonResp(false, 'File quá lớn! Tối đa 5 MB.');

    $allowedMime = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $mime        = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) {
        jsonResp(false, 'Định dạng không hợp lệ! Chỉ chấp nhận JPEG, PNG, GIF, WEBP.');
    }

    if (!is_dir(IMG_UPLOAD_DIR)) {
        mkdir(IMG_UPLOAD_DIR, 0755, true);
    }

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest    = IMG_UPLOAD_DIR . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonResp(false, 'Lưu file thất bại! Kiểm tra quyền ghi.');
    }

    jsonResp(true, 'Upload thành công!', null, 200, ['path' => $newName]);
}

// =============================================================
// HELPER FUNCTIONS
// =============================================================
function validateSpBody(array $b): string
{
    if (trim($b['ma_sp']  ?? '') === '') return 'Mã sản phẩm không được để trống!';
    if (trim($b['ten_sp'] ?? '') === '') return 'Tên sản phẩm không được để trống!';
    if (empty($b['ma_loai']))            return 'Vui lòng chọn loại sản phẩm!';
    return '';
}

function buildSpParams(array $b, bool $includeMaSp = false): array
{
    $params = [
        trim($b['ten_sp']),
        $b['ma_loai'] ?: null,
        trim($b['don_vi_tinh']      ?? 'Cai') ?: 'Cai',
        max(0, (int)($b['so_luong_ton']    ?? 0)),
        max(0, (float)($b['gia_von']        ?? 0)),
        max(0, (float)($b['ty_le_loi_nhuan']?? 0)),
        max(0, (float)($b['gia_ban']        ?? 0)),
        trim($b['hinh_anh'] ?? '') ?: null,
        trim($b['mo_ta']    ?? '') ?: null,
        in_array($b['hien_trang'] ?? '', ['hien_thi', 'an'], true) ? $b['hien_trang'] : 'hien_thi',
        $b['ngay_them'] ?? date('Y-m-d'),
    ];

    if ($includeMaSp) {
        array_unshift($params, trim($b['ma_sp']));
    }
    return $params;
}

// =============================================================
// QUẢN LÝ HÌNH ẢNH SẢN PHẨM (CHI TIẾT)
// =============================================================
function handleSanPhamHinh(string $method, array $body): void
{
    $pdo = getDB();

    switch ($method) {
        case 'GET':
            // GET /san-pham-hinh?ma_sp=SP001
            $maSp = trim($_GET['ma_sp'] ?? '');
            if ($maSp === '') jsonResp(false, 'Thiếu mã sản phẩm!');

            $st = $pdo->prepare('
                SELECT ma_sp, thu_tu, duong_dan, ngay_them
                FROM san_pham_hinh_anh
                WHERE ma_sp = ?
                ORDER BY thu_tu ASC
            ');
            $st->execute([$maSp]);
            $hinhList = $st->fetchAll();

            jsonResp(true, 'OK', $hinhList);
            break;

        case 'POST':
            // Thêm hình ảnh mới cho sản phẩm
            $maSp = trim($body['ma_sp'] ?? '');
            $duongDan = trim($body['duong_dan'] ?? '');

            if ($maSp === '' || $duongDan === '') {
                jsonResp(false, 'Thiếu mã sản phẩm hoặc đường dẫn hình ảnh!');
            }

            // Tìm thứ tự lớn nhất hiện có
            $q = $pdo->prepare('SELECT MAX(thu_tu) FROM san_pham_hinh_anh WHERE ma_sp = ?');
            $q->execute([$maSp]);
            $maxThuTu = (int)($q->fetchColumn() ?? 0);

            $st = $pdo->prepare('
                INSERT INTO san_pham_hinh_anh (ma_sp, thu_tu, duong_dan)
                VALUES (?, ?, ?)
            ');
            $st->execute([$maSp, $maxThuTu + 1, $duongDan]);

            jsonResp(true, 'Thêm hình ảnh thành công!', null, 201);
            break;

        default: jsonResp(false, 'Method không được hỗ trợ!', null, 405);
    }
}

function handleXoaHinhSanPham(array $body): void
{
    $pdo = getDB();

    $maSp = trim($body['ma_sp'] ?? '');
    $thuTu = (int)($body['thu_tu'] ?? 0);

    if ($maSp === '' || $thuTu <= 0) {
        jsonResp(false, 'Thiếu mã sản phẩm hoặc thứ tự hình ảnh!');
    }

    // Lấy đường dẫn để xóa file
    $q = $pdo->prepare('SELECT duong_dan FROM san_pham_hinh_anh WHERE ma_sp = ? AND thu_tu = ?');
    $q->execute([$maSp, $thuTu]);
    $duongDan = (string)($q->fetchColumn() ?: '');

    // Xóa từ DB
    $st = $pdo->prepare('DELETE FROM san_pham_hinh_anh WHERE ma_sp = ? AND thu_tu = ?');
    $st->execute([$maSp, $thuTu]);

    // Xóa file
    if ($duongDan !== '' && file_exists(IMG_UPLOAD_DIR . $duongDan)) {
        @unlink(IMG_UPLOAD_DIR . $duongDan);
    }

    // Sắp xếp lại thứ tự
    $q = $pdo->prepare('SELECT thu_tu FROM san_pham_hinh_anh WHERE ma_sp = ? ORDER BY thu_tu ASC');
    $q->execute([$maSp]);
    $rows = $q->fetchAll();

    foreach ($rows as $i => $row) {
        $newThuTu = $i + 1;
        if ($newThuTu !== (int)$row['thu_tu']) {
            $upd = $pdo->prepare('UPDATE san_pham_hinh_anh SET thu_tu = ? WHERE ma_sp = ? AND thu_tu = ?');
            $upd->execute([$newThuTu, $maSp, (int)$row['thu_tu']]);
        }
    }

    jsonResp(true, 'Xóa hình ảnh thành công!');
}

function jsonResp(bool $success, string $message = '', ?array $data = null, int $httpCode = 200, array $extra = []): never {
    http_response_code($httpCode);
    $out = ['success' => $success, 'message' => $message];
    if ($data !== null) $out['data'] = $data;
    foreach ($extra as $k => $v) $out[$k] = $v;
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}