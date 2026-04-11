// =============================================================
// JSAdmin/PhieuNhap.js  –  Quan ly Phieu Nhap Hang
// =============================================================

const PN_API = '../Admin/process_phieunhap.php';

let danhSachPhieu   = [];   // tat ca phieu nhap
let chiTietHienTai  = [];   // chi tiet phieu dang mo trong popup
let maPhieuDangMo   = null; // MÃ phieu dang mo popup
let trangThaiDangMo = '';  
let spGoiY          = [];   
let spDuocChon      = null; 

// =============================================================
// VALIDATION & ERROR DISPLAY
// =============================================================
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    clearFieldError(fieldId);
    field.classList.add('field-error');
    field.style.borderColor = '#e74c3c';
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error-message';
    errorDiv.style.cssText = `
        color:#e74c3c;font-size:12px;margin-top:4px;margin-bottom:8px;
        display:flex;align-items:center;gap:4px
    `;
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    errorDiv.id = `error-${fieldId}`;
    
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    field.classList.remove('field-error');
    field.style.borderColor = '';
    
    const errorDiv = document.getElementById(`error-${fieldId}`);
    if (errorDiv) errorDiv.remove();
}

function bindFieldClearError(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    field.addEventListener('input', () => {
        if (field.classList.contains('field-error')) {
            clearFieldError(fieldId);
        }
    });
    
    field.addEventListener('change', () => {
        if (field.classList.contains('field-error')) {
            clearFieldError(fieldId);
        }
    });
} 

document.addEventListener('DOMContentLoaded', () => {
    fetchPhieuNhap();
    bindTaoPhieu();
    bindTimKiem();
    bindPopupPhieu();
    bindSuaDong();
});

async function pnFetch(resource, method = 'GET', body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    try {
        const res  = await fetch(`${PN_API}?resource=${resource}`, opts);
        const json = await res.json();
        return json;
    } catch (err) {
        showToast('Lỗi kết nối: ' + err.message, 'error');
        return { success: false, message: err.message };
    }
}

async function fetchPhieuNhap(keyword = '') {
    const url = keyword
        ? `${PN_API}?resource=phieu-nhap&q=${encodeURIComponent(keyword)}`
        : `${PN_API}?resource=phieu-nhap`;
    try {
        const res = await fetch(url);
        const json = await res.json();
        if (json.success) {
            danhSachPhieu = json.data || [];
            renderBangPhieu(danhSachPhieu);
        }
    } catch (err) {
        showToast('Lỗi tải danh sách phiếu: ' + err.message, 'error');
    }
}

function renderBangPhieu(list) {
    const tbody = document.getElementById('tbodyPhieuNhap');
    if (!tbody) return;

    if (list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#999;padding:16px">Chưa có phiếu nhập nào.</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    list.forEach(pn => {
        const badge     = badgeTrangThai(pn.trang_thai);
        const tongTien  = fmtVND(pn.tong_tien || 0);
        const ngayFmt   = fmtDate(pn.ngay_nhap);
        const daHT      = pn.trang_thai === 'hoan_thanh';

        const btnSua = daHT ? '' : `<button class="btn-sua1" onclick="moPopupPhieu('${pn.ma_phieu}')"><i class="fas fa-edit"></i> Sửa</button>`;
        const btnXoa = daHT ? '' : `<button class="btn-xoa1" onclick="xoaPhieu('${pn.ma_phieu}')"><i class="fas fa-trash"></i> Xóa</button>`;

        const tr = document.createElement('tr');
        if (daHT) tr.style.background = '#f0fff4';
        tr.innerHTML = `
            <td><strong>${pn.ma_phieu}</strong></td>
            <td>${ngayFmt}</td>
            <td style="text-align:center">${pn.so_dong}</td>
            <td style="text-align:right;color:#e74c3c;font-weight:600">${tongTien}</td>
            <td style="font-size:12px">${pn.ghi_chu || '—'}</td>
            <td>${badge}</td>
            <td>
              <button class="btn-sua1" onclick="moPopupPhieu('${pn.ma_phieu}')" style="background:${daHT?'#2980b9':'red'}">
                <i class="fas fa-${daHT?'eye':'edit'}"></i> ${daHT ? 'Xem' : 'Sửa'}
              </button>
              ${btnXoa}
            </td>`;
        tbody.appendChild(tr);
    });
}

