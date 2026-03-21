function login() {
    const emailInput = document.getElementById("email").value.trim();
    const passwordInput = document.getElementById("password").value.trim();

    // ✅ Tài khoản cố định
    const EMAIL_CONST = "Hoangdanghau@gmail.com";
    const PASSWORD_CONST = "1911";

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

    popup.style.display = "block";

    if (!emailInput && !passwordInput) {
        popup.innerHTML = "⚠️ Vui lòng nhập email và mật khẩu.";
        setTimeout(function() {
            popup.style.display = "none";
        }, 2000);
    } else if (!emailInput) {
        popup.innerHTML = "⚠️ Vui lòng nhập email.";
        setTimeout(function() {
            popup.style.display = "none";
        }, 2000);
    } else if (!passwordInput) {
        popup.innerHTML = "⚠️ Vui lòng nhập mật khẩu.";
        setTimeout(function() {
            popup.style.display = "none";
        }, 2000);
    } else if (emailInput === EMAIL_CONST && passwordInput === PASSWORD_CONST) {
        popup.innerHTML = "🎉 Đăng nhập thành công!";
        setTimeout(function() {
            window.location.href = "User/User.html";
        }, 2000);
    } else {
        popup.innerHTML = "❌ Sai email hoặc mật khẩu!";
        setTimeout(function() {
            popup.style.display = "none";
        }, 2000);
    }
}

// Thêm event listener cho Enter key trên các input
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");

    if (emailInput) {
        emailInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                login();
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                login();
            }
        });
    }

    // Bấm enter để tìm kiếm trong trang user
    const searchBox = document.getElementById("searchBox");
    if (searchBox) {
        searchBox.addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                window.location.href = "Search.html";
            }
        });
    }
});

