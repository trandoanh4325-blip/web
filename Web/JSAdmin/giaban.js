// =============================================================
// JSAdmin/giaban.js  –  Logic Quản lý Giá Bán
// Kết nối: Admin/giaban_api.php + Admin/process_SanPham.php
// =============================================================

const API_URL    = '../Admin/giaban_api.php';
const SP_API_URL = '../Admin/process_SanPham.php';  // dùng chung để lấy danh sách loại

/* ── Utility ── */
const fmt    = n  => Number(n).toLocaleString('vi-VN') + 'đ';
const fmtPct = n  => Number(n).toLocaleString('vi-VN') + '%';
const $      = id => document.getElementById(id);

/* ── Dữ liệu toàn cục ── */
let _allData  = [];
let _loaiData = [];  // cache loại sản phẩm từ DB

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
   gia_ban = gia_von * (100% + ty_le_loi_nhuan)
   Ưu tiên: loi_nhuan_san_pham > loi_nhuan_loai
   ════════════════════════════════════════════════ */
function tinhGiaBan(giaVon, loiNhuanLoai, loiNhuanSanPham) {
  const gv    = parseFloat(giaVon)          || 0;
  const lnSP  = parseFloat(loiNhuanSanPham);
  const lnL   = parseFloat(loiNhuanLoai)    || 0;
  const hasLnSP = !isNaN(lnSP) &&
                  loiNhuanSanPham !== '' &&
                  loiNhuanSanPham !== null;
  const ln = hasLnSP ? lnSP : lnL;
  return {
    giaBan: gv * (1 + ln / 100),
    ln,
    nguon: hasLnSP ? 'riêng SP' : 'theo loại'
  };
}

/* ════════════════════════════════════════════════
   PREVIEW REAL-TIME – Form thêm
   ════════════════════════════════════════════════ */
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

/* ════════════════════════════════════════════════
   PREVIEW REAL-TIME – Modal sửa
   ════════════════════════════════════════════════ */
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
   TẢI DANH SÁCH LOẠI → điền dropdown
   Dùng process_SanPham.php (đồng bộ với trang SanPham)
   ════════════════════════════════════════════════ */
async function taiLoai() {
  try {
    const res  = await fetch(`${SP_API_URL}?resource=loai-san-pham`);
    const data = await res.json();
    if (!data.success) return;

    _loaiData = data.data || [];

    const opts = _loaiData
      .map(l => `<option value="${l.id}">${l.ten_loai}</option>`)
      .join('');
    const placeholder = '<option value="">-- Chọn loại --</option>';

    $('loaiSanPham').innerHTML = placeholder + opts;
    $('suaLoai').innerHTML     = placeholder + opts;
  } catch {
    /* silent – dropdown sẽ hiện "Đang tải..." */
  }
}

/* Khi chọn loại → tự điền % lợi nhuận mặc định của loại đó */
function onLoaiChange(selectId, lnInputId) {
  const idLoai = parseInt($(selectId).value);
  const loai   = _loaiData.find(l => l.id === idLoai);
  $(lnInputId).value = (loai && loai.ty_le_loi_nhuan > 0)
                       ? loai.ty_le_loi_nhuan : '';
}

/* ════════════════════════════════════════════════
   RENDER BẢNG
   Field aliases từ giaban_api.php khớp với JS:
     r.loai           = l.ten_loai
     r.ten_san_pham   = sp.ten_sp
     r.loi_nhuan_loai = l.ty_le_loi_nhuan
     r.loi_nhuan_san_pham = sp.ty_le_loi_nhuan
     r.gia_ban_tinh   = sp.gia_ban
     r.loi_nhuan_hieu_dung (computed)
   ════════════════════════════════════════════════ */
