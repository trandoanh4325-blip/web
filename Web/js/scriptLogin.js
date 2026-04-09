// Xử lý nếu ảnh logo bị lỗi đường dẫn
        function handleImageError(imgElement) {
            imgElement.style.display = 'none';
            document.getElementById('logoFallback').style.display = 'block';
        }

        // Logic Xử lý Form Đăng Nhập
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Ngăn chặn form tải lại trang

            const emailInput = document.getElementById('email').value.trim();
            const passwordInput = document.getElementById('password').value.trim();
            const loginBtn = document.getElementById('loginBtn');

            // Kiểm tra rỗng
            if(emailInput === '' || passwordInput === '') {
                showToast('Lỗi xác thực', 'Vui lòng điền đầy đủ Email và Mật khẩu.', 'error');
                return;
            }

            // Hiệu ứng đang tải cho nút bấm (tuỳ chọn thêm)
            const originalBtnText = loginBtn.innerText;
            loginBtn.innerText = 'Đang xử lý...';
            loginBtn.disabled = true;

            // Giả lập gọi API kiểm tra đăng nhập (delay 1 giây)
            setTimeout(() => {
                loginBtn.innerText = originalBtnText;
                loginBtn.disabled = false;


                if (emailInput === 'hoangdanghau@gmail.com' && passwordInput === '12345') {
                    // Thành công
                    showToast('Thành công', 'Đăng nhập thành công! Đang chuyển hướng...', 'success');
                    
                    // Chuyển hướng sang trang Admin.php sau 1.5 giây
                    setTimeout(() => { window.location.href = "Admin.php"; }, 1000);
                } else {
                    // Thất bại
                    showToast('Đăng nhập thất bại', 'Sai email hoặc mật khẩu. Vui lòng thử lại!', 'error');
                }
            }, 1000);
        });

        // Hàm tạo và hiển thị Toast Popup
        function showToast(title, message, type = 'error') {
            const container = document.getElementById('toastContainer');
            
            // Tạo element toast
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            // Cấu trúc HTML của toast
            toast.innerHTML = `
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-msg">${message}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            `;
            
            // Thêm vào container
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => {
                toast.classList.add('show');
            }, 10); // Cần 1 khoảng trễ nhỏ để CSS transition hoạt động

            // Tự động tắt sau 4 giây
            setTimeout(() => {
                toast.classList.remove('show');
                // Xoá element sau khi transition kết thúc
                setTimeout(() => {
                    toast.remove();
                }, 400); 
            }, 4000);
        }