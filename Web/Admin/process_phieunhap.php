<?php
// =============================================================
// Admin/process_PhieuNhap.php  –  REST API Quan ly Phieu Nhap
// =============================================================
// ?resource=phieu-nhap        GET=danh sach, POST=tao moi, DELETE=xoa
// ?resource=chi-tiet          GET?id_phieu=X, POST=them dong, DELETE=xoa dong
// ?resource=hoan-thanh        POST { id } → hoan thanh phieu + cap nhat gia von
// ?resource=tim-san-pham      GET?q=keyword → tim kiem san pham de them vao phieu
// =============================================================

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/dbSanPham.php';

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
    default         => jsonResp(false, 'Resource khong hop le', null, 404),
};

// =============================================================
//  PHIEU NHAP  (GET list / POST tao moi / DELETE xoa)
// =============================================================
function handlePhieuNhap(string $method, array $body): void
{
    $pdo = getDB();

    switch ($method) {

        // ---- GET: danh sach phieu nhap + tong tien + so dong ----
        case 'GET':
            $keyword = trim($_GET['q'] ?? '');
            $sql = "
                SELECT pn.*,
                       COUNT(ct.id)               AS so_dong,
                       IFNULL(SUM(ct.so_luong * ct.don_gia), 0) AS tong_tien
                FROM phieu_nhap pn
                LEFT JOIN chi_tiet_phieu_nhap ct ON ct.id_phieu = pn.id
            ";
            $params = [];
            if ($keyword !== '') {
                $sql .= " WHERE pn.ma_phieu LIKE ? OR EXISTS (
                    SELECT 1 FROM chi_tiet_phieu_nhap c2
                    JOIN san_pham sp ON sp.id = c2.id_sp
                    WHERE c2.id_phieu = pn.id AND sp.ten_sp LIKE ?
                )";
                $params = ["%$keyword%", "%$keyword%"];
            }
            $sql .= " GROUP BY pn.id ORDER BY pn.id DESC";
            $st = $pdo->prepare($sql);
            $st->execute($params);
            jsonResp(true, 'OK', $st->fetchAll());
            break;

        // ---- POST: tao phieu nhap moi (rong, them chi tiet sau) ----
        case 'POST':
            $ngayNhap = trim($body['ngay_nhap'] ?? '');
            $ghiChu   = trim($body['ghi_chu']   ?? '');
            if ($ngayNhap === '') jsonResp(false, 'Ngay nhap khong duoc de trong!');

            // Sinh ma phieu tu dong: PN001, PN002...
            $maxId   = (int)$pdo->query('SELECT IFNULL(MAX(id),0) FROM phieu_nhap')->fetchColumn();
            $maPhieu = 'PN' . str_pad((string)($maxId + 1), 3, '0', STR_PAD_LEFT);

            $st = $pdo->prepare(
                'INSERT INTO phieu_nhap (ma_phieu, ngay_nhap, ghi_chu) VALUES (?,?,?)'
            );
            $st->execute([$maPhieu, $ngayNhap, $ghiChu ?: null]);
            $newId = (int)$pdo->lastInsertId();

            jsonResp(true, "Da tao phieu: $maPhieu", null, 201,
                ['id' => $newId, 'ma_phieu' => $maPhieu]);
            break;

        // ---- PUT: sua thong tin dau phieu (ngay, ghi chu) - chi khi chua hoan thanh ----
        case 'PUT':
            $id       = (int)($body['id']       ?? 0);
            $ngayNhap = trim($body['ngay_nhap'] ?? '');
            if (!$id || $ngayNhap === '') jsonResp(false, 'Thieu id hoac ngay nhap!');

            // Kiem tra trang thai
            $ck = $pdo->prepare('SELECT trang_thai FROM phieu_nhap WHERE id=?');
            $ck->execute([$id]);
            $row = $ck->fetch();
            if (!$row) jsonResp(false, 'Khong tim thay phieu nhap!');
            if ($row['trang_thai'] === 'hoan_thanh')
                jsonResp(false, 'Phieu da hoan thanh, khong the sua!');

            $st = $pdo->prepare(
                'UPDATE phieu_nhap SET ngay_nhap=?, ghi_chu=? WHERE id=?'
            );
            $st->execute([$ngayNhap, trim($body['ghi_chu'] ?? '') ?: null, $id]);
            jsonResp(true, 'Cap nhat phieu thanh cong!');
            break;

        // ---- DELETE: xoa phieu (chi khi chua hoan thanh) ----
        case 'DELETE':
            $id = (int)($body['id'] ?? 0);
            if (!$id) jsonResp(false, 'Thieu id phieu!');

            $ck = $pdo->prepare('SELECT trang_thai FROM phieu_nhap WHERE id=?');
            $ck->execute([$id]);
            $row = $ck->fetch();
            if (!$row) jsonResp(false, 'Khong tim thay phieu nhap!');
            if ($row['trang_thai'] === 'hoan_thanh')
                jsonResp(false, 'Phieu da hoan thanh, khong the xoa!');

            // Chi tiet se tu xoa (ON DELETE CASCADE)
            $pdo->prepare('DELETE FROM phieu_nhap WHERE id=?')->execute([$id]);
            jsonResp(true, 'Da xoa phieu nhap!');
            break;

        default:
            jsonResp(false, 'Method khong ho tro!', null, 405);
    }
}

