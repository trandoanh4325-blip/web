// =============================================================
// JSAdmin/SanPham.js  –  Kết nối với phpAdmin/SanPham.php
// =============================================================

const API_BASE = '../Admin/process_SanPham.php';

let loaiList      = [];
let spList        = [];
let editingLoaiId = null;
let editingSpId   = null;

// =============================================================
// KHỞI TẠO
// =============================================================
document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([fetchLoaiList(), fetchSpList()]);
    renderLoaiTable();
    renderSpTable();
    bindFormLoai();
    bindPopupLoai();
    bindFormThem();
    bindFormSua();
    bindSearch();
    bindPreviewHinh();
});

// =============================================================
// API FETCH CHUNG
// =============================================================
async function apiFetch(resource, method = 'GET', body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    try {
        const res  = await fetch(`${API_BASE}?resource=${resource}`, opts);
        const json = await res.json();
        return json;
    } catch (err) {
        showToast('Lỗi kết nối server: ' + err.message, 'error');
        return { success: false, message: err.message };
    }
}

async function fetchLoaiList() {
    const res = await apiFetch('loai-san-pham');
    if (res.success) loaiList = res.data || [];
}

async function fetchSpList() {
    const res = await apiFetch('san-pham');
    if (res.success) spList = res.data || [];
}

// =============================================================
// LOẠI SẢN PHẨM – HIỂN THỊ
// =============================================================
function renderLoaiTable() {
    const tbody = document.querySelector('#danhSachLoai tbody');
    if (!tbody) return;

    if (loaiList.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:#999;padding:16px">
            Chưa có loại sản phẩm nào.</td></tr>`;
        updateLoaiDropdown();
        return;
    }

    tbody.innerHTML = '';
    loaiList.forEach((loai, i) => {
        const fmt = loai.ngay_them
            ? new Date(loai.ngay_them).toLocaleDateString('vi-VN')
            : '—';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${i + 1}</td>
            <td>${loai.ma_loai || ''}</td>
            <td>${loai.ten_loai}</td>
            <td>${fmt}</td>
            <td>
              <button class="btn-sua1" onclick="openEditLoai(${loai.id})">
                <i class="fas fa-edit"></i> Sửa
              </button>
              <button class="btn-xoa1" onclick="confirmDeleteLoai(${loai.id},'${escHtml(loai.ten_loai)}')">
                <i class="fas fa-trash"></i> Xoa
              </button>
            </td>`;
        tbody.appendChild(tr);
    });
    updateLoaiDropdown();
}

// =============================================================
// LOẠI SẢN PHẨM – THÊM
// =============================================================
function bindFormLoai() {
    const btn = document.querySelector('#formLoaiSanPham button');
    if (btn) btn.addEventListener('click', e => { e.preventDefault(); submitAddLoai(); });
}

async function submitAddLoai() {
    const tenLoai  = document.getElementById('tenLoai').value.trim();
    const ngayThem = document.getElementById('tungay').value;
    if (!tenLoai)   { showToast('Vui lòng nhập tên loại sản phẩm!', 'warn'); return; }
    if (!ngayThem)  { showToast('Vui lòng chọn ngày thêm!', 'warn');         return; }

    const res = await apiFetch('loai-san-pham', 'POST', { ten_loai: tenLoai, ngay_them: ngayThem });
    if (res.success) {
        showToast(`Đã thêm loại: ${tenLoai} (${res.ma_loai})`, 'success');
        document.getElementById('formLoaiSanPham').reset();
        // Reset lại ngày mặc định
        document.getElementById('tungay').value = todayStr();
        await fetchLoaiList();
        renderLoaiTable();
    } else {
        showToast(res.message, 'error');
    }
}

// =============================================================
// LOẠI SẢN PHẨM – SỬA (popup)
// =============================================================
function bindPopupLoai() {
    const btn = document.querySelector('#formLoaiSanPhamPopup button');
    if (btn) btn.addEventListener('click', e => { e.preventDefault(); submitEditLoai(); });
}

function openEditLoai(id) {
    const loai = loaiList.find(l => l.id == id);
    if (!loai) { showToast('Không tìm thấy loại!', 'error'); return; }
    editingLoaiId = id;
    document.getElementById('editTenLoai').value = loai.ten_loai;
    document.getElementById('tungayPopup').value  = loai.ngay_them || '';
    window.location.hash = '#popup-themsp';
}

