(function () {
  const API = 'process_my_order.php';

  const HOAT_DONG_LABEL = {
    dang_cho: 'Đang chờ',
    dang_chuan_bi: 'Đang chuẩn bị',
    cho_lay_hang: 'Chờ lấy hàng',
    dang_van_chuyen: 'Đang vận chuyển',
    giao_thanh_cong: 'Giao thành công',
    da_huy: 'Đã hủy',
  };

  const TT_LABEL = {
    chua_thanh_toan: 'Chưa thanh toán',
    da_thanh_toan: 'Đã thanh toán',
    hoan_tien: 'Hoàn tiền',
  };

  let currentMaDonForCancel = '';

  function fmtVnd(n) {
    return Number(n || 0).toLocaleString('vi-VN') + ' đ';
  }

  function fmtDate(dt) {
    if (!dt) return '—';
    const d = new Date(dt.replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return dt;
    return d.toLocaleString('vi-VN');
  }

  function dongModal() {
    const m = document.getElementById('orderDetailModal');
    if (!m) return;
    const panel = document.getElementById('cancelOrderPanel');
    if (panel) panel.hidden = true;
    const ta = document.getElementById('cancelReasonInput');
    if (ta) ta.value = '';
    m.classList.remove('open');
    setTimeout(function () {
      m.style.display = 'none';
    }, 200);
  }

  function toast(msg, isErr) {
    let w = document.getElementById('orderToastWrap');
    if (!w) {
      w = document.createElement('div');
      w.id = 'orderToastWrap';
      w.className = 'order-toast-wrap';
      document.body.appendChild(w);
    }
    const t = document.createElement('div');
    t.className = 'order-toast' + (isErr ? ' err' : '');
    t.textContent = msg;
    w.appendChild(t);
    setTimeout(function () {
      t.classList.add('hide');
      setTimeout(function () {
        t.remove();
      }, 300);
    }, 3200);
  }

  function escapeHtml(s) {
    if (s == null) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function attrSafe(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;');
  }

  function renderModal(dh, items) {
    const body = document.getElementById('orderModalBody');
    const foot = document.getElementById('orderModalFooter');
    if (!body || !foot) return;

    currentMaDonForCancel = dh.ma_don;

    const region = [dh.phuong, dh.quan, dh.thanh_pho].filter(Boolean).join(' — ');
    const canCancel = dh.hoat_dong === 'dang_cho';

    let rows = '';
    if (items.length) {
      items.forEach(function (it) {
        const line = Number(it.so_luong) * Number(it.gia_ban);
        const src = it.hinh_anh ? it.hinh_anh : '../Image/sp.jpg';
        const img =
          '<img src="' +
          attrSafe(src) +
          '" alt="" class="order-item-img">';
        rows +=
          '<tr><td class="td-product">' +
          img +
          '<span>' +
          escapeHtml(it.ten_sp || it.ma_sp || '—') +
          '</span></td><td>' +
          escapeHtml(String(it.so_luong)) +
          '</td><td>' +
          fmtVnd(it.gia_ban) +
          '</td><td><strong>' +
          fmtVnd(line) +
          '</strong></td></tr>';
      });
    } else {
      rows = '<tr><td colspan="4" class="muted">Chưa có dòng sản phẩm.</td></tr>';
    }

    body.innerHTML =
      '<div class="order-detail-grid">' +
      '<div class="order-detail-card">' +
      '<h4>Thông tin đơn</h4>' +
      '<p><strong>Mã đơn:</strong> ' +
      escapeHtml(dh.ma_don) +
      '</p>' +
      '<p><strong>Trạng thái:</strong> ' +
      escapeHtml(HOAT_DONG_LABEL[dh.hoat_dong] || dh.hoat_dong) +
      '</p>' +
      '<p><strong>Thanh toán:</strong> ' +
      escapeHtml(TT_LABEL[dh.trang_thai_tt] || dh.trang_thai_tt) +
      '</p>' +
      '<p><strong>Ngày đặt:</strong> ' +
      escapeHtml(fmtDate(dh.ngay_dat)) +
      '</p>' +
      '<p><strong>Tổng tiền:</strong> <span class="sum">' +
      fmtVnd(dh.tong_tien) +
      '</span></p>' +
      (dh.ly_do_huy
        ? '<p class="ly-do"><strong>Lý do hủy:</strong> ' + escapeHtml(dh.ly_do_huy) + '</p>'
        : '') +
      '</div>' +
      '<div class="order-detail-card">' +
      '<h4>Địa chỉ giao hàng</h4>' +
      '<p>' +
      escapeHtml(dh.dia_chi_giao || '—') +
      '</p>' +
      (region ? '<p class="muted">' + escapeHtml(region) + '</p>' : '') +
      '</div></div>' +
      '<div class="order-detail-card order-items-card">' +
      '<h4>Sản phẩm</h4>' +
      '<table class="order-items-table"><thead><tr>' +
      '<th>Sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th>' +
      '</tr></thead><tbody>' +
      rows +
      '</tbody></table></div>';

    if (canCancel) {
      foot.innerHTML =
        '<button type="button" class="btn-modal btn-close" data-close-modal>Đóng</button>' +
        '<button type="button" class="btn-modal btn-danger-outline" id="btnOpenCancel">Hủy đơn hàng</button>';
    } else {
      foot.innerHTML = '<button type="button" class="btn-modal btn-close" data-close-modal>Đóng</button>';
    }
  }

  function submitCancel() {
    const maDon = currentMaDonForCancel;
    if (!maDon) return;
    const ta = document.getElementById('cancelReasonInput');
    const ly = ta ? ta.value.trim() : '';
    fetch(API + '?action=cancel_order', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ma_don: maDon, ly_do_huy: ly }),
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (res) {
        if (res.ok) {
          toast('Đã hủy đơn hàng.');
          dongModal();
          window.location.href = 'order_history.php?cancelled=1';
        } else {
          toast(res.message || 'Hủy đơn thất bại.', true);
        }
      })
      .catch(function () {
        toast('Lỗi kết nối.', true);
      });
  }

  window.openOrderDetail = function (maDon) {
    const modal = document.getElementById('orderDetailModal');
    const body = document.getElementById('orderModalBody');
    const foot = document.getElementById('orderModalFooter');
    if (!modal || !body || !foot) return;

    const panel = document.getElementById('cancelOrderPanel');
    if (panel) panel.hidden = true;
    const ta = document.getElementById('cancelReasonInput');
    if (ta) ta.value = '';

    body.innerHTML = '<p class="muted center">Đang tải…</p>';
    foot.innerHTML = '';
    modal.style.display = 'flex';
    setTimeout(function () {
      modal.classList.add('open');
    }, 10);

    Promise.all([
      fetch(API + '?action=get_detail&ma_don=' + encodeURIComponent(maDon)).then(function (r) {
        return r.json();
      }),
      fetch(API + '?action=get_items&ma_don=' + encodeURIComponent(maDon)).then(function (r) {
        return r.json();
      }),
    ])
      .then(function (pair) {
        const resD = pair[0];
        const resI = pair[1];
        if (!resD.ok) {
          toast(resD.message || 'Không tải được chi tiết.', true);
          dongModal();
          return;
        }
        renderModal(resD.data, resI.ok ? resI.data : []);
      })
      .catch(function () {
        toast('Lỗi kết nối.', true);
        dongModal();
      });
  };

  document.addEventListener('DOMContentLoaded', function () {
    const wrap = document.querySelector('.wrap');
    if (wrap) {
      wrap.addEventListener('click', function (e) {
        const b = e.target.closest && e.target.closest('.js-order-detail');
        if (b && b.dataset.maDon) window.openOrderDetail(b.dataset.maDon);
      });
    }

    const modal = document.getElementById('orderDetailModal');
    if (!modal) return;

    modal.addEventListener('click', function (e) {
      const t = e.target;
      if (t === modal) dongModal();
      if (t.matches && t.matches('[data-close-modal]')) dongModal();
      if (t.id === 'btnOpenCancel') {
        const panel = document.getElementById('cancelOrderPanel');
        if (panel) panel.hidden = false;
      }
      if (t.id === 'btnCloseCancelPanel') {
        const panel = document.getElementById('cancelOrderPanel');
        if (panel) panel.hidden = true;
      }
      if (t.id === 'btnConfirmCancel') submitCancel();
    });
  });
})();
