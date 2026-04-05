/* ================================================
   giaban.js – Logic Quản lý Giá Bán
   Dùng bảng: san_pham + loai_san_pham
   ================================================ */

const API_URL = 'giaban_api.php';

/* ── Utility ── */
const fmt    = n  => Number(n).toLocaleString('vi-VN') + 'đ';
const fmtPct = n  => Number(n).toLocaleString('vi-VN') + '%';
const $      = id => document.getElementById(id);

/* ────────────────────────────────────────────────
   TOAST THÔNG BÁO
   ────────────────────────────────────────────── */
let _toastTimer;
function showToast(msg, isError = false) {
  clearTimeout(_toastTimer);
  const t = $('toast');
  t.innerHTML = `<i class="fas fa-${isError ? 'circle-xmark' : 'circle-check'}"></i> ${msg}`;
  t.className = 'show' + (isError ? ' error' : '');
  _toastTimer = setTimeout(() => { t.className = ''; }, 3500);
}

/* ────────────────────────────────────────────────
   TÍNH GIÁ BÁN
   Công thức: gia_ban = gia_von * (1 + ty_le / 100)
   tức là:    gia_ban = gia_von * (100% + ty_le_loi_nhuan)
   Ưu tiên:   ty_le_rieng_SP > ty_le_theo_loai
   ────────────────────────────────────────────── */
function tinhGiaBan(giaVon, loiNhuanLoai, loiNhuanSanPham) {
  const gv    = parseFloat(giaVon)          || 0;
  const lnSP  = parseFloat(loiNhuanSanPham);
  const lnL   = parseFloat(loiNhuanLoai)    || 0;
  const hasLnSP = !isNaN(lnSP) &&
                  loiNhuanSanPham !== '' &&
                  loiNhuanSanPham !== null;
  const ln    = hasLnSP ? lnSP : lnL;
  return {
    giaBan: gv * (1 + ln / 100),
    ln,
    nguon: hasLnSP ? 'riêng SP' : 'theo loại'
  };
}

/* ────────────────────────────────────────────────
   PREVIEW REAL-TIME – Form thêm
   ────────────────────────────────────────────── */
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

/* ────────────────────────────────────────────────
   PREVIEW REAL-TIME – Modal sửa
   ────────────────────────────────────────────── */
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

/* ────────────────────────────────────────────────
   DỮ LIỆU TOÀN CỤC
   ────────────────────────────────────────────── */
let _allData = [];

