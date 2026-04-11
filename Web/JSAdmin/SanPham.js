// =============================================================
// JSAdmin/SanPham.js  –  Kết nối với Admin/process_SanPham.php
// Hỗ trợ MULTIPLE IMAGES & FIX REQUEST LOOP
// =============================================================

const API_BASE = '../Admin/process_SanPham.php';

let loaiList      = [];
let spList        = [];
let editingLoaiMa = null;
let editingSpMa   = null;
let filesThemTam  = []; // Lưu tạm file thêm mới
let filesSuaTam   = []; // Lưu tạm file sửa mới

// Control flag riêng cho từng loại request (tránh xung đột khi gọi song song)
let isLoadingLoai  = false;
let isLoadingSp    = false;
let originalSpData = null; // Snapshot dữ liệu ban đầu khi mở popup sửa

// =============================================================
// VALIDATION & ERROR DISPLAY
// =============================================================
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    // Xóa error cũ nếu có
    clearFieldError(fieldId);
    
    // Thêm class error vào field
    field.classList.add('field-error');
    field.style.borderColor = '#e74c3c';
    
    // Tạo error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error-message';
    errorDiv.style.cssText = `
        color:#e74c3c;font-size:12px;margin-top:4px;margin-bottom:8px;
        display:flex;align-items:center;gap:4px
    `;
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    errorDiv.id = `error-${fieldId}`;
    
    // Chèn sau field
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

function clearAllFieldErrors(formId) {
    const form = document.getElementById(formId) || document.querySelector(formId);
    if (!form) return;
    
    const fields = form.querySelectorAll('input, select, textarea');
    fields.forEach(field => {
        if (field.id) clearFieldError(field.id);
    });
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
    bindPreviewHinhThem();
    bindPreviewHinhSua();
    bindCloseSuaPopup();
});

// =============================================================
// API FETCH CHUNG (với timeout)
// =============================================================
async function apiFetch(resource, method = 'GET', body = null, timeout = 30000) {
    const opts = { 
        method, 
        headers: { 'Content-Type': 'application/json' },
        signal: AbortSignal.timeout(timeout)
    };
    if (body) opts.body = JSON.stringify(body);
    try {
        // Xử lý query string trong resource (e.g., 'san-pham-hinh?ma_sp=SP001')
        const url = API_BASE + '?resource=' + encodeURIComponent(resource.split('?')[0]) + 
                    (resource.includes('?') ? '&' + resource.split('?')[1] : '');
        const res = await fetch(url, opts);
        return await res.json();
    } catch (err) {
        console.error('API Error:', err);
        return { success: false, message: 'Lỗi kết nối: ' + err.message };
    }
}

async function fetchLoaiList() {
    if (isLoadingLoai) return;
    isLoadingLoai = true;
    try {
        const res = await apiFetch('loai-san-pham');
        if (res.success) loaiList = res.data || [];
    } finally {
        isLoadingLoai = false;
    }
}

async function fetchSpList() {
    if (isLoadingSp) return;
    isLoadingSp = true;
    try {
        const res = await apiFetch('san-pham');
        if (res.success) spList = res.data || [];
    } finally {
        isLoadingSp = false;
    }
}

