// donhang.js — Quản lý đơn hàng (Đã sửa lỗi đồng bộ với PHP)

const API_URL = 'donhang_api.php'; // Đảm bảo tên file này đúng với file PHP của bạn

// =============================================
//  KHỞI TẠO
// =============================================
document.addEventListener('DOMContentLoaded', () => {
    taiDanhSachDonHang();

    document.getElementById('formTimKiem')
        ?.addEventListener('submit', e => { 
            e.preventDefault(); 
            timKiemDonHang(); 
        });
});


// =============================================
//  1. TẢI DANH SÁCH ĐƠN HÀNG (Sửa action=get_all)
// =============================================
function taiDanhSachDonHang() {
    fetch(`${API_URL}?action=get_all`) 
        .then(r => r.json())
        .then(json => {
            if (json.success) renderBang(json.data, 'tbodyDonHang');
            else hienLoi('tbodyDonHang', json.message);
        })
        .catch(() => hienLoi('tbodyDonHang',
            'Không kết nối được server. Kiểm tra XAMPP và file PHP.'));
}


// =============================================
//  2. TÌM KIẾM / LỌC
// =============================================
function timKiemDonHang() {
    const p = new URLSearchParams({
        action:     'search',
        tu_ngay:    document.getElementById('tuNgay')?.value    ?? '',
        den_ngay:   document.getElementById('denNgay')?.value   ?? '',
        trang_thai: document.getElementById('trangThaiFilter')?.value ?? '', // Sẽ gửi về PHP là hoat_dong
        phuong:     document.getElementById('phuongFilter')?.value   ?? '',
    });

    hienLoi('tbodyTimKiem', '⏳ Đang tìm kiếm...', '#555');

    fetch(`${API_URL}?${p}`)
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                renderBang(json.data, 'tbodyTimKiem');
                const el = document.getElementById('soKetQua');
                if (el) el.textContent = json.data.length 
                    ? `Tìm thấy ${json.data.length} đơn hàng` 
                    : 'Không tìm thấy đơn hàng nào';
            } else {
                hienLoi('tbodyTimKiem', json.message);
            }
        })
        .catch(() => hienLoi('tbodyTimKiem', 'Lỗi kết nối server.'));
}


// =============================================
//  3. RENDER BẢNG (Sửa dh.id và dh.ten_khach_hang)
// =============================================
function renderBang(data, tbodyId) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    if (!data?.length) {
        tbody.innerHTML = `<tr><td colspan="6">
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Không có đơn hàng nào</p>
            </div>
        </td></tr>`;
        return;
    }

    tbody.innerHTML = data.map(dh => `
        <tr>
            <td class="ma-don">#${esc(dh.id)}</td>
            <td class="ten-khach">${esc(dh.ten_khach_hang)}</td>
            <td>${fmtNgay(dh.ngay_dat)}</td>
            <td>${esc(dh.hoat_dong)}</td>
            <td><span class="badge ${badgeClass(dh.hoat_dong)}">${esc(dh.hoat_dong)}</span></td>
            <td>
                <button class="btn-sua" onclick="xemChiTiet('${esc(dh.id)}')">
                    <i class="fas fa-eye"></i> Xem
                </button>
            </td>
        </tr>`).join('');
}


// =============================================
//  4. XEM CHI TIẾT (Sửa action=get_detail)
// =============================================
function xemChiTiet(idDonHang) {
    document.getElementById('modalBody').innerHTML = `
        <div style="text-align:center;padding:40px 20px;color:#aaa">
            <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#e74c3c;margin-bottom:12px;display:block"></i>
            <p>Đang tải dữ liệu...</p>
        </div>`;
    moModal();

    fetch(`${API_URL}?action=get_detail&id=${encodeURIComponent(idDonHang)}`)
        .then(r => r.json())
        .then(json => {
            if (json.success) renderModalChiTiet(json.data); // PHP trả về json.data
            else document.getElementById('modalBody').innerHTML = `<p>Lỗi: ${esc(json.message)}</p>`;
        })
        .catch(() => {
            document.getElementById('modalBody').innerHTML = `<p>Không kết nối được server.</p>`;
        });
}


