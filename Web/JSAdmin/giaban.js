// =============================================================
// JSAdmin/giaban.js  –  Logic Quản lý Giá Bán
// Logic mới: tìm sản phẩm tồn tại → cập nhật giá bán (không tạo mới)
// =============================================================

const API_URL    = typeof GIABAN_API !== 'undefined' ? GIABAN_API : '../Admin/process_giaban.php';
const SP_API_URL = typeof SP_API     !== 'undefined' ? SP_API     : '../Admin/process_SanPham.php';

const fmt    = n => Number(n).toLocaleString('vi-VN') + 'đ';
const fmtPct = n => Number(n).toLocaleString('vi-VN') + '%';
const $      = id => document.getElementById(id);

let _allData    = [];
let _loaiData   = [];
let _spDangChon = null;   // sản phẩm đang được chọn trong form

/* ════════════════════════════════════════════════
   TOAST
   ════════════════════════════════════════════════ */
let _toastTimer;
function showToast(msg, isError = false) {
  clearTimeout(_toastTimer);
  const t = $('toast');
  t.innerHTML = `<i class="fas fa-${isError ? 'circle-xmark' : 'circle-check'}"></i> ${msg}`;
  t.className = 'show' + (isError ? ' error' : '');
  _toastTimer = setTimeout(() => { t.className = ''; }, 3500);
}

/* ════════════════════════════════════════════════
   TÍNH GIÁ BÁN
   ════════════════════════════════════════════════ */
function tinhGiaBan(giaVon, loiNhuanLoai, loiNhuanSanPham) {
  const gv   = parseFloat(giaVon)          || 0;
  const lnSP = parseFloat(loiNhuanSanPham);
  const lnL  = parseFloat(loiNhuanLoai)    || 0;
  const hasLnSP = !isNaN(lnSP) && loiNhuanSanPham !== '' && loiNhuanSanPham !== null;
  const ln = hasLnSP ? lnSP : lnL;
  return { giaBan: gv * (1 + ln / 100), ln, nguon: hasLnSP ? 'riêng SP' : 'theo loại' };
}

function updatePreview() {
  const { giaBan, ln, nguon } = tinhGiaBan(
    $('giaVon').value,
    $('loiNhuanTheoLoai').value,
    $('loiNhuanTheoSanPham').value
  );
  if (parseFloat($('giaVon').value) > 0) {
    $('previewGiaBan').textContent = fmt(giaBan);
    $('previewNote').textContent   = `LN áp dụng: ${ln}% (${nguon})`;
  } else {
    $('previewGiaBan').textContent = '—';
    $('previewNote').textContent   = '';
  }
}

function updateSuaPreview() {
  const { giaBan, ln, nguon } = tinhGiaBan(
    $('suaGiaVon').value,
    $('suaLoiNhuanLoai').value,
    $('suaLoiNhuanSP').value
  );
  if (parseFloat($('suaGiaVon').value) > 0) {
    $('suaPreviewGiaBan').textContent = fmt(giaBan);
    $('suaPreviewNote').textContent   = `LN áp dụng: ${ln}% (${nguon})`;
  } else {
    $('suaPreviewGiaBan').textContent = '—';
    $('suaPreviewNote').textContent   = '';
  }
}

/* ════════════════════════════════════════════════
   TẢI DANH SÁCH LOẠI → dropdown modal sửa
   ════════════════════════════════════════════════ */
async function taiLoai() {
  try {
    const res  = await fetch(`${SP_API_URL}?resource=loai-san-pham`);
    const data = await res.json();
    if (!data.success) return;
    _loaiData = data.data || [];
    const opts = _loaiData
      .map(l => `<option value="${l.ma_loai}">${l.ten_loai}</option>`)
      .join('');
    $('suaLoai').innerHTML = '<option value="">-- Chọn loại --</option>' + opts;
  } catch { /* silent */ }
}

/* ════════════════════════════════════════════════
   LOCK / UNLOCK form chính
   ════════════════════════════════════════════════ */