async function submitEditLoai() {
    if (!editingLoaiId) return;
    const tenLoai  = document.getElementById('editTenLoai').value.trim();
    const ngayThem = document.getElementById('tungayPopup').value;
    if (!tenLoai) { showToast('Tên loại không được để trống!', 'warn'); return; }

    const res = await apiFetch('loai-san-pham', 'PUT',
        { id: editingLoaiId, ten_loai: tenLoai, ngay_them: ngayThem });
    if (res.success) {
        showToast('Cập nhật loại thành công!', 'success');
        editingLoaiId = null;
        window.location.hash = '';
        await fetchLoaiList();
        renderLoaiTable();
    } else {
        showToast(res.message, 'error');
    }
}

// =============================================================
// LOẠI SẢN PHẨM – XÓA
// =============================================================
async function confirmDeleteLoai(id, ten) {
    if (!confirm(`Xác nhận xóa loại "${ten}"?\nTất cả sản phẩm loại này phải được xóa trước!`)) return;
    const res = await apiFetch('loai-san-pham', 'DELETE', { id });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) { await fetchLoaiList(); renderLoaiTable(); }
}

// =============================================================
// SẢN PHẨM – HIỂN THỊ (có lọc/tìm kiếm)
// =============================================================
function renderSpTable(filter = {}) {
    const tbody = document.getElementById('tableProductBody');
    if (!tbody) return;

    // Loc du lieu
    let list = spList;
    if (filter.keyword) {
        const kw = filter.keyword.toLowerCase();
        list = list.filter(sp =>
            sp.ma_sp.toLowerCase().includes(kw) ||
            sp.ten_sp.toLowerCase().includes(kw));
    }
    if (filter.loai)  list = list.filter(sp => sp.id_loai == filter.loai);
    if (filter.trang) list = list.filter(sp => sp.hien_trang === filter.trang);

    if (list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="13" style="text-align:center;color:#999;padding:16px">
            Không có sản phẩm phù hợp.</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    list.forEach((sp, i) => {
        const badge = sp.hien_trang === 'hien_thi'
            ? '<span class="badge-hienthi"><i class="fas fa-eye"></i> Đang bán</span>'
            : '<span class="badge-an"><i class="fas fa-eye-slash"></i> An</span>';
        const imgSrc   = sp.hinh_anh
            ? `../Image/${sp.hinh_anh}`
            : '../Image/placeholder.png';
        const moTaShort = (sp.mo_ta || '').substring(0, 60)
            + ((sp.mo_ta || '').length > 60 ? '...' : '');

        const tr = document.createElement('tr');
        if (sp.hien_trang === 'an') tr.style.opacity = '0.55';
        tr.innerHTML = `
            <td>${i + 1}</td>
            <td><img src="${imgSrc}"
                     style="width:65px;height:65px;object-fit:cover;border-radius:6px"
                     onerror="this.src='../Image/placeholder.png'" /></td>
            <td><strong>${sp.ma_sp}</strong></td>
            <td>${sp.ten_sp}</td>
            <td>${sp.ten_loai || '—'}</td>
            <td>${sp.don_vi_tinh || ''}</td>
            <td style="text-align:right">${sp.so_luong_ton}</td>
            <td style="text-align:right">${fmtVND(sp.gia_von)}</td>
            <td style="text-align:center">${sp.ty_le_loi_nhuan}%</td>
            <td style="text-align:right">${fmtVND(sp.gia_ban)}</td>
            <td><p style="font-size:11px;margin:0;max-width:140px">${moTaShort}</p></td>
            <td>${badge}</td>
            <td>
              <button class="btn-sua1" onclick="openEditSp(${sp.id})">
                <i class="fas fa-edit"></i> Sửa
              </button>
              <button class="btn-xoa1" onclick="confirmDeleteSp(${sp.id},'${escHtml(sp.ten_sp)}')">
                <i class="fas fa-trash"></i> Xoa
              </button>
            </td>`;
        tbody.appendChild(tr);
    });
}

// =============================================================
// SẢN PHẨM – TÌM KIẾM / LỌC
// =============================================================
function bindSearch() {
    const searchEl  = document.getElementById('searchSP');
    const filterEl  = document.getElementById('filterLoai');
    const filterTr  = document.getElementById('filterTrang');

    const doFilter = () => renderSpTable({
        keyword : searchEl?.value  || '',
        loai    : filterEl?.value  || '',
        trang   : filterTr?.value  || '',
    });

    searchEl?.addEventListener('input', doFilter);
    filterEl?.addEventListener('change', doFilter);
    filterTr?.addEventListener('change', doFilter);
}

// =============================================================
// SẢN PHẨM – THÊM
// =============================================================
function bindFormThem() {
    const form = document.getElementById('formThemSanPham');
    if (!form) return;
    form.addEventListener('submit', async e => {
        e.preventDefault();
        await submitAddSp();
    });

    // Auto tinh gia ban
    const gv   = form.querySelector('#giaVon');
    const tyle = form.querySelector('#tyleLN');
    const gb   = form.querySelector('#giaBan');
    const calc = () => {
        if (gv && tyle && gb && parseFloat(gv.value) > 0)
            gb.value = Math.round(parseFloat(gv.value) * (1 + parseFloat(tyle.value || 0) / 100));
    };
    gv?.addEventListener('input', calc);
    tyle?.addEventListener('input', calc);
}

async function submitAddSp() {
    const form   = document.getElementById('formThemSanPham');
    const hinhEl = form.querySelector('#hinhAnh');
    const hinh   = hinhEl?.files[0] || null;

    let hinhPath = '';
    if (hinh) {
        hinhPath = await uploadHinh(hinh);
        if (hinhPath === null) return; // loi upload
    }

    const payload = collectSpForm(form, {
        maSP : '#maSP', tenSP : '#tenSP', loaiSP : '#loaiSP',
        dvt  : '#donViTinh', giaVon : '#giaVon', tyleLN : '#tyleLN',
        giaBan: '#giaBan', soLuong: '#soLuongTon',
        hienTrang: '#hienTrang', moTa: '#moTa',
    }, hinhPath);
    if (!payload) return;

    const res = await apiFetch('san-pham', 'POST', payload);
    if (res.success) {
        showToast('Thêm sản phẩm thành công!', 'success');
        form.reset();
        document.getElementById('previewHinhThem').style.display = 'none';
        await fetchSpList();
        renderSpTable();
        updateLoaiDropdown();
    } else {
        showToast(res.message, 'error');
    }
}

// =============================================================
// SẢN PHẨM – SỬA (popup)
// =============================================================
function bindFormSua() {
    const form = document.getElementById('formSuaSanPham');
    if (!form) return;
    form.addEventListener('submit', async e => {
        e.preventDefault();
        await submitEditSp();
    });

    // Auto tính giá bán trong popup sửa
    const gv   = form.querySelector('#suaGiaVon');
    const tyle = form.querySelector('#suaTyleLN');
    const gb   = form.querySelector('#suaGiaBan');
    const calc = () => {
        if (gv && tyle && gb && parseFloat(gv.value) > 0)
            gb.value = Math.round(parseFloat(gv.value) * (1 + parseFloat(tyle.value || 0) / 100));
    };
    gv?.addEventListener('input', calc);
    tyle?.addEventListener('input', calc);

    // Nút bỏ hình
    document.getElementById('btnXoaHinh')?.addEventListener('click', () => {
        document.getElementById('previewHinhSua').style.display = 'none';
        document.getElementById('btnXoaHinh').style.display     = 'none';
        document.getElementById('suaXoaHinh').value = '1';
    });
}

function openEditSp(id) {
    const sp = spList.find(s => s.id == id);
    if (!sp) { showToast('Không tìm thấy sản phẩm!', 'error'); return; }
    editingSpId = id;

    document.getElementById('suaMaSP').value        = sp.ma_sp;
    document.getElementById('suaTenSP').value       = sp.ten_sp;
    document.getElementById('suaMoTa').value        = sp.mo_ta || '';
    document.getElementById('suaDonViTinh').value   = sp.don_vi_tinh || 'Cái';
    document.getElementById('suaGiaVon').value      = sp.gia_von;
    document.getElementById('suaTyleLN').value      = sp.ty_le_loi_nhuan;
    document.getElementById('suaGiaBan').value      = sp.gia_ban;
    document.getElementById('suaSoLuongTon').value  = sp.so_luong_ton;
    document.getElementById('suaHienTrang').value   = sp.hien_trang;
    document.getElementById('suaXoaHinh').value     = '0';

    // Populate dropdown loai
    const loaiSel = document.getElementById('suaLoaiSP');
    populateLoaiSelect(loaiSel);
    loaiSel.value = sp.id_loai;

    // Hiển hình cũ
    const imgEl  = document.getElementById('previewHinhSua');
    const btnXoa = document.getElementById('btnXoaHinh');
    if (sp.hinh_anh) {
        imgEl.src             = `../Image/${sp.hinh_anh}`;
        imgEl.style.display   = 'block';
        btnXoa.style.display  = 'inline-block';
    } else {
        imgEl.style.display   = 'none';
        btnXoa.style.display  = 'none';
    }

    // Reset input file
    const fileEl = document.getElementById('suaHinhAnh');
    if (fileEl) fileEl.value = '';

    window.location.hash = '#popup-suasp';
}

async function submitEditSp() {
    if (!editingSpId) return;

    const hinhEl  = document.getElementById('suaHinhAnh');
    const hinh    = hinhEl?.files[0] || null;
    const xoaHinh = document.getElementById('suaXoaHinh')?.value === '1';

    let hinhPath = '';
    if (hinh) {
        hinhPath = await uploadHinh(hinh);
        if (hinhPath === null) return;
    } else if (xoaHinh) {
        hinhPath = '__XOA__'; // bao hieu PHP xoa hinh cu
    }

    const form    = document.getElementById('formSuaSanPham');
    const payload = collectSpForm(form, {
        maSP : '#suaMaSP', tenSP : '#suaTenSP', loaiSP : '#suaLoaiSP',
        dvt  : '#suaDonViTinh', giaVon : '#suaGiaVon', tyleLN : '#suaTyleLN',
        giaBan: '#suaGiaBan', soLuong: '#suaSoLuongTon',
        hienTrang: '#suaHienTrang', moTa: '#suaMoTa',
    }, hinhPath);
    if (!payload) return;
    payload.id = editingSpId;

    const res = await apiFetch('san-pham', 'PUT', payload);
    if (res.success) {
        showToast('Cập nhật sản phẩm thành công!', 'success');
        editingSpId = null;
        window.location.hash = '';
        await fetchSpList();
        renderSpTable();
    } else {
        showToast(res.message, 'error');
    }
}

// =============================================================
// SẢN PHẨM – XÓA
// =============================================================
async function confirmDeleteSp(id, ten) {
    if (!confirm(
        `Xác nhận xóa sản phẩm "${ten}"?\n\n` +
        `• Chưa nhập hàng   → xóa hẳn khỏi CSDL\n` +
        `• Đã có phiếu nhập → chỉ đặt trạng thái Ẩn`
    )) return;
    const res = await apiFetch('san-pham', 'DELETE', { id });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) { await fetchSpList(); renderSpTable(); }
}

