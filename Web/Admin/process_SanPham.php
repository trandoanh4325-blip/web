<?php
// =============================================================
// phpAdmin/SanPham.php  –  REST API San pham & Loai San pham
// =============================================================
// Duoc goi tu JS voi:
//   ?resource=loai-san-pham    →  CRUD loai san pham
//   ?resource=san-pham         →  CRUD san pham
//   ?resource=upload-hinh      →  Upload hinh anh (POST multipart)
//
// Method: GET / POST / PUT / DELETE
// Body:   JSON (tru upload-hinh dung multipart/form-data)
// =============================================================

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tra loi OPTIONS preflight cua browser
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/dbSanPham.php';

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

// =============================================================
// ROUTER
// =============================================================
match ($resource) {
    'loai-san-pham' => handleLoai($method, $body),
    'san-pham'      => handleSanPham($method, $body),
    'upload-hinh'   => handleUploadHinh(),
    default         => jsonResp(false, 'Resource khong hop le', null, 404),
};


// =============================================================
//  LOAI SAN PHAM  (GET / POST / PUT / DELETE)
// =============================================================
function handleLoai(string $method, array $body): void
{
    $pdo = getDB();

    switch ($method) {

        // ------ LAY DANH SACH ------
        case 'GET':
            $rows = $pdo
                ->query('SELECT * FROM loai_san_pham ORDER BY id ASC')
                ->fetchAll();
            jsonResp(true, 'OK', $rows);
            break;

        // ------ THEM LOAI MOI ------
        case 'POST':
            $tenLoai  = trim($body['ten_loai']  ?? '');
            $ngayThem = trim($body['ngay_them'] ?? '');

            if ($tenLoai === '') {
                jsonResp(false, 'Ten loai khong duoc de trong!');
            }

            // Sinh ma_loai tu dong: LSP001, LSP002...
            $maxId  = (int) $pdo->query('SELECT IFNULL(MAX(id),0) FROM loai_san_pham')->fetchColumn();
            $maLoai = 'LSP' . str_pad((string)($maxId + 1), 3, '0', STR_PAD_LEFT);

            // Kiem tra trung ten
            $ck = $pdo->prepare('SELECT id FROM loai_san_pham WHERE ten_loai = ?');
            $ck->execute([$tenLoai]);
            if ($ck->fetch()) {
                jsonResp(false, "Ten loai \"$tenLoai\" da ton tai!");
            }

            $st = $pdo->prepare(
                'INSERT INTO loai_san_pham (ma_loai, ten_loai, ngay_them) VALUES (?,?,?)'
            );
            $st->execute([$maLoai, $tenLoai, $ngayThem ?: null]);

            jsonResp(true, "Da them loai: $tenLoai", null, 201, ['ma_loai' => $maLoai]);
            break;

        // ------ SUA LOAI ------
        case 'PUT':
            $id       = (int)($body['id']       ?? 0);
            $tenLoai  = trim($body['ten_loai']  ?? '');
            $ngayThem = trim($body['ngay_them'] ?? '');

            if (!$id || $tenLoai === '') {
                jsonResp(false, 'Thieu id hoac ten loai!');
            }

            // Kiem tra trung ten (loai tru chinh no)
            $ck = $pdo->prepare('SELECT id FROM loai_san_pham WHERE ten_loai = ? AND id != ?');
            $ck->execute([$tenLoai, $id]);
            if ($ck->fetch()) {
                jsonResp(false, "Ten loai \"$tenLoai\" da ton tai!");
            }

            $st = $pdo->prepare(
                'UPDATE loai_san_pham SET ten_loai=?, ngay_them=? WHERE id=?'
            );
            $st->execute([$tenLoai, $ngayThem ?: null, $id]);

            jsonResp(true, 'Cap nhat loai thanh cong!');
            break;

        // ------ XOA LOAI ------
        case 'DELETE':
            $id = (int)($body['id'] ?? 0);
            if (!$id) jsonResp(false, 'Thieu id loai!');

            // Khong cho xoa neu con san pham thuoc loai nay
            $ck = $pdo->prepare('SELECT COUNT(*) FROM san_pham WHERE id_loai = ?');
            $ck->execute([$id]);
            if ((int)$ck->fetchColumn() > 0) {
                jsonResp(false, 'Khong the xoa: loai nay dang co san pham! Hay xoa san pham truoc.');
            }

            $st = $pdo->prepare('DELETE FROM loai_san_pham WHERE id=?');
            $st->execute([$id]);

            jsonResp(true, 'Da xoa loai san pham!');
            break;

        default:
            jsonResp(false, 'Method khong duoc ho tro!', null, 405);
    }
}


