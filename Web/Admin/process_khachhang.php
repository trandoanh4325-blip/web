<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
    exit();
}

$action = $data['action'];

switch ($action) {
    // 1. THÊM TÀI KHOẢN
    case 'add':
        $username = $conn->real_escape_string($data['username']);
        $fullname = $conn->real_escape_string($data['fullname']);
        $email = $conn->real_escape_string($data['email']);
        $address = $conn->real_escape_string($data['address']);
        $phone = $conn->real_escape_string($data['phone']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        // Kiểm tra trùng
        $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
        if ($check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Username hoặc Email đã tồn tại.']);
            exit();
        }

        $sql = "INSERT INTO users (username, full_name, email, address, phone, password, role) 
                VALUES ('$username', '$fullname', '$email', '$address', '$phone', '$password', 'customer')";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['status' => 'success', 'message' => 'Thêm tài khoản thành công!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi DB: ' . $conn->error]);
        }
        break;

    // 2. KHÓA / MỞ KHÓA TÀI KHOẢN
    case 'toggle_lock':
        $user_id = (int)$data['user_id'];
        $current_status = $data['current_status'];
        $new_status = ($current_status === 'active') ? 'locked' : 'active';

        $sql = "UPDATE users SET status = '$new_status' WHERE id = $user_id";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Không thể cập nhật trạng thái.']);
        }
        break;

    // 3. RESET MẬT KHẨU
    case 'reset_password':
        $user_id = (int)$data['user_id'];
        // Đặt mật khẩu mặc định là 123456
        $new_pass_hashed = password_hash('123456', PASSWORD_DEFAULT);

        $sql = "UPDATE users SET password = '$new_pass_hashed' WHERE id = $user_id";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['status' => 'success', 'message' => 'Mật khẩu đã được reset về: 123456']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật mật khẩu.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Hành động không xác định.']);
}

$conn->close();
?>