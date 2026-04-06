<?php
// =============================================================
// Admin/giaban_api.php  –  API Quản lý Giá Bán
// Dùng chung DB với SanPham (shop_hoa_db via dbSanPham.php)
// =============================================================
// GET                          → danh sách san_pham JOIN loai
// GET ?loai=all                → danh sách loai_san_pham (cho dropdown)
// GET ?search=<kw>             → tìm kiếm
// POST                         → thêm san_pham (auto-sinh ma_sp)
// PUT                          → cập nhật ty_le_loi_nhuan + gia_ban
// DELETE ?id=<id>              → xóa / ẩn san_pham
// =============================================================

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Dùng chung cấu hình & hàm getDB() với SanPham
require_once __DIR__ . '/dbSanPham.php';

/* ── Helper ── */
function jsonOut(bool $ok, string $msg = '', $data = null, int $code = 200): never {
    http_response_code($code);
    $r = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

/* ══════════════════════════════════════════════════════════
   BASE SELECT
   Aliases khớp với field names trong giaban.js:
     r.loai              = ten_loai của loại
     r.ten_san_pham      = ten_sp
     r.loi_nhuan_loai    = ty_le_loi_nhuan của loại (có thể NULL → 0)
     r.loi_nhuan_san_pham= ty_le_loi_nhuan của sản phẩm
     r.gia_ban_tinh      = gia_ban (lưu trong DB)
     r.loi_nhuan_hieu_dung = tỉ lệ thực sự áp dụng
   ══════════════════════════════════════════════════════════ */
$BASE = "
    SELECT
        sp.id,
        sp.ma_sp,
        sp.id_loai,
        COALESCE(l.ten_loai, '—')                                       AS loai,
        sp.ten_sp                                                        AS ten_san_pham,
        sp.don_vi_tinh,
        sp.so_luong_ton,
        sp.gia_von,
        COALESCE(l.ty_le_loi_nhuan, 0)                                  AS loi_nhuan_loai,
        sp.ty_le_loi_nhuan                                               AS loi_nhuan_san_pham,
        sp.gia_ban                                                       AS gia_ban_tinh,
        IF(sp.ty_le_loi_nhuan > 0,
           sp.ty_le_loi_nhuan,
           COALESCE(l.ty_le_loi_nhuan, 0))                              AS loi_nhuan_hieu_dung
    FROM  san_pham sp
    LEFT  JOIN loai_san_pham l ON sp.id_loai = l.id
    WHERE sp.hien_trang = 'hien_thi'
";

/* ══════════ GET ══════════ */
if ($method === 'GET') {

    /* Danh sách loại cho dropdown */
    if (isset($_GET['loai']) && $_GET['loai'] === 'all') {
        $rows = $pdo->query(
            "SELECT id, ma_loai, ten_loai, COALESCE(ty_le_loi_nhuan, 0) AS ty_le_loi_nhuan
             FROM loai_san_pham ORDER BY ten_loai"
        )->fetchAll();
        jsonOut(true, '', $rows);
    }

    /* Tìm kiếm */
    if (!empty($_GET['search'])) {
        $kw  = '%' . trim($_GET['search']) . '%';
        $sql = $BASE . "
            AND (sp.ten_sp                       LIKE :k1
              OR sp.ma_sp                        LIKE :k2
              OR l.ten_loai                      LIKE :k3
              OR CAST(sp.gia_von           AS CHAR) LIKE :k4
              OR CAST(sp.gia_ban           AS CHAR) LIKE :k5
              OR CAST(sp.ty_le_loi_nhuan   AS CHAR) LIKE :k6)
            ORDER BY l.ten_loai, sp.ten_sp
        ";
        $st = $pdo->prepare($sql);
        $st->execute([':k1'=>$kw,':k2'=>$kw,':k3'=>$kw,':k4'=>$kw,':k5'=>$kw,':k6'=>$kw]);
        jsonOut(true, '', $st->fetchAll());
    }

    /* Toàn bộ danh sách */
    $rows = $pdo->query($BASE . " ORDER BY l.ten_loai, sp.ten_sp")->fetchAll();
    jsonOut(true, '', $rows);
}

/* ══════════ POST – Thêm sản phẩm ══════════ */
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $idLoai = intval($body['id_loai']         ?? 0);
    $ten    = trim($body['tenSanPham']         ?? '');
    $gv     = floatval($body['giaVon']         ?? 0);
    $lnSP   = floatval($body['loiNhuanSanPham']?? 0);
    $lnLoai = floatval($body['loiNhuanLoai']   ?? 0);

    if (!$idLoai || $ten === '' || $gv <= 0) {
        jsonOut(false, 'Vui lòng nhập đủ: Loại, Tên sản phẩm và Giá vốn!', null, 422);
    }

    // Auto-sinh ma_sp: SPxxx (tiếp theo sau max id hiện có)
    $maxId = (int) $pdo->query("SELECT IFNULL(MAX(id), 0) FROM san_pham")->fetchColumn();
    $maSP  = 'SP' . str_pad((string)($maxId + 1), 3, '0', STR_PAD_LEFT);

    // Kiểm tra ma_sp trùng (phòng race condition)
    while ($pdo->prepare("SELECT id FROM san_pham WHERE ma_sp = ?")->execute([$maSP])
           && $pdo->prepare("SELECT id FROM san_pham WHERE ma_sp = ?")->execute([$maSP])) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM san_pham WHERE ma_sp = ?");
        $chk->execute([$maSP]);
        if ((int)$chk->fetchColumn() === 0) break;
        $maxId++;
        $maSP = 'SP' . str_pad((string)$maxId, 3, '0', STR_PAD_LEFT);
    }

    // Nếu có loiNhuanLoai → cập nhật ty_le cho loại (cần cột migration)
    if ($lnLoai > 0) {
        $pdo->prepare("UPDATE loai_san_pham SET ty_le_loi_nhuan = ? WHERE id = ?")
            ->execute([$lnLoai, $idLoai]);
    }

    // Tính gia_ban: ưu tiên lnSP > lnLoai
    $tyLe   = $lnSP > 0 ? $lnSP : $lnLoai;
    $giaBan = round($gv * (1 + $tyLe / 100), 2);

    $st = $pdo->prepare("
        INSERT INTO san_pham
            (ma_sp, ten_sp, id_loai, gia_von, ty_le_loi_nhuan, gia_ban, ngay_them)
        VALUES (?, ?, ?, ?, ?, ?, CURDATE())
    ");
    $st->execute([$maSP, $ten, $idLoai, $gv, $lnSP > 0 ? $lnSP : 0, $giaBan]);

    jsonOut(true, "Thêm \"$ten\" thành công! (Mã: $maSP)", [
        'id'    => (int)$pdo->lastInsertId(),
        'ma_sp' => $maSP,
    ], 201);
}

