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

    document.getElementById('khuTimSP').style.display      = daHT ? 'none' : 'block';
    document.getElementById('khuHoanThanh').style.display  = daHT ? 'none' : 'block';
    document.getElementById('btnLuuDauPhieu').style.display= daHT ? 'none' : 'inline-block';
    document.getElementById('popupNgayNhap').disabled      = daHT;
    document.getElementById('popupGhiChu').disabled        = daHT;
    document.getElementById('cotChucNang').textContent     = daHT ? '' : 'Chức năng';

    window.location.hash = '#popup-suaphieu';
    await fetchChiTiet(maPhieu);
}

async function luuDauPhieu() {
    if (!maPhieuDangMo) return;
    const ngay   = document.getElementById('popupNgayNhap').value;
    const ghiChu = document.getElementById('popupGhiChu').value.trim();
    if (!ngay) { showToast('Vui lòng chọn ngày nhập!', 'warn'); return; }

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
    if (!spDuocChon) { showToast('Vui lòng chọn sản phẩm!', 'warn'); return; }
    const soLuong = parseInt(document.getElementById('soLuongThem').value || 0);
    const donGia  = parseFloat(document.getElementById('donGiaThem').value || 0);

    if (soLuong <= 0 || donGia < 0) { showToast('Dữ liệu không hợp lệ!', 'warn'); return; }

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
        const soLuong = parseInt(document.getElementById('suaDongSoLuong').value);
        const donGia  = parseFloat(document.getElementById('suaDongDonGia').value);

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

function showToast(msg, type = 'success') {
    let wrap = document.getElementById('toastWrap');
    if (!wrap) {
        wrap = document.createElement('div'); wrap.id = 'toastWrap';
        wrap.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:8px';
        document.body.appendChild(wrap);
    }
    const colors = { success:'#27ae60', error:'#e74c3c', warn:'#f39c12' }, icons = { success:'✅', error:'❌', warn:'⚠️' };
    const div = document.createElement('div');
    div.style.cssText = `background:${colors[type]||'#333'};color:#fff;padding:12px 18px;border-radius:10px;font-size:14px;box-shadow:0 4px 18px rgba(0,0,0,.25);display:flex;gap:9px;animation:pnSlide .3s ease`;
    div.innerHTML = `<span>${icons[type]||''}</span><span>${msg}</span>`;
    wrap.appendChild(div);
    setTimeout(() => { div.style.transition='opacity .4s'; div.style.opacity='0'; setTimeout(()=>div.remove(), 420); }, 3800);
}