// =============================================================
//  SAN PHAM  (GET / POST / PUT / DELETE)
// =============================================================
function handleSanPham(string $method, array $body): void
{
    $pdo = getDB();

    switch ($method) {

        // ------ LAY DANH SACH (JOIN ten loai) ------
        case 'GET':
            $sql = '
                SELECT sp.*, lsp.ten_loai, lsp.ma_loai
                FROM   san_pham sp
                LEFT JOIN loai_san_pham lsp ON sp.id_loai = lsp.id
                ORDER BY sp.id ASC
            ';
            $rows = $pdo->query($sql)->fetchAll();
            jsonResp(true, 'OK', $rows);
            break;

        // ------ THEM SAN PHAM MOI ------
        case 'POST':
            $errMsg = validateSpBody($body);
            if ($errMsg) jsonResp(false, $errMsg);

            // Kiem tra ma trung
            $ck = $pdo->prepare('SELECT id FROM san_pham WHERE ma_sp = ?');
            $ck->execute([$body['ma_sp']]);
            if ($ck->fetch()) {
                jsonResp(false, "Ma san pham \"{$body['ma_sp']}\" da ton tai!");
            }

            $st = $pdo->prepare('
                INSERT INTO san_pham
                  (ma_sp, ten_sp, id_loai, don_vi_tinh, so_luong_ton,
                   gia_von, ty_le_loi_nhuan, gia_ban,
                   hinh_anh, mo_ta, hien_trang, ngay_them)
                VALUES (?,?,?,?,?, ?,?,?, ?,?,?,?)
            ');
            $st->execute(buildSpParams($body));

            jsonResp(true, 'Da them san pham thanh cong!', null, 201);
            break;

        // ------ SUA SAN PHAM ------
        case 'PUT':
            $id = (int)($body['id'] ?? 0);
            if (!$id) jsonResp(false, 'Thieu id san pham!');

            $errMsg = validateSpBody($body);
            if ($errMsg) jsonResp(false, $errMsg);

            // Kiem tra ma trung (loai tru ban than)
            $ck = $pdo->prepare('SELECT id FROM san_pham WHERE ma_sp = ? AND id != ?');
            $ck->execute([$body['ma_sp'], $id]);
            if ($ck->fetch()) {
                jsonResp(false, "Ma san pham \"{$body['ma_sp']}\" da ton tai!");
            }

            // Neu khong upload hinh moi → giu hinh cu
            $hinhMoi = trim($body['hinh_anh'] ?? '');
            if ($hinhMoi === '') {
                $q = $pdo->prepare('SELECT hinh_anh FROM san_pham WHERE id=?');
                $q->execute([$id]);
                $hinhMoi = (string)($q->fetchColumn() ?: '');
            }
            $body['hinh_anh'] = $hinhMoi;

            $params   = buildSpParams($body);
            $params[] = $id; // WHERE id=?

            $st = $pdo->prepare('
                UPDATE san_pham SET
                  ma_sp=?, ten_sp=?, id_loai=?, don_vi_tinh=?, so_luong_ton=?,
                  gia_von=?, ty_le_loi_nhuan=?, gia_ban=?,
                  hinh_anh=?, mo_ta=?, hien_trang=?, ngay_them=?
                WHERE id=?
            ');
            $st->execute($params);

            jsonResp(true, 'Cap nhat san pham thanh cong!');
            break;

        // ------ XOA SAN PHAM ------
        case 'DELETE':
            $id = (int)($body['id'] ?? 0);
            if (!$id) jsonResp(false, 'Thieu id san pham!');

            // Kiem tra co phieu nhap chua
            $ck = $pdo->prepare('SELECT COUNT(*) FROM chi_tiet_phieu_nhap WHERE id_sp=?');
            $ck->execute([$id]);
            $daCoNhapHang = (int)$ck->fetchColumn() > 0;

            if ($daCoNhapHang) {
                // Da nhap hang → chi dat an
                $pdo->prepare("UPDATE san_pham SET hien_trang='an' WHERE id=?")
                    ->execute([$id]);
                jsonResp(true, 'San pham da duoc an (co phieu nhap → khong xoa han)!');
            } else {
                // Chua nhap hang → xoa han + xoa file hinh
                $q = $pdo->prepare('SELECT hinh_anh FROM san_pham WHERE id=?');
                $q->execute([$id]);
                $hinh = (string)($q->fetchColumn() ?: '');
                if ($hinh !== '' && file_exists(IMG_UPLOAD_DIR . $hinh)) {
                    @unlink(IMG_UPLOAD_DIR . $hinh);
                }
                $pdo->prepare('DELETE FROM san_pham WHERE id=?')->execute([$id]);
                jsonResp(true, 'Da xoa san pham khoi co so du lieu!');
            }
            break;

        default:
            jsonResp(false, 'Method khong duoc ho tro!', null, 405);
    }
}


// =============================================================
//  UPLOAD HINH ANH
// =============================================================
function handleUploadHinh(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResp(false, 'Chi chap nhan POST cho upload-hinh!', null, 405);
    }

    if (!isset($_FILES['hinh_anh']) || $_FILES['hinh_anh']['error'] !== UPLOAD_ERR_OK) {
        $errCode = $_FILES['hinh_anh']['error'] ?? -1;
        jsonResp(false, "Loi nhan file (ma loi: $errCode). Kiem tra kich thuoc va dinh dang!");
    }

    $file    = $_FILES['hinh_anh'];
    $maxSize = 5 * 1024 * 1024; // 5 MB

    if ($file['size'] > $maxSize) {
        jsonResp(false, 'File qua lon! Toi da 5 MB.');
    }

    $allowedMime = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $mime        = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) {
        jsonResp(false, 'Dinh dang khong hop le! Chi chap nhan JPEG, PNG, GIF, WEBP.');
    }

    // Dam bao thu muc Image/ ton tai
    if (!is_dir(IMG_UPLOAD_DIR)) {
        mkdir(IMG_UPLOAD_DIR, 0755, true);
    }

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest    = IMG_UPLOAD_DIR . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonResp(false, 'Luu file that bai! Kiem tra quyen ghi thu muc Image/.');
    }

    jsonResp(true, 'Upload thanh cong!', null, 200, ['path' => $newName]);
}


