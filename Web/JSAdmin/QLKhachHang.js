// ==========================================
// TÌM KIẾM KHÁCH HÀNG (Real-time)
// ==========================================
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            // Chuyển chữ gõ vào thành chữ thường để dễ so sánh
            const searchText = this.value.toLowerCase().trim();
            const tableRows = document.querySelectorAll('.table-khachhang tbody tr');

            tableRows.forEach(row => {
                // Bỏ qua dòng "Chưa có khách hàng nào..." (nếu bảng trống)
                if (row.cells.length <= 1) return;

                // Lấy nội dung các cột (Tài khoản, Tên, Email, SĐT)
                const username = row.cells[1].innerText.toLowerCase();
                const fullname = row.cells[2].innerText.toLowerCase();
                const email = row.cells[3].innerText.toLowerCase();
                const phone = row.cells[4].innerText.toLowerCase();

                // Kiểm tra xem chữ gõ vào có khớp với bất kỳ trường nào không
                if (username.includes(searchText) || 
                    fullname.includes(searchText) || 
                    email.includes(searchText) || 
                    phone.includes(searchText)) {
                    row.style.display = ''; // Hiện dòng
                } else {
                    row.style.display = 'none'; // Ẩn dòng
                }
            });
        });
    }
});


// ==========================================
// QUẢN LÝ MODAL THÊM KHÁCH HÀNG
// ==========================================
function openAddModal() {
    document.getElementById('add-user-modal').style.display = 'block';
}

function closeAddModal() {
    document.getElementById('add-user-modal').style.display = 'none';
    document.getElementById('form-add-user').reset();
}

// 1. Gọi API Thêm Khách Hàng
function handleAddUser(e) {
    e.preventDefault();
    const data = {
        action: 'add',
        username: document.getElementById('add-username').value.trim(),
        fullname: document.getElementById('add-fullname').value.trim(),
        email: document.getElementById('add-email').value.trim(),
        address: document.getElementById('add-address').value.trim(),
        phone: document.getElementById('add-phone').value.trim(),
        password: document.getElementById('add-password').value.trim()
    };

    fetch('process_khachhang.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        closeAddModal();
        const modal = document.getElementById('custom-confirm-modal');
        const icon = document.getElementById('modal-icon-status');
        
        document.getElementById('btn-confirm').style.display = 'none';
        document.getElementById('btn-cancel').style.display = 'none';
        document.getElementById('btn-ok').style.display = 'block';

        if (data.status === 'success') {
            icon.className = 'fa-solid fa-circle-check custom-modal-icon success';
            document.getElementById('modal-title-text').innerText = 'Thành công!';
            document.getElementById('modal-message-text').innerText = data.message;
        } else {
            icon.className = 'fa-solid fa-circle-xmark custom-modal-icon error';
            document.getElementById('modal-title-text').innerText = 'Thất bại!';
            document.getElementById('modal-message-text').innerText = "Lỗi: " + data.message;
        }
        modal.classList.add('active');
    })
    .catch(err => {
        console.error(err);
        closeAddModal();
        const modal = document.getElementById('custom-confirm-modal');
        const icon = document.getElementById('modal-icon-status');
        
        document.getElementById('btn-confirm').style.display = 'none';
        document.getElementById('btn-cancel').style.display = 'none';
        document.getElementById('btn-ok').style.display = 'block';

        icon.className = 'fa-solid fa-circle-xmark custom-modal-icon error';
        document.getElementById('modal-title-text').innerText = 'Lỗi hệ thống!';
        document.getElementById('modal-message-text').innerText = 'Không thể kết nối đến máy chủ.';
        modal.classList.add('active');
    });
}

// ==========================================
// LOGIC POPUP XÁC NHẬN CHUNG (RESET / KHÓA)
// ==========================================
let targetResetId = null;
let targetLockId = null;
let targetLockStatus = null;

// Đóng Popup
function closeCustomModal() {
    document.getElementById('custom-confirm-modal').classList.remove('active');
    targetResetId = null;
    targetLockId = null;

    if (document.getElementById('btn-ok').style.display === 'block') {
        location.reload();
    }
}