function bindTaoPhieu() {
    // Không dùng form submit nữa, dùng sự kiện click của nút bấm mới
    const btnTaoNhanh = document.getElementById('btnTaoPhieuMoiNhanh');
    if (!btnTaoNhanh) return;

    btnTaoNhanh.addEventListener('click', async () => {
        // Tự động lấy ngày hôm nay
        const ngay = todayStr();
        const ghiChu = ''; // Ghi chú rỗng ban đầu

        // Gửi API tạo phiếu
        const res = await pnFetch('phieu-nhap', 'POST', { ngay_nhap: ngay, ghi_chu: ghiChu });
        
        if (res.success) {
            showToast(`Đã tạo phiếu mới! Mời bạn thêm sản phẩm.`, 'success');
            await fetchPhieuNhap(); // Load lại bảng ở ngoài
            moPopupPhieu(res.ma_phieu); // Tự động mở popup lên luôn
        } else {
            showToast(res.message, 'error');
        }
    });
}

function bindTimKiem() {
    document.getElementById('btnTimKiem')?.addEventListener('click', async () => {
        const kw = document.getElementById('timKiemPhieu').value.trim();
        await fetchPhieuNhap(kw);
    });
    document.getElementById('timKiemPhieu')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') document.getElementById('btnTimKiem').click();
    });
}

function bindPopupPhieu() {
    document.getElementById('btnLuuDauPhieu')?.addEventListener('click', luuDauPhieu);
    let timerTimSP;
    document.getElementById('timSPPhieu')?.addEventListener('input', function () {
        clearTimeout(timerTimSP);
        timerTimSP = setTimeout(() => timSanPhamGoi(this.value.trim()), 300);
    });
    document.getElementById('btnThemSPVaoPhieu')?.addEventListener('click', themSPVaoPhieu);
    document.getElementById('btnHoanThanhPhieu')?.addEventListener('click', hoanThanhPhieu);
}

async function moPopupPhieu(maPhieu) {
    maPhieuDangMo = maPhieu;
    let phieu = danhSachPhieu.find(p => p.ma_phieu === maPhieu);
    if (!phieu) {
        await fetchPhieuNhap();
        phieu = danhSachPhieu.find(p => p.ma_phieu === maPhieu);
    }
    if (!phieu) { showToast('Không tìm thấy phiếu!', 'error'); return; }

    trangThaiDangMo = phieu.trang_thai;
    const daHT = phieu.trang_thai === 'hoan_thanh';

    document.getElementById('popupMaPhieu').textContent = phieu.ma_phieu;
    document.getElementById('popupBadge').innerHTML     = badgeTrangThai(phieu.trang_thai);
    document.getElementById('popupNgayNhap').value      = phieu.ngay_nhap;
    document.getElementById('popupGhiChu').value        = phieu.ghi_chu || '';

    document.getElementById('khuTimSP')?.style.setProperty('display', daHT ? 'none' : 'block');
    document.getElementById('khuHoanThanh')?.style.setProperty('display', daHT ? 'none' : 'block');
    document.getElementById('btnLuuDauPhieu').style.display= daHT ? 'none' : 'inline-block';
    document.getElementById('popupNgayNhap').disabled      = daHT;
    document.getElementById('popupGhiChu').disabled        = daHT;
    document.getElementById('cotChucNang').textContent     = daHT ? '' : 'Chức năng';

    window.location.hash = '#popup-suaphieu';
    await fetchChiTiet(maPhieu);
}

async function luuDauPhieu() {
    if (!maPhieuDangMo) return;
    
    clearFieldError('popupNgayNhap');
    clearFieldError('popupGhiChu');
    
    const ngay   = document.getElementById('popupNgayNhap').value;
    const ghiChu = document.getElementById('popupGhiChu').value.trim();
    
    let hasError = false;
    if (!ngay) {
        showFieldError('popupNgayNhap', 'Vui lòng chọn ngày nhập');
        hasError = true;
    }
    
    if (hasError) return;

    const res = await pnFetch('phieu-nhap', 'PUT', { ma_phieu: maPhieuDangMo, ngay_nhap: ngay, ghi_chu: ghiChu });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) await fetchPhieuNhap();
}

