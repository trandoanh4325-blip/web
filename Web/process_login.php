<?php
session_start(); // Khởi tạo session để lưu trạng thái đăng nhập
header('Content-Type: application/json');
require_once 'includes/db_connect.php'; 

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $email = $conn->real_escape_string($data['email']);
    $password = $data['password'];

    // Tìm user bằng Email
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Kiểm tra mật khẩu (vì lúc đăng ký mình đã mã hóa hash)
        if (password_verify($password, $user['password'])) {
           // KIỂM TRA TÀI KHOẢN CÓ BỊ KHÓA HAY KHÔNG
        if (isset($user['status']) && $user['status'] === 'locked') {
            // Nếu bị khóa, trả về thông báo lỗi dạng JSON (vì bạn đang dùng fetch/AJAX)
            echo json_encode([
                'status' => 'error', 
                'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Quản trị viên!'
            ]);
            exit(); 
        }
            
            // Lưu thông tin vào Session để dùng cho Giỏ hàng và Trang cá nhân sau này
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            echo json_encode([
                'status' => 'success', 
                'message' => 'Đăng nhập thành công! Đang chuyển hướng...',
                'redirect' => 'User/User.php
                ' // Chuyển về trang của bạn
            ]);
        } else {
            // Có email nhưng sai mật khẩu
            echo json_encode(['status' => 'error', 'message' => 'Mật khẩu không chính xác!']);
        }
    } else {
        // Không tìm thấy email trong CSDL
        echo json_encode(['status' => 'error', 'message' => 'Tài khoản không tồn tại!']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Không nhận được dữ liệu.']);
}
$conn->close();
?>