let slideIndex = 1; // Slide hiện tại
      let slideTimer;     // Biến đếm thời gian tự động chạy

      // Cấu hình link tương ứng cho từng slide
      const slideLinks = [
        "#", // Link cho banner 1
        "#", // Link cho banner 2
        "#", // Link cho banner 3
        "#", // Link cho banner 4
        "#"  // Link cho banner 5
      ];

      // Thêm sự kiện click và con trỏ chuột cho từng slide
      document.addEventListener("DOMContentLoaded", function() {
        let slidesElements = document.getElementsByClassName("mySlides");
        for (let i = 0; i < slidesElements.length; i++) {
          slidesElements[i].style.cursor = "pointer"; // Hiển thị con trỏ dạng bàn tay khi lướt qua ảnh
          slidesElements[i].onclick = function() {
            window.location.href = slideLinks[i]; // Chuyển trang dựa trên mảng slideLinks
          };
        }
      });

      // Gọi hàm khởi tạo
      showSlides(slideIndex);
      startAutoSlide();

      // Nút điều hướng Qua lại (Prev/Next)
      function plusSlides(n) {
        clearInterval(slideTimer); // Dừng chạy tự động khi bấm tay
        showSlides(slideIndex += n);
        startAutoSlide(); // Bật lại chạy tự động
      }

      // Nút chấm tròn (Dots)
      function currentSlide(n) {
        clearInterval(slideTimer);
        showSlides(slideIndex = n);
        startAutoSlide();
      }

      // Hàm xử lý hiển thị Slide
      function showSlides(n) {
        let i;
        let slides = document.getElementsByClassName("mySlides");
        let dots = document.getElementsByClassName("dot");
        
        // Quay vòng slide nếu vượt giới hạn
        if (n > slides.length) { slideIndex = 1 }    
        if (n < 1) { slideIndex = slides.length }
        
        // Ẩn tất cả slide
        for (i = 0; i < slides.length; i++) {
          slides[i].style.display = "none";  
        }
        
        // Xóa class 'active' khỏi tất cả các chấm
        for (i = 0; i < dots.length; i++) {
          dots[i].className = dots[i].className.replace(" active", "");
        }
        
        // Hiển thị slide hiện tại và làm sáng chấm tương ứng
        slides[slideIndex - 1].style.display = "block";  
        dots[slideIndex - 1].className += " active";
      }

      // Hàm thiết lập tự động chạy (sau mỗi 4 giây)
      function startAutoSlide() {
        slideTimer = setInterval(function() {
          showSlides(slideIndex += 1);
        }, 4000); 
      }