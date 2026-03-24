<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db_connect.php'; // Đảm bảo đường dẫn đúng

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập!']);
    exit();
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $user_id = $_SESSION['user_id'];

    // Lấy lại dữ liệu cũ từ DB
    $sql_current = "SELECT * FROM users WHERE id = '$user_id'";
    $res = $conn->query($sql_current);
    $current = $res->fetch_assoc();

    // Logic: Nếu input trống (empty) -> lấy thông tin cũ ($current). Nếu có -> lấy thông tin mới.
    $username = !empty($data['username']) ? $conn->real_escape_string($data['username']) : $current['username'];
    $fullname = !empty($data['fullname']) ? $conn->real_escape_string($data['fullname']) : $current['full_name'];
    $phone = !empty($data['phone']) ? $conn->real_escape_string($data['phone']) : $current['phone'];
    $address = !empty($data['address']) ? $conn->real_escape_string($data['address']) : $current['address'];
    $email = !empty($data['email']) ? $conn->real_escape_string($data['email']) : $current['email'];

    // Xử lý đổi mật khẩu (nếu có nhập)
    $pass_query = "";
    if (!empty($data['password'])) {
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        $pass_query = ", password = '$hashed_password'";
    }

    // Viết câu lệnh UPDATE
    $update_sql = "UPDATE users SET 
                    username = '$username', 
                    full_name = '$fullname', 
                    phone = '$phone', 
                    address = '$address', 
                    email = '$email'
                    $pass_query
                   WHERE id = '$user_id'";

    if ($conn->query($update_sql) === TRUE) {
        // Cập nhật lại Session cho tên hiển thị
        $_SESSION['full_name'] = $fullname;
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi: ' . $conn->error]);
    }
}
$conn->close();
?>