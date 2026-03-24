CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL, -- Dùng cho trang cá nhân
  `phone` varchar(20) NOT NULL, -- Dùng để liên hệ giao hàng
  `address` text NOT NULL, -- Địa chỉ mặc định khi xuất hóa đơn/giỏ hàng
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL, -- Mật khẩu sẽ được mã hóa dài nên để 255
  `role` enum('customer','admin') DEFAULT 'customer', -- Mặc định đăng ký là khách
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;