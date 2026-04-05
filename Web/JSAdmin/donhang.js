/* ============================================================
   donhang.js — Quản lý đơn hàng  (hoàn chỉnh)
   ============================================================ */

const API_URL = 'api_donhang.php';

/* ===== MAP TRẠNG THÁI → BADGE ===== */
const TRANG_THAI_MAP = {
    dang_cho:          { label: 'Đang chờ',          cls: 'badge-pending',  icon: 'fa-clock'           },
    dang_chuan_bi:     { label: 'Đang chuẩn bị',     cls: 'badge-info',     icon: 'fa-box-open'        },
    cho_lay_hang:      { label: 'Chờ lấy hàng',      cls: 'badge-warning',  icon: 'fa-hand-holding-box'},
    dang_van_chuyen:   { label: 'Đang vận chuyển',   cls: 'badge-status',   icon: 'fa-truck'           },
    giao_thanh_cong:   { label: 'Giao thành công',   cls: 'badge-success',  icon: 'fa-circle-check'    },
    da_huy:            { label: 'Đã hủy',             cls: 'badge-danger',   icon: 'fa-ban'             },
};

function badge(value) {
    const t = TRANG_THAI_MAP[value] || { label: value, cls: 'badge-pending', icon: 'fa-question' };
    return `<span class="badge ${t.cls}"><i class="fas ${t.icon}"></i> ${t.label}</span>`;
}

function fmt(so) {
    return Number(so || 0).toLocaleString('vi-VN') + 'đ';
}

function fmtDate(dt) {
    if (!dt) return '—';
    const d = new Date(dt);
    return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

/* ===== KHỞI TẠO ===== */
document.addEventListener('DOMContentLoaded', () => {
    taiDanhSachDonHang();

    document.getElementById('formTimKiem')?.addEventListener('submit', e => {
        e.preventDefault();
        timKiemDonHang();
    });

    // Đóng modal khi click ngoài
    document.getElementById('modalChiTiet')?.addEventListener('click', function(e) {
        if (e.target === this) dongModal();
    });
});

/* ===== TẢI BẢNG CHÍNH ===== */
function taiDanhSachDonHang() {
    fetch(`${API_URL}?action=get_all`)
        .then(r => r.json())
        .then(res => {
            if (res.ok) renderBangChinh(res.data);
            else showToast('Không tải được danh sách đơn hàng', 'error');
        })
        .catch(() => showToast('Lỗi kết nối máy chủ', 'error'));
}

/* ===== RENDER BẢNG CHÍNH (7 cột) ===== */
function renderBangChinh(data) {
    const tbody = document.getElementById('tbodyDonHang');
    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">
            <i class="fas fa-inbox"></i><p>Chưa có đơn hàng nào</p></div></td></tr>`;
        return;
    }

    tbody.innerHTML = data.map(dh => `
        <tr>
            <td class="ma-don">#${dh.id} <small style="color:#aaa;font-size:11px">${dh.ma_don || ''}</small></td>
            <td class="ten-khach">${dh.ten_khach_hang || '<span style="color:#bbb">Ẩn danh</span>'}</td>
            <td>${fmtDate(dh.ngay_dat)}</td>
            <td>${badge(dh.hoat_dong)}</td>
            <td>${(() => { const t = mapTrangThai(dh.hoat_dong); return `<span class="badge ${t.cls}"><i class="fas ${t.icon}"></i> ${t.label}</span>`; })()}</td>
            <td>
                <button class="btn-sua" onclick="xemChiTiet(${dh.id})">
                    <i class="fas fa-eye"></i> Chi tiết
                </button>
            </td>
            <td><strong>${fmt(dh.tong_tien)}</strong></td>
        </tr>`).join('');
}

/* map trạng thái thanh toán sang badge */
function mapTrangThai(hoat_dong) {
    if (hoat_dong === 'dang_cho') 
        return { label: 'Đang chờ', cls: 'badge-pending', icon: 'fa-clock' };
    if (['dang_chuan_bi','cho_lay_hang','dang_van_chuyen'].includes(hoat_dong)) 
        return { label: 'Đã xác nhận', cls: 'badge-info', icon: 'fa-circle-check' };
    if (hoat_dong === 'giao_thanh_cong') 
        return { label: 'Hoàn thành', cls: 'badge-success', icon: 'fa-check-double' };
    if (hoat_dong === 'da_huy') 
        return { label: 'Đã hủy', cls: 'badge-danger', icon: 'fa-ban' };
    return { label: 'Không rõ', cls: 'badge-pending', icon: 'fa-question' };
}

/* ===== TÌM KIẾM ===== */
function timKiemDonHang() {
    const p = new URLSearchParams({
        action:    'search',
        tu_ngay:   document.getElementById('tuNgay').value,
        den_ngay:  document.getElementById('denNgay').value,
        trang_thai: document.getElementById('trangThaiFilter').value,
        phuong:    document.getElementById('phuongFilter').value,
        thanh_pho: document.getElementById('thanhPhoFilter').value,
    });

    fetch(`${API_URL}?${p}`)
        .then(r => r.json())
        .then(res => {
            if (res.ok) renderBangTimKiem(res.data);
            else showToast('Lỗi tìm kiếm', 'error');
        });
}

/* ===== RENDER BẢNG TÌM KIẾM (9 cột) ===== */
function renderBangTimKiem(data) {
    const tbody = document.getElementById('tbodyTimKiem');
    const kq    = document.getElementById('soKetQua');

    kq.textContent = `Tìm thấy ${data.length} kết quả`;

    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state">
            <i class="fas fa-search"></i><p>Không tìm thấy đơn hàng phù hợp</p></div></td></tr>`;
        return;
    }

    tbody.innerHTML = data.map(dh => `
        <tr>
            <td class="ma-don">#${dh.id}</td>
            <td class="ten-khach">${dh.ten_khach_hang || 'Ẩn danh'}</td>
            <td>${fmtDate(dh.ngay_dat)}</td>
            <td>${badge(dh.hoat_dong)}</td>
            <td class="dia-chi">${dh.dia_chi_giao || '—'}</td>
            <td>${dh.phuong || '—'}</td>
            <td>${dh.thanh_pho || '—'}</td>
            <td class="${dh.ly_do_huy ? 'ly-do-huy' : 'ly-do-huy empty'}">
                ${dh.ly_do_huy || '—'}
            </td>
            <td><strong>${fmt(dh.tong_tien)}</strong></td>
        </tr>`).join('');
}

