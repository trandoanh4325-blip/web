// ========== QUẢN LÝ SẢN PHẨM - SanPham.js ==========
// Lưu dữ liệu trong localStorage (sẽ không mất khi F5)

// ===== KHỞI TẠO =====
let loaiSanPhams = [];
let sanPhams = [];
let currentEditingProductId = null;

// Tải dữ liệu từ localStorage khi page load
document.addEventListener('DOMContentLoaded', function() {
  console.log('Page loaded - Initializing...');
  
  // Load dữ liệu từ localStorage
  loadDataFromStorage();
  
  // Hiển thị bảng
  loadLoaiSanPhamTable();
  loadProductTable();
  
  // Setup forms
  setupFormLoaiSanPham();
  setupFormThemSanPham();
  setupFormSuaSanPham();
  setupFormEditLoai();
  
  console.log('Initialization complete!');
});

// ========== STORAGE FUNCTIONS ==========

function saveDataToStorage() {
  localStorage.setItem('loaiSanPhams', JSON.stringify(loaiSanPhams));
  localStorage.setItem('sanPhams', JSON.stringify(sanPhams));
}

function loadDataFromStorage() {
  const savedLoai = localStorage.getItem('loaiSanPhams');
  const savedSanPham = localStorage.getItem('sanPhams');
  
  if (savedLoai) {
    loaiSanPhams = JSON.parse(savedLoai);
  }
  if (savedSanPham) {
    sanPhams = JSON.parse(savedSanPham);
  }
  
  console.log('Loaded data:', { loaiSanPhams, sanPhams });
}

// ========== LOẠI SẢN PHẨM ==========