function makeRow(r, idx) {
  const gb  = parseFloat(r.gia_ban_tinh)
              || tinhGiaBan(r.gia_von, r.loi_nhuan_loai, r.loi_nhuan_san_pham).giaBan;
  const ln  = r.loi_nhuan_hieu_dung
              ?? (r.loi_nhuan_san_pham ?? r.loi_nhuan_loai);
  const ten = (r.ten_san_pham || '').replace(/'/g, "\\'");

  return `<tr data-id="${r.id}">
    <td style="color:#999;font-size:13px;">${idx + 1}</td>
    <td style="font-size:12px;color:#666;">${r.ma_sp ?? '—'}</td>
    <td>${r.loai}</td>
    <td><strong>${r.ten_san_pham}</strong></td>
    <td>${fmt(r.gia_von)}</td>
    <td style="color:#27ae60;font-weight:600;">${fmtPct(ln)}</td>
    <td style="color:#0195b2;font-weight:700;">${fmt(gb)}</td>
    <td style="text-align:center;">
      <button class="btn-sua" title="Sửa" onclick="moModalSua(${r.id})">
        <i class="fas fa-pen"></i>
      </button>
    </td>
    <td style="text-align:center;">
      <button class="btn-xoa" title="Xóa" onclick="moConfirmXoa(${r.id},'${ten}')">
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
    if (data.success) {
      _allData = data.data;
      renderMainTable(data.data);
    } else {
      showToast('Lỗi tải dữ liệu: ' + data.message, true);
    }
  } catch {
    showToast('Không kết nối được server!', true);
    $('tbodyMain').innerHTML =
      '<tr class="loading-row"><td colspan="9">Lỗi kết nối server.</td></tr>';
  }
}

/* ════════════════════════════════════════════════
   THÊM SẢN PHẨM
   ════════════════════════════════════════════════ */
async function themSanPham() {
  const idLoai = parseInt($('loaiSanPham').value);
  const ten    = $('tenSanPham').value.trim();
  const gv     = $('giaVon').value;

  if (!idLoai) {
    showToast('Vui lòng chọn loại sản phẩm!', true); return;
  }
  if (!ten || !gv || parseFloat(gv) <= 0) {
    showToast('Vui lòng nhập Tên sản phẩm và Giá vốn!', true); return;
  }

  const btn  = $('btnThem');
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Đang thêm...';
  btn.disabled  = true;

  try {
    const res  = await fetch(API_URL, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_loai:         idLoai,
        loiNhuanLoai:    $('loiNhuanTheoLoai').value    || 0,
        tenSanPham:      ten,
        giaVon:          gv,
        loiNhuanSanPham: $('loiNhuanTheoSanPham').value !== ''
                         ? $('loiNhuanTheoSanPham').value : 0,
      })
    });
    const data = await res.json();
    if (data.success) {
      showToast('✅ ' + data.message);
      ['tenSanPham', 'giaVon', 'loiNhuanTheoSanPham', 'loiNhuanTheoLoai'].forEach(id =>
        $(id).value = ''
      );
      $('loaiSanPham').value         = '';
      $('previewGiaBan').textContent = '—';
      $('previewNote').textContent   = '';
      taiDuLieu();
    } else {
      showToast('Lỗi: ' + data.message, true);
    }
  } catch {
    showToast('Lỗi kết nối server!', true);
  } finally {
    btn.innerHTML = orig;
    btn.disabled  = false;
  }
}

/* ════════════════════════════════════════════════
   XÓA – confirm dialog
   ════════════════════════════════════════════════ */
let _xoaId = null;

function moConfirmXoa(id, ten) {
  _xoaId = id;
  $('confirmMsg').innerHTML =
    `Bạn có chắc muốn xóa <strong>"${ten}"</strong>?<br/>
     <small style="color:#e74c3c">SP đã có phiếu nhập sẽ được ẩn thay vì xóa hẳn.</small>`;
  $('confirmDel').classList.add('open');
}

function dongConfirm() {
  $('confirmDel').classList.remove('open');
  _xoaId = null;
}

async function thucHienXoa() {
  if (!_xoaId) return;
  const id = _xoaId;
  dongConfirm();

  try {
    const res  = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) {
      showToast('🗑️ ' + data.message);
      taiDuLieu();
      const kw = $('timKiemPhieu').value.trim();
      if (kw) timKiem(kw);
    } else {
      showToast('Lỗi: ' + data.message, true);
    }
  } catch {
    showToast('Lỗi kết nối server!', true);
  }
}

/* ════════════════════════════════════════════════
   SỬA – Modal popup
   ════════════════════════════════════════════════ */
function moModalSua(id) {
  const row = _allData.find(r => r.id == id);
  if (!row) { showToast('Không tìm thấy dữ liệu!', true); return; }

  $('suaId').value           = row.id;
  $('suaLoai').value         = row.id_loai;            // dùng ID cho select
  $('suaLoiNhuanLoai').value = row.loi_nhuan_loai;     // alias từ SQL
  $('suaTen').value          = row.ten_san_pham;       // alias từ SQL
  $('suaGiaVon').value       = row.gia_von;
  $('suaLoiNhuanSP').value   = row.loi_nhuan_san_pham > 0
                               ? row.loi_nhuan_san_pham : '';

  updateSuaPreview();
  $('modalSua').classList.add('open');
}

function dongModal() { $('modalSua').classList.remove('open'); }

/* ── Lưu sửa ── */
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
        id:              parseInt($('suaId').value),
        idLoai:          parseInt($('suaLoai').value),   // gửi ID loại
        loiNhuanLoai:    $('suaLoiNhuanLoai').value || 0,
        tenSanPham:      ten,
        giaVon:          gv,
        loiNhuanSanPham: $('suaLoiNhuanSP').value !== ''
                         ? $('suaLoiNhuanSP').value : 0,
      })
    });
    const data = await res.json();
    if (data.success) {
      showToast('✏️ ' + data.message);
      dongModal();
      // Cập nhật row trong _allData không cần reload toàn bộ
      if (data.data) {
        const idx = _allData.findIndex(r => r.id == data.data.id);
        if (idx !== -1) _allData[idx] = data.data;
        renderMainTable(_allData);
      } else {
        taiDuLieu();
      }
      const kw = $('timKiemPhieu').value.trim();
      if (kw) timKiem(kw);
    } else {
      showToast('Lỗi: ' + data.message, true);
    }
  } catch {
    showToast('Lỗi kết nối server!', true);
  } finally {
    btn.innerHTML = orig;
    btn.disabled  = false;
  }
}

/* ════════════════════════════════════════════════
   TÌM KIẾM
   ════════════════════════════════════════════════ */
async function timKiem(kw) {
  $('tbodySearch').innerHTML =
    '<tr class="loading-row"><td colspan="9">Đang tìm kiếm...</td></tr>';
  try {
    const res  = await fetch(`${API_URL}?search=${encodeURIComponent(kw)}`);
    const data = await res.json();
    if (data.success) renderSearchTable(data.data);
    else showToast('Lỗi tìm kiếm!', true);
  } catch {
    showToast('Lỗi kết nối server!', true);
  }
}

/* ════════════════════════════════════════════════
   GẮN SỰ KIỆN & KHỞI ĐỘNG
   ════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', async () => {

  // Tải loại trước để dropdown có dữ liệu
  await taiLoai();

  // Khi chọn loại → tự điền % lợi nhuận mặc định
  $('loaiSanPham').addEventListener('change', () =>
    onLoaiChange('loaiSanPham', 'loiNhuanTheoLoai')
  );
  $('suaLoai').addEventListener('change', () =>
    onLoaiChange('suaLoai', 'suaLoiNhuanLoai')
  );

  // Preview real-time form thêm
  ['giaVon', 'loiNhuanTheoLoai', 'loiNhuanTheoSanPham'].forEach(id =>
    $(id).addEventListener('input', updatePreview)
  );

  // Preview real-time modal sửa
  ['suaGiaVon', 'suaLoiNhuanLoai', 'suaLoiNhuanSP'].forEach(id =>
    $(id).addEventListener('input', updateSuaPreview)
  );

  // Nút thêm
  $('btnThem').addEventListener('click', themSanPham);

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

  // Tìm kiếm
  $('btnTimKiem').addEventListener('click', () => {
    const kw = $('timKiemPhieu').value.trim();
    if (!kw) { showToast('Vui lòng nhập từ khóa!', true); return; }
    timKiem(kw);
  });
  $('timKiemPhieu').addEventListener('keydown', e => {
    if (e.key === 'Enter') $('btnTimKiem').click();
  });

  // Tải danh sách sản phẩm
  taiDuLieu();
});