/* ══════════ PUT – Cập nhật ══════════ */
if ($method === 'PUT') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = intval($body['id']               ?? 0);
    $idLoai = intval($body['idLoai']           ?? 0);
    $ten    = trim($body['tenSanPham']         ?? '');
    $gv     = floatval($body['giaVon']         ?? 0);
    $lnSP   = (isset($body['loiNhuanSanPham']) && $body['loiNhuanSanPham'] !== '')
              ? floatval($body['loiNhuanSanPham']) : 0;
    $lnLoai = floatval($body['loiNhuanLoai']   ?? 0);

    if ($id <= 0 || $ten === '' || $gv <= 0) {
        jsonOut(false, 'Dữ liệu không hợp lệ!', null, 422);
    }

    // Lấy ty_le của loại hiện tại (để tính gia_ban đúng nếu lnSP = 0)
    $tlLoai = 0;
    if ($idLoai > 0) {
        $q = $pdo->prepare("SELECT COALESCE(ty_le_loi_nhuan, 0) FROM loai_san_pham WHERE id = ?");
        $q->execute([$idLoai]);
        $tlLoai = floatval($q->fetchColumn() ?: 0);
    }

    // Cập nhật ty_le loại nếu admin nhập mới
    if ($idLoai > 0 && $lnLoai > 0 && $lnLoai != $tlLoai) {
        $pdo->prepare("UPDATE loai_san_pham SET ty_le_loi_nhuan = ? WHERE id = ?")
            ->execute([$lnLoai, $idLoai]);
        $tlLoai = $lnLoai;
    }

    // Tính gia_ban mới
    $tyLe   = $lnSP > 0 ? $lnSP : $tlLoai;
    $giaBan = round($gv * (1 + $tyLe / 100), 2);

    $st = $pdo->prepare("
        UPDATE san_pham
        SET ten_sp = ?, id_loai = ?, gia_von = ?,
            ty_le_loi_nhuan = ?, gia_ban = ?
        WHERE id = ?
    ");
    $st->execute([$ten, $idLoai ?: null, $gv, $lnSP, $giaBan, $id]);

    if ($st->rowCount() === 0) {
        jsonOut(false, 'Không tìm thấy sản phẩm hoặc không có thay đổi!', null, 404);
    }

    // Trả về bản ghi đã cập nhật
    $row = $pdo->prepare($BASE . " AND sp.id = ?");
    $row->execute([$id]);
    jsonOut(true, "Cập nhật \"$ten\" thành công!", $row->fetch());
}

/* ══════════ DELETE ══════════ */
if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) jsonOut(false, 'ID không hợp lệ!', null, 422);

    $chk = $pdo->prepare("SELECT ten_sp FROM san_pham WHERE id = ?");
    $chk->execute([$id]);
    $ten = $chk->fetchColumn();
    if (!$ten) jsonOut(false, 'Không tìm thấy sản phẩm!', null, 404);

    // Đã có phiếu nhập → chỉ ẩn, không xóa hẳn
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM chi_tiet_phieu_nhap WHERE id_sp = ?");
    $cnt->execute([$id]);
    if ((int)$cnt->fetchColumn() > 0) {
        $pdo->prepare("UPDATE san_pham SET hien_trang = 'an' WHERE id = ?")->execute([$id]);
        jsonOut(true, "\"$ten\" đã được ẩn (có phiếu nhập, không xóa hẳn).");
    }

    $pdo->prepare("DELETE FROM san_pham WHERE id = ?")->execute([$id]);
    jsonOut(true, "Đã xóa \"$ten\" thành công!");
}

jsonOut(false, 'Phương thức không được hỗ trợ!', null, 405);
