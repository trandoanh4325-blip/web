function register() {
            const username = document.getElementById("username").value.trim();
            const name = document.getElementById("name").value.trim();
            const phone = document.getElementById("phone").value.trim();
            const address = document.getElementById("address").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value.trim();

            // Tạo popup nếu chưa có
            let popup = document.getElementById("custom-popup");
            if (!popup) {
                popup = document.createElement("div");
                popup.id = "custom-popup";
                popup.style.position = "fixed";
                popup.style.top = "50%";
                popup.style.left = "50%";
                popup.style.transform = "translate(-50%, -50%)";
                popup.style.backgroundColor = "#fff";
                popup.style.border = "1px solid #ccc";
                popup.style.borderRadius = "10px";
                popup.style.padding = "20px";
                popup.style.boxShadow = "0 4px 15px rgba(0,0,0,0.3)";
                popup.style.zIndex = "2000";
                popup.style.textAlign = "center";
                popup.style.fontSize = "18px";
                popup.style.fontFamily = "Arial, sans-serif";
                document.body.appendChild(popup);
            }

            if (username && name && phone && address && email && password) {
                popup.innerHTML = "🎉 Đăng ký thành công! Mời bạn đăng nhập.";
                popup.style.display = "block";

                // Sau 2 giây, chuyển sang Login.html
                setTimeout(function() {
                    window.location.href = "Login.html";
                }, 2000);
            } else {
                popup.innerHTML = "⚠️ Vui lòng nhập đầy đủ thông tin.";
                popup.style.display = "block";

                // Ẩn popup sau 2 giây (không chuyển trang)
                setTimeout(function() {
                    popup.style.display = "none";
                }, 2000);
            }
        }

// Thêm event listener cho Enter key trên các input
document.addEventListener('DOMContentLoaded', function() {
  const inputs = document.querySelectorAll('input');

  inputs.forEach(input => {
    input.addEventListener('keydown', function(event) {
      if (event.key === 'Enter') {
        register();
      }
    });
  });
});