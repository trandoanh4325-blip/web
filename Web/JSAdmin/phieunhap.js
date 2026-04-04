// =============================================================
// JSAdmin/PhieuNhap.js  –  Quan ly Phieu Nhap Hang
// =============================================================

const PN_API = '../Admin/process_PhieuNhap.php';

let danhSachPhieu  = [];   // tat ca phieu nhap
let chiTietHienTai = [];   // chi tiet phieu dang mo trong popup
let idPhieuDangMo  = null; // id phieu dang mo popup
let trangThaiDangMo = '';  // trang thai phieu dang mo
let spGoiY         = [];   // danh sach san pham goi y tim kiem
let spDuocChon     = null; // san pham da chon tu goi y

// =============================================================
// KHOI TAO
// =============================================================
document.addEventListener('DOMContentLoaded', () => {
    fetchPhieuNhap();
    bindTaoPhieu();
    bindTimKiem();
    bindPopupPhieu();
    bindSuaDong();
});

// =============================================================
// API HELPER
// =============================================================
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

// =============================================================
// LAY & HIEN THI DANH SACH PHIEU NHAP
// =============================================================
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
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#999;padding:16px">
            Chưa có phiếu nhập nào.</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    list.forEach(pn => {
        const badge     = badgeTrangThai(pn.trang_thai);
        const tongTien  = fmtVND(pn.tong_tien || 0);
        const ngayFmt   = fmtDate(pn.ngay_nhap);
        const daHT      = pn.trang_thai === 'hoan_thanh';

        const btnSua = daHT ? '' : `
            <button class="btn-sua1"
                    onclick="moPopupPhieu(${pn.id})">
              <i class="fas fa-edit"></i> Sửa
            </button>`;
        const btnXoa = daHT ? '' : `
            <button class="btn-xoa1"
                    onclick="xoaPhieu(${pn.id},'${pn.ma_phieu}')">
              <i class="fas fa-trash"></i> Xóa
            </button>`;

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
              <button class="btn-sua1"
                      onclick="moPopupPhieu(${pn.id})"
                      style="background:${daHT?'#2980b9':'red'}">
                <i class="fas fa-${daHT?'eye':'edit'}"></i>
                ${daHT ? 'Xem' : 'Sửa'}
              </button>
              ${btnXoa}
            </td>`;
        tbody.appendChild(tr);
    });
}

// =============================================================
// TAO PHIEU NHAP MOI
// =============================================================
function bindTaoPhieu() {
    const form = document.getElementById('formTaoPhieu');
    if (!form) return;
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const ngay   = document.getElementById('ngayNhapMoi').value;
        const ghiChu = document.getElementById('ghiChuMoi').value.trim();
        if (!ngay) { showToast('Vui lòng chọn ngày nhập!', 'warn'); return; }

        const res = await pnFetch('phieu-nhap', 'POST', { ngay_nhap: ngay, ghi_chu: ghiChu });
        if (res.success) {
            showToast(`Đã tạo ${res.ma_phieu}! Bấm Sửa để thêm sản phẩm.`, 'success');
            form.reset();
            document.getElementById('ngayNhapMoi').value = todayStr();
            await fetchPhieuNhap();
            // Tu dong mo popup phieu vua tao
            moPopupPhieu(res.id);
        } else {
            showToast(res.message, 'error');
        }
    });
}

// =============================================================
// TIM KIEM PHIEU NHAP
// =============================================================
function bindTimKiem() {
    document.getElementById('btnTimKiem')?.addEventListener('click', async () => {
        const kw = document.getElementById('timKiemPhieu').value.trim();
        const url = `${PN_API}?resource=phieu-nhap${kw ? '&q=' + encodeURIComponent(kw) : ''}`;
        const res = await fetch(url).then(r => r.json());
        renderBangTimKiem(res.success ? res.data : []);
    });
    document.getElementById('timKiemPhieu')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') document.getElementById('btnTimKiem').click();
    });
}

function renderBangTimKiem(list) {
    const tbody = document.getElementById('tbodyTimKiem');
    if (!tbody) return;
    if (list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:#999">
            Không tìm thấy phiếu phù hợp.</td></tr>`;
        return;
    }
    tbody.innerHTML = '';
    list.forEach(pn => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><a href="#popup-suaphieu" onclick="moPopupPhieu(${pn.id})"
                   style="color:#2980b9;font-weight:700;text-decoration:none">
              ${pn.ma_phieu}</a></td>
            <td>${fmtDate(pn.ngay_nhap)}</td>
            <td style="text-align:center">${pn.so_dong}</td>
            <td style="text-align:right;color:#e74c3c;font-weight:600">
              ${fmtVND(pn.tong_tien||0)}</td>
            <td>${badgeTrangThai(pn.trang_thai)}</td>`;
        tbody.appendChild(tr);
    });
}

// =============================================================
// POPUP PHIEU NHAP (xem / sua / them san pham / hoan thanh)
// =============================================================
function bindPopupPhieu() {
    // Luu thong tin dau phieu
    document.getElementById('btnLuuDauPhieu')?.addEventListener('click', luuDauPhieu);

    // Tim kiem san pham trong popup
    let timerTimSP;
    document.getElementById('timSPPhieu')?.addEventListener('input', function () {
        clearTimeout(timerTimSP);
        timerTimSP = setTimeout(() => timSanPhamGoi(this.value.trim()), 300);
    });

    // Them SP vao phieu
    document.getElementById('btnThemSPVaoPhieu')?.addEventListener('click', themSPVaoPhieu);

    // Hoan thanh phieu
    document.getElementById('btnHoanThanhPhieu')?.addEventListener('click', hoanThanhPhieu);
}

async function moPopupPhieu(idPhieu) {
    idPhieuDangMo = idPhieu;
    const pn = danhSachPhieu.find(p => p.id == idPhieu);
    if (!pn) {
        // fetch lai neu khong co trong cache
        await fetchPhieuNhap();
    }
    const phieu = danhSachPhieu.find(p => p.id == idPhieu);
    if (!phieu) { showToast('Không tìm thấy phiếu!', 'error'); return; }

    trangThaiDangMo = phieu.trang_thai;
    const daHT = phieu.trang_thai === 'hoan_thanh';

    document.getElementById('popupMaPhieu').textContent = phieu.ma_phieu;
    document.getElementById('popupBadge').innerHTML     = badgeTrangThai(phieu.trang_thai);
    document.getElementById('popupNgayNhap').value      = phieu.ngay_nhap;
    document.getElementById('popupGhiChu').value        = phieu.ghi_chu || '';

    // An/hien cac khu vuc theo trang thai
    const khuTim   = document.getElementById('khuTimSP');
    const khuHT    = document.getElementById('khuHoanThanh');
    const btnLuu   = document.getElementById('btnLuuDauPhieu');
    const cotCN    = document.getElementById('cotChucNang');

    khuTim.style.display      = daHT ? 'none' : 'block';
    khuHT.style.display       = daHT ? 'none' : 'block';
    btnLuu.style.display      = daHT ? 'none' : 'inline-block';
    document.getElementById('popupNgayNhap').disabled = daHT;
    document.getElementById('popupGhiChu').disabled   = daHT;
    cotCN.textContent = daHT ? '' : 'Chức năng';

    window.location.hash = '#popup-suaphieu';

    // Tai chi tiet
    await fetchChiTiet(idPhieu);
}

async function luuDauPhieu() {
    if (!idPhieuDangMo) return;
    const ngay   = document.getElementById('popupNgayNhap').value;
    const ghiChu = document.getElementById('popupGhiChu').value.trim();
    if (!ngay) { showToast('Vui lòng chọn ngày nhập!', 'warn'); return; }

    const res = await pnFetch('phieu-nhap', 'PUT',
        { id: idPhieuDangMo, ngay_nhap: ngay, ghi_chu: ghiChu });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) await fetchPhieuNhap();
}

// =============================================================
// CHI TIET PHIEU NHAP
// =============================================================
async function fetchChiTiet(idPhieu) {
    const url = `${PN_API}?resource=chi-tiet&id_phieu=${idPhieu}`;
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
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:#999;padding:12px">
            Chưa có sản phẩm nào trong phiếu.</td></tr>`;
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
              <button class="btn-sua1" style="width:60px;font-size:12px"
                      onclick="moSuaDong(${ct.id},'${escJ(ct.ten_sp)}',${ct.so_luong},${ct.don_gia})">
                Sửa
              </button>
              <button class="btn-xoa1" style="width:60px;font-size:12px"
                      onclick="xoaDongCT(${ct.id},'${escJ(ct.ten_sp)}')">
                Xóa
              </button>`}
            </td>`;
        tbody.appendChild(tr);
    });
    if (tongEl) tongEl.textContent = fmtVND(tongTien);
}

