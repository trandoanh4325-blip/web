<?php
header('Content-Type: application/json');
require_once 'includes/db_connect.php'; // Gọi file kết nối

// Nhận dữ liệu JSON từ JavaScript gửi lên
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $username = $conn->real_escape_string($data['username']);
    $full_name = $conn->real_escape_string($data['name']);
    $phone = $conn->real_escape_string($data['phone']);
    $address = $conn->real_escape_string($data['address']);
    $email = $conn->real_escape_string($data['email']);
    $password = $data['password'];

    // 1. Kiểm tra xem Email hoặc Username đã tồn tại chưa
    $check_sql = "SELECT id FROM users WHERE email = '$email' OR username = '$username'";
    $result = $conn->query($check_sql);

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tên đăng nhập hoặc Email đã được sử dụng!']);
        exit();
    }

    // 2. Mã hóa mật khẩu (Tuyệt đối không lưu mật khẩu thô)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 3. Insert dữ liệu vào bảng
    $insert_sql = "INSERT INTO users (username, full_name, phone, address, email, password) 
                   VALUES ('$username', '$full_name', '$phone', '$address', '$email', '$hashed_password')";

    if ($conn->query($insert_sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Đăng ký thành công! Mời bạn đăng nhập.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Không nhận được dữ liệu.']);
}
$conn->close();
?>