/* ── Tạo 1 dòng bảng ── */
function makeRow(r, idx) {
  // Đối chiếu: r.gia_ban từ SQL, nếu không có thì mới tính bằng JS
  const gb  = r.gia_ban || 0; 
  const ln  = r.ty_le_hieu_dung || 0; // ty_le_hieu_dung là tên cột trong Source 2
  const ten = (r.ten_sp || '').replace(/'/g, "\\'"); // Source 2 dùng ten_sp

  return `<tr data-id="${r.id}">
    <td style="color:#999;font-size:13px;">${idx + 1}</td>
    <td>${r.ten_loai}</td> 
    <td><strong>${r.ten_sp}</strong></td>
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
    : '<tr class="loading-row"><td colspan="8">Chưa có dữ liệu.</td></tr>';
}

function renderSearchTable(rows) {
  $('tbodySearch').innerHTML = rows.length
    ? rows.map((r, i) => makeRow(r, i)).join('')
    : '<tr class="loading-row"><td colspan="8">Không tìm thấy kết quả.</td></tr>';
}

/* ────────────────────────────────────────────────
   TẢI DANH SÁCH
   ────────────────────────────────────────────── */
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
      '<tr class="loading-row"><td colspan="8">Lỗi kết nối server.</td></tr>';
  }
}

/* ────────────────────────────────────────────────
   THÊM SẢN PHẨM
   ────────────────────────────────────────────── */
async function themSanPham() {
  const ten = $('tenSanPham').value.trim();
  const gv  = $('giaVon').value;

  if (!ten || !gv || parseFloat(gv) <= 0) {
    showToast('Vui lòng nhập Tên sản phẩm và Giá vốn!', true);
    return;
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
        loai:            $('loaiSanPham').value,
        loiNhuanLoai:    $('loiNhuanTheoLoai').value   || 0,
        tenSanPham:      ten,
        giaVon:          gv,
        loiNhuanSanPham: $('loiNhuanTheoSanPham').value !== ''
                         ? $('loiNhuanTheoSanPham').value : null,
      })
    });
    const data = await res.json();
    if (data.success) {
      showToast('✅ ' + data.message);
      ['tenSanPham', 'giaVon', 'loiNhuanTheoSanPham'].forEach(id => $(id).value = '');
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

/* ────────────────────────────────────────────────
   XÓA SẢN PHẨM – confirm dialog
   ────────────────────────────────────────────── */
let _xoaId = null;

function moConfirmXoa(id, ten) {
  _xoaId = id;
  $('confirmMsg').innerHTML =
    `Bạn có chắc muốn xóa <strong>"${ten}"</strong>?<br/>Hành động này không thể hoàn tác.`;
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

/* ────────────────────────────────────────────────
   SỬA – Mở modal popup
   ────────────────────────────────────────────── */
function moModalSua(id) {
  const row = _allData.find(r => r.id == id);
  if (!row) { showToast('Không tìm thấy dữ liệu!', true); return; }

  $('suaId').value           = row.id;
  $('suaLoai').value         = row.loai;
  $('suaLoiNhuanLoai').value = row.loi_nhuan_loai;
  $('suaTen').value          = row.ten_san_pham;
  $('suaGiaVon').value       = row.gia_von;
  $('suaLoiNhuanSP').value   = row.loi_nhuan_san_pham ?? '';

  updateSuaPreview();
  $('modalSua').classList.add('open');
}

function dongModal() {
  $('modalSua').classList.remove('open');
}

/* ── Lưu sửa ── */
async function luuSua() {
  const ten = $('suaTen').value.trim();
  const gv  = $('suaGiaVon').value;

  if (!ten || !gv || parseFloat(gv) <= 0) {
    showToast('Vui lòng nhập Tên sản phẩm và Giá vốn!', true);
    return;
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
        loai:            $('suaLoai').value,
        loiNhuanLoai:    $('suaLoiNhuanLoai').value    || 0,
        tenSanPham:      ten,
        giaVon:          gv,
        loiNhuanSanPham: $('suaLoiNhuanSP').value !== ''
                         ? $('suaLoiNhuanSP').value : null,
      })
    });
    const data = await res.json();
    if (data.success) {
      showToast('✏️ ' + data.message);
      dongModal();
      taiDuLieu();
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

/* ────────────────────────────────────────────────
   TÌM KIẾM
   ────────────────────────────────────────────── */
async function timKiem(kw) {
  $('tbodySearch').innerHTML =
    '<tr class="loading-row"><td colspan="8">Đang tìm kiếm...</td></tr>';
  try {
    const res  = await fetch(`${API_URL}?search=${encodeURIComponent(kw)}`);
    const data = await res.json();
    if (data.success) renderSearchTable(data.data);
    else showToast('Lỗi tìm kiếm!', true);
  } catch {
    showToast('Lỗi kết nối server!', true);
  }
}

/* ────────────────────────────────────────────────
   GẮN SỰ KIỆN (chạy sau khi DOM sẵn sàng)
   ────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {

  /* Form thêm – preview real-time */
  ['giaVon', 'loiNhuanTheoLoai', 'loiNhuanTheoSanPham'].forEach(id =>
    $(id).addEventListener('input', updatePreview)
  );

  /* Nút thêm */
  $('btnThem').addEventListener('click', themSanPham);

  /* Confirm xóa */
  $('btnCancelDel').addEventListener('click', dongConfirm);
  $('btnOkDel').addEventListener('click', thucHienXoa);
  $('confirmDel').addEventListener('click', e => {
    if (e.target === $('confirmDel')) dongConfirm();
  });

  /* Modal sửa – preview real-time */
  ['suaGiaVon', 'suaLoiNhuanLoai', 'suaLoiNhuanSP'].forEach(id =>
    $(id).addEventListener('input', updateSuaPreview)
  );

  /* Modal sửa – đóng/lưu */
  $('btnDongModal').addEventListener('click', dongModal);
  $('btnCancelModal').addEventListener('click', dongModal);
  $('btnSua').addEventListener('click', luuSua);
  $('modalSua').addEventListener('click', e => {
    if (e.target === $('modalSua')) dongModal();
  });

  /* Tìm kiếm */
  $('btnTimKiem').addEventListener('click', () => {
    const kw = $('timKiemPhieu').value.trim();
    if (!kw) { showToast('Vui lòng nhập từ khóa!', true); return; }
    timKiem(kw);
  });
  $('timKiemPhieu').addEventListener('keydown', e => {
    if (e.key === 'Enter') $('btnTimKiem').click();
  });

  /* Khởi động – tải danh sách */
  taiDuLieu();
});