/* ===== XEM CHI TIẾT + MỞ MODAL ===== */
function xemChiTiet(id) {
    // Mở modal trước, hiện loading
    const modal = document.getElementById('modalChiTiet');
    const body  = document.getElementById('modalBody');
    const footer = document.getElementById('modalFooter');

    body.innerHTML = `<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Đang tải...</p></div>`;
    footer.innerHTML = '';
    modal.style.cssText = 'display:flex; position:fixed; inset:0; background:rgba(15,15,30,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;';
    setTimeout(() => modal.classList.add('open'), 10);

    // Gọi 2 API song song: thông tin đơn + chi tiết sản phẩm
    Promise.all([
        fetch(`${API_URL}?action=get_detail&id=${id}`).then(r => r.json()),
        fetch(`${API_URL}?action=get_items&id=${id}`).then(r => r.json()),
    ]).then(([resDetail, resItems]) => {
        if (!resDetail.ok) { showToast('Không lấy được chi tiết', 'error'); return; }
        renderModal(resDetail.data, resItems.ok ? resItems.data : []);
    }).catch(() => showToast('Lỗi kết nối', 'error'));
}

/* ===== RENDER NỘI DUNG MODAL ===== */
function renderModal(dh, items) {
    const body   = document.getElementById('modalBody');
    const footer = document.getElementById('modalFooter');
    const da_xong = dh.hoat_dong === 'giao_thanh_cong' || dh.hoat_dong === 'da_huy';

    /* --- Bảng sản phẩm --- */
    const rowsSP = items.length
        ? items.map(it => `
            <tr>
                <td>${it.ten_sp || 'SP #' + it.id_san_pham}</td>
                <td>${it.so_luong}</td>
                <td>${fmt(it.gia_ban)}</td>
                <td><strong>${fmt(it.so_luong * it.gia_ban)}</strong></td>
            </tr>`).join('')
        : `<tr><td colspan="4" style="color:#aaa;text-align:center">Chưa có sản phẩm</td></tr>`;

    /* --- Radio trạng thái --- */
    const radios = Object.entries(TRANG_THAI_MAP).map(([val, info]) => `
        <label class="radio-pill ${dh.hoat_dong === val ? 'selected' : ''}" onclick="chonTT(this,'${val}')">
            <input type="radio" name="hoat_dong" value="${val}" ${dh.hoat_dong === val ? 'checked' : ''}/>
            <i class="fas ${info.icon}"></i> ${info.label}
        </label>`).join('');

    body.innerHTML = `
        <!-- Thông tin chung -->
        <div class="info-grid">
            <div class="info-card">
                <h4><i class="fas fa-receipt"></i> Thông tin đơn</h4>
                <p><strong>Mã đơn:</strong> ${dh.ma_don || '#' + dh.id}</p>
                <p><strong>Khách hàng:</strong> ${dh.ten_khach_hang || 'Ẩn danh'}</p>
                <p><strong>Ngày đặt:</strong> ${fmtDate(dh.ngay_dat)}</p>
                <p><strong>Trạng thái:</strong> ${badge(dh.hoat_dong)}</p>
                <p><strong>Tổng tiền:</strong> <span style="color:#c0392b;font-weight:800">${fmt(dh.tong_tien)}</span></p>
            </div>
            <div class="info-card">
                <h4><i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng</h4>
                <p><strong>Địa chỉ:</strong> ${dh.dia_chi_giao || '—'}</p>
                <p><strong>Phường:</strong> ${dh.phuong || '—'}</p>
                <p><strong>Quận:</strong> ${dh.quan || '—'}</p>
                <p><strong>Thành phố:</strong> ${dh.thanh_pho || '—'}</p>
                ${dh.ly_do_huy ? `<p><strong>Lý do hủy:</strong> <span style="color:#c0392b">${dh.ly_do_huy}</span></p>` : ''}
            </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="info-card">
            <h4><i class="fas fa-shopping-basket"></i> Sản phẩm trong đơn</h4>
            <table class="product-table">
                <thead><tr><th>Tên sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
                <tbody>${rowsSP}</tbody>
                <tfoot><tr>
                    <td colspan="3" style="text-align:right">Tổng cộng:</td>
                    <td>${fmt(dh.tong_tien)}</td>
                </tr></tfoot>
            </table>
        </div>

        <!-- Cập nhật trạng thái -->
        ${da_xong
            ? `<div class="status-final-box ${dh.hoat_dong === 'giao_thanh_cong' ? 'success' : 'danger'}">
                <div class="icon-big">${dh.hoat_dong === 'giao_thanh_cong' ? '✅' : '❌'}</div>
                <h4>${dh.hoat_dong === 'giao_thanh_cong' ? 'Đã giao hàng thành công' : 'Đơn hàng đã bị hủy'}</h4>
                <p>Không thể thay đổi trạng thái đơn này</p>
               </div>`
            : `<div class="update-card">
                <h4><i class="fas fa-pen"></i> Cập nhật trạng thái</h4>
                <div class="radio-group" id="rgTrangThai">${radios}</div>
                <div class="ly-do-huy-wrap" id="lyDoWrap">
                    <textarea id="lyDoHuy" rows="3" placeholder="Nhập lý do hủy đơn hàng...">${dh.ly_do_huy || ''}</textarea>
                </div>
               </div>`
        }`;

    footer.innerHTML = da_xong
        ? `<button class="btn-dong" onclick="dongModal()"><i class="fas fa-times"></i> Đóng</button>`
        : `<button class="btn-capnhat" onclick="capNhatTrangThai(${dh.id})">
               <i class="fas fa-save"></i> Lưu thay đổi
           </button>
           <button class="btn-dong" onclick="dongModal()"><i class="fas fa-times"></i> Đóng</button>`;
}

