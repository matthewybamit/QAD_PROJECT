const slides = document.querySelectorAll('#coverflow [data-index]');
  const total = slides.length;
  let activeIndex = 0;
  let startX = 0;
  let isDragging = false;

  function updateSlides() {
    slides.forEach((slide, i) => {
      const offset = (i - activeIndex + total) % total;
      if (offset === 0) {
        slide.style.transform = "translateX(0) translateZ(200px) scale(1) rotateY(0deg)";
        slide.style.zIndex = 10;
        slide.style.opacity = 1;
      } else if (offset === 1) {
        slide.style.transform = "translateX(220px) translateZ(100px) scale(0.9) rotateY(-10deg)";
        slide.style.zIndex = 8;
        slide.style.opacity = 0.9;
      } else if (offset === 2) {
        slide.style.transform = "translateX(360px) translateZ(-100px) scale(0.7) rotateY(-30deg)";
        slide.style.zIndex = 5;
        slide.style.opacity = 0.6;
      } else if (offset === total - 1) {
        slide.style.transform = "translateX(-220px) translateZ(100px) scale(0.9) rotateY(10deg)";
        slide.style.zIndex = 8;
        slide.style.opacity = 0.9;
      } else if (offset === total - 2) {
        slide.style.transform = "translateX(-360px) translateZ(-100px) scale(0.7) rotateY(30deg)";
        slide.style.zIndex = 5;
        slide.style.opacity = 0.6;
      } else {
        slide.style.transform = "translateX(0) translateZ(-400px) scale(0.5)";
        slide.style.opacity = 0;
        slide.style.zIndex = 0;
      }
    });
  }

  // Controls
  document.getElementById('prev').addEventListener('click', () => {
    activeIndex = (activeIndex - 1 + total) % total;
    updateSlides();
  });

  document.getElementById('next').addEventListener('click', () => {
    activeIndex = (activeIndex + 1) % total;
    updateSlides();
  });

  // Drag / Swipe Support
  const coverflow = document.getElementById('coverflow');

  coverflow.addEventListener('mousedown', (e) => {
    isDragging = true;
    startX = e.clientX;
  });

  coverflow.addEventListener('mouseup', (e) => {
    if (!isDragging) return;
    const diff = e.clientX - startX;
    if (diff > 50) {
      activeIndex = (activeIndex - 1 + total) % total;
    } else if (diff < -50) {
      activeIndex = (activeIndex + 1) % total;
    }
    updateSlides();
    isDragging = false;
  });

  coverflow.addEventListener('touchstart', (e) => {
    startX = e.touches[0].clientX;
  });

  coverflow.addEventListener('touchend', (e) => {
    const diff = e.changedTouches[0].clientX - startX;
    if (diff > 50) {
      activeIndex = (activeIndex - 1 + total) % total;
    } else if (diff < -50) {
      activeIndex = (activeIndex + 1) % total;
    }
    updateSlides();
  });

  updateSlides();