// 2. Gọi API Khóa / Mở Khóa bằng Popup
function toggleLock(userId, currentStatus) {
    targetLockId = userId;
    targetLockStatus = currentStatus;
    const modal = document.getElementById('custom-confirm-modal');
    
    const actionText = currentStatus === 'active' ? 'Khóa' : 'Mở Khóa';
    const actionMsg = currentStatus === 'active' ? 'khóa' : 'mở khóa';
    
    document.getElementById('modal-icon-status').className = 'fa-solid fa-circle-exclamation custom-modal-icon warning';
    document.getElementById('modal-title-text').innerText = `Xác nhận ${actionText}`;
    document.getElementById('modal-message-text').innerText = `Bạn có chắc chắn muốn ${actionMsg} tài khoản này không?`;
    
    document.getElementById('btn-confirm').onclick = executeLock;

    document.getElementById('btn-confirm').style.display = 'block';
    document.getElementById('btn-cancel').style.display = 'block';
    document.getElementById('btn-ok').style.display = 'none';

    modal.classList.add('active');
}

function executeLock() {
    if (!targetLockId) return;

    fetch('process_khachhang.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle_lock', user_id: targetLockId, current_status: targetLockStatus })
    })
    .then(res => res.json())
    .then(data => {
        const icon = document.getElementById('modal-icon-status');
        
        document.getElementById('btn-confirm').style.display = 'none';
        document.getElementById('btn-cancel').style.display = 'none';
        document.getElementById('btn-ok').style.display = 'block';

        if (data.status === 'success') {
            icon.className = 'fa-solid fa-circle-check custom-modal-icon success';
            document.getElementById('modal-title-text').innerText = 'Thành công!';
            document.getElementById('modal-message-text').innerText = 'Đã cập nhật trạng thái tài khoản.';
        } else {
            icon.className = 'fa-solid fa-circle-xmark custom-modal-icon error';
            document.getElementById('modal-title-text').innerText = 'Thất bại!';
            document.getElementById('modal-message-text').innerText = "Lỗi: " + data.message;
        }
    })
    .catch(err => console.error(err));
}

// 3. Hiển thị Popup Xác nhận Reset Mật Khẩu
function resetPassword(userId) {
    targetResetId = userId;
    const modal = document.getElementById('custom-confirm-modal');
    
    document.getElementById('modal-icon-status').className = 'fa-solid fa-circle-exclamation custom-modal-icon warning';
    document.getElementById('modal-title-text').innerText = 'Xác nhận Reset';
    document.getElementById('modal-message-text').innerText = 'Bạn có chắc chắn muốn khôi phục mật khẩu tài khoản này về mặc định (123456) không?';
    
    document.getElementById('btn-confirm').onclick = executeReset;

    document.getElementById('btn-confirm').style.display = 'block';
    document.getElementById('btn-cancel').style.display = 'block';
    document.getElementById('btn-ok').style.display = 'none';

    modal.classList.add('active');
}

function executeReset() {
    if (!targetResetId) return;

    fetch('process_khachhang.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'reset_password', user_id: targetResetId })
    })
    .then(res => res.json())
    .then(data => {
        const icon = document.getElementById('modal-icon-status');
        
        document.getElementById('btn-confirm').style.display = 'none';
        document.getElementById('btn-cancel').style.display = 'none';
        document.getElementById('btn-ok').style.display = 'block';

        if (data.status === 'success') {
            icon.className = 'fa-solid fa-circle-check custom-modal-icon success';
            document.getElementById('modal-title-text').innerText = 'Thành công!';
            document.getElementById('modal-message-text').innerText = data.message;
        } else {
            icon.className = 'fa-solid fa-circle-xmark custom-modal-icon error';
            document.getElementById('modal-title-text').innerText = 'Thất bại!';
            document.getElementById('modal-message-text').innerText = "Lỗi: " + data.message;
        }
    })
    .catch(err => console.error(err));
}