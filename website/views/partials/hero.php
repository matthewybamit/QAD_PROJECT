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
  <div class="relative z-10 flex flex-col items-center justify-center text-center h-full px-6 sm:flex-row sm:justify-between sm:text-right sm:px-8 lg:px-20">
    
    <!-- Left: Logo -->
    <div class="flex-shrink-0 mb-6 sm:mb-0">
      <img src="/assets/images/QAD_LOGO.png" 
           alt="DepEd Logo" 
           class="w-32 sm:w-40 h-auto lg:w-56">
    </div>

    <!-- Right: Title + Strokes -->
    <div class="text-white max-w-lg relative">
      <!-- Vertical Line (hidden on mobile) -->
      <div class="hidden sm:block absolute top-0 right-0 h-full border-r-4 border-white"></div>

      <!-- Text -->
      <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-snug sm:mr-5">
        QUALITY<br>
        ASSURANCE<br>
        DIVISION
      </h1>

      <!-- Horizontal Line -->
      <div class="mt-3 sm:mt-4 border-t-4 border-white w-24 sm:w-40 mx-auto sm:ml-auto"></div>
    </div>

  </div>
</section>


<script src="/public/js/time.js"></script>


<!-- Section Title -->
<div class="relative w-full max-w-7xl mx-auto mt-16 mb-8 text-center">
  <!-- Decorative lines -->
  <div class="flex items-center justify-center gap-4">
    <div class="h-0.5 w-12 bg-yellow-400"></div>
    <h2 class="text-3xl font-bold text-gray-800">
      Guide For Application
    </h2>
    <div class="h-0.5 w-12 bg-yellow-400"></div>
  </div>
  <!-- Optional subtitle -->
  <p class="mt-2 text-gray-600">Swipe or use arrows to navigate</p>
</div>

<!-- Section Title -->

<!-- 3D Coverflow Carousel -->
<div class="relative w-full max-w-7xl mx-auto mt-12 px-6">
  <!-- Carousel Wrapper -->
  <div id="coverflow" class="relative h-96 sm:h-[32rem] flex items-center justify-center perspective overflow-hidden touch-pan-x">
    <!-- Slides -->
    <div class="absolute w-56 sm:w-72 h-72 sm:h-96 transition-transform duration-700 ease-in-out" data-index="0">
      <img src="/assets/images/guidelines01.png" class="w-full h-full object-cover rounded-2xl shadow-2xl" alt="Slide 1">
    </div>
    <div class="absolute w-56 sm:w-72 h-72 sm:h-96 transition-transform duration-700 ease-in-out" data-index="1">
      <img src="/assets/images/02guidelines.png" class="w-full h-full object-cover rounded-2xl shadow-2xl" alt="Slide 2">
    </div>
    <div class="absolute w-56 sm:w-72 h-72 sm:h-96 transition-transform duration-700 ease-in-out" data-index="2">
      <img src="/assets/images/03guidelines.png" class="w-full h-full object-cover rounded-2xl shadow-2xl" alt="Slide 3">
    </div>
    <div class="absolute w-56 sm:w-72 h-72 sm:h-96 transition-transform duration-700 ease-in-out" data-index="3">
      <img src="/assets/images/04guidelines.png" class="w-full h-full object-cover rounded-2xl shadow-2xl" alt="Slide 4">
    </div>
    <div class="absolute w-56 sm:w-72 h-72 sm:h-96 transition-transform duration-700 ease-in-out" data-index="4">
      <img src="/assets/images/05guidelines.png" class="w-full h-full object-cover rounded-2xl shadow-2xl" alt="Slide 5">
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
    perspective:2500px; /* More depth for larger container */
  }
</style>


<script src="/public/js/spinningCorousel.js">
  
</script>