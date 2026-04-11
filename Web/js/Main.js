let slideIndex = 1; // Slide hiện tại
      let slideTimer;     // Biến đếm thời gian tự động chạy

      // Cấu hình link tương ứng cho từng slide
      const slideLinks = [
        "../User/product_detail.php?id=SP073", // Link cho banner 1
        "../User/product_detail.php?id=SP071", // Link cho banner 2
        "../User/product_detail.php?id=SP054", // Link cho banner 3
        "../User/product_detail.php?id=SP075", // Link cho banner 4
        "../User/product_detail.php?id=SP065"  // Link cho banner 5
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
// ... existing code ...
  // Hàm thiết lập tự động chạy (sau mỗi 4 giây)
  function startAutoSlide() {
    slideTimer = setInterval(function() {
      showSlides(slideIndex += 1);
    }, 4000); 
  }

// ================= SCRIPT XỬ LÝ PHÂN TRANG (KHÔNG LOAD LẠI TRANG) =================
document.addEventListener("DOMContentLoaded", function() {
  const products = document.querySelectorAll('.js-product-item');
  
  // Nếu trang không có sản phẩm (không phải trang User/Main) thì bỏ qua để không báo lỗi
  if(products.length === 0) return; 

  const itemsPerPage = 16; // Số sản phẩm trên mỗi trang
  const paginationContainer = document.getElementById('js-pagination-controls');
  const totalPages = Math.ceil(products.length / itemsPerPage);
  let currentPage = 1;

  // Hàm hiển thị sản phẩm theo trang
  function displayPage(page) {
    currentPage = page;
    const start = (page - 1) * itemsPerPage;
    const end = start + itemsPerPage;

    products.forEach((product, index) => {
      if (index >= start && index < end) {
        product.style.display = ''; // Hiện SP
      } else {
        product.style.display = 'none'; // Ẩn SP
      }
    });

    renderControls();
  }

  // Hàm tạo các nút bấm phân trang
  function renderControls() {
    paginationContainer.innerHTML = '';
    if (totalPages <= 1) return; // Nếu chỉ có 1 trang thì không hiện nút

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement('button');
      btn.innerText = i;
      btn.className = 'js-page-btn ' + (i === currentPage ? 'active' : '');
      
      btn.onclick = function() {
        displayPage(i);
        // Tự động cuộn mượt lên đầu khu vực sản phẩm khi bấm chuyển trang
        const khuVuc = document.getElementById('khu-vuc-san-pham');
        if(khuVuc) khuVuc.scrollIntoView({ behavior: 'smooth' });
      };
      
      paginationContainer.appendChild(btn);
    }
  }

  // Kích hoạt hiển thị trang 1 lúc mới vào web
  displayPage(1);
});