// =============================================================
//  HELPER FUNCTIONS
// =============================================================

/** Kiem tra cac truong bat buoc cua san pham */
function validateSpBody(array $b): string
{
    if (trim($b['ma_sp']  ?? '') === '') return 'Ma san pham khong duoc de trong!';
    if (trim($b['ten_sp'] ?? '') === '') return 'Ten san pham khong duoc de trong!';
    if (empty($b['id_loai']))            return 'Vui long chon loai san pham!';
    return '';
}

/** Xay dung mang tham so INSERT / UPDATE san_pham */
function buildSpParams(array $b): array
{
    return [
        trim($b['ma_sp']),
        trim($b['ten_sp']),
        $b['id_loai'] ?: null,
        trim($b['don_vi_tinh']      ?? 'Cai') ?: 'Cai',
        max(0, (int)($b['so_luong_ton']    ?? 0)),
        max(0, (float)($b['gia_von']        ?? 0)),
        max(0, (float)($b['ty_le_loi_nhuan']?? 0)),
        max(0, (float)($b['gia_ban']        ?? 0)),
        trim($b['hinh_anh'] ?? '') ?: null,
        trim($b['mo_ta']    ?? '') ?: null,
        in_array($b['hien_trang'] ?? '', ['hien_thi', 'an'], true)
            ? $b['hien_trang'] : 'hien_thi',
        $b['ngay_them'] ?? date('Y-m-d'),
    ];
}

/** Gui JSON response va dung thuc thi */
function jsonResp(
    bool    $success,
    string  $message  = '',
    ?array  $data     = null,
    int     $httpCode = 200,
    array   $extra    = []
): never {
    http_response_code($httpCode);
    $out = ['success' => $success, 'message' => $message];
    if ($data !== null) $out['data'] = $data;
    foreach ($extra as $k => $v) $out[$k] = $v;
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}