// =============================================================
// UPLOAD HÌNH ẢNH
// =============================================================
async function uploadHinh(file) {
    const fd = new FormData();
    fd.append('hinh_anh', file);
    try {
        const res  = await fetch(`${API_BASE}?resource=upload-hinh`, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) return json.path;
        showToast('Upload hình thất bại: ' + json.message, 'error');
        return null;
    } catch (err) {
        showToast('Lỗi upload: ' + err.message, 'error');
        return null;
    }
}

// =============================================================
// XEM TRƯỚC HÌNH KHI CHỌN FILE
// =============================================================
function bindPreviewHinh() {
    // Form thêm sản phẩm
    document.getElementById('hinhAnh')?.addEventListener('change', function () {
        const wrap = document.getElementById('previewHinhThem');
        const img  = document.getElementById('imgPreviewThem');
        if (this.files[0]) {
            img.src = URL.createObjectURL(this.files[0]);
            wrap.style.display = 'block';
        } else {
            wrap.style.display = 'none';
        }
    });

    // Form sửa sản phẩm
    document.getElementById('suaHinhAnh')?.addEventListener('change', function () {
        if (this.files[0]) {
            const imgEl = document.getElementById('previewHinhSua');
            imgEl.src             = URL.createObjectURL(this.files[0]);
            imgEl.style.display   = 'block';
            document.getElementById('btnXoaHinh').style.display  = 'inline-block';
            document.getElementById('suaXoaHinh').value = '0';
        }
    });
}

