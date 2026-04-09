// =============================================================
// JSAdmin/giaban.js  –  Logic Quản lý Giá Bán
// Luồng mới: admin nhập tự do → bấm lưu → server check SP tồn tại → cập nhật
// Autocomplete chỉ là gợi ý nhanh để tự điền, không bắt buộc dùng
// =============================================================

const API_URL    = typeof GIABAN_API !== 'undefined' ? GIABAN_API : '../Admin/process_giaban.php';
const SP_API_URL = typeof SP_API     !== 'undefined' ? SP_API     : '../Admin/process_SanPham.php';

const fmt    = n => Number(n).toLocaleString('vi-VN') + 'đ';
const fmtPct = n => Number(n).toLocaleString('vi-VN') + '%';
const $      = id => document.getElementById(id);

let _allData    = [];
let _loaiData   = [];
let _spDangChon = null;   // object SP chọn qua autocomplete (có thể null nếu nhập thủ công)

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
   TÍNH GIÁ BÁN REAL-TIME
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
   TẢI LOẠI cho dropdown modal sửa
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
   AUTOCOMPLETE – gợi ý nhanh (không bắt buộc)
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

  // Ẩn khi click ngoài
  document.addEventListener('click', e => {
    if (!inp.closest('.sp-search-wrap')?.contains(e.target)) dongSuggest();
  });

  // Bàn phím điều hướng
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
    } else if (e.key === 'Enter') {
      e.preventDefault();
      list.querySelector('li.active')?.click();
    } else if (e.key === 'Escape') {
      dongSuggest();
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

/* Khi chọn SP từ gợi ý → tự điền form (không lock) */
function chonSanPham(sp) {
  _spDangChon = sp;

  // Hiện card
  $('spDaChonMa').textContent   = sp.ma_sp;
  $('spDaChonTen').textContent  = sp.ten_sp;
  $('spDaChonLoai').textContent = `[${sp.loai}]`;
  $('spDaChon').style.display   = 'block';

  // Điền vào form (admin có thể sửa tiếp)
  $('maSPDangChon').value        = sp.ma_sp;
  $('loiNhuanTheoLoai').value    = sp.loi_nhuan_loai   > 0 ? sp.loi_nhuan_loai  : '';
  $('giaVon').value              = sp.gia_von;
  $('loiNhuanTheoSanPham').value = sp.loi_nhuan_san_pham > 0 ? sp.loi_nhuan_san_pham : '';

  updatePreview();
  dongSuggest();
  $('timSanPham').value = '';
}

/* Bỏ chọn – xoá card nhưng form vẫn mở */
function boChonSanPham() {
  _spDangChon = null;
  $('spDaChon').style.display = 'none';
  $('maSPDangChon').value     = '';
  // Không xoá các field – admin có thể tự nhập
}

/* ════════════════════════════════════════════════
   CẬP NHẬT GIÁ BÁN
   Luồng: validate cơ bản → gọi PUT
   process_giaban.php sẽ check SP tồn tại phía server
   ════════════════════════════════════════════════ */
async function capNhatGiaBan() {
  const maSP = $('maSPDangChon').value.trim();
  const gv   = $('giaVon').value;
  const lnSP = $('loiNhuanTheoSanPham').value;
  const lnL  = $('loiNhuanTheoLoai').value;

  // Validate phía client
  if (!maSP) {
    showToast('Vui lòng chọn sản phẩm từ gợi ý trước khi lưu!', true);
    $('timSanPham').focus();
    return;
  }
  if (!gv || parseFloat(gv) <= 0) {
    showToast('Vui lòng nhập Giá vốn hợp lệ!', true);
    $('giaVon').focus();
    return;
  }
  if (lnSP === '' && (!lnL || parseFloat(lnL) <= 0)) {
    showToast('Vui lòng nhập ít nhất 1 mức lợi nhuận!', true);
    $('loiNhuanTheoSanPham').focus();
    return;
  }

  const btn  = $('btnThem');
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Đang kiểm tra & lưu...';
  btn.disabled  = true;

  try {
    // process_giaban.php PUT sẽ check SP tồn tại phía server
    const res  = await fetch(API_URL, {
      method:  'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ma_sp:           maSP,
        maLoai:          _spDangChon?.ma_loai ?? '',
        loiNhuanLoai:    lnL  || 0,
        tenSanPham:      _spDangChon?.ten_sp ?? maSP,  // fallback về maSP nếu chưa chọn qua gợi ý
        giaVon:          gv,
        loiNhuanSanPham: lnSP !== '' ? lnSP : 0,
      })
    });
    const data = await res.json();

    if (data.success) {
      showToast('✅ ' + data.message);
      // Reset form sau khi lưu thành công
      boChonSanPham();
      $('loiNhuanTheoLoai').value    = '';
      $('giaVon').value              = '';
      $('loiNhuanTheoSanPham').value = '';
      $('previewGiaBan').textContent = '—';
      $('previewNote').textContent   = '';
      taiDuLieu();
    } else {
      // Nếu server báo SP không tồn tại → thông báo rõ
      showToast('❌ ' + data.message, true);
    }
  } catch {
    showToast('Lỗi kết nối server!', true);
  } finally {
    btn.innerHTML = orig;
    btn.disabled  = false;
  }
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
   TẢI DANH SÁCH
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
}

/* ════════════════════════════════════════════════
   KHỞI ĐỘNG
   ════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', async () => {
  await taiLoai();
  khoiDongAutoComplete();

  $('btnBoChon').addEventListener('click', boChonSanPham);

  // Preview real-time khi thay đổi bất kỳ field nào
  ['giaVon', 'loiNhuanTheoLoai', 'loiNhuanTheoSanPham'].forEach(id =>
    $(id).addEventListener('input', updatePreview)
  );

  // Nút cập nhật
  $('btnThem').addEventListener('click', capNhatGiaBan);

  // Confirm xóa
  $('btnCancelDel').addEventListener('click', dongConfirm);
  $('btnOkDel').addEventListener('click', thucHienXoa);
  $('confirmDel').addEventListener('click', e => {
    if (e.target === $('confirmDel')) dongConfirm();
  });

  // Modal sửa
  $('btnDongModal').addEventListener('click', dongModal);
  $('btnCancelModal').addEventListener('click', dongModal);
  $('btnSua').addEventListener('click', luuSua);
  $('modalSua').addEventListener('click', e => {
    if (e.target === $('modalSua')) dongModal();
  });
  ['suaGiaVon','suaLoiNhuanLoai','suaLoiNhuanSP'].forEach(id =>
    $(id).addEventListener('input', updateSuaPreview)
  );

  // Tìm kiếm bảng
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
