document.addEventListener("DOMContentLoaded", function() {

    // 1. CHỨC NĂNG CẢNH BÁO
    const formCanhBao = document.getElementById('formCanhBao');
    if(formCanhBao) {
        formCanhBao.addEventListener('submit', function(e) {
            e.preventDefault();
            const nguong = document.getElementById('nguongCanhBao').value;
            
            const formData = new FormData();
            formData.append('action', 'canh_bao');
            formData.append('nguong', nguong);

            fetch('process_khovan.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                const tbody = document.getElementById('tbodyCanhBao');
                tbody.innerHTML = '';
                if(res.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">Kho hàng an toàn, không có sản phẩm nào chạm ngưỡng cảnh báo!</td></tr>`;
                    return;
                }
                res.data.forEach(item => {
                    let badge = item.so_luong_ton == 0 
                                ? `<span class="badge bg-danger">Hết hàng</span>` 
                                : `<span class="badge bg-warning">Sắp hết</span>`;
                    tbody.innerHTML += `
                        <tr>
                            <td>${item.ma_sp}</td>
                            <td>${item.ten_sp}</td>
                            <td>${item.ten_loai || 'Không rõ'}</td>
                            <td style="font-weight:bold">${item.so_luong_ton}</td>
                            <td>${badge}</td>
                        </tr>
                    `;
                });
            });
        });
        // Chạy mặc định lần đầu
        formCanhBao.dispatchEvent(new Event('submit'));
    }

    // 2. CHỨC NĂNG TRA CỨU TỒN KHO TẠI 1 THỜI ĐIỂM
    const formTonKho = document.getElementById('formTonKhoThoiDiem');
    if(formTonKho) {
        formTonKho.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'ton_kho_thoi_diem');
            formData.append('ma_loai', document.getElementById('loaiSPTraCuu').value);
            formData.append('ngay', document.getElementById('ngayTraCuu').value);

            fetch('process_khovan.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                const tbody = document.getElementById('tbodyTonKhoThoiDiem');
                tbody.innerHTML = '';
                if(res.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;">Không tìm thấy sản phẩm nào!</td></tr>`;
                    return;
                }
                res.data.forEach(item => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${item.ma_sp}</td>
                            <td>${item.ten_sp}</td>
                            <td>${item.ten_loai || 'Không rõ'}</td>
                            <td style="font-weight:bold; color:#0195b2">${item.ton_luc_do}</td>
                        </tr>
                    `;
                });
            });
        });
    }

    // 3. CHỨC NĂNG BÁO CÁO NHẬP - XUẤT
    const formNhapXuat = document.getElementById('formBaoCaoNhapXuat');
    if(formNhapXuat) {
        formNhapXuat.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'nhap_xuat');
            formData.append('tu_ngay', document.getElementById('tuNgay').value);
            formData.append('den_ngay', document.getElementById('denNgay').value);

            fetch('process_khovan.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                const tbody = document.getElementById('tbodyBaoCaoNhapXuat');
                tbody.innerHTML = '';
                if(res.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">Không có hoạt động Nhập/Xuất nào trong khoảng thời gian này.</td></tr>`;
                    return;
                }
                res.data.forEach(item => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${item.ma_sp}</td>
                            <td>${item.ten_sp}</td>
                            <td style="color:#2ecc71; font-weight:bold;">+ ${item.tong_nhap}</td>
                            <td style="color:#e74c3c; font-weight:bold;">- ${item.tong_xuat}</td>
                            <td>${item.so_luong_ton}</td>
                        </tr>
                    `;
                });
            });
        });
        // Chạy báo cáo mặc định
        formNhapXuat.dispatchEvent(new Event('submit'));
    }
});