// =============================================================
// DROPDOWN LOẠI SẢN PHẨM
// =============================================================
function updateLoaiDropdown() {
    // Dropdown trong form thêm
    const selThem = document.getElementById('loaiSP');
    if (selThem) populateLoaiSelect(selThem);

    // Dropdown filter bảng
    const selFilter = document.getElementById('filterLoai');
    if (selFilter) {
        const cur = selFilter.value;
        selFilter.innerHTML = '<option value="">-- Tất cả loại --</option>';
        loaiList.forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.id; opt.textContent = l.ten_loai;
            selFilter.appendChild(opt);
        });
        if (cur) selFilter.value = cur;
    }
}

function populateLoaiSelect(sel) {
    const cur = sel.value;
    sel.innerHTML = '<option value="">-- Chọn loại --</option>';
    loaiList.forEach(l => {
        const opt = document.createElement('option');
        opt.value = l.id; opt.textContent = `${l.ma_loai} – ${l.ten_loai}`;
        sel.appendChild(opt);
    });
    if (cur) sel.value = cur;
}

// =============================================================
// THU THẬP DỮ LIỆU FORM SẢN PHẨM
// =============================================================
function collectSpForm(form, ids, hinhPath) {
    const g = sel => form.querySelector(sel);

    const maSP   = (g(ids.maSP)?.value   || '').trim();
    const tenSP  = (g(ids.tenSP)?.value  || '').trim();
    const idLoai =  g(ids.loaiSP)?.value || '';
    const dvt    = (g(ids.dvt)?.value    || 'Cai').trim();
    const giaVon =  parseFloat(g(ids.giaVon)?.value  || 0);
    const tyleLN =  parseFloat(g(ids.tyleLN)?.value  || 0);
    const giaBan =  parseFloat(g(ids.giaBan)?.value  || 0);
    const sl     =  parseInt(g(ids.soLuong)?.value   || 0);
    const htrang =  g(ids.hienTrang)?.value || 'hien_thi';
    const moTa   = (g(ids.moTa)?.value   || '').trim();

    if (!maSP)  { showToast('Mã sản phẩm không được để trống!', 'warn'); return null; }
    if (!tenSP) { showToast('Tên sản phẩm không được để trống!', 'warn'); return null; }
    if (!idLoai){ showToast('Vui lòng chọn loại sản phẩm!', 'warn');      return null; }

    return {
        ma_sp: maSP, ten_sp: tenSP, id_loai: idLoai,
        don_vi_tinh: dvt, so_luong_ton: sl,
        gia_von: giaVon, ty_le_loi_nhuan: tyleLN, gia_ban: giaBan,
        hinh_anh: hinhPath || '', mo_ta: moTa, hien_trang: htrang,
        ngay_them: todayStr(),
    };
}

