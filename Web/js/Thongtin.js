document.addEventListener('DOMContentLoaded', () => {
    const avatarContainer = document.getElementById('avatar-container');
    const avatarInput = document.getElementById('avatar-input');
    const avatarImage = document.getElementById('avatar-image');
    const defaultIcon = document.getElementById('default-avatar-icon');
    const removeBtn = document.getElementById('remove-avatar-btn');

    // Mệnh đề IF này giúp code chỉ chạy khi chúng ta đang ở trang ThongTin.html (Tránh lỗi null)
    if (avatarContainer && avatarInput && avatarImage && defaultIcon && removeBtn) {
        
        // 1. Tải ảnh từ LocalStorage
        const savedAvatar = localStorage.getItem('userAvatar');
        if (savedAvatar) {
            avatarImage.src = savedAvatar;
            avatarImage.style.display = 'block';
            defaultIcon.style.display = 'none';
            removeBtn.style.display = 'flex'; 
        }

        // 2. Mở hộp thoại chọn file
        avatarContainer.addEventListener('click', () => {
            avatarInput.click();
        });

        // 3. Sự kiện Gỡ Avatar
        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Ngăn click lan ra ngoài
            localStorage.removeItem('userAvatar');
            avatarImage.src = '';
            avatarImage.style.display = 'none';
            defaultIcon.style.display = 'block';
            removeBtn.style.display = 'none'; 
        });

        // 4. Xử lý khi chọn ảnh mới
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

// ==================== LOGIC TRANG ĐỔI THÔNG TIN (FORM SAVE) ====================
// Gắn hàm vào đối tượng window để HTML có thể gọi qua onsubmit và onclick
window.handleSaveInfo = function(e) {
    if(e) e.preventDefault(); // Ngăn trình duyệt tải lại trang khi submit form
    
    const modal = document.getElementById('custom-modal');
    const modalContent = document.getElementById('modal-content-box');
    
    if (modal && modalContent) {
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.style.opacity = '1';
            modalContent.style.transform = 'scale(1)';
        }, 10);
    }
};

window.closeModal = function() {
    const modal = document.getElementById('custom-modal');
    const modalContent = document.getElementById('modal-content-box');
    
    if (modal && modalContent) {
        modal.style.opacity = '0';
        modalContent.style.transform = 'scale(0.8)';
        
        // Sau khi đóng xong thì chuyển về trang Thông tin
        setTimeout(() => {
            modal.style.display = 'none';
            window.location.href = 'ThongTin.html';
        }, 300);
    }
};