// =============================================================
// TIM KIEM SAN PHAM (goi y trong popup)
// =============================================================
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
        div.innerHTML = `
            <strong>${sp.ma_sp}</strong> – ${sp.ten_sp}
            <span style="color:#888;font-size:12px">(${sp.ten_loai || ''} | ${sp.don_vi_tinh})</span>
            <span style="float:right;color:#e74c3c;font-size:12px">GV: ${fmtVND(sp.gia_von)}</span>`;
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
    if (box && !box.contains(e.target) &&
        e.target.id !== 'timSPPhieu') {
        box.style.display = 'none';
    }
});

// =============================================================
// THEM SAN PHAM VAO PHIEU
// =============================================================
async function themSPVaoPhieu() {
    if (!idPhieuDangMo) return;
    if (!spDuocChon) {
        showToast('Vui lòng chọn sản phẩm từ danh sách gợi ý!', 'warn');
        return;
    }
    const soLuong = parseInt(document.getElementById('soLuongThem').value || 0);
    const donGia  = parseFloat(document.getElementById('donGiaThem').value || 0);

    if (soLuong <= 0) { showToast('Số lượng phải lớn hơn 0!', 'warn'); return; }
    if (donGia  < 0)  { showToast('Giá nhập không hợp lệ!', 'warn');   return; }

    const res = await pnFetch('chi-tiet', 'POST', {
        id_phieu : idPhieuDangMo,
        id_sp    : spDuocChon.id,
        so_luong : soLuong,
        don_gia  : donGia,
    });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        // Reset form them
        document.getElementById('timSPPhieu').value  = '';
        document.getElementById('soLuongThem').value = '1';
        document.getElementById('donGiaThem').value  = '';
        spDuocChon = null;
        await fetchChiTiet(idPhieuDangMo);
        await fetchPhieuNhap();
    }
}