async function fetchChiTiet(maPhieu) {
    const url = `${PN_API}?resource=chi-tiet&ma_phieu=${maPhieu}`;
    const res = await fetch(url).then(r => r.json());
    if (res.success) {
        chiTietHienTai = res.data || [];
        renderChiTiet(chiTietHienTai);
    }
}

function renderChiTiet(list) {
    const tbody = document.getElementById('tbodyChiTiet');
    const tongEl = document.getElementById('tongTienChiTiet');
    const daHT   = trangThaiDangMo === 'hoan_thanh';
    if (!tbody) return;

    if (list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:#999;padding:12px">Chưa có sản phẩm nào trong phiếu.</td></tr>`;
        if (tongEl) tongEl.textContent = fmtVND(0);
        return;
    }

    let tongTien = 0;
    tbody.innerHTML = '';
    list.forEach((ct, i) => {
        tongTien += parseFloat(ct.thanh_tien || 0);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${i + 1}</td>
            <td>${ct.ma_sp}</td>
            <td>${ct.ten_sp}</td>
            <td>${ct.don_vi_tinh || ''}</td>
            <td style="text-align:right">${ct.so_luong}</td>
            <td style="text-align:right">${fmtVND(ct.don_gia)}</td>
            <td style="text-align:right;font-weight:600">${fmtVND(ct.thanh_tien)}</td>
            <td>${daHT ? '' : `
              <button class="btn-sua1" style="width:60px;font-size:12px" onclick="moSuaDong('${ct.ma_phieu}','${ct.ma_sp}','${escJ(ct.ten_sp)}',${ct.so_luong},${ct.don_gia})">Sửa</button>
              <button class="btn-xoa1" style="width:60px;font-size:12px" onclick="xoaDongCT('${ct.ma_phieu}','${ct.ma_sp}','${escJ(ct.ten_sp)}')">Xóa</button>`}
            </td>`;
        tbody.appendChild(tr);
    });
    if (tongEl) tongEl.textContent = fmtVND(tongTien);
}

async function timSanPhamGoi(keyword) {
    const url = `${PN_API}?resource=tim-san-pham&q=${encodeURIComponent(keyword)}`;
    const res = await fetch(url).then(r => r.json());
    spGoiY = res.success ? (res.data || []) : [];
    hienGoiY();
}

function hienGoiY() {
    const box = document.getElementById('goiYSP');
    if (!box) return;
    if (spGoiY.length === 0) { box.style.display = 'none'; return; }

    box.innerHTML = '';
    spGoiY.forEach(sp => {
        const div = document.createElement('div');
        div.className = 'goi-y-item';
        div.innerHTML = `<strong>${sp.ma_sp}</strong> – ${sp.ten_sp} <span style="float:right;color:#e74c3c;font-size:12px">GV: ${fmtVND(sp.gia_von)}</span>`;
        div.addEventListener('click', () => {
            spDuocChon = sp;
            document.getElementById('timSPPhieu').value  = `${sp.ma_sp} – ${sp.ten_sp}`;
            document.getElementById('donGiaThem').value  = sp.gia_von || 0;
            box.style.display = 'none';
        });
        box.appendChild(div);
    });
    box.style.display = 'block';
}

document.addEventListener('click', e => {
    const box = document.getElementById('goiYSP');
    if (box && !box.contains(e.target) && e.target.id !== 'timSPPhieu') box.style.display = 'none';
});

async function themSPVaoPhieu() {
    if (!maPhieuDangMo) return;
    
    clearFieldError('timSPPhieu');
    clearFieldError('soLuongThem');
    clearFieldError('donGiaThem');
    
    if (!spDuocChon) {
        showFieldError('timSPPhieu', 'Vui lòng chọn sản phẩm từ danh sách gợi ý');
        return;
    }
    
    const soLuong = parseInt(document.getElementById('soLuongThem').value || 0);
    const donGia  = parseFloat(document.getElementById('donGiaThem').value || 0);

    let hasError = false;
    if (soLuong <= 0) {
        showFieldError('soLuongThem', 'Số lượng phải lớn hơn 0');
        hasError = true;
    }
    if (donGia < 0) {
        showFieldError('donGiaThem', 'Giá nhập không được âm');
        hasError = true;
    }
    
    if (hasError) return;

    const res = await pnFetch('chi-tiet', 'POST', {
        ma_phieu : maPhieuDangMo,
        ma_sp    : spDuocChon.ma_sp,
        so_luong : soLuong,
        don_gia  : donGia
    });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        document.getElementById('timSPPhieu').value = '';
        spDuocChon = null;
        await fetchChiTiet(maPhieuDangMo);
        await fetchPhieuNhap();
    }
}

let suaDong_maPhieu = null;
let suaDong_maSp    = null;

function bindSuaDong() {
    document.getElementById('btnLuuSuaDong')?.addEventListener('click', async () => {
        clearFieldError('suaDongSoLuong');
        clearFieldError('suaDongDonGia');
        
        const soLuong = parseInt(document.getElementById('suaDongSoLuong').value);
        const donGia  = parseFloat(document.getElementById('suaDongDonGia').value);

        let hasError = false;
        if (soLuong <= 0) {
            showFieldError('suaDongSoLuong', 'Số lượng phải lớn hơn 0');
            hasError = true;
        }
        if (donGia < 0) {
            showFieldError('suaDongDonGia', 'Giá nhập không được âm');
            hasError = true;
        }
        
        if (hasError) return;

        const res = await pnFetch('chi-tiet', 'PUT', { ma_phieu: suaDong_maPhieu, ma_sp: suaDong_maSp, so_luong: soLuong, don_gia: donGia });
        showToast(res.message, res.success ? 'success' : 'error');
        if (res.success) {
            window.location.hash = '#popup-suaphieu';
            await fetchChiTiet(maPhieuDangMo);
            await fetchPhieuNhap();
        }
    });
}

function moSuaDong(maPhieu, maSp, tenSP, soLuong, donGia) {
    suaDong_maPhieu = maPhieu;
    suaDong_maSp    = maSp;
    document.getElementById('suaDongTen').value      = tenSP;
    document.getElementById('suaDongSoLuong').value  = soLuong;
    document.getElementById('suaDongDonGia').value   = donGia;
    window.location.hash = '#popup-suadong';
}

async function xoaDongCT(maPhieu, maSp, tenSP) {
    if (!confirm(`Xóa "${tenSP}" khỏi phiếu?`)) return;
    const res = await pnFetch('chi-tiet', 'DELETE', { ma_phieu: maPhieu, ma_sp: maSp });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        await fetchChiTiet(maPhieuDangMo);
        await fetchPhieuNhap();
    }
}

async function hoanThanhPhieu() {
    if (!maPhieuDangMo) return;
    if (!confirm(`Xác nhận hoàn thành phiếu ${maPhieuDangMo}?`)) return;

    const res = await pnFetch('hoan-thanh', 'POST', { ma_phieu: maPhieuDangMo });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        trangThaiDangMo = 'hoan_thanh';
        await fetchPhieuNhap();
        moPopupPhieu(maPhieuDangMo);
    }
}

async function xoaPhieu(maPhieu) {
    if (!confirm(`Xóa phiếu "${maPhieu}" và toàn bộ chi tiết?`)) return;
    const res = await pnFetch('phieu-nhap', 'DELETE', { ma_phieu: maPhieu });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) await fetchPhieuNhap();
}

// =============================================================
// TIEN ICH
// =============================================================
function fmtVND(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v || 0); }
function fmtDate(str) { return str ? new Date(str).toLocaleDateString('vi-VN') : '—'; }
function todayStr() { return new Date().toISOString().split('T')[0]; }
function escJ(s) { return (s || '').replace(/'/g, "\\'").replace(/"/g, '\\"'); }
function badgeTrangThai(tt) { return tt === 'hoan_thanh' ? `<span class="badge-hienthi"><i class="fas fa-check"></i> Hoàn thành</span>` : `<span class="badge-chuaht"><i class="fas fa-clock"></i> Chưa hoàn thành</span>`; }

function showToast(msg, type = 'success', title = null) {
    // Tạo overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);
        display:flex;align-items:center;justify-content:center;z-index:99999;
        animation:fadeIn 0.3s ease
    `;

    // Xác định icon, tiêu đề, và màu sắc
    const configs = {
        success: { icon: '✓', bgColor: '#27ae60', defaultTitle: 'Thành công!' },
        error: { icon: '✕', bgColor: '#e74c3c', defaultTitle: 'Lỗi!' },
        warn: { icon: '⚠', bgColor: '#f39c12', defaultTitle: 'Cảnh báo!' }
    };
    const config = configs[type] || configs.success;
    const finalTitle = title || config.defaultTitle;

    // Tạo dialog box
    const box = document.createElement('div');
    box.style.cssText = `
        background:white;border-radius:12px;padding:30px 40px;text-align:center;
        box-shadow:0 8px 32px rgba(0,0,0,0.2);max-width:420px;width:90%;
        animation:slideUp 0.4s ease;position:relative
    `;

    // Icon tròn
    const iconDiv = document.createElement('div');
    iconDiv.style.cssText = `
        width:60px;height:60px;margin:0 auto 16px;border-radius:50%;display:flex;
        align-items:center;justify-content:center;background:${config.bgColor};color:white;
        font-size:32px;font-weight:bold
    `;
    iconDiv.textContent = config.icon;

    // Tiêu đề
    const titleDiv = document.createElement('h2');
    titleDiv.style.cssText = 'margin:0 0 8px;font-size:18px;color:#333;font-weight:600';
    titleDiv.textContent = finalTitle;

    // Nội dung
    const msgDiv = document.createElement('p');
    msgDiv.style.cssText = 'margin:0 0 24px;font-size:14px;color:#666;line-height:1.5';
    msgDiv.textContent = msg;

    // Nút đóng
    const btn = document.createElement('button');
    btn.style.cssText = `
        background:${config.bgColor};color:white;border:none;padding:10px 28px;
        border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;
        transition:opacity 0.2s
    `;
    btn.textContent = 'Đóng';
    btn.onmouseover = () => btn.style.opacity = '0.9';
    btn.onmouseout = () => btn.style.opacity = '1';
    btn.addEventListener('click', () => {
        overlay.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => overlay.remove(), 300);
    });

    box.appendChild(iconDiv);
    box.appendChild(titleDiv);
    box.appendChild(msgDiv);
    box.appendChild(btn);
    overlay.appendChild(box);
    document.body.appendChild(overlay);

    // Tự động đóng sau 4 giây
    setTimeout(() => {
        if (overlay.parentNode) {
            overlay.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => overlay.remove(), 300);
        }
    }, 4000);
}

