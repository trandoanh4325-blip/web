function register() {
            const username = document.getElementById("username").value.trim();
            const name = document.getElementById("name").value.trim();
            const phone = document.getElementById("phone").value.trim();
            const address = document.getElementById("address").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value.trim();

            if (username && name && phone && address && email && password) {
                alert("🎉 Đăng ký thành công! Mời bạn đăng nhập.");
                window.location.href = "Login.html"; // Quay lại trang login
            } else {
                alert("⚠️ Vui lòng nhập đầy đủ thông tin.");
            }
        }