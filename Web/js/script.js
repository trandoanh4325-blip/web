function login() {
            const emailInput = document.getElementById("email").value.trim();
            const passwordInput = document.getElementById("password").value.trim();
            const errorMsg = document.getElementById("error");

            // ✅ Tài khoản cố định
            const EMAIL_CONST = "Hoangdanghau@gmail.com";
            const PASSWORD_CONST = "1911";

            if (emailInput === EMAIL_CONST && passwordInput === PASSWORD_CONST) {
                alert("🎉 Đăng nhập thành công!");
                window.location.href = "User/User.html"; // Chuyển sang trang người dùng
            } else {
                errorMsg.style.display = "block";
            }
        }
// Bấm enter để tìm kiếm trong trang user
document.getElementById("searchBox").addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
        event.preventDefault();
        window.location.href = "Search.html";
    }
});