function moKhoaForm(lock) {
  const fields = $('formGiaBanFields');
  const btn    = $('btnThem');
  if (lock) {
    fields.classList.add('form-fields-locked');
    btn.disabled = true;
  } else {
    fields.classList.remove('form-fields-locked');
    btn.disabled = false;
  }
}

/* ════════════════════════════════════════════════
   AUTOCOMPLETE – tìm sản phẩm theo mã / tên
   ════════════════════════════════════════════════ */
let _suggestTimer = null;

function khoiDongAutoComplete() {
  const inp  = $('timSanPham');
  const list = $('spSuggestList');

  inp.addEventListener('input', () => {
    clearTimeout(_suggestTimer);
    const kw = inp.value.trim();
    if (kw.length === 0) { dongSuggest(); return; }

    _suggestTimer = setTimeout(async () => {
      try {
        const res  = await fetch(`${API_URL}?search_sp=${encodeURIComponent(kw)}`);
        const data = await res.json();
        hienSuggest(data.success ? data.data : []);
      } catch { dongSuggest(); }
    }, 250);
  });

  document.addEventListener('click', e => {
    if (!inp.closest('.sp-search-wrap')?.contains(e.target)) dongSuggest();
  });

  inp.addEventListener('keydown', e => {
    const items  = list.querySelectorAll('li.suggest-item');
    const active = list.querySelector('li.active');
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      const next = active ? active.nextElementSibling : items[0];
      active?.classList.remove('active');
      next?.classList.add('active');
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      const prev = active ? active.previousElementSibling : items[items.length - 1];
      active?.classList.remove('active');
      prev?.classList.add('active');
    } // TÌM VÀ THAY ĐOẠN ENTER CŨ THÀNH ĐOẠN NÀY:
    
  else if (e.key === 'Enter') {
    e.preventDefault();
    const activeItem = list.querySelector('li.active');
    const firstItem  = list.querySelector('li.suggest-item');
    
    if (activeItem) {
        activeItem.click();
    } else if (firstItem) {
        firstItem.click(); // Nếu chưa bấm mũi tên, nhấn Enter chọn luôn dòng đầu
    }
}
  });
}

function hienSuggest(items) {
  const list = $('spSuggestList');
  if (!items.length) {
    list.innerHTML = `<li class="suggest-empty"><i class="fas fa-search"></i> Không tìm thấy sản phẩm</li>`;
    list.classList.add('open');
    return;
  }
  list.innerHTML = items.map(sp => `
    <li data-masp="${sp.ma_sp}" class="suggest-item">
      <span class="suggest-ma">${sp.ma_sp}</span>
      <span class="suggest-ten">${sp.ten_sp}</span>
      <span class="suggest-loai">${sp.loai}</span>
      ${sp.hien_trang === 'an' ? '<span class="suggest-badge-an">Ẩn</span>' : ''}
    </li>
  `).join('');

  list.querySelectorAll('li.suggest-item').forEach(li => {
    const sp = items.find(s => s.ma_sp === li.dataset.masp);
    li.addEventListener('click', () => chonSanPham(sp));
  });
  list.classList.add('open');
}

function dongSuggest() {
  const list = $('spSuggestList');
  list.classList.remove('open');
  list.innerHTML = '';
}

function chonSanPham(sp) {
  _spDangChon = sp;

  // Hiện card SP đã chọn
  $('spDaChonMa').textContent   = sp.ma_sp;
  $('spDaChonTen').textContent  = sp.ten_sp;
  $('spDaChonLoai').textContent = `[${sp.loai}]`;
  $('spDaChon').style.display   = 'block';

  // Điền form
  $('maSPDangChon').value        = sp.ma_sp;
  $('loiNhuanTheoLoai').value    = sp.loi_nhuan_loai   > 0 ? sp.loi_nhuan_loai  : '';
  $('giaVon').value              = sp.gia_von;
  $('loiNhuanTheoSanPham').value = sp.loi_nhuan_san_pham > 0 ? sp.loi_nhuan_san_pham : '';

  // Form lúc này vẫn unlock để admin có thể tùy chỉnh giá vốn, lợi nhuận
  // Chỉ khi bấm "Cập nhật" mới check sản phẩm có tồn tại
  moKhoaForm(false);
  updatePreview();
  dongSuggest();
  $('timSanPham').value = '';  // ← CLEAR input sau khi chọn
}

