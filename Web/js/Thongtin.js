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
window.handleSaveInfo = function(e) {
    if(e) e.preventDefault(); // CHẶN RELOAD TRANG
    
    // Thu thập dữ liệu
    const updateData = {
        username: document.getElementById('upd-username').value.trim(),
        fullname: document.getElementById('upd-fullname').value.trim(),
        phone: document.getElementById('upd-phone').value.trim(),
        address: document.getElementById('upd-address').value.trim(),
        email: document.getElementById('upd-email').value.trim(),
        password: document.getElementById('upd-password').value.trim()
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