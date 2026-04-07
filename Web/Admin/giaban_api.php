<?php
// =============================================================
// Admin/giaban_api.php  –  API Quản lý Giá Bán
// Dùng chung DB với SanPham (shop_hoa_db)
// =============================================================
// GET                          → danh sách san_pham JOIN loai
// GET ?loai=all                → danh sách loai_san_pham (cho dropdown)
// GET ?search=<kw>             → tìm kiếm
// POST                         → thêm san_pham (auto-sinh ma_sp)
// PUT                          → cập nhật ty_le_loi_nhuan + gia_ban
// DELETE ?ma_sp=<ma_sp>        → xóa / ẩn san_pham
// =============================================================

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── Kết nối DB (inline – không include process_SanPham.php để tránh xung đột routing) ──
function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $dsn = 'mysql:host=localhost;port=3306;dbname=shop_hoa_db;charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, 'root', '', [
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
     r.ma_loai           = ma_loai của sản phẩm (dùng cho dropdown)
     r.loai              = ten_loai của loại
     r.ten_san_pham      = ten_sp
     r.loi_nhuan_loai    = ty_le_loi_nhuan của loại (có thể NULL → 0)
     r.loi_nhuan_san_pham= ty_le_loi_nhuan của sản phẩm
     r.gia_ban_tinh      = gia_ban (lưu trong DB)
     r.loi_nhuan_hieu_dung = tỉ lệ thực sự áp dụng
   ══════════════════════════════════════════════════════════ */
$BASE = "
    SELECT
        sp.ma_sp,
        sp.ma_loai,
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
    LEFT  JOIN loai_san_pham l ON sp.ma_loai = l.ma_loai
    WHERE sp.hien_trang = 'hien_thi'
";