// =============================================================
//  CHI TIET PHIEU NHAP  (GET / POST them dong / PUT sua dong / DELETE xoa dong)
// =============================================================
function handleChiTiet(string $method, array $body): void
{
    $pdo = getDB();

    switch ($method) {

        // ---- GET: lay chi tiet theo id_phieu ----
        case 'GET':
            $idPhieu = (int)($_GET['id_phieu'] ?? 0);
            if (!$idPhieu) jsonResp(false, 'Thieu id_phieu!');

            $sql = "
                SELECT ct.*, sp.ma_sp, sp.ten_sp, sp.don_vi_tinh,
                       (ct.so_luong * ct.don_gia) AS thanh_tien
                FROM chi_tiet_phieu_nhap ct
                JOIN san_pham sp ON sp.id = ct.id_sp
                WHERE ct.id_phieu = ?
                ORDER BY ct.id ASC
            ";
            $st = $pdo->prepare($sql);
            $st->execute([$idPhieu]);
            jsonResp(true, 'OK', $st->fetchAll());
            break;

        // ---- POST: them dong chi tiet vao phieu ----
        case 'POST':
            $idPhieu  = (int)($body['id_phieu'] ?? 0);
            $idSp     = (int)($body['id_sp']    ?? 0);
            $soLuong  = (int)($body['so_luong'] ?? 0);
            $donGia   = (float)($body['don_gia'] ?? 0);

            if (!$idPhieu || !$idSp || $soLuong <= 0 || $donGia < 0)
                jsonResp(false, 'Du lieu khong hop le! Kiem tra lai id_phieu, id_sp, so_luong, don_gia.');

            // Kiem tra phieu ton tai va chua hoan thanh
            $ck = $pdo->prepare('SELECT trang_thai FROM phieu_nhap WHERE id=?');
            $ck->execute([$idPhieu]);
            $phieu = $ck->fetch();
            if (!$phieu) jsonResp(false, 'Khong tim thay phieu nhap!');
            if ($phieu['trang_thai'] === 'hoan_thanh')
                jsonResp(false, 'Phieu da hoan thanh, khong the them dong!');

            // Kiem tra san pham da co trong phieu chua → neu co thi cap nhat so luong
            $ck2 = $pdo->prepare(
                'SELECT id FROM chi_tiet_phieu_nhap WHERE id_phieu=? AND id_sp=?'
            );
            $ck2->execute([$idPhieu, $idSp]);
            $existing = $ck2->fetch();

            if ($existing) {
                $pdo->prepare(
                    'UPDATE chi_tiet_phieu_nhap SET so_luong=?, don_gia=? WHERE id=?'
                )->execute([$soLuong, $donGia, $existing['id']]);
                jsonResp(true, 'Cap nhat dong san pham trong phieu!');
            } else {
                $pdo->prepare(
                    'INSERT INTO chi_tiet_phieu_nhap (id_phieu,id_sp,so_luong,don_gia) VALUES (?,?,?,?)'
                )->execute([$idPhieu, $idSp, $soLuong, $donGia]);
                jsonResp(true, 'Da them san pham vao phieu!', null, 201);
            }
            break;

        // ---- PUT: sua 1 dong chi tiet ----
        case 'PUT':
            $idCt    = (int)($body['id']       ?? 0);
            $soLuong = (int)($body['so_luong'] ?? 0);
            $donGia  = (float)($body['don_gia'] ?? 0);
            if (!$idCt || $soLuong <= 0 || $donGia < 0)
                jsonResp(false, 'Du lieu khong hop le!');

            // Kiem tra phieu chua hoan thanh
            $ck = $pdo->prepare("
                SELECT pn.trang_thai FROM chi_tiet_phieu_nhap ct
                JOIN phieu_nhap pn ON pn.id = ct.id_phieu
                WHERE ct.id = ?
            ");
            $ck->execute([$idCt]);
            $row = $ck->fetch();
            if (!$row) jsonResp(false, 'Khong tim thay dong chi tiet!');
            if ($row['trang_thai'] === 'hoan_thanh')
                jsonResp(false, 'Phieu da hoan thanh, khong the sua!');

            $pdo->prepare(
                'UPDATE chi_tiet_phieu_nhap SET so_luong=?, don_gia=? WHERE id=?'
            )->execute([$soLuong, $donGia, $idCt]);
            jsonResp(true, 'Cap nhat dong thanh cong!');
            break;

        // ---- DELETE: xoa 1 dong chi tiet ----
        case 'DELETE':
            $idCt = (int)($body['id'] ?? 0);
            if (!$idCt) jsonResp(false, 'Thieu id chi tiet!');

            $ck = $pdo->prepare("
                SELECT pn.trang_thai FROM chi_tiet_phieu_nhap ct
                JOIN phieu_nhap pn ON pn.id = ct.id_phieu
                WHERE ct.id = ?
            ");
            $ck->execute([$idCt]);
            $row = $ck->fetch();
            if (!$row) jsonResp(false, 'Khong tim thay dong chi tiet!');
            if ($row['trang_thai'] === 'hoan_thanh')
                jsonResp(false, 'Phieu da hoan thanh, khong the xoa dong!');

            $pdo->prepare('DELETE FROM chi_tiet_phieu_nhap WHERE id=?')->execute([$idCt]);
            jsonResp(true, 'Da xoa dong chi tiet!');
            break;

        default:
            jsonResp(false, 'Method khong ho tro!', null, 405);
    }
}

// =============================================================
//  HOAN THANH PHIEU NHAP
//  → cap nhat so_luong_ton va gia_von trong bang san_pham
// =============================================================
function handleHoanThanh(array $body): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        jsonResp(false, 'Chi ho tro POST!', null, 405);

    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResp(false, 'Thieu id phieu!');

    $pdo = getDB();

    // Lay phieu
    $ck = $pdo->prepare('SELECT * FROM phieu_nhap WHERE id=?');
    $ck->execute([$id]);
    $phieu = $ck->fetch();
    if (!$phieu) jsonResp(false, 'Khong tim thay phieu nhap!');
    if ($phieu['trang_thai'] === 'hoan_thanh')
        jsonResp(false, 'Phieu nay da hoan thanh truoc do roi!');

    // Lay tat ca chi tiet
    $stCt = $pdo->prepare('SELECT * FROM chi_tiet_phieu_nhap WHERE id_phieu=?');
    $stCt->execute([$id]);
    $chiTiets = $stCt->fetchAll();

    if (count($chiTiets) === 0)
        jsonResp(false, 'Phieu khong co san pham nao! Them san pham truoc khi hoan thanh.');

    // Dung transaction de dam bao toan ven du lieu
    $pdo->beginTransaction();
    try {
        foreach ($chiTiets as $ct) {
            // Tang so_luong_ton
            $pdo->prepare(
                'UPDATE san_pham SET so_luong_ton = so_luong_ton + ? WHERE id=?'
            )->execute([$ct['so_luong'], $ct['id_sp']]);

            // Cap nhat gia_von = don_gia nhap moi nhat (theo yeu cau)
            $pdo->prepare(
                'UPDATE san_pham SET gia_von = ?,
                 gia_ban = ROUND(? * (1 + ty_le_loi_nhuan / 100))
                 WHERE id=?'
            )->execute([$ct['don_gia'], $ct['don_gia'], $ct['id_sp']]);
        }

        // Danh dau phieu da hoan thanh
        $pdo->prepare(
            "UPDATE phieu_nhap SET trang_thai='hoan_thanh' WHERE id=?"
        )->execute([$id]);

        $pdo->commit();
        jsonResp(true,
            "Phieu {$phieu['ma_phieu']} da hoan thanh! Gia von va so luong ton da duoc cap nhat.");

    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResp(false, 'Loi khi hoan thanh phieu: ' . $e->getMessage());
    }
}

// =============================================================
//  TIM KIEM SAN PHAM (de chon them vao phieu)
// =============================================================
function handleTimSanPham(): void
{
    $q   = trim($_GET['q'] ?? '');
    $pdo = getDB();

    if ($q === '') {
        // Tra ve tat ca san pham dang ban
        $rows = $pdo->query("
            SELECT sp.id, sp.ma_sp, sp.ten_sp, sp.don_vi_tinh, sp.gia_von,
                   lsp.ten_loai
            FROM san_pham sp
            LEFT JOIN loai_san_pham lsp ON lsp.id = sp.id_loai
            WHERE sp.hien_trang = 'hien_thi'
            ORDER BY sp.ten_sp ASC
            LIMIT 50
        ")->fetchAll();
    } else {
        $st = $pdo->prepare("
            SELECT sp.id, sp.ma_sp, sp.ten_sp, sp.don_vi_tinh, sp.gia_von,
                   lsp.ten_loai
            FROM san_pham sp
            LEFT JOIN loai_san_pham lsp ON lsp.id = sp.id_loai
            WHERE sp.hien_trang = 'hien_thi'
              AND (sp.ten_sp LIKE ? OR sp.ma_sp LIKE ?)
            ORDER BY sp.ten_sp ASC
            LIMIT 20
        ");
        $st->execute(["%$q%", "%$q%"]);
        $rows = $st->fetchAll();
    }
    jsonResp(true, 'OK', $rows);
}

// =============================================================
//  HELPER
// =============================================================
function jsonResp(
    bool   $success,
    string $message  = '',
    ?array $data     = null,
    int    $httpCode = 200,
    array  $extra    = []
): never {
    http_response_code($httpCode);
    $out = ['success' => $success, 'message' => $message];
    if ($data !== null) $out['data'] = $data;
    foreach ($extra as $k => $v) $out[$k] = $v;
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}