// =============================================================
// TOAST NOTIFICATION
// =============================================================
function showToast(msg, type = 'success') {
    let wrap = document.getElementById('toastWrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'toastWrap';
        wrap.style.cssText =
            'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:8px';
        document.body.appendChild(wrap);
    }
    const colors = { success:'#27ae60', error:'#e74c3c', warn:'#f39c12' };
    const icons  = { success:'✅', error:'❌', warn:'⚠️' };
    const div    = document.createElement('div');
    div.style.cssText =
        `background:${colors[type]||'#333'};color:#fff;padding:12px 18px;border-radius:10px;` +
        `font-size:14px;max-width:340px;box-shadow:0 4px 18px rgba(0,0,0,.25);` +
        `display:flex;align-items:center;gap:9px;animation:spSlide .3s ease`;
    div.innerHTML = `<span>${icons[type]||''}</span><span>${msg}</span>`;
    wrap.appendChild(div);
    setTimeout(() => {
        div.style.transition = 'opacity .4s';
        div.style.opacity    = '0';
        setTimeout(() => div.remove(), 420);
    }, 3600);
}

// =============================================================
// TIEN ICH
// =============================================================
function fmtVND(v) {
    return new Intl.NumberFormat('vi-VN', { style:'currency', currency:'VND' }).format(v || 0);
}

function todayStr() {
    return new Date().toISOString().split('T')[0];
}

function escHtml(str) {
    return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

// CSS cho toast + badge
const _style = document.createElement('style');
_style.textContent = `
@keyframes spSlide { from{transform:translateX(110%);opacity:0} to{transform:translateX(0);opacity:1} }
.badge-hienthi { background:#d4edda;color:#155724;padding:3px 9px;border-radius:12px;font-size:11px;white-space:nowrap }
.badge-an      { background:#f8d7da;color:#721c24;padding:3px 9px;border-radius:12px;font-size:11px;white-space:nowrap }
.popup-large   { width:min(680px,95vw)!important;max-height:90vh;overflow-y:auto }
.form-row      { display:flex;gap:14px;margin-bottom:10px;flex-wrap:wrap }
.form-group    { flex:1;min-width:160px;display:flex;flex-direction:column;gap:4px }
.form-group label { font-weight:600;font-size:13px;color:#333 }
.form-group input,
.form-group select,
.form-group textarea {
  padding:8px 10px;border:1px solid #ccc;border-radius:7px;
  font-size:14px;box-sizing:border-box;width:100%
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color:#0195b2;outline:none }
.full-width { flex:1 1 100%!important }
.required   { color:#e74c3c }
`;
document.head.appendChild(_style);