function loadLoaiSanPhamTable() {
  const tableBody = document.querySelector('#danhSachLoai tbody');
  if (!tableBody) return;
  
  tableBody.innerHTML = '';

  if (loaiSanPhams.length === 0) {
    const row = document.createElement('tr');
    row.innerHTML = `<td colspan="4" style="text-align:center; color:#999;">Chưa có loại sản phẩm nào</td>`;
    tableBody.appendChild(row);
    return;
  }

  loaiSanPhams.forEach((loai, index) => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${index + 1}</td>
      <td>${loai.ten_loai}</td>
      <td>${loai.ngay_them}</td>
      <td>
        <button class="btn-sua1" onclick="editLoai(${loai.id})">Sửa</button>
        <button class="btn-xoa1" onclick="deleteLoai(${loai.id})">Xóa</button>
      </td>
    `;
    tableBody.appendChild(row);
  });
}

function setupFormLoaiSanPham() {
  const form = document.getElementById('formLoaiSanPham');
  if (!form) return;

  const btn = form.querySelector('button');
  if (btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      addLoai();
    });
  }
}

function addLoai() {
  const tenLoai = document.getElementById('tenLoai').value.trim();
  const tungay = document.getElementById('tungay').value;

  if (!tenLoai || !tungay) {
    alert('Vui lòng điền đầy đủ thông tin!');
    return;
  }

  // Kiểm tra trùng
  if (loaiSanPhams.some(l => l.ten_loai === tenLoai)) {
    alert('Loại sản phẩm này đã tồn tại!');
    return;
  }

  const maLoai = 'L' + String(loaiSanPhams.length + 1).padStart(3, '0');
  
  loaiSanPhams.push({
    id: Math.max(...loaiSanPhams.map(l => l.id), 0) + 1,
    ma_loai: maLoai,
    ten_loai: tenLoai,
    ngay_them: tungay
  });

  saveDataToStorage();
  loadLoaiSanPhamTable();
  updateLoaiDropdown();
  document.getElementById('formLoaiSanPham').reset();
  alert('✅ Thêm loại sản phẩm thành công! (Mã: ' + maLoai + ')');
}

function setupFormEditLoai() {
  const form = document.getElementById('formLoaiSanPhamPopup');
  if (!form) return;

  const btn = form.querySelector('button');
  if (btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      saveLoaiEdit();
    });
  }
}

function editLoai(id) {
  const loai = loaiSanPhams.find(l => l.id === id);
  if (!loai) {
    alert('Không tìm thấy loại sản phẩm!');
    return;
  }

  const form = document.getElementById('formLoaiSanPhamPopup');
  const inputs = form.querySelectorAll('input');
  inputs[0].value = loai.ten_loai;
  inputs[1].value = loai.ngay_them;
  form.dataset.loaiId = id;

  // Mở popup
  window.location.hash = '#popup-themsp';
}

function saveLoaiEdit() {
  const form = document.getElementById('formLoaiSanPhamPopup');
  const loaiId = parseInt(form.dataset.loaiId);
  const inputs = form.querySelectorAll('input');
  const tenLoai = inputs[0].value.trim();
  const tungay = inputs[1].value;

  if (!tenLoai || !tungay) {
    alert('Vui lòng điền đầy đủ thông tin!');
    return;
  }

  const loai = loaiSanPhams.find(l => l.id === loaiId);
  if (loai) {
    loai.ten_loai = tenLoai;
    loai.ngay_them = tungay;
    saveDataToStorage();
    loadLoaiSanPhamTable();
    updateLoaiDropdown();
    alert('✅ Cập nhật thành công!');
    window.location.hash = '';
  }
}

function deleteLoai(id) {
  const hasProducts = sanPhams.some(sp => sp.id_loai === id);
  
  if (hasProducts) {
    alert('❌ Không thể xóa! Còn sản phẩm dùng loại này.');
    return;
  }

  if (confirm('Bạn chắc chắn xóa loại này?')) {
    loaiSanPhams = loaiSanPhams.filter(l => l.id !== id);
    saveDataToStorage();
    loadLoaiSanPhamTable();
    updateLoaiDropdown();
    alert('✅ Xóa thành công!');
  }
}

// ========== SẢN PHẨM ==========

function loadProductTable() {
  const tableBody = document.getElementById('tableProductBody');
  if (!tableBody) return;

  tableBody.innerHTML = '';

  if (sanPhams.length === 0) {
    const row = document.createElement('tr');
    row.innerHTML = `<td colspan="10" style="text-align:center; color:#999;">Chưa có sản phẩm nào</td>`;
    tableBody.appendChild(row);
    return;
  }

  sanPhams.forEach((sp, index) => {
    const loaiName = loaiSanPhams.find(l => l.id === sp.id_loai)?.ten_loai || 'N/A';
    const giaVon = formatCurrency(sp.gia_von);
    const giaBan = formatCurrency(sp.gia_ban);
    const moTa = sp.mo_ta.substring(0, 50) + (sp.mo_ta.length > 50 ? '...' : '');

    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${index + 1}</td>
      <td><img src="../Image/${sp.hinh_anh}" style="width:70px; height:70px; object-fit:cover;" onerror="this.src='../Image/placeholder.png'"></td>
      <td>${sp.ma_sp}</td>
      <td>${sp.ten_sp}</td>
      <td>${loaiName}</td>
      <td>${sp.so_luong_ton}</td>
      <td>${giaVon}</td>
      <td>${giaBan}</td>
      <td><p style="font-size:12px; margin:0;">${moTa}</p></td>
      <td>
        <button class="btn-sua1" onclick="editProduct(${sp.id})">Sửa</button>
        <button class="btn-xoa1" onclick="deleteProduct(${sp.id})">Xóa</button>
      </td>
    `;
    tableBody.appendChild(row);
  });
}

function updateLoaiDropdown() {
  const selects = document.querySelectorAll('select#loaiSP');
  
  selects.forEach(select => {
    const currentValue = select.value;
    select.innerHTML = '';
    
    loaiSanPhams.forEach(loai => {
      const option = document.createElement('option');
      option.value = loai.ten_loai;
      option.textContent = loai.ten_loai;
      select.appendChild(option);
    });
    
    // Nếu không có loại nào, thêm placeholder
    if (loaiSanPhams.length === 0) {
      const option = document.createElement('option');
      option.value = '';
      option.textContent = '-- Chọn loại sản phẩm --';
      option.selected = true;
      select.insertBefore(option, select.firstChild);
    }
  });
}

function setupFormThemSanPham() {
  const form = document.getElementById('formThemSanPham');
  if (!form) return;

  // Tìm button submit của form thêm (không phải popup)
  const buttons = form.querySelectorAll('button');
  const addBtn = buttons[buttons.length - 1];
  
  addBtn.addEventListener('click', function(e) {
    e.preventDefault();
    addProduct();
  });
}

