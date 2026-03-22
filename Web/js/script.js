// Xử lý chuyển đổi giữa Đăng nhập và Đăng ký
        function switchTab(tab) {
            const loginBtn = document.getElementById('tab-login');
            const regBtn = document.getElementById('tab-register');
            const indicator = document.getElementById('indicator');
            const formLogin = document.getElementById('form-login');
            const formRegister = document.getElementById('form-register');
            const wrapper = document.getElementById('forms-wrapper');

            if (tab === 'login') {
                loginBtn.classList.add('active');
                regBtn.classList.remove('active');
                indicator.style.transform = 'translateX(0)';
                
                formLogin.classList.remove('slide-left');
                formLogin.classList.add('active');
                formRegister.classList.remove('active');
                
                // Chỉnh chiều cao khung bọc vừa với form đăng nhập
                wrapper.style.height = '280px'; 
            } else {
                regBtn.classList.add('active');
                loginBtn.classList.remove('active');
                indicator.style.transform = 'translateX(100%)';
                
                formLogin.classList.remove('active');
                formLogin.classList.add('slide-left');
                formRegister.classList.add('active');
                
                // Chỉnh chiều cao khung bọc vừa với form đăng ký (tăng chiều cao cho trường địa chỉ)
                wrapper.style.height = '540px'; 
            }
        }

        // Xử lý Modal Thông báo Đẹp Hơn
        function showModal(title, message, type = 'success') {
            const modal = document.getElementById('custom-modal');
            const modalContent = document.getElementById('modal-content-box');
            const iconContainer = document.getElementById('modal-icon');
            const closeBtn = document.getElementById('modal-btn');
            
            document.getElementById('modal-title').innerText = title;
            document.getElementById('modal-message').innerText = message;
            
            // Đổi icon và màu sắc theo type
            if (type === 'success') {
                iconContainer.className = 'modal-icon success';
                iconContainer.innerHTML = '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
                closeBtn.style.display = 'none'; // Ẩn nút đóng nếu thành công để tự chuyển trang
            } else {
                iconContainer.className = 'modal-icon error';
                iconContainer.innerHTML = '<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
                closeBtn.style.display = 'inline-block'; // Hiện nút đóng
            }

            modal.style.display = 'flex';
            // Timeout để hiệu ứng mượt
            setTimeout(() => {
                modal.style.opacity = '1';
                modalContent.style.transform = 'scale(1)';
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('custom-modal');
            const modalContent = document.getElementById('modal-content-box');
            modal.style.opacity = '0';
            modalContent.style.transform = 'scale(0.8)';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Xử lý logic Đăng nhập từ script.js
        function handleLogin(e) {
            e.preventDefault(); 
            const emailInput = document.getElementById('login-email').value.trim();
            const passwordInput = document.getElementById('login-password').value.trim();
            
            // Tài khoản cố định
            const EMAIL_CONST = "Hoangdanghau@gmail.com";
            const PASSWORD_CONST = "1911";

            if (!emailInput || !passwordInput) {
                showModal("Lỗi", "⚠️ Vui lòng nhập đầy đủ email và mật khẩu.", "error");
            } else if (emailInput === EMAIL_CONST && passwordInput === PASSWORD_CONST) {
                showModal("Thành công!", "🎉 Đăng nhập thành công!", "success");
                setTimeout(function() {
                    window.location.href = "User/User.html";
                }, 2000);
            } else {
                showModal("Thất bại", "❌ Sai email hoặc mật khẩu!", "error");
            }
        }

        // Xử lý logic Đăng ký từ jsRegister.js
        function handleRegister(e) {
            e.preventDefault();
            const username = document.getElementById('reg-username').value.trim();
            const name = document.getElementById('reg-name').value.trim();
            const phone = document.getElementById('reg-phone').value.trim();
            const address = document.getElementById('reg-address').value.trim();
            const email = document.getElementById('reg-email').value.trim();
            const password = document.getElementById('reg-password').value.trim();
            
            if (username && name && phone && address && email && password) {
                showModal("Thành công!", "🎉 Đăng ký thành công! Mời bạn đăng nhập.", "success");
                setTimeout(function() {
                    closeModal();
                    switchTab('login');
                }, 2000);
            } else {
                showModal("Lỗi", "⚠️ Vui lòng nhập đầy đủ thông tin.", "error");
            }
        }