/* ══════════ GET ══════════ */
if ($method === 'GET') {

    /* Danh sách loại cho dropdown */
    if (isset($_GET['loai']) && $_GET['loai'] === 'all') {
        $rows = $pdo->query(
            "SELECT ma_loai, ten_loai, COALESCE(ty_le_loi_nhuan, 0) AS ty_le_loi_nhuan
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
    $maLoai = trim($body['ma_loai']          ?? '');
    $ten    = trim($body['tenSanPham']        ?? '');
    $gv     = floatval($body['giaVon']        ?? 0);
    $lnSP   = floatval($body['loiNhuanSanPham'] ?? 0);
    $lnLoai = floatval($body['loiNhuanLoai']  ?? 0);

    if ($maLoai === '' || $ten === '' || $gv <= 0) {
        jsonOut(false, 'Vui lòng nhập đủ: Loại, Tên sản phẩm và Giá vốn!', null, 422);
    }

    // Auto-sinh ma_sp: SPxxx (lấy số lớn nhất từ ma_sp hiện có)
    $maxNum = (int) $pdo->query(
        "SELECT IFNULL(MAX(CAST(SUBSTRING(ma_sp, 3) AS UNSIGNED)), 0) FROM san_pham"
    )->fetchColumn();
    $maSP = 'SP' . str_pad((string)($maxNum + 1), 3, '0', STR_PAD_LEFT);

    // Kiểm tra ma_sp trùng (phòng race condition)
    $chkSP = $pdo->prepare("SELECT COUNT(*) FROM san_pham WHERE ma_sp = ?");
    $chkSP->execute([$maSP]);
    while ((int)$chkSP->fetchColumn() > 0) {
        $maxNum++;
        $maSP = 'SP' . str_pad((string)$maxNum, 3, '0', STR_PAD_LEFT);
        $chkSP->execute([$maSP]);
    }

    // Nếu có loiNhuanLoai → cập nhật ty_le cho loại
    if ($lnLoai > 0) {
        $pdo->prepare("UPDATE loai_san_pham SET ty_le_loi_nhuan = ? WHERE ma_loai = ?")
            ->execute([$lnLoai, $maLoai]);
    }

    // Tính gia_ban: ưu tiên lnSP > lnLoai
    $tyLe   = $lnSP > 0 ? $lnSP : $lnLoai;
    $giaBan = round($gv * (1 + $tyLe / 100), 2);

    $st = $pdo->prepare("
        INSERT INTO san_pham
            (ma_sp, ten_sp, ma_loai, gia_von, ty_le_loi_nhuan, gia_ban, ngay_them)
        VALUES (?, ?, ?, ?, ?, ?, CURDATE())
    ");
    $st->execute([$maSP, $ten, $maLoai ?: null, $gv, $lnSP > 0 ? $lnSP : 0, $giaBan]);

    jsonOut(true, "Thêm \"$ten\" thành công! (Mã: $maSP)", ['ma_sp' => $maSP], 201);
}

/* ══════════ PUT – Cập nhật ══════════ */
if ($method === 'PUT') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $maSP   = trim($body['ma_sp']            ?? '');
    $maLoai = trim($body['maLoai']           ?? '');
    $ten    = trim($body['tenSanPham']        ?? '');
    $gv     = floatval($body['giaVon']        ?? 0);
    $lnSP   = (isset($body['loiNhuanSanPham']) && $body['loiNhuanSanPham'] !== '')
              ? floatval($body['loiNhuanSanPham']) : 0;
    $lnLoai = floatval($body['loiNhuanLoai']  ?? 0);

    if ($maSP === '' || $ten === '' || $gv <= 0) {
        jsonOut(false, 'Dữ liệu không hợp lệ!', null, 422);
    }

    // Lấy ty_le của loại hiện tại (để tính gia_ban đúng nếu lnSP = 0)
    $tlLoai = 0;
    if ($maLoai !== '') {
        $q = $pdo->prepare("SELECT COALESCE(ty_le_loi_nhuan, 0) FROM loai_san_pham WHERE ma_loai = ?");
        $q->execute([$maLoai]);
        $tlLoai = floatval($q->fetchColumn() ?: 0);
    }

    // Cập nhật ty_le loại nếu admin nhập mới
    if ($maLoai !== '' && $lnLoai > 0 && $lnLoai != $tlLoai) {
        $pdo->prepare("UPDATE loai_san_pham SET ty_le_loi_nhuan = ? WHERE ma_loai = ?")
            ->execute([$lnLoai, $maLoai]);
        $tlLoai = $lnLoai;
    }

    // Tính gia_ban mới
    $tyLe   = $lnSP > 0 ? $lnSP : $tlLoai;
    $giaBan = round($gv * (1 + $tyLe / 100), 2);

    $st = $pdo->prepare("
        UPDATE san_pham
        SET ten_sp = ?, ma_loai = ?, gia_von = ?,
            ty_le_loi_nhuan = ?, gia_ban = ?
        WHERE ma_sp = ?
    ");
    $st->execute([$ten, $maLoai ?: null, $gv, $lnSP, $giaBan, $maSP]);

    if ($st->rowCount() === 0) {
        jsonOut(false, 'Không tìm thấy sản phẩm hoặc không có thay đổi!', null, 404);
    }

    // Trả về bản ghi đã cập nhật
    $row = $pdo->prepare($BASE . " AND sp.ma_sp = ?");
    $row->execute([$maSP]);
    jsonOut(true, "Cập nhật \"$ten\" thành công!", $row->fetch());
}

/* ══════════ DELETE ══════════ */
if ($method === 'DELETE') {
    $maSP = trim($_GET['ma_sp'] ?? '');
    if ($maSP === '') jsonOut(false, 'Mã sản phẩm không hợp lệ!', null, 422);

    $chk = $pdo->prepare("SELECT ten_sp FROM san_pham WHERE ma_sp = ?");
    $chk->execute([$maSP]);
    $ten = $chk->fetchColumn();
    if (!$ten) jsonOut(false, 'Không tìm thấy sản phẩm!', null, 404);

    // Đã có phiếu nhập → chỉ ẩn, không xóa hẳn
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM chi_tiet_phieu_nhap WHERE ma_sp = ?");
    $cnt->execute([$maSP]);
    if ((int)$cnt->fetchColumn() > 0) {
        $pdo->prepare("UPDATE san_pham SET hien_trang = 'an' WHERE ma_sp = ?")->execute([$maSP]);
        jsonOut(true, "\"$ten\" đã được ẩn (có phiếu nhập, không xóa hẳn).");
    }

    $pdo->prepare("DELETE FROM san_pham WHERE ma_sp = ?")->execute([$maSP]);
    jsonOut(true, "Đã xóa \"$ten\" thành công!");
}

jsonOut(false, 'Phương thức không được hỗ trợ!', null, 405);