// =============================================================
// SUA 1 DONG CHI TIET (popup con)
// =============================================================
function bindSuaDong() {
    document.getElementById('btnLuuSuaDong')?.addEventListener('click', async () => {
        const idCt    = parseInt(document.getElementById('suaDongId').value);
        const soLuong = parseInt(document.getElementById('suaDongSoLuong').value);
        const donGia  = parseFloat(document.getElementById('suaDongDonGia').value);

        if (!idCt || soLuong <= 0 || donGia < 0) {
            showToast('Dữ liệu không hợp lệ!', 'warn'); return;
        }
        const res = await pnFetch('chi-tiet', 'PUT', { id: idCt, so_luong: soLuong, don_gia: donGia });
        showToast(res.message, res.success ? 'success' : 'error');
        if (res.success) {
            window.location.hash = '#popup-suaphieu';
            await fetchChiTiet(idPhieuDangMo);
            await fetchPhieuNhap();
        }
    });
}

function moSuaDong(idCt, tenSP, soLuong, donGia) {
    document.getElementById('suaDongId').value       = idCt;
    document.getElementById('suaDongTen').value      = tenSP;
    document.getElementById('suaDongSoLuong').value  = soLuong;
    document.getElementById('suaDongDonGia').value   = donGia;
    window.location.hash = '#popup-suadong';
}

async function xoaDongCT(idCt, tenSP) {
    if (!confirm(`Xóa "${tenSP}" khỏi phiếu?`)) return;
    const res = await pnFetch('chi-tiet', 'DELETE', { id: idCt });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        await fetchChiTiet(idPhieuDangMo);
        await fetchPhieuNhap();
    }
}

// =============================================================
// HOAN THANH PHIEU NHAP
// =============================================================
async function hoanThanhPhieu() {
    if (!idPhieuDangMo) return;
    const pn = danhSachPhieu.find(p => p.id == idPhieuDangMo);
    if (!confirm(
        `Xác nhận hoàn thành phiếu ${pn?.ma_phieu || ''}?\n\n` +
        `⚠️ Sau khi hoàn thành:\n` +
        `• Số lượng tồn sản phẩm sẽ được cộng thêm\n` +
        `• Giá vốn sẽ được cập nhật theo giá nhập mới\n` +
        `• Không thể sửa hoặc xóa phiếu này nữa!`
    )) return;

    const res = await pnFetch('hoan-thanh', 'POST', { id: idPhieuDangMo });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        trangThaiDangMo = 'hoan_thanh';
        await fetchPhieuNhap();
        // Reload lai popup de cap nhat UI
        moPopupPhieu(idPhieuDangMo);
    }
}

// =============================================================
// XOA PHIEU NHAP
// =============================================================
async function xoaPhieu(id, maPhieu) {
    if (!confirm(`Xóa phiếu "${maPhieu}" và toàn bộ chi tiết?`)) return;
    const res = await pnFetch('phieu-nhap', 'DELETE', { id });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) await fetchPhieuNhap();
}