/* ===== CHỌN TRẠNG THÁI (radio pill) ===== */
function chonTT(el, val) {
    document.querySelectorAll('.radio-pill').forEach(p => p.classList.remove('selected'));
    el.classList.add('selected');
    const lyDoWrap = document.getElementById('lyDoWrap');
    if (lyDoWrap) lyDoWrap.style.display = val === 'da_huy' ? 'block' : 'none';
}

/* ===== CẬP NHẬT TRẠNG THÁI ===== */
function capNhatTrangThai(id) {
    const checked = document.querySelector('input[name="hoat_dong"]:checked');
    if (!checked) { showToast('Vui lòng chọn trạng thái', 'warning'); return; }

    const hoat_dong = checked.value;
    const ly_do_huy = hoat_dong === 'da_huy' ? (document.getElementById('lyDoHuy')?.value || '') : '';

    fetch(`${API_URL}?action=update_status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, hoat_dong, ly_do_huy }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            showToast('Cập nhật trạng thái thành công!', 'success');
            dongModal();
            taiDanhSachDonHang();
        } else {
            showToast(res.message || 'Cập nhật thất bại', 'error');
        }
    })
    .catch(() => showToast('Lỗi kết nối', 'error'));
}

/* ===== MODAL OPEN / CLOSE ===== */
function dongModal() {
    const modal = document.getElementById('modalChiTiet');
    modal.classList.remove('open');
    setTimeout(() => { modal.style.display = 'none'; }, 280);
}

/* ===== TOAST NOTIFICATION ===== */
function showToast(msg, type = 'info', duration = 3000) {
    const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
    const wrap  = document.getElementById('toastWrap');
    const t     = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> ${msg}`;
    wrap.appendChild(t);
    setTimeout(() => {
        t.classList.add('hide');
        setTimeout(() => t.remove(), 400);
    }, duration);
}
