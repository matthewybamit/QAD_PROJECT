<!-- Navbar -->
<nav class="bg-blue-900 shadow-lg">
  <div class="max-w-9xl mx-auto px-10 sm:px-12 lg:px-16">
    <div class="flex justify-between items-center h-24">
      
      <!-- Left: Logo + Agency -->
      <div class="flex items-center space-x-5">
        <img src="/assets/images/QAD_LOGO.png" alt="DepEd Logo" class="h-16 w-auto">
        <div>
          <span class="block text-2xl font-bold text-white tracking-wide">Department of Education</span>
          <span class="block text-base text-yellow-400">Republic of the Philippines</span>
        </div>
      </div>

    </div>
  </div>
</nav>
<!-- Sub Navigation Links -->
<div class="bg-white shadow relative">
  <div class="max-w-9xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Mobile menu button -->
    <div class="flex lg:hidden justify-end py-4">
      <button id="mobile-menu-button" class="text-gray-600 hover:text-gray-900">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
    </div>

    <!-- Navigation Links - Desktop -->
    <div id="nav-links" class="hidden lg:flex lg:justify-center lg:space-x-8 lg:h-12 lg:items-center">
      <a href="/" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Home</a>
      <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">About</a>
      <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Programs and Projects</a>

      <!-- Dropdown: Authorized Private School -->
      <div class="relative group">
        <button class="text-gray-800 font-medium hover:text-blue-600 flex items-center space-x-1">
          <span>Authorized Private School</span>
          <svg class="w-4 h-4 text-gray-600 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <!-- Dropdown Menu -->
        <div class="absolute left-0 mt-2 w-64 bg-white shadow-lg rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-20">
          <a href="/listing" class="block px-4 py-2 text-gray-700 hover:bg-blue-100">Homeschool</a>
          <a href="/listing" class="block px-4 py-2 text-gray-700 hover:bg-blue-100">Private Schools with DepEd Permit</a>
          <a href="/listing" class="block px-4 py-2 text-gray-700 hover:bg-blue-100">Private Schools with DepEd Recognition</a>
          <a href="/listing" class="block px-4 py-2 text-gray-700 hover:bg-blue-100">Private Schools with SHS Provisional Permit</a>
          <a href="/listing" class="block px-4 py-2 text-gray-700 hover:bg-blue-100">SHS-VP Schools</a>
        </div>
      </div>

      <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">References</a>
      <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Resources</a>
      <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Contact Us</a>
    </div>

    <!-- Mobile Navigation Menu -->
    <div id="mobile-menu" class="hidden lg:hidden pb-4">
      <div class="flex flex-col space-y-2">
        <a href="/" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Home</a>
        <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">About</a>
        <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Programs and Projects</a>
        
        <!-- Mobile Dropdown -->
        <div class="relative">
          <button id="mobile-dropdown-button" class="w-full text-left py-2 text-gray-800 font-medium hover:text-blue-600 flex items-center justify-between">
            <span>Authorized Private School</span>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div id="mobile-dropdown-menu" class="hidden bg-gray-50 px-4 py-2 space-y-2">
            <a href="/listing" class="block py-2 text-gray-700 hover:text-blue-600">Homeschool</a>
            <a href="/listing" class="block py-2 text-gray-700 hover:text-blue-600">Private Schools with DepEd Permit</a>
            <a href="/listing" class="block py-2 text-gray-700 hover:text-blue-600">Private Schools with DepEd Recognition</a>
            <a href="/listing" class="block py-2 text-gray-700 hover:text-blue-600">Private Schools with SHS Provisional Permit</a>
            <a href="/listing" class="block py-2 text-gray-700 hover:text-blue-600">SHS-VP Schools</a>
          </div>
        </div>

        <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">References</a>
        <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Resources</a>
        <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Contact Us</a>
      </div>
    </div>
  </div>
</div>

<script>
  // Mobile menu toggle
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');
  const mobileDropdownButton = document.getElementById('mobile-dropdown-button');
  const mobileDropdownMenu = document.getElementById('mobile-dropdown-menu');

  mobileMenuButton.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
  });

  mobileDropdownButton.addEventListener('click', () => {
    mobileDropdownMenu.classList.toggle('hidden');
  });
</script>