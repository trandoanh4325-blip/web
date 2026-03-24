<?php
$host = "localhost";
$user = "root"; // Tên user MySQL của bạn (thường ở XAMPP là root)
$pass = ""; // Mật khẩu MySQL (thường ở XAMPP là rỗng)
$db_name = "shop_hoa_"; // Đổi thành tên database bạn vừa tạo

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    die("Kết nối CSDL thất bại: " . $conn->connect_error);
}
// Đảm bảo tiếng Việt không bị lỗi font
$conn->set_charset("utf8mb4");
?>