// Thêm CSS animations cho toast + validation
if (!document.querySelector('style[data-toast-pn]')) {
    const toastStyle = document.createElement('style');
    toastStyle.setAttribute('data-toast-pn', 'true');
    toastStyle.textContent = `
        @keyframes slideUp { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
        @keyframes fadeOut { from{opacity:1} to{opacity:0} }
        .field-error { background-color:rgba(231,76,60,0.05)!important;border-color:#e74c3c!important;transition:border-color 0.2s }
        .field-error-message { color:#e74c3c;font-size:12px;margin-top:4px;margin-bottom:8px;display:flex;align-items:center;gap:4px }
        input:focus,select:focus,textarea:focus { outline:none;border-color:#0195b2 }
    `;
    document.head.appendChild(toastStyle);
}

// Auto-bind error clearing cho các input fields
document.addEventListener('DOMContentLoaded', () => {
    const fieldIds = ['timSPPhieu', 'soLuongThem', 'donGiaThem', 'suaDongSoLuong', 'suaDongDonGia', 'popupNgayNhap', 'popupGhiChu'];
    fieldIds.forEach(id => {
        const field = document.getElementById(id);
        if (field) {
            field.addEventListener('input', () => {
                if (field.classList.contains('field-error')) {
                    clearFieldError(id);
                }
            });
        }
    });
});