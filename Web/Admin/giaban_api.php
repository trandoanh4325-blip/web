<?php
/**
 * giaban_api.php
 * API Quản lý Giá Bán – dùng bảng san_pham + loai_san_pham
 *
 * GET    /giaban_api.php                → danh sách tất cả SP
 * GET    /giaban_api.php?search=<kw>    → tìm kiếm
 * GET    /giaban_api.php?loai=all       → danh sách loại (cho dropdown)
 * PUT    /giaban_api.php                → cập nhật ty_le_loi_nhuan + gia_ban của 1 SP
 * PUT    /giaban_api.php  (bulk_loai)   → cập nhật ty_le cho cả loại
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

/* ── Cấu hình DB ── */
$db_host = 'localhost';
$db_name = 'shop_hoa_db';  
$db_user = 'root';
$db_pass = '';

function jsonOut(bool $ok, string $msg = '', $data = null, int $code = 200): void {
    http_response_code($code);
    $r = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    jsonOut(false, 'Kết nối CSDL thất bại: ' . $e->getMessage(), null, 500);
}

/* ══════════════════════════════════════════
   BASE SELECT – JOIN san_pham + loai_san_pham
   Tính ty_le_hieu_dung:
     - Nếu sp.ty_le_loi_nhuan > 0 → dùng của SP
     - Ngược lại                  → dùng của Loại
   Tính gia_ban_tinh theo ty_le_hieu_dung
   ══════════════════════════════════════════ */
$BASE_SELECT = "
    SELECT
        sp.id,
        sp.ma_sp,
        sp.ten_sp,
        sp.id_loai,
        l.ten_loai,
        sp.don_vi_tinh,
        sp.so_luong_ton,
        sp.gia_von,
        sp.ty_le_loi_nhuan                                       AS ty_le_rieng,
        COALESCE(l.ty_le_loi_nhuan, 0)                          AS ty_le_loai,
        IF(sp.ty_le_loi_nhuan > 0,
            sp.ty_le_loi_nhuan,
            COALESCE(l.ty_le_loi_nhuan, 0))                     AS ty_le_hieu_dung,
        sp.gia_ban,
        sp.hien_trang,
        sp.ngay_them,
        IF(sp.ty_le_loi_nhuan > 0, 'SP', 'Loai')               AS nguon_ty_le
    FROM san_pham sp
    LEFT JOIN loai_san_pham l ON sp.id_loai = l.id
";

$method = $_SERVER['REQUEST_METHOD'];

/* ══════════════════════════════════
   GET
   ══════════════════════════════════ */
if ($method === 'GET') {

    /* ── Lấy danh sách loại cho dropdown ── */
    if (isset($_GET['loai']) && $_GET['loai'] === 'all') {
        $stmt = $pdo->query("
            SELECT id, ma_loai, ten_loai,
                   COALESCE(ty_le_loi_nhuan, 0) AS ty_le_loi_nhuan
            FROM loai_san_pham
            ORDER BY ten_loai
        ");
        jsonOut(true, '', $stmt->fetchAll());
    }

    /* ── Tìm kiếm ── */
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $kw   = '%' . trim($_GET['search']) . '%';
        $sql  = $BASE_SELECT . "
            WHERE sp.hien_trang = 'hien_thi'
              AND (
                sp.ten_sp                       LIKE :kw1
                OR sp.ma_sp                     LIKE :kw2
                OR l.ten_loai                   LIKE :kw3
                OR CAST(sp.gia_von   AS CHAR)   LIKE :kw4
                OR CAST(sp.gia_ban   AS CHAR)   LIKE :kw5
                OR CAST(sp.ty_le_loi_nhuan AS CHAR) LIKE :kw6
              )
            ORDER BY sp.ten_sp
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':kw1'=>$kw, ':kw2'=>$kw, ':kw3'=>$kw,
            ':kw4'=>$kw, ':kw5'=>$kw, ':kw6'=>$kw,
        ]);
        jsonOut(true, '', $stmt->fetchAll());
    }

    /* ── Danh sách tất cả ── */
    $stmt = $pdo->query($BASE_SELECT . " WHERE sp.hien_trang = 'hien_thi' ORDER BY l.ten_loai, sp.ten_sp");
    jsonOut(true, '', $stmt->fetchAll());
}

