<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    // ---------------------------------------------------------
    // 1. CẢNH BÁO SẮP HẾT HÀNG
    // ---------------------------------------------------------
    case 'canh_bao':
        $nguong = (int)$_POST['nguong'];
        
        $sql = "SELECT sp.ma_sp, sp.ten_sp, l.ten_loai, sp.so_luong_ton 
                FROM san_pham sp
                LEFT JOIN loai_san_pham l ON sp.ma_loai = l.ma_loai
                WHERE sp.so_luong_ton <= $nguong
                ORDER BY sp.so_luong_ton ASC";
                
        $result = $conn->query($sql);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
        break;

    // ---------------------------------------------------------
    // 2. TỒN KHO TẠI 1 THỜI ĐIỂM (Tính toán ngược)
    // ---------------------------------------------------------
    case 'ton_kho_thoi_diem':
        $ma_loai = $conn->real_escape_string($_POST['ma_loai']);
        $ngay = $conn->real_escape_string($_POST['ngay']); // Định dạng YYYY-MM-DD
        
        $dieu_kien_loai = "";
        if (!empty($ma_loai)) {
            $dieu_kien_loai = " AND sp.ma_loai = '$ma_loai' ";
        }

        // Công thức: Tồn lúc đó = Tồn hiện tại - Nhập(từ lúc đó đến nay) + Xuất(từ lúc đó đến nay)
        $sql = "SELECT 
                    sp.ma_sp, 
                    sp.ten_sp, 
                    l.ten_loai, 
                    sp.so_luong_ton as ton_hien_tai,
                    COALESCE((
                        SELECT SUM(ctpn.so_luong)
                        FROM chi_tiet_phieu_nhap ctpn
                        JOIN phieu_nhap pn ON ctpn.ma_phieu = pn.ma_phieu
                        WHERE ctpn.ma_sp = sp.ma_sp 
                          AND pn.trang_thai = 'hoan_thanh' 
                          AND DATE(pn.ngay_nhap) > '$ngay'
                    ), 0) AS nhap_sau_do,
                    COALESCE((
                        SELECT SUM(ctdh.so_luong)
                        FROM chi_tiet_don_hang ctdh
                        JOIN don_hang dh ON ctdh.ma_don = dh.ma_don
                        WHERE ctdh.ma_sp = sp.ma_sp 
                          AND dh.hoat_dong != 'da_huy' 
                          AND DATE(dh.ngay_dat) > '$ngay'
                    ), 0) AS xuat_sau_do
                FROM san_pham sp
                LEFT JOIN loai_san_pham l ON sp.ma_loai = l.ma_loai
                WHERE 1=1 $dieu_kien_loai
                ORDER BY sp.ma_sp ASC";

        $result = $conn->query($sql);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Tính toán ra con số chốt cuối cùng
                $ton_luc_do = $row['ton_hien_tai'] - $row['nhap_sau_do'] + $row['xuat_sau_do'];
                $row['ton_luc_do'] = max(0, $ton_luc_do); // Đảm bảo không âm do sai số
                $data[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
        break;

    // ---------------------------------------------------------
    // 3. BÁO CÁO TỔNG NHẬP - XUẤT TRONG KHOẢNG THỜI GIAN
    // ---------------------------------------------------------
    case 'nhap_xuat':
        $tu_ngay = $conn->real_escape_string($_POST['tu_ngay']);
        $den_ngay = $conn->real_escape_string($_POST['den_ngay']);

        $sql = "SELECT 
                    sp.ma_sp, 
                    sp.ten_sp, 
                    sp.so_luong_ton,
                    COALESCE((
                        SELECT SUM(ctpn.so_luong)
                        FROM chi_tiet_phieu_nhap ctpn
                        JOIN phieu_nhap pn ON ctpn.ma_phieu = pn.ma_phieu
                        WHERE ctpn.ma_sp = sp.ma_sp 
                          AND pn.trang_thai = 'hoan_thanh' 
                          AND DATE(pn.ngay_nhap) BETWEEN '$tu_ngay' AND '$den_ngay'
                    ), 0) AS tong_nhap,
                    COALESCE((
                        SELECT SUM(ctdh.so_luong)
                        FROM chi_tiet_don_hang ctdh
                        JOIN don_hang dh ON ctdh.ma_don = dh.ma_don
                        WHERE ctdh.ma_sp = sp.ma_sp 
                          AND dh.hoat_dong != 'da_huy' 
                          AND DATE(dh.ngay_dat) BETWEEN '$tu_ngay' AND '$den_ngay'
                    ), 0) AS tong_xuat
                FROM san_pham sp
                HAVING tong_nhap > 0 OR tong_xuat > 0 
                ORDER BY sp.ma_sp ASC";
        // Lệnh HAVING giúp chỉ lấy ra các SP có phát sinh giao dịch trong kỳ, bảng sẽ bớt bị rác

        $result = $conn->query($sql);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Hành động không xác định']);
        break;
}
$conn->close();
?>