function addProduct() {
  const maSP = document.getElementById('maSP').value.trim();
  const tenSP = document.getElementById('tenSP').value.trim();
  const loaiSP = document.getElementById('loaiSP').value;
  const moTa = document.getElementById('moTa').value.trim();
  const giaVon = parseFloat(document.getElementById('giaVon').value) || 0;
  const giaBan = parseFloat(document.getElementById('giaBan').value) || 0;
  const soLuong = parseInt(document.getElementById('soLuongTon').value) || 0;
  const hinhAnh = document.getElementById('hinhAnh').value;

  if (!maSP || !tenSP || !loaiSP || !hinhAnh) {
    alert('⚠️ Vui lòng điền: Mã, Tên, Loại, Hình ảnh!');
    return;
  }

  if (sanPhams.some(sp => sp.ma_sp === maSP)) {
    alert('❌ Mã sản phẩm này đã tồn tại!');
    return;
  }

  const loai = loaiSanPhams.find(l => l.ten_loai === loaiSP);
  const idLoai = loai ? loai.id : 1;
  const fileName = hinhAnh.split('\\').pop();

  sanPhams.push({
    id: Math.max(...sanPhams.map(sp => sp.id), 0) + 1,
    ma_sp: maSP,
    ten_sp: tenSP,
    id_loai: idLoai,
    ten_loai: loaiSP,
    so_luong_ton: soLuong,
    gia_von: giaVon,
    gia_ban: giaBan,
    mo_ta: moTa,
    hinh_anh: fileName,
    ngay_them: new Date().toISOString().split('T')[0]
  });

  saveDataToStorage();
  loadProductTable();
  document.getElementById('formThemSanPham').reset();
  alert('✅ Thêm sản phẩm thành công!');
}

function setupFormSuaSanPham() {
  const form = document.querySelector('#popup-suasp form');
  if (!form) return;

  const buttons = form.querySelectorAll('button');
  const editBtn = buttons[buttons.length - 1];
  
  editBtn.addEventListener('click', function(e) {
    e.preventDefault();
    saveProductEdit();
  });
}

function editProduct(id) {
  const product = sanPhams.find(sp => sp.id === id);
  if (!product) {
    alert('Không tìm thấy sản phẩm!');
    return;
  }

  currentEditingProductId = id;

  const form = document.querySelector('#popup-suasp form');
  form.querySelector('#maSP').value = product.ma_sp;
  form.querySelector('#tenSP').value = product.ten_sp;
  form.querySelector('#loaiSP').value = product.ten_loai;
  form.querySelector('#moTa').value = product.mo_ta;
  form.querySelector('#giaVon').value = product.gia_von;
  form.querySelector('#giaBan').value = product.gia_ban;
  form.querySelector('#soLuongTon').value = product.so_luong_ton;

  window.location.hash = '#popup-suasp';
}

function saveProductEdit() {
  if (!currentEditingProductId) {
    alert('Lỗi: Không tìm thấy sản phẩm!');
    return;
  }

  const form = document.querySelector('#popup-suasp form');
  const maSP = form.querySelector('#maSP').value.trim();
  const tenSP = form.querySelector('#tenSP').value.trim();
  const loaiSP = form.querySelector('#loaiSP').value;
  const moTa = form.querySelector('#moTa').value.trim();
  const giaVon = parseFloat(form.querySelector('#giaVon').value) || 0;
  const giaBan = parseFloat(form.querySelector('#giaBan').value) || 0;
  const soLuong = parseInt(form.querySelector('#soLuongTon').value) || 0;
  const hinhAnh = form.querySelector('#hinhAnh').value;

  if (!maSP || !tenSP || !loaiSP) {
    alert('⚠️ Vui lòng điền đầy đủ thông tin!');
    return;
  }

  const product = sanPhams.find(sp => sp.id === currentEditingProductId);
  if (!product) return;

  if (maSP !== product.ma_sp && sanPhams.some(sp => sp.ma_sp === maSP)) {
    alert('❌ Mã sản phẩm này đã tồn tại!');
    return;
  }

  product.ma_sp = maSP;
  product.ten_sp = tenSP;
  product.ten_loai = loaiSP;
  product.mo_ta = moTa;
  product.gia_von = giaVon;
  product.gia_ban = giaBan;
  product.so_luong_ton = soLuong;

  if (hinhAnh) {
    product.hinh_anh = hinhAnh.split('\\').pop();
  }

  saveDataToStorage();
  loadProductTable();
  alert('✅ Cập nhật sản phẩm thành công!');
  window.location.hash = '';
  currentEditingProductId = null;
}

function deleteProduct(id) {
  if (confirm('Bạn chắc chắn xóa sản phẩm này?')) {
    sanPhams = sanPhams.filter(sp => sp.id !== id);
    saveDataToStorage();
    loadProductTable();
    alert('✅ Xóa sản phẩm thành công!');
  }
}

// ========== HỖ TRỢ ==========

function formatCurrency(value) {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND'
  }).format(value);
}