function boChonSanPham() {
  _spDangChon = null;
  $('spDaChon').style.display    = 'none';
  $('maSPDangChon').value        = '';
  $('loiNhuanTheoLoai').value    = '';
  $('giaVon').value              = '';
  $('loiNhuanTheoSanPham').value = '';
  $('previewGiaBan').textContent = '—';
  $('previewNote').textContent   = '';
  moKhoaForm(true);
}

/* ════════════════════════════════════════════════
   CHECK SẢN PHẨM TỒN TẠI (MỚI)
   ════════════════════════════════════════════════ */
async function checkSanPhamTonTai(maSP) {
  try {
    const res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ma_sp: maSP,
        action: 'check_product'
      })
    });
    const data = await res.json();
    return { success: data.success, message: data.message, product: data.data };
  } catch (err) {
    return { success: false, message: 'Lỗi kết nối server!' };
  }
}

/* ════════════════════════════════════════════════
   CẬP NHẬT GIÁ BÁN (PUT) – KIỂM TRA TRƯỚC
   ════════════════════════════════════════════════ */
async function capNhatGiaBan() {
  // Đầu tiên: lấy mã SP từ input (không bắt buộc phải chọn từ gợi ý)
  const maSPFromHidden = $('maSPDangChon').value.trim();
  const maSPFromInput = $('timSanPham').value.trim();
  const maSP = maSPFromHidden || maSPFromInput;

  if (!maSP) {
    showToast('❌ Vui lòng nhập hoặc chọn sản phẩm!', true);
    return;
  }

  const gv   = $('giaVon').value;
  const lnSP = $('loiNhuanTheoSanPham').value;
  const lnL  = $('loiNhuanTheoLoai').value;

  if (!gv || parseFloat(gv) <= 0) {
    showToast('❌ Giá vốn không hợp lệ!', true);
    return;
  }
  if (lnSP === '' && (!lnL || parseFloat(lnL) <= 0)) {
    showToast('❌ Vui lòng nhập ít nhất 1 mức lợi nhuận!', true);
    return;
  }

  const btn  = $('btnThem');
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Đang kiểm tra...';
  btn.disabled  = true;

  try {
    // BƯỚC 1: Kiểm tra sản phẩm có tồn tại không
    const checkResult = await checkSanPhamTonTai(maSP);
    if (!checkResult.success) {
      showToast(checkResult.message, true);
      btn.innerHTML = orig;
      btn.disabled  = false;
      return;
    }

    // BƯỚC 2: Sản phẩm tồn tại, tiến hành cập nhật giá bán
    btn.innerHTML = '<span class="spinner"></span> Đang lưu...';

    const product = checkResult.product;
    const res = await fetch(API_URL, {
      method:  'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ma_sp:           maSP,
        maLoai:          product.ma_loai,
        loiNhuanLoai:    lnL || 0,
        tenSanPham:      product.ten_sp,
        giaVon:          gv,
        loiNhuanSanPham: lnSP !== '' ? lnSP : 0,
      })
    });
    const data = await res.json();
    if (data.success) {
      showToast('✅ ' + data.message);
      boChonSanPham();
      taiDuLieu();
    } else {
      showToast('❌ Lỗi: ' + data.message, true);
    }
  } catch (err) {
    showToast('❌ Lỗi kết nối server!', true);
  } finally {
    btn.innerHTML = orig;
    if (_spDangChon) btn.disabled = false;
  }

/* ════════════════════════════════════════════════
   RENDER BẢNG
   ════════════════════════════════════════════════ */