// =============================================================
// TIEN ICH
// =============================================================
function fmtVND(v) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' })
                   .format(v || 0);
}
function fmtDate(str) {
    if (!str) return '—';
    const d = new Date(str);
    return d.toLocaleDateString('vi-VN');
}
function todayStr() {
    return new Date().toISOString().split('T')[0];
}
function escJ(s) {
    return (s || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
}
function badgeTrangThai(tt) {
    return tt === 'hoan_thanh'
        ? `<span class="badge-hienthi"><i class="fas fa-check"></i> Hoàn thành</span>`
        : `<span class="badge-chuaht"><i class="fas fa-clock"></i> Chưa hoàn thành</span>`;
}

function showToast(msg, type = 'success') {
    let wrap = document.getElementById('toastWrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'toastWrap';
        wrap.style.cssText =
            'position:fixed;top:20px;right:20px;z-index:99999;' +
            'display:flex;flex-direction:column;gap:8px';
        document.body.appendChild(wrap);
    }
    const colors = { success:'#27ae60', error:'#e74c3c', warn:'#f39c12' };
    const icons  = { success:'✅', error:'❌', warn:'⚠️' };
    const div = document.createElement('div');
    div.style.cssText =
        `background:${colors[type]||'#333'};color:#fff;padding:12px 18px;` +
        `border-radius:10px;font-size:14px;max-width:360px;` +
        `box-shadow:0 4px 18px rgba(0,0,0,.25);` +
        `display:flex;align-items:center;gap:9px;animation:pnSlide .3s ease`;
    div.innerHTML = `<span>${icons[type]||''}</span><span>${msg}</span>`;
    wrap.appendChild(div);
    setTimeout(() => {
        div.style.transition = 'opacity .4s';
        div.style.opacity = '0';
        setTimeout(() => div.remove(), 420);
    }, 3800);
}

// CSS dong bo vao trang
const _st = document.createElement('style');
_st.textContent = `
@keyframes pnSlide {from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}
.badge-hienthi{background:#d4edda;color:#155724;padding:3px 10px;border-radius:12px;font-size:12px;white-space:nowrap}
.badge-chuaht {background:#fff3cd;color:#856404;padding:3px 10px;border-radius:12px;font-size:12px;white-space:nowrap}

/* Popup lon cho phieu nhap */
.popup-phieu-large {
  width: min(860px, 96vw) !important;
  max-height: 90vh;
  overflow-y: auto;
}
.popup-info-row {
  display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start;
  background:#f8f9fa;padding:12px;border-radius:8px;margin-bottom:4px;
}
.popup-field { display:flex;flex-direction:column;gap:4px;flex:1;min-width:160px }
.popup-field label { font-weight:600;font-size:13px;color:#444 }
.popup-field input {
  padding:8px 10px;border:1px solid #ccc;border-radius:7px;
  font-size:14px;box-sizing:border-box
}
.popup-field input:disabled { background:#f0f0f0;color:#999 }

/* Khu tim san pham */
.khu-tim-sp {
  background:#f0f8ff;border:1px solid #bee3f8;
  padding:14px;border-radius:10px;margin-bottom:12px;position:relative;
}
.khu-tim-sp input[type=text],
.khu-tim-sp input[type=number] {
  width:100%;padding:8px 10px;border:1px solid #ccc;
  border-radius:7px;font-size:14px;box-sizing:border-box;
}
.btn-them-sp {
  background:#27ae60;color:#fff;border:none;padding:8px 18px;
  border-radius:7px;cursor:pointer;font-weight:700;font-size:14px;
  white-space:nowrap;transition:.2s;
}
.btn-them-sp:hover { background:#219150 }
.btn-luu-info {
  background:#2980b9;color:#fff;border:none;padding:9px 16px;
  border-radius:7px;cursor:pointer;font-weight:700;font-size:13px;
  white-space:nowrap;transition:.2s;
}
.btn-luu-info:hover { background:#1f6fa0 }

/* Goi y san pham */
.goi-y-sp {
  position:absolute;left:14px;right:14px;
  background:#fff;border:1px solid #ccc;border-radius:8px;
  box-shadow:0 6px 20px rgba(0,0,0,.15);
  max-height:220px;overflow-y:auto;z-index:2000;
}
.goi-y-item {
  padding:10px 14px;cursor:pointer;font-size:13px;
  border-bottom:1px solid #f0f0f0;transition:background .15s;
}
.goi-y-item:hover { background:#eaf4ff }

/* Nut hoan thanh */
.btn-hoan-thanh {
  background:linear-gradient(135deg,#27ae60,#2ecc71);
  color:#fff;border:none;padding:11px 28px;border-radius:8px;
  cursor:pointer;font-weight:700;font-size:15px;
  box-shadow:0 3px 12px rgba(39,174,96,.4);transition:.2s;
}
.btn-hoan-thanh:hover {
  background:linear-gradient(135deg,#219150,#27ae60);
  transform:translateY(-1px);
}

/* Bang chi tiet trong popup */
#bangChiTietPhieu { width:100%;border-collapse:collapse;font-size:13px }
#bangChiTietPhieu th,
#bangChiTietPhieu td {
  border:1px solid #ddd;padding:7px 10px;
  text-align:left;vertical-align:middle;
}
#bangChiTietPhieu th { background:#eaf3ff;font-weight:700 }
#bangChiTietPhieu tfoot td {
  background:#f8f9fa;border-top:2px solid #ccc;
}
`;
document.head.appendChild(_st);