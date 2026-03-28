<?php
// ============================================
// API QUẢN LÝ SẢN PHẨM
// ============================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Kết nối database
$servername = "localhost";
$username = "root";
$password = ""; // Thay đổi theo mật khẩu của bạn
$dbname = "shop_hoa"; // Thay đổi theo tên database của bạn

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Kết nối database thất bại: ' . $conn->connect_error]));
}

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$resource = $request_uri[0] ?? '';

// Router API
switch ($resource) {
    case 'loai-san-pham':
        handle_loai_san_pham($conn, $request_method);
        break;
    case 'san-pham':
        handle_san_pham($conn, $request_method);
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint không tìm thấy']);
        break;
}

$conn->close();

// ========== HÀM QUẢN LÝ LOẠI SẢN PHẨM ==========

function handle_loai_san_pham($conn, $method) {
    if ($method === 'GET') {
        get_all_loai_san_pham($conn);
    } elseif ($method === 'POST') {
        create_loai_san_pham($conn);
    } elseif ($method === 'PUT') {
        update_loai_san_pham($conn);
    } elseif ($method === 'DELETE') {
        delete_loai_san_pham($conn);
    }
}

function get_all_loai_san_pham($conn) {
    $sql = "SELECT * FROM loai_san_pham ORDER BY ngay_them DESC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => []]);
    }
}

function create_loai_san_pham($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['ten_loai']) || !isset($data['ngay_them'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }

    $ma_loai = 'L' . str_pad(count_loai_san_pham($conn) + 1, 3, '0', STR_PAD_LEFT);
    $ten_loai = $conn->real_escape_string($data['ten_loai']);
    $ngay_them = $conn->real_escape_string($data['ngay_them']);

    $sql = "INSERT INTO loai_san_pham (ma_loai, ten_loai, ngay_them) 
            VALUES ('$ma_loai', '$ten_loai', '$ngay_them')";

    if ($conn->query($sql) === TRUE) {
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Thêm loại sản phẩm thành công', 'id' => $conn->insert_id]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
    }
}

function update_loai_san_pham($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        return;
    }

    $id = intval($data['id']);
    $ten_loai = $conn->real_escape_string($data['ten_loai']);
    $ngay_them = $conn->real_escape_string($data['ngay_them']);

    $sql = "UPDATE loai_san_pham SET ten_loai = '$ten_loai', ngay_them = '$ngay_them' WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
    }
}

function delete_loai_san_pham($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        return;
    }

    $id = intval($data['id']);

    // Kiểm tra xem có sản phẩm nào dùng loại này không
    $check_sql = "SELECT COUNT(*) as count FROM san_pham WHERE id_loai = $id";
    $result = $conn->query($check_sql);
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Không thể xóa loại này vì còn sản phẩm']);
        return;
    }

    $sql = "DELETE FROM loai_san_pham WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Xóa thành công']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
    }
}

// ========== HÀM QUẢN LÝ SẢN PHẨM ==========

function handle_san_pham($conn, $method) {
    if ($method === 'GET') {
        get_all_san_pham($conn);
    } elseif ($method === 'POST') {
        create_san_pham($conn);
    } elseif ($method === 'PUT') {
        update_san_pham($conn);
    } elseif ($method === 'DELETE') {
        delete_san_pham($conn);
    }
}

function get_all_san_pham($conn) {
    $sql = "SELECT sp.*, lsp.ten_loai 
            FROM san_pham sp
            LEFT JOIN loai_san_pham lsp ON sp.id_loai = lsp.id
            ORDER BY sp.ngay_them DESC";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => []]);
    }
}

function create_san_pham($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['ma_sp']) || !isset($data['ten_sp'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }

    // Kiểm tra mã sản phẩm trùng
    $check_sql = "SELECT COUNT(*) as count FROM san_pham WHERE ma_sp = '" . $conn->real_escape_string($data['ma_sp']) . "'";
    $result = $conn->query($check_sql);
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Mã sản phẩm này đã tồn tại']);
        return;
    }

    $ma_sp = $conn->real_escape_string($data['ma_sp']);
    $ten_sp = $conn->real_escape_string($data['ten_sp']);
    $id_loai = intval($data['id_loai'] ?? 1);
    $mo_ta = $conn->real_escape_string($data['mo_ta'] ?? '');
    $so_luong_ton = intval($data['so_luong_ton'] ?? 0);
    $gia_von = floatval($data['gia_von'] ?? 0);
    $gia_ban = floatval($data['gia_ban'] ?? 0);
    $hinh_anh = $conn->real_escape_string($data['hinh_anh'] ?? '');
    $ngay_them = $conn->real_escape_string($data['ngay_them'] ?? date('Y-m-d'));

    $sql = "INSERT INTO san_pham (ma_sp, ten_sp, id_loai, mo_ta, so_luong_ton, gia_von, gia_ban, hinh_anh, ngay_them)
            VALUES ('$ma_sp', '$ten_sp', $id_loai, '$mo_ta', $so_luong_ton, $gia_von, $gia_ban, '$hinh_anh', '$ngay_them')";

    if ($conn->query($sql) === TRUE) {
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Thêm sản phẩm thành công', 'id' => $conn->insert_id]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
    }
}

function update_san_pham($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        return;
    }

    $id = intval($data['id']);
    $ma_sp = $conn->real_escape_string($data['ma_sp']);
    $ten_sp = $conn->real_escape_string($data['ten_sp']);
    $id_loai = intval($data['id_loai'] ?? 1);
    $mo_ta = $conn->real_escape_string($data['mo_ta'] ?? '');
    $so_luong_ton = intval($data['so_luong_ton'] ?? 0);
    $gia_von = floatval($data['gia_von'] ?? 0);
    $gia_ban = floatval($data['gia_ban'] ?? 0);
    $hinh_anh = $conn->real_escape_string($data['hinh_anh'] ?? '');

    $sql = "UPDATE san_pham SET 
            ma_sp = '$ma_sp',
            ten_sp = '$ten_sp',
            id_loai = $id_loai,
            mo_ta = '$mo_ta',
            so_luong_ton = $so_luong_ton,
            gia_von = $gia_von,
            gia_ban = $gia_ban";
    
    if (!empty($hinh_anh)) {
        $sql .= ", hinh_anh = '$hinh_anh'";
    }
    
    $sql .= " WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Cập nhật sản phẩm thành công']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
    }
}

function delete_san_pham($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        return;
    }

    $id = intval($data['id']);
    $sql = "DELETE FROM san_pham WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
    }
}

// ========== HÀM HỖ TRỢ ==========

function count_loai_san_pham($conn) {
    $sql = "SELECT COUNT(*) as count FROM loai_san_pham";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'];
}

?>