function makeRow(r, idx) {
  const gb  = parseFloat(r.gia_ban_tinh)
              || tinhGiaBan(r.gia_von, r.loi_nhuan_loai, r.loi_nhuan_san_pham).giaBan;
  const ln  = r.loi_nhuan_hieu_dung ?? (r.loi_nhuan_san_pham ?? r.loi_nhuan_loai);
  const ten = (r.ten_san_pham || '').replace(/'/g, "\\'");
  const maSP = r.ma_sp ?? '';
  return `<tr data-masp="${maSP}">
    <td style="color:#999;font-size:13px;">${idx + 1}</td>
    <td style="font-size:12px;color:#666;">${maSP || '—'}</td>
    <td>${r.loai}</td>
    <td><strong>${r.ten_san_pham}</strong></td>
    <td>${fmt(r.gia_von)}</td>
    <td style="color:#27ae60;font-weight:600;">${fmtPct(ln)}</td>
    <td style="color:#0195b2;font-weight:700;">${fmt(gb)}</td>
    <td style="text-align:center;">
      <button class="btn-sua" title="Sửa" onclick="moModalSua('${maSP}')">
        <i class="fas fa-pen"></i>
      </button>
    </td>
    <td style="text-align:center;">
      <button class="btn-xoa" title="Xóa" onclick="moConfirmXoa('${maSP}','${ten}')">
        <i class="fas fa-trash"></i>
      </button>
    </td>
  </tr>`;
}

function renderMainTable(rows) {
  $('tbodyMain').innerHTML = rows.length
    ? rows.map((r, i) => makeRow(r, i)).join('')
    : '<tr class="loading-row"><td colspan="9">Chưa có dữ liệu.</td></tr>';
}
function renderSearchTable(rows) {
  $('tbodySearch').innerHTML = rows.length
    ? rows.map((r, i) => makeRow(r, i)).join('')
    : '<tr class="loading-row"><td colspan="9">Không tìm thấy kết quả.</td></tr>';
}

/* ════════════════════════════════════════════════
   TẢI DANH SÁCH SẢN PHẨM
   ════════════════════════════════════════════════ */
async function taiDuLieu() {
  try {
    const res  = await fetch(API_URL);
    const data = await res.json();
    if (data.success) { _allData = data.data; renderMainTable(data.data); }
    else showToast('Lỗi tải dữ liệu: ' + data.message, true);
  } catch {
    showToast('Không kết nối được server!', true);
    $('tbodyMain').innerHTML =
      '<tr class="loading-row"><td colspan="9">Lỗi kết nối server.</td></tr>';
  }
}

/* ════════════════════════════════════════════════
   XÓA
   ════════════════════════════════════════════════ */
let _xoaMaSP = null;
function moConfirmXoa(maSP, ten) {
  _xoaMaSP = maSP;
  $('confirmMsg').innerHTML =
    `Bạn có chắc muốn xóa <strong>"${ten}"</strong>?<br/>
     <small style="color:#e74c3c">SP đã có phiếu nhập sẽ được ẩn thay vì xóa hẳn.</small>`;
  $('confirmDel').classList.add('open');
}
function dongConfirm() { $('confirmDel').classList.remove('open'); _xoaMaSP = null; }
async function thucHienXoa() {
  if (!_xoaMaSP) return;
  const maSP = _xoaMaSP;
  dongConfirm();
  try {
    const res  = await fetch(`${API_URL}?ma_sp=${encodeURIComponent(maSP)}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) {
      showToast('🗑️ ' + data.message);
      taiDuLieu();
      const kw = $('timKiemPhieu').value.trim();
      if (kw) timKiem(kw);
    } else showToast('Lỗi: ' + data.message, true);
  } catch { showToast('Lỗi kết nối server!', true); }
}

/* ════════════════════════════════════════════════
   SỬA – Modal
   ════════════════════════════════════════════════ */
function moModalSua(maSP) {
  const row = _allData.find(r => r.ma_sp === maSP);
  if (!row) { showToast('Không tìm thấy dữ liệu!', true); return; }
  $('suaId').value           = row.ma_sp;
  $('suaMaSP').value         = row.ma_sp;
  $('suaLoai').value         = row.ma_loai;
  $('suaLoiNhuanLoai').value = row.loi_nhuan_loai;
  $('suaTen').value          = row.ten_san_pham;
  $('suaGiaVon').value       = row.gia_von;
  $('suaLoiNhuanSP').value   = row.loi_nhuan_san_pham > 0 ? row.loi_nhuan_san_pham : '';
  updateSuaPreview();
  $('modalSua').classList.add('open');
}
function dongModal() { $('modalSua').classList.remove('open'); }

async function luuSua() {
  const ten = $('suaTen').value.trim();
  const gv  = $('suaGiaVon').value;
  if (!ten || !gv || parseFloat(gv) <= 0) {
    showToast('Vui lòng nhập Tên sản phẩm và Giá vốn!', true); return;
  }
  const btn  = $('btnSua');
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Đang lưu...';
  btn.disabled  = true;
  try {
    const res  = await fetch(API_URL, {
      method:  'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ma_sp:           $('suaId').value,
        maLoai:          $('suaLoai').value,
        loiNhuanLoai:    $('suaLoiNhuanLoai').value || 0,
        tenSanPham:      ten,
        giaVon:          gv,
        loiNhuanSanPham: $('suaLoiNhuanSP').value !== '' ? $('suaLoiNhuanSP').value : 0,
      })
    });
    const data = await res.json();
    if (data.success) {
      showToast('✏️ ' + data.message);
      dongModal();
      if (data.data) {
        const idx = _allData.findIndex(r => r.ma_sp === data.data.ma_sp);
        if (idx !== -1) _allData[idx] = data.data;
        renderMainTable(_allData);
      } else { taiDuLieu(); }
      const kw = $('timKiemPhieu').value.trim();
      if (kw) timKiem(kw);
    } else showToast('Lỗi: ' + data.message, true);
  } catch { showToast('Lỗi kết nối server!', true); }
  finally { btn.innerHTML = orig; btn.disabled = false; }
}

/* ════════════════════════════════════════════════
   TÌM KIẾM bảng
   ════════════════════════════════════════════════ */
async function timKiem(kw) {
  $('tbodySearch').innerHTML =
    '<tr class="loading-row"><td colspan="9">Đang tìm kiếm...</td></tr>';
  try {
    const res  = await fetch(`${API_URL}?search=${encodeURIComponent(kw)}`);
    const data = await res.json();
    if (data.success) renderSearchTable(data.data);
    else showToast('Lỗi tìm kiếm!', true);
  } catch { showToast('Lỗi kết nối server!', true); }


/* ════════════════════════════════════════════════
   KHỞI ĐỘNG
   ════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', async () => {
  await taiLoai();
  moKhoaForm(true);
  khoiDongAutoComplete();

  $('btnBoChon').addEventListener('click', boChonSanPham);
  $('loiNhuanTheoSanPham').addEventListener('input', updatePreview);
  $('btnThem').addEventListener('click', capNhatGiaBan);

  $('btnCancelDel').addEventListener('click', dongConfirm);
  $('btnOkDel').addEventListener('click', thucHienXoa);
  $('confirmDel').addEventListener('click', e => {
    if (e.target === $('confirmDel')) dongConfirm();
  });

  $('btnDongModal').addEventListener('click', dongModal);
  $('btnCancelModal').addEventListener('click', dongModal);
  $('btnSua').addEventListener('click', luuSua);
  $('modalSua').addEventListener('click', e => {
    if (e.target === $('modalSua')) dongModal();
  });
  ['suaGiaVon','suaLoiNhuanLoai','suaLoiNhuanSP'].forEach(id =>
    $(id).addEventListener('input', updateSuaPreview)
  );

  $('btnTimKiem').addEventListener('click', () => {
    const kw = $('timKiemPhieu').value.trim();
    if (!kw) { showToast('Vui lòng nhập từ khóa!', true); return; }
    timKiem(kw);
  });
  $('timKiemPhieu').addEventListener('keydown', e => {
    if (e.key === 'Enter') $('btnTimKiem').click();
  });

  taiDuLieu();
});
