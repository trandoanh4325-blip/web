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
                wrapper.style.height = '650px'; 
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

        // Xử lý logic Đăng nhập kết nối với Database
function handleLogin(e) {
    e.preventDefault(); 
    const emailInput = document.getElementById('login-email').value.trim();
    const passwordInput = document.getElementById('login-password').value.trim();

    if (!emailInput || !passwordInput) {
        showModal("Lỗi", "⚠️ Vui lòng nhập đầy đủ email và mật khẩu.", "error");
        return;
    }

    // Đóng gói dữ liệu gửi đi
    const loginData = {
        email: emailInput,
        password: passwordInput
    };

    // Gửi yêu cầu kiểm tra đăng nhập bằng Fetch API
    fetch('process_login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(loginData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showModal("Thành công!", data.message, "success");
            
            // Chuyển hướng trang sau 2 giây
            setTimeout(function() {
                window.location.href = data.redirect; // Lấy đường dẫn từ PHP trả về
            }, 2000);
        } else {
            showModal("Thất bại", "❌ " + data.message, "error");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showModal("Lỗi", "⚠️ Không thể kết nối đến máy chủ.", "error");
    });
}

        // === CÁC BIẾN VÀ LUẬT VALIDATE (THÊM VÀO SCRIPT.JS) ===
const regInputs = {
    username: document.getElementById('reg-username'),
    email: document.getElementById('reg-email'),
    password: document.getElementById('reg-password'),
    phone: document.getElementById('reg-phone'),
    name: document.getElementById('reg-name'),
    address: document.getElementById('reg-address')
};

// Khai báo Regex và câu thông báo
const validators = {
    username: {
        required: true,
        regex: /^[a-zA-Z0-9_]{3,20}$/,
        errorMsg: "Tên đăng nhập không dấu, không khoảng trắng, 3-20 ký tự."
    },
    email: {
        required: true,
        regex: /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/,
        errorMsg: "Email không đúng định dạng (VD: abc@gmail.com)."
    },
    password: {
        required: true,
        regex: /^.{6,}$/,
        errorMsg: "Mật khẩu phải có ít nhất 6 ký tự."
    },
    phone: {
        required: false, // Không bắt buộc, nhưng nếu nhập thì phải chuẩn
        regex: /^(0[3|5|7|8|9])+([0-9]{8})\b/,
        errorMsg: "Số điện thoại không hợp lệ (10 số, bắt đầu 03, 05, 07, 08, 09)."
    }
};

// Hàm kiểm tra từng Input
function validateInput(field) {
    const inputElement = regInputs[field];
    const errorElement = document.getElementById(`err-${field}`);
    const value = inputElement.value.trim();
    const rule = validators[field];

    if (!rule) return true; // Các trường không có luật (name, address) luôn qua

    // 1. Kiểm tra bắt buộc nhập
    if (rule.required && value === "") {
        errorElement.innerText = "⚠️ Trường này là bắt buộc nhập!";
        inputElement.classList.add('invalid-input');
        inputElement.classList.remove('valid-input');
        return false;
    }

    // 2. Kiểm tra định dạng (nếu có nhập)
    if (value !== "" && rule.regex && !rule.regex.test(value)) {
        errorElement.innerText = `⚠️ ${rule.errorMsg}`;
        inputElement.classList.add('invalid-input');
        inputElement.classList.remove('valid-input');
        return false;
    }

    // 3. Hợp lệ
    errorElement.innerText = "";
    inputElement.classList.remove('invalid-input');
    if (value !== "") inputElement.classList.add('valid-input'); // Viền xanh khi chuẩn
    return true;
}

// Bắt sự kiện Gõ phím (input) và Rời ô nhập (blur)
Object.keys(validators).forEach(field => {
    regInputs[field].addEventListener('input', () => validateInput(field));
    regInputs[field].addEventListener('blur', () => validateInput(field));
});

// === THAY THẾ HÀM handleRegister CŨ BẰNG HÀM NÀY ===
function handleRegister(e) {
    e.preventDefault();
    
    // Kiểm tra toàn bộ form một lần nữa trước khi gửi
    let isValidForm = true;
    Object.keys(validators).forEach(field => {
        if (!validateInput(field)) {
            isValidForm = false;
        }
    });

    if (!isValidForm) {
        showModal("Lỗi", "⚠️ Vui lòng kiểm tra lại các thông tin màu đỏ.", "error");
        return; // Dừng lại, không gửi fetch lên Server
    }

    // Lấy dữ liệu
    const userData = {
        username: regInputs.username.value.trim(),
        name: regInputs.name.value.trim(),
        phone: regInputs.phone.value.trim(),
        address: regInputs.address.value.trim(),
        email: regInputs.email.value.trim(),
        password: regInputs.password.value.trim()
    };

    // Gửi dữ liệu qua PHP bằng Fetch API
    fetch('process_register.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showModal("Thành công!", data.message, "success");
            
            // Xóa rác trên form và chuyển tab
            setTimeout(function() {
                closeModal();
                document.getElementById('form-register').reset(); 
                
                // Reset lại màu viền xanh/đỏ
                Object.values(regInputs).forEach(input => {
                    input.classList.remove('valid-input', 'invalid-input');
                });
                Object.keys(validators).forEach(field => {
                    document.getElementById(`err-${field}`).innerText = "";
                });

                switchTab('login');
            }, 2000);
        } else {
            showModal("Lỗi", "⚠️ " + data.message, "error");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showModal("Lỗi", "⚠️ Không thể kết nối đến máy chủ.", "error");
    });
}