// =============================================================
// LOẠI SẢN PHẨM – HIỂN THỊ
// =============================================================
function renderLoaiTable() {
    const tbody = document.querySelector('#danhSachLoai tbody');
    if (!tbody) return;

    if (loaiList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#999">Chưa có loại nào</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    loaiList.forEach((loai, i) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${i + 1}</td>
            <td>${escHtml(loai.ma_loai)}</td>
            <td>${escHtml(loai.ten_loai)}</td>
            <td>${loai.ngay_them || ''}</td>
            <td>
                <button class="btn-sua1" type="button" onclick="openEditLoai('${loai.ma_loai}'); return false;">
                    <i class="fas fa-edit"></i> Sửa
                </button>
                <button class="btn-xoa1" type="button" onclick="confirmDeleteLoai('${loai.ma_loai}', '${escHtml(loai.ten_loai)}'); return false;">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    updateLoaiDropdown();
}

// =============================================================
// LOẠI SẢN PHẨM – THÊM
// =============================================================
function bindFormLoai() {
    const btn = document.querySelector('#formLoaiSanPham button');
    if (btn) btn.addEventListener('click', submitAddLoai);
}

async function submitAddLoai() {
    clearAllFieldErrors('#formLoaiSanPham');
    
    const tenLoai  = document.getElementById('tenLoai').value.trim();
    const ngayThem = document.getElementById('tungay').value;
    let hasError = false;
    
    if (!tenLoai) {
        showFieldError('tenLoai', 'Vui lòng nhập tên loại sản phẩm');
        hasError = true;
    }
    if (!ngayThem) {
        showFieldError('tungay', 'Vui lòng chọn ngày thêm');
        hasError = true;
    }
    
    if (hasError) return;

    const res = await apiFetch('loai-san-pham', 'POST', { ten_loai: tenLoai, ngay_them: ngayThem });
    if (res.success) {
        showToast(res.message, 'success');
        document.getElementById('tenLoai').value = '';
        document.getElementById('tungay').value = new Date().toISOString().split('T')[0];
        clearAllFieldErrors('#formLoaiSanPham');
        await fetchLoaiList();
        renderLoaiTable();
    } else {
        showToast(res.message, 'error');
    }
}

// =============================================================
// LOẠI SẢN PHẨM – SỬA
// =============================================================
function bindPopupLoai() {
    const btn = document.querySelector('#formLoaiSanPhamPopup button');
    if (btn) btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        submitEditLoai();
    });
}

function openEditLoai(maLoai) {
    const loai = loaiList.find(l => l.ma_loai === maLoai);
    if (!loai) { showToast('Không tìm thấy loại!', 'error'); return; }
    editingLoaiMa = maLoai;
    document.getElementById('editTenLoai').value = loai.ten_loai;
    document.getElementById('tungayPopup').value  = loai.ngay_them || '';
    window.location.hash = '#popup-themsp';
}

async function submitEditLoai() {
    if (!editingLoaiMa) { showToast('Vui lòng chọn loại để sửa!', 'error'); return; }
    const tenLoai  = document.getElementById('editTenLoai').value.trim();
    const ngayThem = document.getElementById('tungayPopup').value;
    if (!tenLoai) { showToast('Vui lòng nhập tên loại!', 'error'); return; }

    const res = await apiFetch('loai-san-pham', 'PUT',
        { ma_loai: editingLoaiMa, ten_loai: tenLoai, ngay_them: ngayThem });
    if (res.success) {
        showToast(res.message, 'success');
        await fetchLoaiList();
        renderLoaiTable();
        window.location.hash = '';
    } else {
        showToast(res.message, 'error');
    }
}

// =============================================================
// LOẠI SẢN PHẨM – XÓA
// =============================================================
async function confirmDeleteLoai(maLoai, ten) {
    if (!confirm(`Xác nhận xóa loại "${ten}"?\nTất cả sản phẩm loại này phải được xóa trước!`)) return;
    const res = await apiFetch('loai-san-pham', 'DELETE', { ma_loai: maLoai });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        await fetchLoaiList();
        renderLoaiTable();
    }
}

