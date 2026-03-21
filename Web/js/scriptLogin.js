// Tài khoản admin có sẵn
const adminEmail = "Hoangdanghau@gmail.com";
const adminPassword = "1911";

function login() {
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

  popup.style.display = "block";

  if (!email && !password) {
    popup.innerHTML = "⚠️ Vui lòng nhập email và mật khẩu.";
    setTimeout(function() {
      popup.style.display = "none";
    }, 2000);
  } else if (!email) {
    popup.innerHTML = "⚠️ Vui lòng nhập email.";
    setTimeout(function() {
      popup.style.display = "none";
    }, 2000);
  } else if (!password) {
    popup.innerHTML = "⚠️ Vui lòng nhập mật khẩu.";
    setTimeout(function() {
      popup.style.display = "none";
    }, 2000);
  } else if (email === adminEmail && password === adminPassword) {
    popup.innerHTML = "🎉 Đăng nhập thành công!";
    setTimeout(function() {
      window.location.href = "Admin.html";
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

  emailInput.addEventListener('keydown', function(event) {
    if (event.key === 'Enter') {
      login();
    }
  });

  passwordInput.addEventListener('keydown', function(event) {
    if (event.key === 'Enter') {
      login();
    }
  });
});