/* ══════════════════════════════════
   PUT – cập nhật tỉ lệ lợi nhuận
   ══════════════════════════════════ */
if ($method === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? 'update_sp';

    /* ── Cập nhật 1 sản phẩm ── */
    if ($action === 'update_sp') {
        $id    = intval($body['id'] ?? 0);
        $tlln  = floatval($body['ty_le_loi_nhuan'] ?? 0);
        $gv    = null; // giá vốn KHÔNG sửa ở đây (do phiếu nhập quản lý)

        if ($id <= 0) jsonOut(false, 'ID sản phẩm không hợp lệ!', null, 422);
        if ($tlln < 0) jsonOut(false, 'Tỉ lệ lợi nhuận không được âm!', null, 422);

        /* Lấy giá vốn hiện tại và loại để tính gia_ban */
        $cur = $pdo->prepare("
            SELECT sp.gia_von, COALESCE(l.ty_le_loi_nhuan, 0) AS ty_le_loai
            FROM san_pham sp
            LEFT JOIN loai_san_pham l ON sp.id_loai = l.id
            WHERE sp.id = :id
        ");
        $cur->execute([':id' => $id]);
        $row = $cur->fetch();
        if (!$row) jsonOut(false, 'Không tìm thấy sản phẩm!', null, 404);

        // Tính ty_le hiệu dụng và gia_ban mới
        $ty_le_hd = ($tlln > 0) ? $tlln : floatval($row['ty_le_loai']);
        $gia_ban  = floatval($row['gia_von']) * (1 + $ty_le_hd / 100);

        $stmt = $pdo->prepare("
            UPDATE san_pham
            SET ty_le_loi_nhuan = :tl,
                gia_ban         = :gb
            WHERE id = :id
        ");
        $stmt->execute([':tl' => $tlln, ':gb' => round($gia_ban, 2), ':id' => $id]);

        // Trả về bản ghi mới
        $upd = $pdo->prepare($BASE_SELECT . " WHERE sp.id = :id");
        $upd->execute([':id' => $id]);
        jsonOut(true, 'Cập nhật giá bán thành công!', $upd->fetch());
    }

    /* ── Cập nhật tỉ lệ theo loại (áp dụng cho tất cả SP trong loại chưa có tỉ lệ riêng) ── */
    if ($action === 'update_loai') {
        $id_loai = intval($body['id_loai'] ?? 0);
        $tlln    = floatval($body['ty_le_loi_nhuan'] ?? 0);

        if ($id_loai <= 0) jsonOut(false, 'ID loại không hợp lệ!', null, 422);
        if ($tlln < 0)     jsonOut(false, 'Tỉ lệ không được âm!', null, 422);

        // 1. Cập nhật ty_le của loại
        $pdo->prepare("UPDATE loai_san_pham SET ty_le_loi_nhuan = :tl WHERE id = :id")
            ->execute([':tl' => $tlln, ':id' => $id_loai]);

        // 2. Cập nhật gia_ban cho tất cả SP thuộc loại này mà CHƯA có ty_le riêng
        $pdo->prepare("
            UPDATE san_pham
            SET gia_ban = gia_von * (1 + :tl / 100)
            WHERE id_loai = :id_loai
              AND (ty_le_loi_nhuan = 0 OR ty_le_loi_nhuan IS NULL)
        ")->execute([':tl' => $tlln, ':id_loai' => $id_loai]);

        // Đếm bao nhiêu SP được cập nhật
        $cnt = $pdo->prepare("
            SELECT COUNT(*) FROM san_pham
            WHERE id_loai = :id_loai AND (ty_le_loi_nhuan = 0 OR ty_le_loi_nhuan IS NULL)
        ");
        $cnt->execute([':id_loai' => $id_loai]);

        // Lấy lại danh sách SP thuộc loại
        $list = $pdo->prepare($BASE_SELECT . " WHERE sp.id_loai = :id_loai ORDER BY sp.ten_sp");
        $list->execute([':id_loai' => $id_loai]);

        jsonOut(true, 'Đã cập nhật tỉ lệ lợi nhuận cho loại và các sản phẩm liên quan!', $list->fetchAll());
    }

    jsonOut(false, 'Action không hợp lệ!', null, 422);
}

jsonOut(false, 'Phương thức không được hỗ trợ!', null, 405);