// =============================================
//  5. RENDER NỘI DUNG MODAL
// =============================================
function renderModalChiTiet(dh) {
    const body = document.getElementById('modalBody');
    const footer = document.getElementById('modalFooter');

    document.querySelector('.modal-header h3').innerHTML = 
        `<i class="fas fa-file-invoice"></i> Chi tiết đơn &nbsp;<span style="color:#e74c3c">#${esc(dh.id)}</span>`;

    let cuoiHTML = (dh.hoat_dong === 'giao_thanh_cong' || dh.hoat_dong === 'da_huy') 
        ? `<div class="status-final-box"><h4>Trạng thái: ${esc(dh.hoat_dong)}</h4></div>`
        : buildUpdateSection(dh);

    body.innerHTML = `
        <div class="info-grid">
            <div class="info-card">
                <h4>Thông tin đơn</h4>
                <p><strong>Mã đơn:</strong> #${esc(dh.id)}</p>
                <p><strong>Ngày đặt:</strong> ${fmtNgay(dh.ngay_dat)}</p>
                <p><strong>Tổng tiền:</strong> ${fmtTien(dh.tong_tien)}</p>
            </div>
            <div class="info-card">
                <h4>Khách hàng</h4>
                <p><strong>Họ tên:</strong> ${esc(dh.ten_khach_hang)}</p>
                <p><strong>Địa chỉ:</strong> ${esc(dh.phuong)}, ${esc(dh.thanh_pho)}</p>
            </div>
        </div>
        ${cuoiHTML}
    `;

    footer.innerHTML = `<button class="btn-dong" onclick="dongModal()">Đóng</button>` + 
        ((dh.hoat_dong !== 'giao_thanh_cong' && dh.hoat_dong !== 'da_huy') 
        ? `<button class="btn-capnhat" onclick="capNhatTrangThai('${esc(dh.id)}')">Lưu</button>` : '');
}

function buildUpdateSection(dh) {
    const options = [
        { val: 'dang_cho', text: 'Đang chờ' },
        { val: 'dang_chuan_bi', text: 'Đang chuẩn bị' },
        { val: 'dang_van_chuyen', text: 'Đang giao' },
        { val: 'giao_thanh_cong', text: 'Thành công' },
        { val: 'da_huy', text: 'Hủy đơn' }
    ];

    const pills = options.map(o => `
        <label class="radio-pill ${o.val === dh.hoat_dong ? 'selected' : ''}" onclick="chonTrangThai(this, '${o.val}')">
            <input type="radio" name="ttMoi" value="${o.val}" ${o.val === dh.hoat_dong ? 'checked' : ''}>
            ${o.text}
        </label>`).join('');

    return `<div class="update-card">
                <h4>Cập nhật trạng thái</h4>
                <div class="radio-group">${pills}</div>
                <div id="lyDoWrap" style="display:none; margin-top:10px">
                    <textarea id="lyDoHuy" placeholder="Lý do hủy..."></textarea>
                </div>
            </div>`;
}


// =============================================
//  6. CẬP NHẬT TRẠNG THÁI (Khớp biến PHP)
// =============================================
function capNhatTrangThai(idDonHang) {
    const radio = document.querySelector('input[name="ttMoi"]:checked');
    if (!radio) return hienToast('Vui lòng chọn trạng thái', 'warning');

    const hoatDong = radio.value;
    const lyDoHuy = document.getElementById('lyDoHuy')?.value || '';

    fetch(API_URL + '?action=update_status', { // Thêm action vào URL
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            id: idDonHang, 
            hoat_dong: hoatDong, 
            ly_do_huy: lyDoHuy 
        })
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            dongModal();
            hienToast('Thành công!');
            taiDanhSachDonHang();
        } else hienToast(json.message, 'error');
    });
}


// =============================================
//  7. HELPERS (Giữ nguyên các hàm bổ trợ)
// =============================================
function chonTrangThai(el, val) {
    document.querySelectorAll('.radio-pill').forEach(p => p.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('lyDoWrap').style.display = (val === 'da_huy') ? 'block' : 'none';
}

function moModal() { document.getElementById('modalChiTiet').classList.add('active'); }
function dongModal() { document.getElementById('modalChiTiet').classList.remove('active'); }

function hienToast(msg, type = 'success') {
    alert(msg); // Tạm thời dùng alert cho nhanh, bạn có thể thay bằng toast của bạn
}

function badgeClass(tt) {
    const map = { 'dang_cho': 'badge-pending', 'giao_thanh_cong': 'badge-success', 'da_huy': 'badge-danger' };
    return map[tt] || 'badge-info';
}

function fmtNgay(s) { return s ? new Date(s).toLocaleDateString('vi-VN') : ''; }
function fmtTien(n) { return Number(n || 0).toLocaleString('vi-VN') + ' ₫'; }
function esc(s) { return s ? String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])) : ''; }

function hienLoi(tbodyId, msg) {
    const tb = document.getElementById(tbodyId);
    if (tb) tb.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:red">${msg}</td></tr>`;
}