// =============================================================
// SẢN PHẨM – HIỂN THỊ (có lọc/tìm kiếm)
// =============================================================
function renderSpTable(filter = {}) {
    const tbody = document.getElementById('tableProductBody');
    if (!tbody) return;

    let list = spList;
    if (filter.keyword) {
        const kw = filter.keyword.toLowerCase();
        list = list.filter(sp => 
            sp.ma_sp.toLowerCase().includes(kw) || 
            sp.ten_sp.toLowerCase().includes(kw)
        );
    }
    if (filter.loai)  list = list.filter(sp => sp.ma_loai === filter.loai);
    if (filter.trang) list = list.filter(sp => sp.hien_trang === filter.trang);

    if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="13" style="text-align:center;color:#999">Không tìm thấy sản phẩm nào</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    list.forEach((sp, i) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${i + 1}</td>
            <td><img src="../ImageSanPham/${escHtml(sp.hinh_anh || '')}" alt="" class="S1" style="width:50px;height:50px;object-fit:cover;" /></td>
            <td>${escHtml(sp.ma_sp)}</td>
            <td>${escHtml(sp.ten_sp)}</td>
            <td>${sp.ten_loai || ''}</td>
            <td>${escHtml(sp.don_vi_tinh)}</td>
            <td>${sp.so_luong_ton}</td>
            <td>${fmtVND(sp.gia_von)}</td>
            <td>${sp.ty_le_loi_nhuan}%</td>
            <td><a href="giaban.php" title="Quản lý giá bán" style="color:#0195b2;font-weight:600;text-decoration:none;">${fmtVND(sp.gia_ban)} <i class="fas fa-external-link-alt" style="font-size:10px"></i></a></td>
            <td>${(sp.mo_ta || '').substring(0, 40)}...</td>
            <td><span class="badge-${sp.hien_trang}">${sp.hien_trang === 'hien_thi' ? 'Hiển thị' : 'Ẩn'}</span></td>
            <td>
                <button class="btn-sua1" onclick="openEditSp('${sp.ma_sp}'); return false;" type="button"><i class="fas fa-edit"></i> Sửa</button>
                <button class="btn-xoa1" onclick="confirmDeleteSp('${sp.ma_sp}', '${escHtml(sp.ten_sp)}'); return false;"><i class="fas fa-trash"></i> Xóa</button>
            </td>
        `;
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

    if (searchEl)  searchEl.addEventListener('input', doFilter);
    if (filterEl)  filterEl.addEventListener('change', doFilter);
    if (filterTr)  filterTr.addEventListener('change', doFilter);
}

// =============================================================
// PREVIEW HÌNH ẢNH KHI THÊM (MULTIPLE)
// =============================================================
// Render preview ảnh thêm mới từ mảng filesThemTam hiện tại
function renderPreviewThem() {
    const previewDiv = document.getElementById('previewHinhThem');
    if (!previewDiv) return;
    previewDiv.innerHTML = '';
    filesThemTam.forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = (evt) => {
            const div = document.createElement('div');
            div.style.cssText = 'position:relative;width:80px;height:80px;';
            div.innerHTML = `
                <img src="${evt.target.result}" alt="preview"
                     style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #ddd;" />
                <button type="button" onclick="removePreviewThem(${idx})"
                        style="position:absolute;top:-8px;right:-8px;width:24px;height:24px;
                               border-radius:50%;background:#e74c3c;color:#fff;border:none;
                               cursor:pointer;font-size:12px;padding:0;">X</button>
            `;
            previewDiv.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

// Chỉ bind 1 lần duy nhất khi init
function bindPreviewHinhThem() {
    const fileInput = document.getElementById('hinhAnhThem');
    if (!fileInput) return;
    fileInput.addEventListener('change', (e) => {
        filesThemTam = Array.from(e.target.files);
        renderPreviewThem();
    });
}

function removePreviewThem(idx) {
    filesThemTam.splice(idx, 1);
    const dt = new DataTransfer();
    filesThemTam.forEach(f => dt.items.add(f));
    document.getElementById('hinhAnhThem').files = dt.files;
    renderPreviewThem(); // re-render từ array, không rebind
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

    // Auto tính giá bán
    const gv   = form.querySelector('#giaVon');
    const tyle = form.querySelector('#tyleLN');
    const gb   = form.querySelector('#giaBan');
    const calc = () => {
        const giaVon = parseFloat(gv?.value || 0);
        const tyleLN = parseFloat(tyle?.value || 0);
        const giaBan = giaVon * (1 + tyleLN / 100);
        if (gb) gb.value = giaBan.toFixed(0);
    };
    if (gv) gv.addEventListener('input', calc);
    if (tyle) tyle.addEventListener('input', calc);
}

async function submitAddSp() {
    clearAllFieldErrors('#formThemSanPham');
    const form = document.getElementById('formThemSanPham');

    const payload = {
        maSP:       form.querySelector('#maSP')?.value.trim() || '',
        tenSP:      form.querySelector('#tenSP')?.value.trim() || '',
        loaiSP:     form.querySelector('#loaiSP')?.value || '',
        donViTinh:  form.querySelector('#donViTinh')?.value.trim() || 'Cái',
        soLuongTon: parseInt(form.querySelector('#soLuongTon')?.value || 0),
        giaVon:     parseFloat(form.querySelector('#giaVon')?.value || 0),
        tyleLN:     parseFloat(form.querySelector('#tyleLN')?.value || 0),
        giaBan:     parseFloat(form.querySelector('#giaBan')?.value || 0),
        moTa:       form.querySelector('#moTa')?.value.trim() || '',
        hienTrang:  form.querySelector('#hienTrang')?.value || 'hien_thi',
    };

    let hasError = false;

    if (!payload.maSP) {
        showFieldError('maSP', 'Vui lòng nhập mã sản phẩm');
        hasError = true;
    }
    if (!payload.tenSP) {
        showFieldError('tenSP', 'Vui lòng nhập tên sản phẩm');
        hasError = true;
    }
    if (!payload.loaiSP) {
        showFieldError('loaiSP', 'Vui lòng chọn loại sản phẩm');
        hasError = true;
    }
    if (payload.soLuongTon < 0) {
        showFieldError('soLuongTon', 'Số lượng không được âm');
        hasError = true;
    }
    if (payload.giaVon < 0) {
        showFieldError('giaVon', 'Giá vốn không được âm');
        hasError = true;
    }
    if (payload.tyleLN < 0 || payload.tyleLN > 200) {
        showFieldError('tyleLN', 'Tỷ lệ lợi nhuận từ 0-200%');
        hasError = true;
    }
    if (filesThemTam.length === 0) {
        const previewBox = document.getElementById('previewThem');
        if (previewBox) {
            const msg = document.createElement('div');
            msg.style.cssText = 'color:#e74c3c;font-size:12px;margin-top:4px';
            msg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Vui lòng chọn ít nhất 1 hình ảnh';
            previewBox.parentNode.insertBefore(msg, previewBox.nextSibling);
        }
        hasError = true;
    }

    if (hasError) return;

    // Upload hình ảnh
    const hinhPaths = [];
    for (const file of filesThemTam) {
        const fd = new FormData();
        fd.append('hinh_anh', file);
        try {
            const resUpload = await fetch(API_BASE + '?resource=upload-hinh', {
                method: 'POST',
                body: fd,
                signal: AbortSignal.timeout(30000)
            });
            const dataUpload = await resUpload.json();
            if (dataUpload.success) {
                hinhPaths.push(dataUpload.path);
            } else {
                showToast('Lỗi upload: ' + dataUpload.message, 'error');
                return;
            }
        } catch (err) {
            showToast('Lỗi upload file: ' + err.message, 'error');
            return;
        }
    }

    // Ghi sản phẩm vào DB (hinh_anh = ảnh đầu tiên)
    const spData = {
        ma_sp: payload.maSP,
        ten_sp: payload.tenSP,
        ma_loai: payload.loaiSP,
        don_vi_tinh: payload.donViTinh,
        so_luong_ton: payload.soLuongTon,
        gia_von: payload.giaVon,
        ty_le_loi_nhuan: payload.tyleLN,
        gia_ban: payload.giaBan,
        hinh_anh: hinhPaths[0],
        mo_ta: payload.moTa,
        hien_trang: payload.hienTrang,
        ngay_them: todayStr()
    };

    const resAdd = await apiFetch('san-pham', 'POST', spData);
    if (!resAdd.success) {
        showToast(resAdd.message, 'error');
        return;
    }

    // Thêm các ảnh còn lại vào bảng san_pham_hinh_anh
    for (let i = 1; i < hinhPaths.length; i++) {
        await apiFetch('san-pham-hinh', 'POST', {
            ma_sp: payload.maSP,
            duong_dan: hinhPaths[i]
        });
    }

    showToast('Thêm sản phẩm thành công!', 'success');
    form.reset();
    filesThemTam = [];
    document.getElementById('previewHinhThem').innerHTML = '';
    await fetchSpList();
    renderSpTable();
}

// =============================================================
// SẢN PHẨM – SỬA
// =============================================================
function bindFormSua() {
    const form = document.getElementById('formSuaSanPham');
    if (!form) return;
    form.addEventListener('submit', async e => {
        e.preventDefault();
        await submitEditSp();
    });

    // Auto tính giá bán
    const gv   = form.querySelector('#suaGiaVon');
    const tyle = form.querySelector('#suaTyleLN');
    const gb   = form.querySelector('#suaGiaBan');
    const calc = () => {
        const giaVon = parseFloat(gv?.value || 0);
        const tyleLN = parseFloat(tyle?.value || 0);
        const giaBan = giaVon * (1 + tyleLN / 100);
        if (gb) gb.value = giaBan.toFixed(0);
    };
    if (gv) gv.addEventListener('input', calc);
    if (tyle) tyle.addEventListener('input', calc);
}

// Lấy snapshot tất cả giá trị form sửa tại thời điểm hiện tại
function getFormSuaSnapshot() {
    const form = document.getElementById('formSuaSanPham');
    if (!form) return null;
    return {
        ma_sp        : form.querySelector('#suaMaSP')?.value || '',
        ten_sp       : form.querySelector('#suaTenSP')?.value || '',
        ma_loai      : form.querySelector('#suaLoaiSP')?.value || '',
        don_vi_tinh  : form.querySelector('#suaDonViTinh')?.value || '',
        gia_von      : form.querySelector('#suaGiaVon')?.value || '',
        ty_le_ln     : form.querySelector('#suaTyleLN')?.value || '',
        gia_ban      : form.querySelector('#suaGiaBan')?.value || '',
        so_luong_ton : form.querySelector('#suaSoLuongTon')?.value || '',
        hien_trang   : form.querySelector('#suaHienTrang')?.value || '',
        mo_ta        : form.querySelector('#suaMoTa')?.value || '',
        so_hinh_moi  : filesSuaTam.length, // Có thêm ảnh mới không
    };
}

// So sánh 2 snapshot để phát hiện thay đổi
function hasFormSuaChanged() {
    if (!originalSpData) return false;
    const current = getFormSuaSnapshot();
    return JSON.stringify(current) !== JSON.stringify(originalSpData);
}

// Nút Đóng popup sửa SP: tự đóng nếu chưa sửa, hỏi nếu đã sửa
function bindCloseSuaPopup() {
    const popup     = document.getElementById('popup-suasp');
    const closeBtn  = popup?.querySelector('a.close');
    const overlayBg = popup?.querySelector('a.overlay-bg');

    const doClose = () => {
        editingSpMa    = null;
        originalSpData = null;
        filesSuaTam    = [];
        window.location.hash = '';
    };

    const handleClose = (e) => {
        if (!editingSpMa) return; // Popup chưa mở

        if (!hasFormSuaChanged()) {
            // Chưa sửa gì → thoát ngay
            e.preventDefault();
            doClose();
            return;
        }

        // Đã sửa → hỏi có muốn lưu không
        e.preventDefault();
        const choice = confirm('Bạn có thay đổi chưa lưu!\n\nNhấn OK → Lưu thay đổi\nNhấn Hủy → Thoát không lưu');
        if (choice) {
            // Bấm OK → submit form để lưu
            document.getElementById('formSuaSanPham')?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        } else {
            // Bấm Hủy → thoát không lưu
            doClose();
        }
    };

    if (closeBtn)   closeBtn.addEventListener('click', handleClose);
    if (overlayBg)  overlayBg.addEventListener('click', handleClose);
}

function renderPreviewSua() {
    const previewDiv = document.getElementById('previewHinhSuaMoi');
    if (!previewDiv) return;
    previewDiv.innerHTML = '';
    filesSuaTam.forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = (evt) => {
            const div = document.createElement('div');
            div.style.cssText = 'position:relative;width:80px;height:80px;';
            div.innerHTML = `
                <img src="${evt.target.result}" alt="preview"
                     style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #ddd;" />
                <button type="button" onclick="removePreviewSua(${idx})"
                        style="position:absolute;top:-8px;right:-8px;width:24px;height:24px;
                               border-radius:50%;background:#e74c3c;color:#fff;border:none;
                               cursor:pointer;font-size:12px;padding:0;">X</button>
            `;
            previewDiv.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

// Chỉ bind 1 lần duy nhất khi init
function bindPreviewHinhSua() {
    const fileInput = document.getElementById('suaHinhAnhThem');
    if (!fileInput) return;
    fileInput.addEventListener('change', (e) => {
        filesSuaTam = Array.from(e.target.files);
        renderPreviewSua();
    });
}

function removePreviewSua(idx) {
    filesSuaTam.splice(idx, 1);
    const dt = new DataTransfer();
    filesSuaTam.forEach(f => dt.items.add(f));
    document.getElementById('suaHinhAnhThem').files = dt.files;
    renderPreviewSua(); // re-render từ array, không rebind
}

async function openEditSp(maSp) {
    const sp = spList.find(s => s.ma_sp === maSp);
    if (!sp) { showToast('Không tìm thấy sản phẩm!', 'error'); return; }
    
    editingSpMa = maSp;
    const form = document.getElementById('formSuaSanPham');
    
    form.querySelector('#suaMaSP').value = sp.ma_sp;
    form.querySelector('#suaTenSP').value = sp.ten_sp;
    form.querySelector('#suaMoTa').value = sp.mo_ta || '';
    form.querySelector('#suaLoaiSP').value = sp.ma_loai || '';
    form.querySelector('#suaDonViTinh').value = sp.don_vi_tinh || '';
    form.querySelector('#suaGiaVon').value = sp.gia_von || 0;
    form.querySelector('#suaTyleLN').value = sp.ty_le_loi_nhuan || 0;
    form.querySelector('#suaGiaBan').value = sp.gia_ban || 0;
    form.querySelector('#suaSoLuongTon').value = sp.so_luong_ton || 0;
    form.querySelector('#suaHienTrang').value = sp.hien_trang || 'hien_thi';

    // Load hình ảnh hiện có
    await loadHinhAnhSanPham(maSp);

    filesSuaTam = [];
    document.getElementById('suaHinhAnhThem').value = '';
    document.getElementById('previewHinhSuaMoi').innerHTML = '';

    // Lưu snapshot ban đầu để phát hiện thay đổi
    originalSpData = getFormSuaSnapshot();

    window.location.hash = '#popup-suasp';
}

async function loadHinhAnhSanPham(maSp) {
    const res = await apiFetch('san-pham-hinh?ma_sp=' + maSp);
    const container = document.getElementById('hinhAnhHienTai');
    container.innerHTML = '';

    if (!res.success || !res.data || res.data.length === 0) {
        container.innerHTML = '<p style="color:#999">Chưa có ảnh nào</p>';
        return;
    }

    res.data.forEach(hinh => {
        const div = document.createElement('div');
        div.style.cssText = 'position:relative;width:80px;height:80px;';
        div.innerHTML = `
            <img src="../ImageSanPham/${escHtml(hinh.duong_dan)}" alt="SP" 
                 style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #ddd;" />
            <button type="button" onclick="confirmXoaHinh('${maSp}', ${hinh.thu_tu})" 
                    style="position:absolute;top:-8px;right:-8px;width:24px;height:24px;
                           border-radius:50%;background:#e74c3c;color:#fff;border:none;
                           cursor:pointer;font-size:12px;padding:0;">✕</button>
        `;
        container.appendChild(div);
    });
}

async function confirmXoaHinh(maSp, thuTu) {
    if (!confirm('Xóa ảnh này?')) return;
    const res = await apiFetch('san-pham-hinh-xoa', 'DELETE', { ma_sp: maSp, thu_tu: thuTu });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) await loadHinhAnhSanPham(maSp);
}

async function submitEditSp() {
    clearAllFieldErrors('#formSuaSanPham');
    
    if (!editingSpMa) { showToast('Không tìm thấy sản phẩm!', 'error'); return; }

    const form = document.getElementById('formSuaSanPham');

    const spData = {
        ma_sp_cu: editingSpMa,
        ma_sp: form.querySelector('#suaMaSP')?.value.trim() || '',
        ten_sp: form.querySelector('#suaTenSP')?.value.trim() || '',
        ma_loai: form.querySelector('#suaLoaiSP')?.value || '',
        don_vi_tinh: form.querySelector('#suaDonViTinh')?.value.trim() || 'Cái',
        so_luong_ton: parseInt(form.querySelector('#suaSoLuongTon')?.value || 0),
        gia_von: parseFloat(form.querySelector('#suaGiaVon')?.value || 0),
        ty_le_loi_nhuan: parseFloat(form.querySelector('#suaTyleLN')?.value || 0),
        gia_ban: parseFloat(form.querySelector('#suaGiaBan')?.value || 0),
        hinh_anh: '',
        mo_ta: form.querySelector('#suaMoTa')?.value.trim() || '',
        hien_trang: form.querySelector('#suaHienTrang')?.value || 'hien_thi',
        ngay_them: todayStr()
    };

    let hasError = false;
    
    if (!spData.ma_sp) {
        showFieldError('suaMaSP', 'Vui lòng nhập mã sản phẩm');
        hasError = true;
    }
    if (!spData.ten_sp) {
        showFieldError('suaTenSP', 'Vui lòng nhập tên sản phẩm');
        hasError = true;
    }
    if (!spData.ma_loai) {
        showFieldError('suaLoaiSP', 'Vui lòng chọn loại sản phẩm');
        hasError = true;
    }
    if (spData.so_luong_ton < 0) {
        showFieldError('suaSoLuongTon', 'Số lượng không được âm');
        hasError = true;
    }
    if (spData.gia_von < 0) {
        showFieldError('suaGiaVon', 'Giá vốn không được âm');
        hasError = true;
    }
    if (spData.ty_le_loi_nhuan < 0 || spData.ty_le_loi_nhuan > 200) {
        showFieldError('suaTyleLN', 'Tỷ lệ lợi nhuận từ 0-200%');
        hasError = true;
    }
    
    if (hasError) return;

    // Upload ảnh mới nếu có
    for (const file of filesSuaTam) {
        const fd = new FormData();
        fd.append('hinh_anh', file);
        try {
            const resUpload = await fetch(API_BASE + '?resource=upload-hinh', {
                method: 'POST',
                body: fd,
                signal: AbortSignal.timeout(30000)
            });
            const dataUpload = await resUpload.json();
            if (dataUpload.success) {
                await apiFetch('san-pham-hinh', 'POST', {
                    ma_sp: editingSpMa,
                    duong_dan: dataUpload.path
                });
            } else {
                showToast('Lỗi upload: ' + dataUpload.message, 'error');
                return;
            }
        } catch (err) {
            showToast('Lỗi upload: ' + err.message, 'error');
            return;
        }
    }

    // Cập nhật sản phẩm
    const resUpd = await apiFetch('san-pham', 'PUT', spData);
    if (resUpd.success) {
        showToast('Cập nhật sản phẩm thành công!', 'success');
        originalSpData = null; // Reset snapshot sau khi lưu thành công
        editingSpMa    = null;
        await fetchSpList();
        renderSpTable();
        window.location.hash = '';
    } else {
        showToast(resUpd.message, 'error');
    }
}

// =============================================================
// SẢN PHẨM – XÓA
// =============================================================
async function confirmDeleteSp(maSp, ten) {
    if (!confirm(`Xác nhận xóa sản phẩm "${ten}"?`)) return;
    const res = await apiFetch('san-pham', 'DELETE', { ma_sp: maSp });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        await fetchSpList();
        renderSpTable();
    }
}

// =============================================================
// DROPDOWN LOẠI SẢN PHẨM
// =============================================================
function updateLoaiDropdown() {
    populateLoaiSelect(document.querySelector('#loaiSP'));
    populateLoaiSelect(document.querySelector('#suaLoaiSP'));
    populateLoaiSelect(document.querySelector('#filterLoai'));
}

function populateLoaiSelect(sel) {
    if (!sel) return;
    const current = sel.value;
    sel.innerHTML = '<option value="">-- Chọn loại --</option>';
    loaiList.forEach(loai => {
        const opt = document.createElement('option');
        opt.value = loai.ma_loai;
        opt.textContent = loai.ten_loai;
        sel.appendChild(opt);
    });
    sel.value = current;
}

// =============================================================
// TOAST NOTIFICATION - Dialog ở giữa màn hình
// =============================================================
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

// =============================================================
// TIỆN ÍCH
// =============================================================
function fmtVND(v) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v);
}

function todayStr() {
    return new Date().toISOString().split('T')[0];
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// CSS cho toast + badge
const _style = document.createElement('style');
_style.textContent = `
@keyframes slideUp { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
@keyframes fadeIn { from{opacity:0} to{opacity:1} }
@keyframes fadeOut { from{opacity:1} to{opacity:0} }
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
  font-size:14px;box-sizing:border-box;width:100%;transition:border-color 0.2s
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color:#0195b2;outline:none }
.field-error { background-color:rgba(231,76,60,0.05)!important;border-color:#e74c3c!important }
.field-error-message { color:#e74c3c;font-size:12px;margin-top:4px;margin-bottom:8px;display:flex;align-items:center;gap:4px }
.full-width { flex:1 1 100%!important }
.required   { color:#e74c3c }
`;
document.head.appendChild(_style);