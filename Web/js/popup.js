document.addEventListener("DOMContentLoaded", function () {
  const popup = document.getElementById("loginPopup");
  const closeBtn = document.getElementById("closePopupBtn");
  const cancelBtn = document.getElementById("cancelPopupBtn");
  
  // Lấy TẤT CẢ các phần tử có class 'require-login'
  const requireLoginElements = document.querySelectorAll(".require-login");

  // Hàm mở popup
  function openPopup(event) {
    event.preventDefault(); // Ngăn hành động mặc định (vd: chuyển trang)
    popup.classList.add("show");
  }

  // Hàm đóng popup
  function closePopup() {
    popup.classList.remove("show");
  }

  // Gán sự kiện click cho tất cả các nút yêu cầu đăng nhập
  requireLoginElements.forEach(function (element) {
    element.addEventListener("click", openPopup);
  });

  // Gán sự kiện đóng popup
  closeBtn.addEventListener("click", closePopup);
  cancelBtn.addEventListener("click", closePopup);

  // Nhấn ra ngoài vùng nội dung để đóng popup
  window.addEventListener("click", function (event) {
    if (event.target === popup) {
      closePopup();
    }
  });
});