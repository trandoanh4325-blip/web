document.addEventListener('DOMContentLoaded', () => {
    const avatarContainer = document.getElementById('avatar-container');
    const avatarInput = document.getElementById('avatar-input');
    const avatarImage = document.getElementById('avatar-image');
    const defaultIcon = document.getElementById('default-avatar-icon');
    const removeBtn = document.getElementById('remove-avatar-btn');

    // Chức năng Avatar giữ nguyên của bạn
    if (avatarContainer && avatarInput && avatarImage && defaultIcon && removeBtn) {
        const savedAvatar = localStorage.getItem('userAvatar');
        if (savedAvatar) {
            avatarImage.src = savedAvatar;
            avatarImage.style.display = 'block';
            defaultIcon.style.display = 'none';
            removeBtn.style.display = 'flex'; 
        }

        avatarContainer.addEventListener('click', () => {
            avatarInput.click();
        });

        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation(); 
            localStorage.removeItem('userAvatar');
            avatarImage.src = '';
            avatarImage.style.display = 'none';
            defaultIcon.style.display = 'block';
            removeBtn.style.display = 'none'; 
        });

        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageUrl = e.target.result;
                    avatarImage.src = imageUrl;
                    avatarImage.style.display = 'block';
                    defaultIcon.style.display = 'none';
                    removeBtn.style.display = 'flex'; 
                    localStorage.setItem('userAvatar', imageUrl);
                }
                reader.readAsDataURL(file); 
            }
        });
    }
});

// ==================== LOGIC TRANG ĐỔI THÔNG TIN ====================

// 1. Khai báo các input và bộ luật kiểm tra (không có required)
const updInputs = {
    username: document.getElementById('upd-username'),
    fullname: document.getElementById('upd-fullname'),
    phone: document.getElementById('upd-phone'),
    address: document.getElementById('upd-address'),
    email: document.getElementById('upd-email'),
    password: document.getElementById('upd-password')
};

const updValidators = {
    username: {
        regex: /^[a-zA-Z0-9_]{3,20}$/,
        errorMsg: "Tên đăng nhập không dấu, không khoảng trắng, 3-20 ký tự."
    },
    email: {
        regex: /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/,
        errorMsg: "Email không đúng định dạng (VD: abc@gmail.com)."
    },
    password: {
        regex: /^.{6,}$/,
        errorMsg: "Mật khẩu phải có ít nhất 6 ký tự."
    },
    phone: {
        regex: /^(0[3|5|7|8|9])+([0-9]{8})\b/,
        errorMsg: "Số điện thoại không hợp lệ (10 số, bắt đầu 03, 05, 07, 08, 09)."
    }
    // fullname và address không có regex cụ thể, gõ gì cũng được
};

// 2. Hàm kiểm tra từng trường
function validateUpdInput(field) {
    const inputElement = updInputs[field];
    if (!inputElement) return true; // Bỏ qua nếu không tìm thấy input trên trang

    const errorElement = document.getElementById(`err-upd-${field}`);
    const value = inputElement.value.trim();
    const rule = updValidators[field];

    // NẾU BỎ TRỐNG -> Hợp lệ (Vì đây là form cập nhật, không nhập nghĩa là không đổi)
    if (value === "") {
        if (errorElement) errorElement.innerText = "";
        inputElement.classList.remove('invalid-input', 'valid-input');
        return true; 
    }

    // NẾU CÓ NHẬP -> Bắt đầu kiểm tra Regex (nếu trường đó có luật Regex)
    if (rule && rule.regex && !rule.regex.test(value)) {
        if (errorElement) errorElement.innerText = `⚠️ ${rule.errorMsg}`;
        inputElement.classList.add('invalid-input');
        inputElement.classList.remove('valid-input');
        return false;
    }

    // NẾU NHẬP ĐÚNG LUẬT
    if (errorElement) errorElement.innerText = "";
    inputElement.classList.remove('invalid-input');
    inputElement.classList.add('valid-input'); // Đổi màu viền thành xanh báo hiệu OK
    return true;
}

// 3. Bắt sự kiện Gõ phím (input) và Rời ô nhập (blur) cho toàn bộ form
Object.keys(updInputs).forEach(field => {
    if (updInputs[field]) {
        updInputs[field].addEventListener('input', () => validateUpdInput(field));
        updInputs[field].addEventListener('blur', () => validateUpdInput(field));
    }
});

// 4. Xử lý khi bấm nút "Lưu thay đổi"
window.handleSaveInfo = function(e) {
    if(e) e.preventDefault(); // Chặn reload trang
    
    // Kiểm tra toàn bộ form 1 lần nữa trước khi gửi
    let isValidForm = true;
    Object.keys(updInputs).forEach(field => {
        if (!validateUpdInput(field)) {
            isValidForm = false;
        }
    });

    // Nếu có ô nào nhập sai định dạng thì dừng lại báo lỗi
    if (!isValidForm) {
        alert("⚠️ Vui lòng sửa các thông tin đang bị lỗi (chữ màu đỏ) trước khi lưu!");
        return; 
    }
    
    // Nếu pass hết (để trống hoặc nhập đúng định dạng) -> Thu thập dữ liệu
    const updateData = {
        username: updInputs.username.value.trim(),
        fullname: updInputs.fullname.value.trim(),
        phone: updInputs.phone.value.trim(),
        address: updInputs.address.value.trim(),
        email: updInputs.email.value.trim(),
        password: updInputs.password.value.trim()
    };

    // Gửi qua PHP
    fetch('process_update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(updateData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Hiện Modal thành công
            const modal = document.getElementById('custom-modal');
            const modalContent = document.getElementById('modal-content-box');
            
            if (modal && modalContent) {
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.style.opacity = '1';
                    modalContent.style.transform = 'scale(1)';
                }, 10);
            }
        } else {
            alert("Lỗi từ hệ thống: " + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Lỗi kết nối đến máy chủ! Vui lòng nhấn F12 kiểm tra.");
    });
};

window.closeModal = function() {
    const modal = document.getElementById('custom-modal');
    const modalContent = document.getElementById('modal-content-box');
    
    if (modal && modalContent) {
        modal.style.opacity = '0';
        modalContent.style.transform = 'scale(0.8)';
        
        // Đóng xong thì quay về trang thông tin
        setTimeout(() => {
            modal.style.display = 'none';
            window.location.href = 'ThongTin.php';
        }, 300);
    }
};