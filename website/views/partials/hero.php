<!-- Hero Section -->
<section class="relative h-[500px] bg-gray-900 overflow-hidden">
  
  <!-- Background Image -->
  <img src="/assets/images/Hero-landing.png" alt="DepEd Building" 
       class="absolute inset-0 w-full h-full object-cover">
  
  <!-- Dark Overlay -->
<div class="absolute inset-0 bg-gradient-to-b from-blue-500/80 via-blue-250/40 to-transparent"></div>

  <!-- Top Bar (Date & PH Time) -->
  <div class="absolute top-4 right-8 z-20 text-right">
    <span class="block font-semibold text-sm text-white tracking-wide uppercase">Philippines Standard Time</span>
    <span id="ph-time" class="block font-bold text-yellow-400 text-lg">
      <?= date("F d, Y - h:i:s A") ?>
    </span>
  </div>

  <!-- Content -->
  <div class="relative z-10 flex items-center justify-between h-full px-8 lg:px-20">
    
    <!-- Left: Logo -->
    <div class="flex-shrink-0">
      <img src="/assets/images/QAD_LOGO.png" 
           alt="DepEd Logo" 
           class="w-40 h-auto lg:w-56">
    </div>

    <!-- Right: Title + Strokes -->
    <div class="text-right text-white max-w-lg relative">
      <!-- Vertical Line -->
      <div class="absolute top-0 right-0 h-full border-r-4 border-white"></div>

      <!-- Text -->
      <h1 class="text-4xl lg:text-5xl font-extrabold leading-snug mr-5">
        QUALITY<br>
        ASSURANCE<br>
        DIVISION
      </h1>

      <!-- Horizontal Line -->
      <div class="mt-4 border-t-4 border-white w-40 ml-auto"></div>
    </div>

  </div>
</section>

<script src="/public/js/time.js"></script>




<!-- 3D Coverflow Carousel -->
<div class="relative w-full max-w-7xl mx-auto mt-12 px-6">
  <!-- Carousel Wrapper -->
  <div id="coverflow" class="relative h-96 sm:h-[32rem] flex items-center justify-center perspective overflow-hidden touch-pan-x">
    <!-- Slides -->
    <div class="absolute w-56 sm:w-72 h-72 sm:h-96 transition-transform duration-700 ease-in-out" data-index="0">
      <img src="/assets/image/slide1.jpg" class="w-full h-full object-cover rounded-2xl shadow-2xl" alt="Slide 1">
    </div>
    <div class="absolute w-56 sm:w-72 h-72 sm:h-96 transition-transform duration-700 ease-in-out" data-index="1">
      <img src="/assets/image/slide2.jpg" class="w-full h-full object-cover rounded-2xl shadow-2xl" alt="Slide 2">
    </div>
    <div class="absolute w-56 sm:w-72 h-72 sm:h-96 transition-transform duration-700 ease-in-out" data-index="2">
      <img src="/assets/image/slide3.jpg" class="w-full h-full object-cover rounded-2xl shadow-2xl" alt="Slide 3">
    </div>
    <div class="absolute w-56 sm:w-72 h-72 sm:h-96 transition-transform duration-700 ease-in-out" data-index="3">
      <img src="/assets/image/slide4.jpg" class="w-full h-full object-cover rounded-2xl shadow-2xl" alt="Slide 4">
    </div>
    <div class="absolute w-56 sm:w-72 h-72 sm:h-96 transition-transform duration-700 ease-in-out" data-index="4">
      <img src="/assets/image/slide5.jpg" class="w-full h-full object-cover rounded-2xl shadow-2xl" alt="Slide 5">
    </div>
  </div>

  <!-- Controls -->
  <button id="prev" class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 bg-yellow-400 hover:bg-yellow-500 text-white p-3 sm:p-4 rounded-full z-10">
    &#8592;
  </button>
  <button id="next" class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 bg-yellow-400 hover:bg-yellow-500 text-white p-3 sm:p-4 rounded-full z-10">
    &#8594;
  </button>
</div>

<style>
  .perspective {
    perspective: 1500px; /* More depth for larger container */
  }
</style>


<script src="/public/js/spinningCorousel.js">
  
</script>